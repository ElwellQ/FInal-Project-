<?php
require_once 'db.php';

echo "<h2>Migrating Users Table</h2>";

try {
    // Check if phone column already exists
    $result = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'phone'");
    if ($result->rowCount() > 0) {
        echo "<p style='color: orange;'>Phone column already exists. Migration skipped.</p>";
    } else {
        // Drop email column if it exists
        echo "<p>Removing email column...</p>";
        $pdo->exec("ALTER TABLE users DROP COLUMN IF EXISTS email");
        echo "<p style='color: green;'>✓ Email column removed!</p>";

        // Add phone column
        echo "<p>Adding phone column...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) UNIQUE AFTER username");
        echo "<p style='color: green;'>✓ Phone column added!</p>";

        echo "<hr>";
        echo "<h3>Migration Complete! ✓</h3>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        h2, h3 {
            color: #333;
        }
        p {
            line-height: 1.6;
        }
        hr {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <p><a href="index.php">← Back to Login</a></p>
</body>
</html>
