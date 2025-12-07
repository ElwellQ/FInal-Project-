<?php
/**
 * Get all zones with current noise levels as JSON
 * Used for real-time updates on the dashboard
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, location, current_db FROM zones ORDER BY id DESC");
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $THRESHOLD = 75;
        
        // Add status to each zone
        foreach ($zones as &$zone) {
            $noise = $zone['current_db'] ?? 0;
            if ($noise >= $THRESHOLD) {
                $zone['status'] = 'Loud';
                $zone['color'] = 'red';
            } else {
                $zone['status'] = 'Quiet';
                $zone['color'] = 'green';
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($zones);
        
    } catch (PDOException $e) {
        error_log("Get Zones Error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
