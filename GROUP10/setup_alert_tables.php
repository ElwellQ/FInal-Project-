<?php
/**
 * Verify/Setup Alert Tables
 */

require_once __DIR__ . '/db.php';

$errors = [];
$success_msgs = [];

try {
    // Check/Create SMS Settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sms_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            phone_number VARCHAR(20),
            api_key VARCHAR(255),
            sender_id VARCHAR(50),
            enabled TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    $success_msgs[] = "✓ SMS settings table ready";
} catch (Exception $e) {
    $errors[] = "SMS table error: " . $e->getMessage();
}

try {
    // Check/Create Telegram Settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS telegram_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            bot_token VARCHAR(255),
            chat_id VARCHAR(50),
            enabled TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    $success_msgs[] = "✓ Telegram settings table ready";
} catch (Exception $e) {
    $errors[] = "Telegram table error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Alert Tables</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: green; padding: 10px; margin: 10px 0; background: #f0f8f0; border-left: 4px solid green; }
        .error { color: red; padding: 10px; margin: 10px 0; background: #f8f0f0; border-left: 4px solid red; }
        .back { margin-top: 20px; }
        a { color: #0fa47f; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Alert Settings Setup</h1>
        
        <?php foreach($success_msgs as $msg): ?>
            <div class="success"><?= $msg ?></div>
        <?php endforeach; ?>
        
        <?php foreach($errors as $error): ?>
            <div class="error"><?= $error ?></div>
        <?php endforeach; ?>
        
        <?php if(empty($errors)): ?>
            <div class="success" style="margin-top: 20px; font-weight: bold;">✓ All tables are ready!</div>
        <?php endif; ?>
        
        <div class="back">
            <a href="alert_settings.php">← Go to Alert Settings</a><br>
            <a href="admindashboard.php">← Go to Dashboard</a>
        </div>
    </div>
</body>
</html>
