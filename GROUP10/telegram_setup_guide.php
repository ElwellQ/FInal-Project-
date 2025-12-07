<?php
/**
 * Telegram Setup Guide - Find Your Chat ID
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Telegram Setup Guide</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #0fa47f;
        }
        .error { border-left-color: #ff6b6b; }
        .warning { border-left-color: #ffa500; }
        .success { border-left-color: #28a745; }
        h2 { color: #0fa47f; margin-top: 0; }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        button {
            background: #0fa47f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #0d8b6f; }
        .step { margin: 15px 0; }
        ol { line-height: 1.8; }
    </style>
</head>
<body>

<h1>🤖 Telegram Setup Guide</h1>

<div class="box warning">
    <h3>⚠️ Problem Found</h3>
    <p>Your Chat ID is set to <code>alertlors_bot</code> (a bot username), but Telegram API needs a numeric Chat ID!</p>
    <p><strong>Chat IDs look like:</strong> <code>123456789</code> (numbers only)</p>
</div>

<div class="box">
    <h2>How to Get Your Correct Chat ID</h2>
    
    <div class="step">
        <h3>Option 1: Using @userinfobot (Easiest)</h3>
        <ol>
            <li>Open Telegram app</li>
            <li>Search for and open: <code>@userinfobot</code></li>
            <li>Send any message or click /start</li>
            <li>It will reply with your ID: <code>Your user id is 123456789</code></li>
            <li>Copy that number (without "Your user id is")</li>
        </ol>
    </div>

    <div class="step">
        <h3>Option 2: Using @BotFather to Create Your Bot</h3>
        <ol>
            <li>Open Telegram and search for <code>@BotFather</code></li>
            <li>Create a new bot or use existing one</li>
            <li>Get your Chat ID from @userinfobot (follow Option 1)</li>
        </ol>
    </div>

    <div class="step">
        <h3>Option 3: Direct Test in Telegram</h3>
        <ol>
            <li>In the URL bar, paste: <code>https://api.telegram.org/bot8554370247:AAFiI-OOPod0e3zglrWTHo7Yi3UY7UrwJPI/getMe</code></li>
            <li>You'll see bot info (confirms token is valid)</li>
            <li>Still need to get YOUR Chat ID from @userinfobot</li>
        </ol>
    </div>
</div>

<div class="box success">
    <h2>Once You Have Your Numeric Chat ID:</h2>
    <ol>
        <li>Go to <a href="alert_settings.php">Alert Settings Page</a></li>
        <li>Find the Telegram section</li>
        <li>Keep Bot Token: <code>8554370247:AAFiI-OOPod0e3zglrWTHo7Yi3UY7UrwJPI</code></li>
        <li>Change Chat ID from <code>alertlors_bot</code> to your numeric ID (e.g., <code>123456789</code>)</li>
        <li>Click "Save Telegram Settings"</li>
    </ol>
</div>

<div class="box">
    <h2>🧪 Test After Setup</h2>
    <p>After saving your correct Chat ID:</p>
    <ol>
        <li>Go to <a href="admindashboard.php">Dashboard</a></li>
        <li>Make a loud noise for 10+ seconds</li>
        <li>You should receive a Telegram message with a camera picture</li>
    </ol>
</div>

<div class="box warning">
    <h3>Common Issues:</h3>
    <ul>
        <li>❌ Using bot username (@alertlors_bot) instead of numeric ID → Won't work</li>
        <li>❌ Using wrong Chat ID → Message won't send to right place</li>
        <li>✅ Using numeric ID from @userinfobot → Will work!</li>
    </ul>
</div>

<hr>

<?php
// Show current settings
require_once __DIR__ . '/telegram_config.php';
$settings = getTelegramSettings();
echo "<h3>Current Settings in Database:</h3>";
echo "<pre>";
echo "Bot Token: " . substr($settings['bot_token'], 0, 20) . "...\n";
echo "Chat ID: " . $settings['chat_id'] . "\n";
echo "Enabled: " . ($settings['enabled'] ? 'Yes' : 'No') . "\n";
echo "</pre>";
?>

</body>
</html>
