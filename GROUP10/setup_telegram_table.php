<?php
/**
 * Create Telegram Settings Table
 * Run this once to set up the database table
 */

require_once __DIR__ . '/db.php';

try {
    // Create telegram_settings table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS telegram_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            bot_token VARCHAR(255) NOT NULL,
            chat_id VARCHAR(255) NOT NULL,
            enabled TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    echo "✓ Telegram settings table created successfully!";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
