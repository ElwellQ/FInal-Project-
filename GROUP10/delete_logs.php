<?php
/**
 * Delete logs endpoint
 */

require_once __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'delete_all') {
    try {
        $pdo->exec("DELETE FROM noise_logs");
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'All logs deleted']);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_filtered') {
    try {
        $zone = $_GET['zone'] ?? '';
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        
        $where = [];
        $params = [];
        
        if ($zone !== '') { $where[] = "zone = ?"; $params[] = $zone; }
        if ($from !== '') { $where[] = "ts >= ?"; $params[] = $from . ' 00:00:00'; }
        if ($to !== '') { $where[] = "ts <= ?"; $params[] = $to . ' 23:59:59'; }
        
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $pdo->prepare("DELETE FROM noise_logs $whereSql");
        $stmt->execute($params);
        $deleted = $stmt->rowCount();
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => "Deleted $deleted log(s)"]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
