<?php
session_start();
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: zones.php");
    exit();
}

$id = $_GET['id'];

// Get zone location before delete (for audit log)
$stmt = $pdo->prepare("SELECT location FROM zones WHERE id = ?");
$stmt->execute([$id]);
$zone = $stmt->fetch();

if ($zone) {
    // delete zone
    $delete = $pdo->prepare("DELETE FROM zones WHERE id = ?");
    $delete->execute([$id]);

    // also remove from noise_levels
    $delNoise = $pdo->prepare("DELETE FROM noise_levels WHERE location = ?");
    $delNoise->execute([$zone['location']]);

    if (function_exists('audit')) {
        audit($_SESSION['role'], "Deleted Zone", "Zone: " . $zone['location']);
    }
}

header("Location: zones.php");
exit();
?>
