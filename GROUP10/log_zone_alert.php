<?php
/**
 * Log alert to SELECTED zone only when sound exceeds 10 seconds
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $zone_id = $input['zone_id'] ?? null;
    $noise_level = $input['noise_level'] ?? 0;
    
    header('Content-Type: application/json');
    
    if (!$zone_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No zone selected'
        ]);
        exit;
    }
    
    try {
        // Get the zone location
        $zone_stmt = $pdo->prepare("SELECT location FROM zones WHERE id = ?");
        $zone_stmt->execute([$zone_id]);
        $zone = $zone_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$zone) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Zone not found'
            ]);
            exit;
        }
        
        $location = $zone['location'];
        
        // Check if we haven't already logged this alert for this zone
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
                0 // rms value
            ]);
            
            echo json_encode([
                'status' => 'success',
                'message' => "Alert logged to zone: $location",
                'zone' => $location
            ]);
        } else {
            echo json_encode([
                'status' => 'duplicate',
                'message' => "Recent alert already logged for this zone",
                'zone' => $location
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>
