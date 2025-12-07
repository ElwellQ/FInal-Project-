<?php
$esp32_ip = '192.168.1.27'; // ESP32 IP

// Servo control
if(isset($_GET['servo'])){
    $servo = intval($_GET['servo']);
    $servo = max(0, min(180, $servo));
    $url = "http://$esp32_ip/servo?pos=$servo";
    $response = @file_get_contents($url);
    echo ($response === FALSE) ? "ESP32 not reachable" : "Servo moved to " . $servo . "°";
    exit;
}

// Sound reading (analog)
if(isset($_GET['sound'])){
    $url = "http://$esp32_ip/sound";
    $response = @file_get_contents($url);
    echo ($response === FALSE) ? "0" : $response;
    exit;
}

echo "No command received";
?>
