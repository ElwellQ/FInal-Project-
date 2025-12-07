<?php
/**
 * Update selected zone noise level in real-time
 * Also logs to database when noise exceeds 10 seconds
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $zone_id = $input['zone_id'] ?? null;
    $noise_level = $input['noise_level'] ?? 0;
    $duration_seconds = $input['duration_seconds'] ?? 0; // How long noise has been detected
    
    // If no zone selected, return error
    if (!$zone_id) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No zone selected']);
        exit;
    }
    
    try {
        // Get the selected zone
        $zone_stmt = $pdo->prepare("SELECT id, location FROM zones WHERE id = ?");
        $zone_stmt->execute([$zone_id]);
        $zone = $zone_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$zone) {
            echo json_encode(['status' => 'error', 'message' => 'Zone not found']);
            exit;
        }
        
        $location = $zone['location'];
        
        // Update ONLY the selected zone with current noise level
        $update_stmt = $pdo->prepare("UPDATE zones SET current_db = ? WHERE id = ?");
        $update_stmt->execute([$noise_level, $zone_id]);
        
        // If noise exceeds 10 seconds threshold, log it for this zone
        if ($duration_seconds >= 10) {
            // Check if we haven't already logged this alert
            $check_stmt = $pdo->prepare(
                "SELECT id FROM noise_logs 
                 WHERE zone = ? 
                 AND db_value >= 75 
                 AND ts > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                 ORDER BY ts DESC LIMIT 1"
            );
            $check_stmt->execute([$location]);
            $recent_log = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Only log if no recent log exists (avoid duplicate logs within 1 minute)
            if (!$recent_log) {
                $log_stmt = $pdo->prepare(
                    "INSERT INTO noise_logs (zone, db_value, rms, ts) 
                     VALUES (?, ?, ?, NOW())"
                );
                $log_stmt->execute([
                    $location,
                    $noise_level,
                    0 // rms value, can be enhanced later
                ]);
                
                // Optional: create alert record
                create_alert_if_needed($location, $noise_level);
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'zone_id' => $zone_id,
            'zone_location' => $location,
            'noise_level' => $noise_level,
            'duration_seconds' => $duration_seconds
        ]);
        
    } catch (PDOException $e) {
        error_log("Zone Update Error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
