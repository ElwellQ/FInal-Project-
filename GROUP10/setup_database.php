<?php
require_once 'db.php';

echo "<h2>Setting up Database Tables</h2>";

try {
    // Drop existing users table if it exists (to reset schema)
    echo "<p>Checking for existing users table...</p>";
    $pdo->exec("DROP TABLE IF EXISTS users");
    echo "<p style='color: orange;'>✓ Old users table removed (if existed)!</p>";

    // Create users table
    echo "<p>Creating users table...</p>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20) UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p style='color: green;'>✓ Users table created!</p>";

    // Create admin user if not exists
    echo "<p>Checking for admin user...</p>";
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $admin_password = password_hash('1234', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, phone, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', '1234567890', $admin_password, 'admin']);
        echo "<p style='color: green;'>✓ Admin user created!</p>";
    } else {
        echo "<p style='color: green;'>✓ Admin user already exists!</p>";
    }

    echo "<hr>";
    echo "<h3>Database Setup Complete! ✓</h3>";
    
    // Show summary
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Users: $users_count</li>";
    echo "</ul>";
    
    echo "<p><strong>Admin Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Username: <code>admin</code></li>";
    echo "<li>Password: <code>1234</code></li>";
    echo "</ul>";
    
    echo "<p style='margin-top: 20px;'>";
    echo "<a href='index.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 4px;'>Go to Login</a>";
    echo "</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
