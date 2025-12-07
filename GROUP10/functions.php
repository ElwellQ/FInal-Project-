<?php
require_once __DIR__ . '/db.php';

/* -------------------- Alerts -------------------- */
function create_alert_if_needed($zone, $db_value) {
    global $pdo;
    $threshold = 75; // hardcoded threshold

    if ($db_value >= $threshold) {
        $stmt = $pdo->prepare("INSERT INTO alerts (zone, db_value) VALUES (?, ?)");
        $stmt->execute([$zone, $db_value]);
        return $pdo->lastInsertId();
    }
    return null;
}

/* -------------------- Zones Functions -------------------- */
function get_zones() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM zones ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_zone_name($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT location FROM zones WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

function get_all_zone_noise() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, location, current_db FROM zones ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
