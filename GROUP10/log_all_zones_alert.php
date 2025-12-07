<?php
/**
 * Log alert to ALL zones when sound exceeds 10 seconds
 * This ensures the event appears in logs for all zones
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $noise_level = $input['noise_level'] ?? 0;
    
    header('Content-Type: application/json');
    
    try {
        // Get all zones
        $zones_stmt = $pdo->query("SELECT id, location FROM zones");
        $zones = $zones_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($zones)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No zones found'
            ]);
            exit;
        }
        
        $logged_count = 0;
        
        // Log to each zone
        foreach ($zones as $zone) {
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
                $logged_count++;
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => "Alert logged to $logged_count zone(s)",
            'logged_count' => $logged_count,
            'total_zones' => count($zones)
        ]);
        
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
