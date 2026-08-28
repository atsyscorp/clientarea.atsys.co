<?php

namespace app\services;

use Yii;
use yii\httpclient\Client;
use Exception;

class NamecheapService
{
    private $apiUser;
    private $apiKey;
    private $userName;
    private $clientIp;
    private $isSandbox;

    public function __construct()
    {
        $this->apiUser = getenv('NAMECHEAP_API_USER') ?: '';
        $this->apiKey = getenv('NAMECHEAP_API_KEY') ?: '';
        $this->userName = getenv('NAMECHEAP_USERNAME') ?: $this->apiUser;
        // The ClientIp must match the whitelisted IP exactly
        $this->clientIp = getenv('NAMECHEAP_CLIENT_IP') ?: ($_SERVER['SERVER_ADDR'] ?? '127.0.0.1');
        $this->isSandbox = (bool)getenv('NAMECHEAP_SANDBOX');
    }

    /**
     * Get the API Endpoint URL
     */
    private function getApiUrl()
    {
        return $this->isSandbox 
            ? 'https://api.sandbox.namecheap.com/xml.response'
            : 'https://api.namecheap.com/xml.response';
    }

    /**
     * Build standard parameters required for all API calls
     */
    private function getBaseParams($command)
    {
        return [
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->userName,
            'Command' => $command,
            'ClientIp' => $this->clientIp,
        ];
    }

    /**
     * Execute an API request
     */
    private function executeRequest($params)
    {
        if (empty($this->apiUser) || empty($this->apiKey)) {
            throw new Exception("Namecheap API credentials are not configured.");
        }

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->getApiUrl())
            ->setData($params)
            ->send();

        if (!$response->isOk) {
            throw new Exception("HTTP request failed: " . $response->statusCode);
        }

        // Parse XML response
        $xml = simplexml_load_string($response->content);
        if ($xml === false) {
            throw new Exception("Failed to parse Namecheap XML response.");
        }

        if (isset($xml->Errors->Error)) {
            $errorMsg = (string)$xml->Errors->Error;
            throw new Exception("Namecheap API Error: " . $errorMsg);
        }

        return $xml->CommandResponse;
    }

    /**
     * Get DNS hosts for a domain
     * @param string $domain e.g., "example.com"
     * @return array Array of DNS records
     */
    public function getHosts($domain)
    {
        $parts = explode('.', $domain, 2);
        if (count($parts) != 2) {
            throw new Exception("Invalid domain name format.");
        }

        $params = $this->getBaseParams('namecheap.domains.dns.getHosts');
        $params['SLD'] = $parts[0];
        $params['TLD'] = $parts[1];

        $response = $this->executeRequest($params);
        
        $records = [];
        if (isset($response->DomainDNSGetHostsResult->host)) {
            foreach ($response->DomainDNSGetHostsResult->host as $host) {
                $records[] = [
                    'HostId' => (string)$host['HostId'],
                    'Name' => (string)$host['Name'],
                    'Type' => (string)$host['Type'],
                    'Address' => (string)$host['Address'],
                    'MXPref' => (string)$host['MXPref'],
                    'TTL' => (string)$host['TTL'],
                ];
            }
        }
        return $records;
    }

    /**
     * Set DNS hosts for a domain
     * @param string $domain e.g., "example.com"
     * @param array $records Array of records: [['HostName' => '@', 'RecordType' => 'A', 'Address' => '1.2.3.4', 'MXPref' => 10, 'TTL' => 1800], ...]
     * @return bool
     */
    public function setHosts($domain, $records)
    {
        $parts = explode('.', $domain, 2);
        if (count($parts) != 2) {
            throw new Exception("Invalid domain name format.");
        }

        $params = $this->getBaseParams('namecheap.domains.dns.setHosts');
        $params['SLD'] = $parts[0];
        $params['TLD'] = $parts[1];

        $i = 1;
        foreach ($records as $record) {
            $params["HostName{$i}"] = $record['HostName'] ?? '';
            $params["RecordType{$i}"] = $record['RecordType'] ?? '';
            $params["Address{$i}"] = $record['Address'] ?? '';
            if (!empty($record['MXPref'])) {
                $params["MXPref{$i}"] = $record['MXPref'];
            }
            if (!empty($record['TTL'])) {
                $params["TTL{$i}"] = $record['TTL'];
            } else {
                $params["TTL{$i}"] = 1800;
            }
            $i++;
        }

        $response = $this->executeRequest($params);
        
        return isset($response->DomainDNSSetHostsResult['IsSuccess']) 
            && (string)$response->DomainDNSSetHostsResult['IsSuccess'] === 'true';
    }

    /**
     * Set Custom Nameservers for a domain
     * @param string $domain e.g., "example.com"
     * @param array $nameservers Array of nameservers: ['ns1.atsys.co', 'ns2.atsys.co']
     * @return bool
     */
    public function setCustomNameservers($domain, $nameservers)
    {
        $parts = explode('.', $domain, 2);
        if (count($parts) != 2) {
            throw new Exception("Invalid domain name format.");
        }

        $params = $this->getBaseParams('namecheap.domains.dns.setCustom');
        $params['SLD'] = $parts[0];
        $params['TLD'] = $parts[1];
        $params['Nameservers'] = implode(',', array_filter($nameservers));

        $response = $this->executeRequest($params);
        
        return isset($response->DomainDNSSetCustomResult['Updated']) 
            && (string)$response->DomainDNSSetCustomResult['Updated'] === 'true';
    }

    /**
     * Register a new Domain via Namecheap
     */
    public function registerDomain($domain, $years, $customer, $couponCode = null)
    {
        $params = $this->getBaseParams('namecheap.domains.create');
        $params['DomainName'] = $domain;
        $params['Years'] = $years;
        if (!empty($couponCode)) {
            $params['PromotionCode'] = $couponCode;
        }

        // Contact info mapping (Namecheap requires Registrant, Tech, Admin, AuxBilling)
        $names = explode(' ', $customer->contact_name ?: $customer->business_name, 2);
        $firstName = $names[0];
        $lastName = $names[1] ?? 'Doe';

        $contactPrefixes = ['Registrant', 'Tech', 'Admin', 'AuxBilling'];
        
        foreach ($contactPrefixes as $prefix) {
            $params[$prefix . 'FirstName'] = $firstName;
            $params[$prefix . 'LastName'] = $lastName;
            $params[$prefix . 'Address1'] = $customer->address ?: 'Av 1 23 45';
            $params[$prefix . 'City'] = $customer->city ?: 'Bogota';
            $params[$prefix . 'StateProvince'] = $customer->state_province ?: 'Cundinamarca';
            $params[$prefix . 'PostalCode'] = '110010';
            $params[$prefix . 'Country'] = 'CO'; // Colombia default for Atsys
            $params[$prefix . 'Phone'] = '+57.' . preg_replace('/\D/', '', $customer->primary_phone ?: '3000000000');
            $params[$prefix . 'EmailAddress'] = $customer->email;
        }

        $response = $this->executeRequest($params);
        
        return isset($response->DomainCreateResult['Registered']) 
            && (string)$response->DomainCreateResult['Registered'] === 'true';
    }

    /**
     * Renew an existing Domain
     */
    public function renewDomain($domain, $years, $couponCode = null)
    {
        $params = $this->getBaseParams('namecheap.domains.renew');
        $params['DomainName'] = $domain;
        $params['Years'] = $years;
        if (!empty($couponCode)) {
            $params['PromotionCode'] = $couponCode;
        }

        $response = $this->executeRequest($params);
        
        return isset($response->DomainRenewResult['Renew']) 
            && (string)$response->DomainRenewResult['Renew'] === 'true';
    }

    /**
     * Get pricing for domain registration or renewal
     * @param string $domain Domain name (used to extract TLD)
     * @param string $action 'REGISTER' or 'RENEW'
     * @param string|null $couponCode
     * @return array
     */
    public function getPricing($domain, $action = 'REGISTER', $couponCode = null)
    {
        $parts = explode('.', $domain, 2);
        if (count($parts) != 2) {
            throw new Exception("Invalid domain name format.");
        }
        $tld = strtoupper($parts[1]);

        $params = $this->getBaseParams('namecheap.users.getPricing');
        $params['ProductType'] = 'DOMAIN';
        $params['ProductCategory'] = $action === 'RENEW' ? 'DOMAINRENEW' : 'DOMAINREGISTER';
        $params['ProductName'] = $tld;
        
        if (!empty($couponCode)) {
            $params['PromotionCode'] = $couponCode;
        }

        $response = $this->executeRequest($params);
        
        $prices = [];
        if (isset($response->UserGetPricingResult->ProductType->ProductCategory->Product->Price)) {
            foreach ($response->UserGetPricingResult->ProductType->ProductCategory->Product->Price as $priceNode) {
                $attrs = $priceNode->attributes();
                $duration = (int)$attrs['Duration'];
                
                $regularPrice = isset($attrs['RegularPrice']) ? (float)$attrs['RegularPrice'] : (float)$attrs['Price'];
                $promoPrice = isset($attrs['PromotionPrice']) && (float)$attrs['PromotionPrice'] > 0 ? (float)$attrs['PromotionPrice'] : null;
                $yourPrice = isset($attrs['YourPrice']) ? (float)$attrs['YourPrice'] : $regularPrice;
                
                // Si PromotionPrice existe y es mayor a 0, ese es el precio con descuento,
                // O si YourPrice es menor que RegularPrice, asumimos descuento (Niveles VIP etc)
                $finalPromoPrice = $promoPrice !== null ? $promoPrice : ($yourPrice < $regularPrice ? $yourPrice : null);
                $finalPrice = $finalPromoPrice !== null ? $finalPromoPrice : $regularPrice;
                
                $prices[$duration] = [
                    'duration' => $duration,
                    'regularPrice' => $regularPrice,
                    'promoPrice' => $finalPromoPrice,
                    'finalPrice' => $finalPrice,
                    'currency' => (string)$attrs['Currency']
                ];
            }
        }
        
        if (empty($prices)) {
            throw new Exception("No pricing found for TLD: " . $tld . " (Action: " . $params['ProductCategory'] . "). Comprueba si la TLD es soportada o el cupón es válido.");
        }

        return $prices;
    }
}
