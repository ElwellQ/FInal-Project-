<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required!';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long!';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $error = 'Phone number must be 10-15 digits!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        // Check if username or phone already exists
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR phone = ?");
            $stmt->execute([$username, $phone]);
            if ($stmt->rowCount() > 0) {
                $error = 'Username or phone number already exists!';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, phone, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $phone, $hashed_password, 'user']);
                $success = 'Account created successfully! You can now login.';
                
                // Clear form
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Noise Monitoring</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: 
                url('images/bg.png') no-repeat right center fixed,
                url('images/logo.png') no-repeat 20px 20px;
            background-size: cover, 120px;
            position: relative;
        }

        .auth-page::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.65);
            z-index: -1;
        }

        .auth-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
        }
        .auth-box h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .phone-input-group {
            display: flex;
            gap: 8px;
            margin: 10px 0;
        }
        .phone-input-group .country-code {
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
            min-width: 50px;
            text-align: center;
        }
        .phone-input-group input {
            flex: 1;
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .auth-box form input,
        .auth-box form button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .auth-box form input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        .auth-box form button {
            background-color: #0fa47f;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .auth-box form button:hover {
            background-color: #0d8b6f;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .auth-link {
            text-align: center;
            margin-top: 20px;
        }
        .auth-link a {
            color: #667eea;
            text-decoration: none;
        }
        .auth-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-box">
        <h2>Create Account</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
            <div class="auth-link">
                <p><a href="index.php">Back to Login</a></p>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                <div class="phone-input-group">
                    <div class="country-code">+63</div>
                    <input type="tel" name="phone" placeholder="9XXXXXXXXX (10 digits)" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                </div>
                <input type="password" name="password" placeholder="Password (min 6 characters)" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" name="register">Create Account</button>
            </form>

            <div class="auth-link">
                <p>Already have an account? <a href="index.php">Login here</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
