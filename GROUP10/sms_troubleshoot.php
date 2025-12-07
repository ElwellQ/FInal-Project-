<?php
/**
 * SMS Troubleshooting Guide
 * API says "sent" but SMS not received - diagnostics
 */

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Admin access required');
}

require_once __DIR__ . '/sms_config.php';

$sms_settings = getSMSSettings();
$phone = $sms_settings['phone_number'] ?? '';
$api_token = $sms_settings['api_key'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>SMS Troubleshooting</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f5f7fa; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .section { background: white; padding: 25px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #0066cc; margin-bottom: 15px; font-size: 20px; }
        h3 { color: #333; margin-top: 20px; margin-bottom: 10px; }
        .checklist { list-style: none; }
        .checklist li { padding: 10px 0; border-bottom: 1px solid #eee; }
        .checklist li:last-child { border-bottom: none; }
        .checklist input { margin-right: 10px; }
        .checklist label { cursor: pointer; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .success-box { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .error-box { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .info-box { background: #cfe2ff; border-left: 4px solid #0066cc; padding: 15px; border-radius: 4px; margin: 15px 0; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        .status-item { display: flex; align-items: center; padding: 12px; margin: 10px 0; background: #f9f9f9; border-radius: 4px; }
        .status-icon { font-size: 24px; margin-right: 15px; }
        .status-text { flex: 1; }
        .status-text strong { color: #333; }
        .status-text p { font-size: 13px; color: #666; margin-top: 5px; }
        .btn { display: inline-block; padding: 12px 20px; background: #0066cc; color: white; text-decoration: none; border-radius: 4px; margin: 5px; cursor: pointer; border: none; }
        .btn:hover { background: #0052a3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f9f9f9; font-weight: bold; }
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-medium { color: #ffc107; font-weight: bold; }
        .priority-low { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 SMS Troubleshooting Guide</h1>
    <p style="text-align: center; color: #666; margin-bottom: 30px;">API reports "sent" but SMS not received - Follow this checklist</p>

    <!-- Current Configuration -->
    <div class="section">
        <h2>📋 Current Configuration</h2>
        <div class="status-item">
            <div class="status-icon">📱</div>
            <div class="status-text">
                <strong>Phone Number:</strong>
                <p><?php echo empty($phone) ? '❌ NOT SET' : htmlspecialchars($phone); ?></p>
            </div>
        </div>
        <div class="status-item">
            <div class="status-icon">🔑</div>
            <div class="status-text">
                <strong>API Token:</strong>
                <p><?php echo empty($api_token) ? '❌ NOT SET' : (substr($api_token, 0, 10) . '...' . substr($api_token, -5)); ?></p>
            </div>
        </div>
        <div class="status-item">
            <div class="status-icon">🌐</div>
            <div class="status-text">
                <strong>Endpoint:</strong>
                <p>https://sms.iprogtech.com/api/v1/sms_messages</p>
            </div>
        </div>
    </div>

    <!-- Quick Checklist -->
    <div class="section">
        <h2>✅ Quick Troubleshooting Checklist</h2>
        
        <h3><span class="priority-high">[CRITICAL]</span> Account Balance</h3>
        <div class="warning-box">
            <strong>Most Common Issue!</strong><br>
            If your iProGSMS account has zero balance, SMS will appear to send (API accepts it) but won't actually be delivered.
        </div>
        <div class="status-item">
            <div class="status-icon">⚠️</div>
            <div class="status-text">
                <strong>Action:</strong>
                <p><a href="https://www.iprogsms.com" target="_blank">Log into your iProGSMS account</a> and check your balance/credits</p>
            </div>
        </div>

        <h3><span class="priority-high">[CRITICAL]</span> Phone Number Format</h3>
        <div class="info-box">
            <strong>Currently configured:</strong> <code><?php echo htmlspecialchars($phone); ?></code><br>
            <br>
            <strong>Try different formats:</strong>
        </div>
        <table>
            <tr>
                <th>Format</th>
                <th>Example</th>
                <th>Notes</th>
            </tr>
            <tr>
                <td><code>09XXXXXXXXX</code></td>
                <td>09123456789</td>
                <td>Philippine format (10 digits with 0)</td>
            </tr>
            <tr>
                <td><code>639XXXXXXXXX</code></td>
                <td>639123456789</td>
                <td>International format (12 digits, no 0)</td>
            </tr>
            <tr>
                <td><code>+639XXXXXXXXX</code></td>
                <td>+639123456789</td>
                <td>International with + prefix</td>
            </tr>
        </table>
        <p style="margin-top: 10px; color: #666;">
            <strong>Try each format:</strong> Go to <a href="sms_settings.php">SMS Settings</a>, update phone number, and test again.
        </p>

        <h3><span class="priority-medium">[IMPORTANT]</span> Carrier/Network Issues</h3>
        <ul class="checklist">
            <li>
                <label>
                    <input type="checkbox"> 
                    <strong>Test with different phone number</strong> - Ask a friend to provide their number and test if SMS works to another phone
                </label>
            </li>
            <li>
                <label>
                    <input type="checkbox"> 
                    <strong>Check SMS settings on your phone</strong> - Make sure SMS is enabled and not blocked
                </label>
            </li>
            <li>
                <label>
                    <input type="checkbox"> 
                    <strong>Disable Call Filtering</strong> - Some carriers have SMS filtering enabled by default
                </label>
            </li>
            <li>
                <label>
                    <input type="checkbox"> 
                    <strong>Check spam folder</strong> - SMS might be going to spam/junk messages
                </label>
            </li>
        </ul>

        <h3><span class="priority-medium">[IMPORTANT]</span> Account Status</h3>
        <div class="warning-box">
            Your iProGSMS account might be:<br>
            • Suspended or inactive<br>
            • Blocked due to too many failed attempts<br>
            • Under review by support<br>
            • API token expired
        </div>
        <p><a href="https://www.iprogsms.com" target="_blank" class="btn">Check Your Account</a></p>

        <h3><span class="priority-low">[OPTIONAL]</span> Sender ID Issues</h3>
        <div class="info-box">
            Some carriers reject SMS if they don't recognize the sender ID.
            The system currently uses: <strong>ALERT</strong>
        </div>
        <p style="margin-top: 10px;">
            <a href="sms_settings.php" class="btn btn-secondary">Modify Sender ID</a>
            (Try using a registered business name or leave empty for default)
        </p>
    </div>

    <!-- Detailed Troubleshooting -->
    <div class="section">
        <h2>🔍 Detailed Troubleshooting Steps</h2>
        
        <h3>Step 1: Verify API is Working</h3>
        <p>First, confirm the API itself is responding:</p>
        <a href="sms_detailed_diagnostic.php" class="btn">Run Detailed Diagnostic</a>
        <p style="margin-top: 10px; color: #666;">This will show the exact API response.</p>

        <h3>Step 2: Check Logs</h3>
        <p>Review all SMS sending attempts:</p>
        <a href="view_sms_logs.php" class="btn">View SMS Logs</a>
        <p style="margin-top: 10px; color: #666;">Look for any error messages or patterns.</p>

        <h3>Step 3: Test Multiple Times</h3>
        <p>Send 3-5 test SMS messages from <a href="test_sms_now.php">SMS Test Page</a> and check if any arrive</p>

        <h3>Step 4: Try Different Phone Number</h3>
        <p>Ask a friend to provide their number and update SMS Settings. Test with a different carrier if possible.</p>

        <h3>Step 5: Contact iProGSMS Support</h3>
        <p>If still not working, contact iProGSMS support with:</p>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li>Your account username/email</li>
            <li>Exact timestamp of failed SMS</li>
            <li>Phone number(s) attempted</li>
            <li>The error message (if any)</li>
        </ul>
    </div>

    <!-- Common Solutions -->
    <div class="section">
        <h2>💡 Common Solutions</h2>
        
        <div class="success-box">
            <strong>✓ Solution 1: Add Credits to Account</strong><br>
            Most common issue. Go to iProGSMS, add SMS credits, and retry.
        </div>

        <div class="success-box">
            <strong>✓ Solution 2: Use Correct Phone Format</strong><br>
            Change from: <code><?php echo htmlspecialchars($phone); ?></code><br>
            Try: <code>639<?php echo substr($phone, -9); ?></code> (if it starts with 09)
        </div>

        <div class="success-box">
            <strong>✓ Solution 3: Register with Carrier</strong><br>
            Some carriers require you to register sender IDs. Update SMS Settings and try with a registered name.
        </div>

        <div class="success-box">
            <strong>✓ Solution 4: Use Different Provider</strong><br>
            If iProGSMS continues to fail, consider switching to Semaphore or Twilio.
        </div>
    </div>

    <!-- What to Report -->
    <div class="section">
        <h2>📝 Information to Report if Still Failing</h2>
        <ol style="margin-left: 20px; line-height: 2;">
            <li><strong>HTTP Response Code</strong> (from Detailed Diagnostic)</li>
            <li><strong>Full Response Body</strong> from API</li>
            <li><strong>Phone number format</strong> you're using</li>
            <li><strong>Number of test attempts</strong> made</li>
            <li><strong>Current iProGSMS account balance</strong></li>
            <li><strong>Any error messages</strong> from logs</li>
        </ol>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="admindashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

</div>

</body>
</html>
