<?php
require_once __DIR__ . '/functions.php';
session_start();
$role = $_SESSION['role'] ?? null;
if (!$role || !in_array($role, ['admin','user'])) { header("Location: index.php"); exit(); }

$zoneFilter = $_GET['zone'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = [];
$params = [];
if ($zoneFilter !== '') { $where[] = "zone = ?"; $params[] = $zoneFilter; }
if ($from !== '') { $where[] = "ts >= ?"; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = "ts <= ?"; $params[] = $to . ' 23:59:59'; }
$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT id, ts, zone, db_value, rms, sent_by FROM noise_logs $whereSql ORDER BY ts DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=noise_logs.csv');
$out = fopen('php://output','w');
fputcsv($out, ['id','ts','zone','db_value','rms','sent_by']);
foreach($rows as $r) fputcsv($out, [$r['id'],$r['ts'],$r['zone'],$r['db_value'],$r['rms'],$r['sent_by']]);
fclose($out);
exit();
