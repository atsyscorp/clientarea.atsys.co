<?php

namespace app\components;

/**
 * DomainChecker helper class to check domain availability.
 */
class DomainChecker
{
    /**
     * Map of common TLDs to their corresponding WHOIS servers and availability status patterns.
     */
    private static $whoisServers = [
        'com' => ['server' => 'whois.verisign-grs.com', 'available' => 'No match for'],
        'net' => ['server' => 'whois.verisign-grs.com', 'available' => 'No match for'],
        'org' => ['server' => 'whois.pir.org', 'available' => 'NOT FOUND'],
        'co' => ['server' => 'whois.nic.co', 'available' => 'Not found'],
        'com.co' => ['server' => 'whois.nic.co', 'available' => 'Not found'],
        'net.co' => ['server' => 'whois.nic.co', 'available' => 'Not found'],
        'nom.co' => ['server' => 'whois.nic.co', 'available' => 'Not found'],
        'info' => ['server' => 'whois.afilias-grs.info', 'available' => 'NOT FOUND'],
        'biz' => ['server' => 'whois.nic.biz', 'available' => 'Not found'],
        'io' => ['server' => 'whois.nic.io', 'available' => 'is available'],
        'me' => ['server' => 'whois.nic.me', 'available' => 'NOT FOUND'],
        'tv' => ['server' => 'whois.nic.tv', 'available' => 'No match for'],
        'cc' => ['server' => 'whois.nic.cc', 'available' => 'No match for'],
        'cl' => ['server' => 'whois.nic.cl', 'available' => 'no existe'],
        'mx' => ['server' => 'whois.mx', 'available' => 'Object_Not_Found'],
        'com.mx' => ['server' => 'whois.mx', 'available' => 'Object_Not_Found'],
        'pe' => ['server' => 'kero.yachay.pe', 'available' => 'Not Registered'],
        'com.pe' => ['server' => 'kero.yachay.pe', 'available' => 'Not Registered'],
        'es' => ['server' => 'whois.nic.es', 'available' => 'libre'],
    ];

    /**
     * Checks if a domain is available.
     * 
     * @param string $domain The domain name to check (e.g. "example.com")
     * @return array Array containing 'status' ('available', 'registered', 'invalid'), 'available' (boolean), and 'method'
     */
    public static function isAvailable($domain)
    {
        $domain = strtolower(trim($domain));

        // 1. Basic syntax validation
        if (!preg_match('/^[a-z0-9-]{1,63}\.[a-z0-9.-]{2,24}$/i', $domain)) {
            return [
                'status' => 'invalid',
                'available' => false,
                'message' => 'Formato de dominio no válido.',
                'method' => 'syntax'
            ];
        }

        // 2. DNS check first: if DNS records exist, it is definitely registered
        try {
            if (checkdnsrr($domain, 'A') || checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'NS')) {
                return [
                    'status' => 'registered',
                    'available' => false,
                    'method' => 'dns'
                ];
            }
        } catch (\Exception $e) {
            // Silence dns resolution issues and proceed to whois
        }

        // Extract TLD to determine the whois server
        $parts = explode('.', $domain);
        if (count($parts) < 2) {
            return [
                'status' => 'invalid',
                'available' => false,
                'message' => 'Dominio incompleto.',
                'method' => 'syntax'
            ];
        }

        $tld = end($parts);
        $sld = $parts[count($parts) - 2];

        // Match two-level TLDs (e.g. com.co, net.co, com.mx)
        $fullTld = $tld;
        if (in_array($sld, ['com', 'net', 'org', 'nom', 'gov', 'edu', 'co'])) {
            $fullTld = $sld . '.' . $tld;
        }

        // 3. WHOIS check
        $whoisInfo = null;
        if (isset(self::$whoisServers[$fullTld])) {
            $whoisInfo = self::$whoisServers[$fullTld];
        } elseif (isset(self::$whoisServers[$tld])) {
            $whoisInfo = self::$whoisServers[$tld];
        }

        if ($whoisInfo) {
            $result = self::checkWHOIS($domain, $whoisInfo['server'], $whoisInfo['available']);
            if ($result !== null) {
                return [
                    'status' => $result ? 'available' : 'registered',
                    'available' => $result,
                    'method' => 'whois'
                ];
            }
        }

        // 4. Fallback checking: resolving host IP
        $ip = gethostbyname($domain);
        if ($ip !== $domain) {
            return [
                'status' => 'registered',
                'available' => false,
                'method' => 'gethostbyname'
            ];
        }

        // If DNS is clear, WHOIS query failed/unsupported, and no IP resolves:
        // We assume it is likely available but warn of fallback method
        return [
            'status' => 'available',
            'available' => true,
            'method' => 'dns_fallback'
        ];
    }

    /**
     * Connects to WHOIS server via socket connection to check domain availability.
     * 
     * @param string $domain The domain to query
     * @param string $server The WHOIS server host
     * @param string $availablePattern The pattern indicating domain availability in the response
     * @return bool|null True if available, False if registered, Null if connection/rate limit failure
     */
    private static function checkWHOIS($domain, $server, $availablePattern)
    {
        $port = 43;
        $timeout = 3;
        $fp = @fsockopen($server, $port, $errno, $errstr, $timeout);

        if (!$fp) {
            return null; // Connection failed
        }

        // Send query (Verisign WHOIS server expects '=' prefix to prevent partial matches)
        if ($server === 'whois.verisign-grs.com') {
            fwrite($fp, '=' . $domain . "\r\n");
        } else {
            fwrite($fp, $domain . "\r\n");
        }

        $response = '';
        stream_set_timeout($fp, $timeout);
        while (!feof($fp)) {
            $line = fgets($fp, 128);
            if ($line === false) {
                break;
            }
            $response .= $line;
        }
        fclose($fp);

        // Check if query limits or errors occurred
        if (stripos($response, 'limit exceeded') !== false || 
            stripos($response, 'too many requests') !== false ||
            stripos($response, 'connection refused') !== false) {
            return null;
        }

        // Check availability pattern
        if (stripos($response, $availablePattern) !== false) {
            return true; // Available
        }

        return false; // Registered (not available)
    }
}
