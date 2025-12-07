<?php
/**
 * Save Telegram Chat ID
 */

require_once __DIR__ . '/telegram_config.php';

$bot_token = '8554370247:AAFiI-OOPod0e3zglrWTHo7Yi3UY7UrwJPI';
$chat_id = '5530178014';

// Save to database
if (updateTelegramSettings($bot_token, $chat_id, 1)) {
    echo "<h2 style='color: #28a745;'>✅ Telegram Chat ID Updated!</h2>";
    echo "<p><strong>Bot Token:</strong> " . substr($bot_token, 0, 20) . "...</p>";
    echo "<p><strong>Chat ID:</strong> $chat_id</p>";
    echo "<p><strong>Status:</strong> Enabled ✓</p>";
    echo "<p style='margin-top: 20px;'><strong>Next Step:</strong> Go to <a href='admindashboard.php'>Dashboard</a> and make a loud noise for 10+ seconds. You should receive a Telegram message with a camera picture!</p>";
} else {
    echo "<h2 style='color: #ff6b6b;'>❌ Error saving Chat ID</h2>";
    echo "<p><a href='alert_settings.php'>Back to Alert Settings</a></p>";
}
?>
