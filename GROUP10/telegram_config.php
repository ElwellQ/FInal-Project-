<?php
/**
 * Telegram Settings Configuration
 */

require_once __DIR__ . '/db.php';

function getTelegramSettings() {
    global $pdo;
    try {
        // Ensure table exists
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
        
        $stmt = $pdo->query("SELECT * FROM telegram_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        return $settings ?: [
            'id' => 0,
            'bot_token' => '',
            'chat_id' => '',
            'enabled' => 0
        ];
    } catch (Exception $e) {
        return [
            'id' => 0,
            'bot_token' => '',
            'chat_id' => '',
            'enabled' => 0
        ];
    }
}

function updateTelegramSettings($bot_token, $chat_id, $enabled = 1) {
    global $pdo;
    try {
        // Ensure table exists
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
        
        $stmt = $pdo->prepare("SELECT id FROM telegram_settings LIMIT 1");
        $stmt->execute();
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists && $exists['id']) {
            $stmt = $pdo->prepare("UPDATE telegram_settings SET bot_token = ?, chat_id = ?, enabled = ? WHERE id = ?");
            return $stmt->execute([$bot_token, $chat_id, $enabled, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO telegram_settings (bot_token, chat_id, enabled) VALUES (?, ?, ?)");
            return $stmt->execute([$bot_token, $chat_id, $enabled]);
        }
    } catch (Exception $e) {
        error_log("Telegram Settings Error: " . $e->getMessage());
        return false;
    }
}
?>
