<?php
/**
 * Update all zones with current sound level
 * Called every 500ms from dashboard
 */

require_once __DIR__ . '/db.php';

// Get current sound level from ESP32
$sound_url = "http://192.168.1.17/sound";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $sound_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$sound_response = curl_exec($ch);
curl_close($ch);

$current_db = intval($sound_response) ?? 0;
$threshold = 75;

try {
    // Get all zones
    $stmt = $pdo->query("SELECT id FROM zones");
    $zones = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Update all zones with current sound level
    $update_stmt = $pdo->prepare("UPDATE zones SET current_db = ?");
    
    foreach($zones as $zone_id) {
        $update_stmt->execute([$current_db]);
    }
    
    echo json_encode([
        'success' => true,
        'current_db' => $current_db,
        'zones_updated' => count($zones)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
