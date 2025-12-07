<?php
/**
 * Send Picture to Telegram when noise exceeds 10 seconds
 */

require_once __DIR__ . '/telegram_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $noise_level = $input['noise_level'] ?? 0;
    $zone = $input['zone'] ?? 'Unknown';
    
    header('Content-Type: application/json');
    
    // Get Telegram settings
    $settings = getTelegramSettings();
    
    // Check if Telegram is enabled
    if (!$settings['enabled'] || empty($settings['bot_token']) || empty($settings['chat_id'])) {
        echo json_encode([
            'status' => 'disabled',
            'message' => 'Telegram notifications not configured'
        ]);
        exit;
    }
    
    try {
        $bot_token = $settings['bot_token'];
        $chat_id = $settings['chat_id'];
        
        // Try to capture screenshot from camera - try /capture endpoint first (single frame)
        $camera_urls = [
            "http://192.168.1.17/capture",     // Single JPEG frame (faster)
            "http://192.168.1.17/stream"       // Stream (backup)
        ];
        
        $image_data = false;
        $max_retries = 2;
        $retry_delay = 1;
        
        foreach ($camera_urls as $camera_url) {
            for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
                // Use curl with custom read function to parse data
            $frame_data = '';
            $headers_received = false;
            $bytes_read = 0;
            
            $ch = curl_init($camera_url);
            curl_setopt_array($ch, [
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => [
                    'Connection: Keep-Alive',
                    'Accept: multipart/x-mixed-replace'
                ],
                CURLOPT_VERBOSE => false,
                CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$frame_data, &$headers_received, &$bytes_read) {
                    $data_len = strlen($data);
                    $bytes_read += $data_len;
                    
                    // Skip HTTP headers (only on first chunk)
                    if (!$headers_received) {
                        $header_end = strpos($data, "\r\n\r\n");
                        if ($header_end !== false) {
                            $headers_received = true;
                            $data = substr($data, $header_end + 4);
                        } else {
                            // Headers not complete yet, wait for more data
                            return $data_len;
                        }
                    }
                    
                    $frame_data .= $data;
                    
                    // Keep reading until we find at least one complete JPEG frame
                    // Look for JPEG end marker to know when to stop
                    if (strpos($frame_data, "\xFF\xD9") !== false && strlen($frame_data) > 10000) {
                        return 0; // Stop reading, we have a complete frame
                    }
                    
                    // Also stop if we've read a lot of data
                    if ($bytes_read > 500000) {
                        return 0;
                    }
                    
                    return $data_len;
                }
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($http_code !== 200 || strlen($frame_data) < 5000) {
                if ($attempt < $max_retries) sleep($retry_delay);
                continue;
            }
            
            // Find ALL JPEG frames and use the LAST one (most recent)
            $search_pos = 0;
            $jpeg_start = false;
            $jpeg_end = false;
            
            while (($pos = strpos($frame_data, "\xFF\xD8\xFF", $search_pos)) !== false) {
                $end_pos = strpos($frame_data, "\xFF\xD9", $pos);
                if ($end_pos !== false) {
                    $jpeg_start = $pos;
                    $jpeg_end = $end_pos;
                    $search_pos = $end_pos + 1;
                } else {
                    break;
                }
            }
            
            if ($jpeg_start !== false && $jpeg_end !== false) {
                $frame = substr($frame_data, $jpeg_start, $jpeg_end - $jpeg_start + 2);
                if (strlen($frame) > 2000) {
                    $image_data = $frame;
                    break 2;  // Break out of both loops
                }
            }
            
            if ($attempt < $max_retries) sleep($retry_delay);
        }
        
        if ($image_data !== false) break;  // Got image, exit endpoint loop
            }
        
        if ($image_data === false || strlen($image_data) < 100) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Camera offline - could not capture image after ' . $max_retries . ' attempts'
            ]);
            exit;
        }
        
        // Save image temporarily (use local temp directory for better compatibility)
        $temp_dir = __DIR__ . '/temp';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }
        $temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'noise_alert_' . time() . '.jpg';
        if (!file_put_contents($temp_file, $image_data)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Could not save image to temp directory: ' . $temp_dir
            ]);
            exit;
        }
        
        // Send message first
        $message = "🔴 Loud Sound Detected\n\n";
        $message .= "Zone: $zone\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        
        // Send photo with caption via Telegram
        $telegram_url = "https://api.telegram.org/bot$bot_token/sendPhoto";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegram_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'chat_id' => $chat_id,
            'photo' => new CURLFile($temp_file, 'image/jpeg'),
            'caption' => $message,
            'parse_mode' => 'HTML'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Clean up temp file
        @unlink($temp_file);
        
        if ($http_code === 200) {
            $response_data = json_decode($response, true);
            if ($response_data['ok']) {
                echo json_encode([
                    'status' => 'sent',
                    'message' => 'Alert sent to Telegram',
                    'zone' => $zone
                ]);
                
                // Log to file
                $log_file = __DIR__ . '/telegram_alerts.log';
                $log_entry = date('Y-m-d H:i:s') . " - Zone: $zone - Noise: {$noise_level}dB - Sent to Telegram\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => $response_data['description'] ?? 'Telegram API error'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => "HTTP $http_code: $curl_error"
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>
