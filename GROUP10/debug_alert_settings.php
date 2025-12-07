<?php
/**
 * Debug Alert Settings
 */

require_once __DIR__ . '/db.php';

echo "<h1>🔍 Alert Settings Debugger</h1>";

// Check tables
echo "<h2>Database Tables:</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '%settings%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($tables) {
        foreach ($tables as $table) {
            echo "✓ Found table: <strong>$table</strong><br>";
        }
    } else {
        echo "✗ No settings tables found<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test SMS Settings functions
echo "<h2>Testing SMS Functions:</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM sms_settings");
    $sms = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sms) {
        echo "✓ SMS Settings exist: " . json_encode($sms) . "<br>";
    } else {
        echo "✓ SMS Settings table empty (will create on first save)<br>";
    }
} catch (Exception $e) {
    echo "✗ SMS Error: " . $e->getMessage() . "<br>";
}

// Test Telegram Settings functions
echo "<h2>Testing Telegram Functions:</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM telegram_settings");
    $tg = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tg) {
        echo "✓ Telegram Settings exist: " . json_encode($tg) . "<br>";
    } else {
        echo "✓ Telegram Settings table empty (will create on first save)<br>";
    }
} catch (Exception $e) {
    echo "✗ Telegram Error: " . $e->getMessage() . "<br>";
}

// Test Update Functions
echo "<h2>Testing Update Functions:</h2>";
require_once __DIR__ . '/sms_config.php';
require_once __DIR__ . '/telegram_config.php';

try {
    $result = updateTelegramSettings("TEST_TOKEN_123", "TEST_CHAT_456", 1);
    echo "✓ updateTelegramSettings returned: " . ($result ? "TRUE" : "FALSE") . "<br>";
    
    // Check if it was inserted/updated
    $stmt = $pdo->query("SELECT * FROM telegram_settings");
    $tg = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Current Telegram data: " . json_encode($tg) . "<br>";
} catch (Exception $e) {
    echo "✗ Update Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<a href='alert_settings.php'>← Back to Alert Settings</a>";
?>

<style>
    body { font-family: Arial; background: #f5f5f5; padding: 20px; }
    h1, h2 { color: #333; }
    a { color: #0fa47f; text-decoration: none; font-weight: bold; }
</style>
