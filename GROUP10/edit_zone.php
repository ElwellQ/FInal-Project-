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

// Fetch zone
$stmt = $pdo->prepare("SELECT * FROM zones WHERE id = ?");
$stmt->execute([$id]);
$zone = $stmt->fetch();

if (!$zone) {
    header("Location: zones.php");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = trim($_POST['location']);
    if ($location !== "") {
        $update = $pdo->prepare("UPDATE zones SET location = ? WHERE id = ?");
        $update->execute([$location, $id]);

        if (function_exists('audit')) audit($_SESSION['role'], "Updated Zone", "Zone ID: $id → $location");

        header("Location: zones.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Zone</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container card">
    <h3>Edit Zone</h3>

    <form method="POST">
        <label>Location:</label>
        <input type="text" name="location" value="<?= htmlspecialchars($zone['location']) ?>" required>
        
        <button class="btn">Save Changes</button>
        <a href="zones.php" class="btn">Cancel</a>
    </form>
</div>

</body>
</html>
