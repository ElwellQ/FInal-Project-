<?php
/**
 * ESP32-CAM Status Check
 */

echo "<h2>🔍 ESP32-CAM Status Diagnostic</h2>";

$esp32_ip = "192.168.1.132";

// Test 1: Basic connectivity
echo "<h3>Test 1: Basic Connectivity</h3>";

$ch = curl_init("http://$esp32_ip/");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 2,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo "<p style='color: green;'>✅ ESP32-CAM is ONLINE (HTTP 200)</p>";
} else {
    echo "<p style='color: red;'>❌ ESP32-CAM not responding or offline (HTTP $http_code)</p>";
    echo "<p><strong>Action:</strong> Check if ESP32-CAM is powered on and connected to WiFi</p>";
    echo "<p><a href='javascript:location.reload()'>Refresh this page</a> to retry</p>";
    exit;
}

// Test 2: Stream endpoint
echo "<h3>Test 2: Stream Endpoint Status</h3>";

$ch = curl_init("http://$esp32_ip/stream");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 2,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_NOBODY => true
]);

curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code === 200) {
    echo "<p style='color: green;'>✅ /stream endpoint is AVAILABLE</p>";
    echo "<p>Content-Type: $content_type</p>";
} else {
    echo "<p style='color: red;'>❌ /stream endpoint returned HTTP $http_code</p>";
    echo "<p><strong>Possible causes:</strong></p>";
    echo "<ul>";
    echo "<li>ESP32-CAM firmware issue</li>";
    echo "<li>Camera module disconnected</li>";
    echo "<li>Power supply problem</li>";
    echo "<li>WiFi connection unstable</li>";
    echo "</ul>";
}

// Test 3: Try to get stream data
echo "<h3>Test 3: Stream Data Retrieval</h3>";

$frame_data = '';
$ch = curl_init("http://$esp32_ip/stream");
curl_setopt_array($ch, [
    CURLOPT_BINARYTRANSFER => true,
    CURLOPT_TIMEOUT => 3,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_HTTPHEADER => [
        'Connection: Keep-Alive',
        'Accept: multipart/x-mixed-replace'
    ],
    CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$frame_data) {
        $frame_data .= $data;
        if (strlen($frame_data) > 100000) {
            return 0;
        }
        return strlen($data);
    }
]);

curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && strlen($frame_data) > 1000) {
    echo "<p style='color: green;'>✅ Stream data received: " . strlen($frame_data) . " bytes</p>";
    
    // Check for JPEG
    $jpeg_start = strpos($frame_data, "\xFF\xD8\xFF");
    if ($jpeg_start !== false) {
        echo "<p style='color: green;'>✅ JPEG data found in stream</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Could not retrieve stream data (HTTP $http_code, size: " . strlen($frame_data) . " bytes)</p>";
}

// Test 4: Quick fix options
echo "<h3>💡 Troubleshooting Steps</h3>";
echo "<ol>";
echo "<li><strong>Power Cycle:</strong> Unplug ESP32-CAM for 10 seconds, then plug back in</li>";
echo "<li><strong>Wait 30 seconds:</strong> Let ESP32 reconnect to WiFi</li>";
echo "<li><strong>Refresh this page:</strong> <a href='javascript:location.reload()'>Click here</a></li>";
echo "<li><strong>Check WiFi:</strong> Verify ESP32-CAM is on the same network</li>";
echo "<li><strong>Update ESP32 firmware:</strong> If problem persists</li>";
echo "</ol>";

// Test 5: Auto-retry
echo "<h3>⏱️ Auto Retry</h3>";
echo "<p>This page will automatically refresh every 10 seconds...</p>";
echo "<script>";
echo "setTimeout(function() { location.reload(); }, 10000);";
echo "</script>";

?>
