<?php
/**
 * Deep ESP32-CAM Diagnostics
 */

echo "<h2>🔧 ESP32-CAM Deep Diagnostics</h2>";

$esp32_ip = "192.168.1.17";

// Test 1: Get root content
echo "<h3>Test 1: Root Endpoint</h3>";
$ch = curl_init("http://$esp32_ip/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$root = curl_exec($ch);
curl_close($ch);

echo "<p>Response:</p>";
echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>" . htmlspecialchars(substr($root, 0, 500)) . "</pre>";

// Test 2: Try different stream URLs
echo "<h3>Test 2: Try Stream Variants</h3>";
$stream_urls = [
    'http://192.168.1.17/stream',
    'http://192.168.1.17:80/stream',
    'http://192.168.1.17/mjpeg/1',
    'http://192.168.1.17:8080/stream',
    'http://192.168.1.17:8081/stream'
];

foreach ($stream_urls as $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => true  // HEAD request only
    ]);
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = ($http_code === 200) ? "✅" : "❌";
    echo "<p>$status <code>$url</code> - HTTP $http_code</p>";
}

// Test 3: Check if stream works with actual data fetch
echo "<h3>Test 3: Stream Data Test</h3>";
$ch = curl_init("http://192.168.1.17/stream");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 2,
    CURLOPT_BINARYTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_RANGE => '0-10000'  // Get first 10KB
]);

$data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code === 206 || $http_code === 200) {
    echo "<p style='color: green;'>✅ Stream data received!</p>";
    echo "<p>HTTP Code: $http_code</p>";
    echo "<p>Content-Type: $content_type</p>";
    echo "<p>Data size: " . strlen($data) . " bytes</p>";
    
    // Check for JPEG markers
    if (strpos($data, "\xFF\xD8\xFF") !== false) {
        echo "<p style='color: green;'>✅ Contains JPEG data (FF D8 FF markers found)</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Stream request failed with HTTP $http_code</p>";
}

// Test 4: Get response headers
echo "<h3>Test 4: Stream Headers</h3>";
$ch = curl_init("http://192.168.1.17/stream");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true
]);

$response = curl_exec($ch);
curl_close($ch);

echo "<pre style='background: #f0f0f0; padding: 10px; font-size: 12px; overflow-x: auto;'>";
echo htmlspecialchars(substr($response, 0, 800));
echo "</pre>";

// Test 5: Check for control endpoints
echo "<h3>Test 5: Common ESP32-CAM Control Endpoints</h3>";
$control_urls = [
    '/control?var=framesize&val=0',
    '/control?var=quality&val=10',
    '/action?action=snapshot',
    '/api/control/framesize?framesize=0',
];

foreach ($control_urls as $endpoint) {
    $url = "http://192.168.1.17$endpoint";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        echo "<p style='color: green;'>✅ <code>$endpoint</code> - HTTP 200</p>";
        if (strlen($response) < 200) {
            echo "<p style='font-size: 12px; margin-left: 20px;'>Response: " . htmlspecialchars($response) . "</p>";
        }
    }
}

?>
