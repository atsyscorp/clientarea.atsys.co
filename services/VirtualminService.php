<?php

namespace app\services;

use Yii;
use yii\base\Exception;

class VirtualminService
{
    private $apiUrl;
    private $apiUser;
    private $apiPass;

    public function __construct()
    {
        $this->apiUrl = $_ENV['VIRTUALMIN_URL'] ?? $_SERVER['VIRTUALMIN_URL'] ?? getenv('VIRTUALMIN_URL') ?: 'https://127.0.0.1:10000/virtual-server/remote.cgi';
        $this->apiUser = $_ENV['VIRTUALMIN_USER'] ?? $_SERVER['VIRTUALMIN_USER'] ?? getenv('VIRTUALMIN_USER') ?: 'root';
        $this->apiPass = $_ENV['VIRTUALMIN_PASSWORD'] ?? $_SERVER['VIRTUALMIN_PASSWORD'] ?? getenv('VIRTUALMIN_PASSWORD') ?: '';
    }

    /**
     * Executes a command on the Virtualmin API
     */
    private function executeCommand($program, $params = [])
    {
        $url = rtrim($this->apiUrl, '/') . '?program=' . urlencode($program) . '&json=1';
        
        foreach ($params as $key => $value) {
            if ($value === null) {
                $url .= '&' . urlencode($key); // Solo el flag, ej: &dns o &no-web
            } else {
                $url .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiUser . ':' . $this->apiPass);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Often self-signed
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception("Virtualmin connection error: " . $error);
        }

        if ($httpCode != 200) {
            throw new Exception("Virtualmin API returned HTTP {$httpCode}: " . $response);
        }

        $decoded = json_decode($response, true);
        if (!$decoded) {
            throw new Exception("Invalid JSON response from Virtualmin");
        }

        if (isset($decoded['status']) && $decoded['status'] !== 'success') {
            $errorMsg = isset($decoded['error']) ? $decoded['error'] : 'Unknown Virtualmin error';
            throw new Exception("Virtualmin API Error: " . $errorMsg);
        }

        return $decoded;
    }

    /**
     * Get DNS hosts for a domain from Virtualmin
     */
    public function getHosts($domain)
    {
        try {
            $response = $this->executeCommand('get-dns', [
                'domain' => $domain,
                'multiline' => null
            ]);
            
            $hosts = [];
            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $record) {
                    if (empty($record['name']) || empty($record['values']) || empty($record['values']['type'])) {
                        continue;
                    }
                    
                    $name = $record['name'];
                    $type = strtoupper($record['values']['type'][0]);
                    
                    $valueArr = $record['values']['value'] ?? [];
                    $address = implode(' ', $valueArr);
                    
                    $ttl = isset($record['values']['ttl']) ? (int)$record['values']['ttl'][0] : 1800;
                    
                    // Virtualmin returns fully qualified names for the host (e.g. "sub.domain.com.")
                    // We need to convert it back to relative host name for the UI, e.g. "@", "www"
                    if (strcasecmp($name, $domain . '.') === 0) {
                        $hostName = '@';
                    } elseif (substr($name, -strlen($domain . '.') - 1) === '.' . $domain . '.') {
                        $hostName = substr($name, 0, strlen($name) - strlen($domain . '.') - 1);
                    } else {
                        $hostName = $name;
                    }

                    $mxPref = 10;
                    if ($type === 'MX') {
                        // In Virtualmin, MX values are often "10 mail.domain.com."
                        $mxParts = explode(' ', $address, 2);
                        if (count($mxParts) == 2 && is_numeric($mxParts[0])) {
                            $mxPref = (int)$mxParts[0];
                            $address = trim($mxParts[1]);
                        }
                    }

                    $hosts[] = [
                        'Name' => $hostName,
                        'Type' => $type,
                        'Address' => $address,
                        'MXPref' => $mxPref,
                        'TTL' => $ttl,
                    ];
                }
            }
            return $hosts;
        } catch (\Exception $e) {
            throw new Exception("Could not fetch DNS from Virtualmin: " . $e->getMessage());
        }
    }

    /**
     * Set DNS hosts for a domain in Virtualmin.
     * Virtualmin doesn't have a bulk "setHosts" command. 
     * We have to delete old ones and create new ones.
     */
    public function setHosts($domain, $records)
    {
        // 1. Delete all existing records except essential ones like NS?
        // Actually, deleting all records via API might be dangerous. 
        // A safer approach for bulk syncing is to fetch existing records and compare,
        // or clear them all except SOA and NS.
        // Let's get existing records first
        $existing = $this->getHosts($domain);

        // Remove all current records except NS and SOA (we don't want to break the zone)
        foreach ($existing as $ex) {
            if ($ex['Type'] === 'NS' || $ex['Type'] === 'SOA') continue;
            
            // Format name back to fully qualified for deletion
            $name = $ex['Name'] === '@' ? $domain . '.' : $ex['Name'] . '.' . $domain . '.';
            
            // For MX, Virtualmin expects the value to be "10 mail.domain.com."
            $val = $ex['Address'];
            if ($ex['Type'] === 'MX') {
                $val = $ex['MXPref'] . ' ' . $ex['Address'];
            }

            try {
                $this->executeCommand('modify-dns', [
                    'domain' => $domain,
                    'remove-record' => trim("{$name} {$ex['Type']} {$val}")
                ]);
            } catch (\Exception $e) {
                // Ignore deletion errors for non-existent records
            }
        }

        // Add the new records
        foreach ($records as $record) {
            $name = $record['HostName'] === '@' ? $domain . '.' : $record['HostName'] . '.' . $domain . '.';
            
            $val = $record['Address'];
            if ($record['RecordType'] === 'MX') {
                $val = $record['MXPref'] . ' ' . $record['Address'];
            }
            
            $ttl = !empty($record['TTL']) ? $record['TTL'] : 1800;
            
            try {
                $this->executeCommand('modify-dns', [
                    'domain' => $domain,
                    'add-record-with-ttl' => trim("{$name} {$record['RecordType']} {$ttl} {$val}")
                ]);
            } catch (\Exception $e) {
                throw new Exception("Error creating record {$record['HostName']}: " . $e->getMessage());
            }
        }
        
        return true;
    }

    /**
     * Initializes a DNS-only zone in Virtualmin as a sub-server of a master account.
     */
    public function createDnsZone($domain)
    {
        $parentDomain = $_ENV['VIRTUALMIN_PARKED_PARENT'] ?? $_SERVER['VIRTUALMIN_PARKED_PARENT'] ?? getenv('VIRTUALMIN_PARKED_PARENT');
        
        if (empty($parentDomain)) {
            throw new Exception("No se ha definido VIRTUALMIN_PARKED_PARENT en el archivo .env. Configura un dominio maestro para alojar las zonas DNS.");
        }

        // Params to create a sub-server. We must explicitly request the 'dns' feature.
        $params = [
            'domain' => $domain,
            'parent' => $parentDomain,
            'dns' => null
        ];

        return $this->executeCommand('create-domain', $params);
    }
}
