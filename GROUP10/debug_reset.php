<?php
require_once __DIR__ . '/db.php';

// First check current values
echo "<h3>Current Zone Values:</h3>";
$stmt = $pdo->query("SELECT id, location, current_db FROM zones");
$zones = $stmt->fetchAll();
foreach ($zones as $z) {
    echo "ID: {$z['id']}, {$z['location']}: {$z['current_db']} dB<br>";
}

echo "<hr>";

// Now reset them
echo "<h3>Resetting all zones to 0 dB...</h3>";
$stmt = $pdo->prepare("UPDATE zones SET current_db = 0");
$result = $stmt->execute();

if ($result) {
    echo "✓ Reset successful!<br>";
} else {
    echo "✗ Reset failed<br>";
}

// Verify the reset
echo "<h3>After Reset:</h3>";
$stmt = $pdo->query("SELECT id, location, current_db FROM zones");
$zones = $stmt->fetchAll();
foreach ($zones as $z) {
    echo "ID: {$z['id']}, {$z['location']}: {$z['current_db']} dB<br>";
}

echo "<br><a href='admindashboard.php'>← Back to Dashboard (hard refresh with Ctrl+Shift+R)</a>";
?>
