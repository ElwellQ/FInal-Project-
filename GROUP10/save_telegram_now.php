<?php
/**
 * Quick save Telegram credentials
 */

require_once __DIR__ . '/telegram_config.php';

$bot_token = '8554370247:AAFiI-OOPod0e3zglrWTHo7Yi3UY7UrwJPI';
$chat_id = 'alertlors_bot';

// Save to database
if (updateTelegramSettings($bot_token, $chat_id, 1)) {
    echo "<h2>✅ Telegram settings saved successfully!</h2>";
    echo "<p><strong>Bot Token:</strong> " . substr($bot_token, 0, 20) . "...</p>";
    echo "<p><strong>Chat ID:</strong> $chat_id</p>";
    echo "<p><strong>Status:</strong> Enabled</p>";
    echo "<p><a href='alert_settings.php'>Back to Alert Settings</a></p>";
} else {
    echo "<h2>❌ Error saving Telegram settings</h2>";
    echo "<p><a href='alert_settings.php'>Back to Alert Settings</a></p>";
}
?>
