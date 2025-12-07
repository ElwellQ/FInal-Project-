<?php
// SMS Settings Configuration
// This file manages SMS alert settings

require_once __DIR__ . '/db.php';

// Get current SMS settings
function getSMSSettings() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM sms_settings LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result;
        }
        
        // Return default if not found
        return array(
            'phone_number' => '',
            'api_key' => '',
            'sender_id' => '',
            'enabled' => 1
        );
    } catch (PDOException $e) {
        error_log("SMS Settings Error: " . $e->getMessage());
        return array(
            'phone_number' => '',
            'api_key' => '',
            'sender_id' => '',
            'enabled' => 1
        );
    }
}

// Update SMS settings
function updateSMSSettings($phone_number, $api_key, $sender_id, $enabled) {
    global $pdo;
    
    try {
        // Check if record exists
        $check = $pdo->query("SELECT id FROM sms_settings LIMIT 1");
        
        if ($check->rowCount() > 0) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE sms_settings SET phone_number=?, api_key=?, sender_id=?, enabled=?");
            return $stmt->execute([$phone_number, $api_key, $sender_id, $enabled]);
        } else {
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO sms_settings (phone_number, api_key, sender_id, enabled) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$phone_number, $api_key, $sender_id, $enabled]);
        }
    } catch (PDOException $e) {
        error_log("SMS Settings Update Error: " . $e->getMessage());
        return false;
    }
}

// Initialize SMS settings table if not exists
function initializeSMSSettingsTable() {
    global $pdo;
    
    try {
        $query = "CREATE TABLE IF NOT EXISTS sms_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            phone_number VARCHAR(20) NOT NULL,
            api_key VARCHAR(255) NOT NULL,
            sender_id VARCHAR(50) NOT NULL,
            enabled BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        return $pdo->exec($query);
    } catch (PDOException $e) {
        error_log("SMS Settings Table Creation Error: " . $e->getMessage());
        return false;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_settings') {
        header('Content-Type: application/json');
        echo json_encode(getSMSSettings());
        exit;
    }
    
    if ($action === 'update_settings') {
        $phone_number = $_POST['phone_number'] ?? '';
        $api_key = $_POST['api_key'] ?? '';
        $sender_id = $_POST['sender_id'] ?? '';
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        
        $success = updateSMSSettings($phone_number, $api_key, $sender_id, $enabled);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => $success ? 'success' : 'error']);
        exit;
    }
}

// Initialize table on first load
initializeSMSSettingsTable();
?>
