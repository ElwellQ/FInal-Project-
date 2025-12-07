<?php
/**
 * SMS Detailed Diagnostic
 * Shows exactly what's happening when we send SMS
 */

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Admin access required');
}

require_once __DIR__ . '/sms_config.php';

// Get current SMS settings
$sms_settings = getSMSSettings();
$phone = $sms_settings['phone_number'] ?? '';
$api_token = $sms_settings['api_key'] ?? '';

if (empty($phone) || empty($api_token)) {
    die('<div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px;">
        <h3>❌ SMS Not Configured</h3>
        <p>Please configure SMS settings first:</p>
        <a href="sms_settings.php" style="color: #721c24; font-weight: bold;">Go to SMS Settings →</a>
    </div>');
}

// Prepare test SMS
$message = 'SMS Test - ' . date('Y-m-d H:i:s');
$url = "https://sms.iprogtech.com/api/v1/sms_messages";

$payload = array(
    'api_token' => $api_token,
    'phone_number' => $phone,
    'message' => $message
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>SMS Diagnostic - Detailed Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #569cd6; margin-bottom: 10px; }
        .subtitle { color: #858585; margin-bottom: 20px; }
        .section { background: #252526; border: 1px solid #3e3e42; border-radius: 8px; padding: 20px; margin: 20px 0; }
        h2 { color: #4ec9b0; margin: 0 0 15px 0; font-size: 16px; }
        .item { display: flex; margin: 10px 0; line-height: 1.6; }
        .label { color: #9cdcfe; min-width: 150px; font-weight: bold; }
        .value { color: #ce9178; word-break: break-all; }
        .code { background: #1e1e1e; padding: 15px; border-left: 3px solid #0066cc; margin: 10px 0; border-radius: 4px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ce9178; }
        .info { color: #9cdcfe; }
        button { background: #0e639c; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background: #1177bb; }
        .result-box { background: #1a2332; border: 1px solid #0066cc; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .status-good { border-left: 4px solid #4ec9b0; background: #1a2a1f; }
        .status-bad { border-left: 4px solid #f48771; background: #2a1a1a; }
        pre { background: #1e1e1e; padding: 15px; overflow-x: auto; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 SMS Diagnostic - Detailed Test</h1>
    <div class="subtitle">Testing iProGSMS API connection with your configured credentials</div>

    <!-- Configuration Section -->
    <div class="section">
        <h2>📋 Configuration</h2>
        <div class="item">
            <span class="label">Phone Number:</span>
            <span class="value"><?php echo htmlspecialchars($phone); ?></span>
        </div>
        <div class="item">
            <span class="label">API Token:</span>
            <span class="value"><?php echo substr($api_token, 0, 10) . '...' . substr($api_token, -5); ?></span>
        </div>
        <div class="item">
            <span class="label">API Endpoint:</span>
            <span class="value"><?php echo $url; ?></span>
        </div>
    </div>

    <!-- Request Section -->
    <div class="section">
        <h2>📤 Request Details</h2>
        <div class="item">
            <span class="label">Method:</span>
            <span class="value info">POST</span>
        </div>
        <div class="item">
            <span class="label">Content-Type:</span>
            <span class="value info">application/json</span>
        </div>
        <div class="item">
            <span class="label">Payload:</span>
        </div>
        <div class="code">
            <pre><?php echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
        </div>
    </div>

    <!-- Execution Section -->
    <div class="section">
        <h2>⚡ Sending Test SMS...</h2>
        
        <?php
        // Send the request with detailed tracking
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        // Capture verbose output
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_info = curl_getinfo($ch);
        
        rewind($verbose);
        $verbose_log = stream_get_contents($verbose);
        fclose($verbose);
        
        curl_close($ch);

        // Log the result
        $log_file = __DIR__ . '/sms_alerts.log';
        $log_entry = date('Y-m-d H:i:s') . " - DIAGNOSTIC TEST - HTTP Code: {$http_code} - Error: {$curl_error} - Response: " . substr($response, 0, 300) . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        ?>

        <!-- Response Section -->
        <div class="section">
            <h2>📥 Response Details</h2>
            
            <div class="item">
                <span class="label">HTTP Code:</span>
                <span class="value <?php echo ($http_code >= 200 && $http_code < 300) ? 'success' : 'error'; ?>">
                    <?php echo $http_code; ?>
                </span>
            </div>

            <?php if ($curl_error): ?>
                <div class="item">
                    <span class="label">cURL Error:</span>
                    <span class="value error"><?php echo htmlspecialchars($curl_error); ?></span>
                </div>
            <?php endif; ?>

            <div class="item">
                <span class="label">Total Time:</span>
                <span class="value"><?php echo round($curl_info['total_time'], 2); ?>s</span>
            </div>

            <div class="item">
                <span class="label">Connect Time:</span>
                <span class="value"><?php echo round($curl_info['connect_time'], 2); ?>s</span>
            </div>

            <div class="item">
                <span class="label">Content Type:</span>
                <span class="value"><?php echo $curl_info['content_type'] ?? 'N/A'; ?></span>
            </div>
        </div>

        <!-- Response Body -->
        <div class="section">
            <h2>📄 Response Body</h2>
            <?php if ($response): ?>
                <div class="code">
                    <pre><?php echo htmlspecialchars($response); ?></pre>
                </div>
                
                <?php if (json_decode($response)): ?>
                    <div style="margin-top: 15px;">
                        <strong style="color: #9cdcfe;">Parsed JSON Response:</strong>
                        <div class="code">
                            <pre><?php echo json_encode(json_decode($response), JSON_PRETTY_PRINT); ?></pre>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="color: #f48771;">⚠️ No response body received</div>
            <?php endif; ?>
        </div>

        <!-- Status Interpretation -->
        <div class="section">
            <h2>📊 Result Interpretation</h2>
            
            <?php if ($http_code >= 200 && $http_code < 300): ?>
                <div class="result-box status-good">
                    <span class="success">✓ SUCCESS</span><br>
                    Request was successful! SMS should be sent or queued for delivery.
                </div>
            <?php elseif ($http_code == 400): ?>
                <div class="result-box status-bad">
                    <span class="error">✗ BAD REQUEST (400)</span><br>
                    The request format is incorrect. Check:<br>
                    • Phone number format (should be 09XXXXXXXXX or 639XXXXXXXXX)<br>
                    • Message length (should be under 160 characters)<br>
                    • Parameter names are correct (api_token, phone_number, message)
                </div>
            <?php elseif ($http_code == 401): ?>
                <div class="result-box status-bad">
                    <span class="error">✗ UNAUTHORIZED (401)</span><br>
                    Authentication failed. Check:<br>
                    • API token is correct<br>
                    • API token has not expired<br>
                    • Your iProGSMS account is active
                </div>
            <?php elseif ($http_code == 403): ?>
                <div class="result-box status-bad">
                    <span class="error">✗ FORBIDDEN (403)</span><br>
                    Access denied. Possible reasons:<br>
                    • Account has no SMS credits<br>
                    • Account is suspended<br>
                    • IP is blocked
                </div>
            <?php elseif ($http_code == 422): ?>
                <div class="result-box status-bad">
                    <span class="error">✗ UNPROCESSABLE ENTITY (422)</span><br>
                    Invalid parameters. Check:<br>
                    • Phone number format<br>
                    • Message content<br>
                    • Parameter values
                </div>
            <?php elseif ($http_code == 404): ?>
                <div class="result-box status-bad">
                    <span class="error">✗ NOT FOUND (404)</span><br>
                    API endpoint not found. Check:<br>
                    • Endpoint URL is correct: <?php echo $url; ?><br>
                    • iProGSMS API structure hasn't changed<br>
                    • Domain is accessible
                </div>
            <?php elseif ($http_code == 0 || empty($http_code)): ?>
                <div class="result-box status-bad">
                    <span class="error">✗ CONNECTION ERROR</span><br>
                    Cannot reach the API server. Check:<br>
                    • Internet connection<br>
                    • Firewall/proxy settings<br>
                    • Domain: <?php echo $url; ?> is accessible<br>
                    • Error: <?php echo htmlspecialchars($curl_error); ?>
                </div>
            <?php elseif ($http_code >= 500): ?>
                <div class="result-box status-bad">
                    <span class="error">✗ SERVER ERROR (<?php echo $http_code; ?>)</span><br>
                    iProGSMS API server error. This is temporary. Try again in a few moments.
                </div>
            <?php else: ?>
                <div class="result-box status-bad">
                    <span class="error">✗ UNEXPECTED ERROR (<?php echo $http_code; ?>)</span><br>
                    Unexpected HTTP status code. Check response above for details.
                </div>
            <?php endif; ?>
        </div>

        <!-- Debug Info -->
        <div class="section">
            <h2>🐛 Debug Information</h2>
            <button onclick="document.getElementById('verbose').style.display = document.getElementById('verbose').style.display === 'none' ? 'block' : 'none';">
                Toggle Verbose Output
            </button>
            <div id="verbose" style="display: none; margin-top: 15px;">
                <div class="code">
                    <pre><?php echo htmlspecialchars($verbose_log); ?></pre>
                </div>
            </div>
        </div>

    </div>

    <!-- Next Steps -->
    <div class="section">
        <h2>📋 Next Steps</h2>
        <ol style="margin-left: 20px; line-height: 2;">
            <li>Check the result above to understand the error</li>
            <li><a href="sms_settings.php" style="color: #9cdcfe;">Review SMS Settings</a> if parameters are wrong</li>
            <li><a href="view_sms_logs.php" style="color: #9cdcfe;">View SMS Logs</a> for history</li>
            <li>If still failing, share the <strong>HTTP Code</strong> and <strong>Response Body</strong> above</li>
            <li><a href="admindashboard.php" style="color: #9cdcfe;">← Back to Dashboard</a></li>
        </ol>
    </div>

</div>

</body>
</html>
