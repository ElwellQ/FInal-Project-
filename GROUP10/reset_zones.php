<?php
/**
 * Reset all zones to 0 dB (no noise detected)
 */

require_once __DIR__ . '/db.php';

try {
    // Reset all zones to 0 dB
    $stmt = $pdo->prepare("UPDATE zones SET current_db = 0");
    $stmt->execute();
    
    echo "✓ All zones reset to 0 dB successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
