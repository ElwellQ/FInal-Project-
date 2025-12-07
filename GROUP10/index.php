<?php
session_start();
require_once 'db.php';

$error = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if (isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        // Check for admin login first
        if ($username === 'admin' && $password === '1234') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            header('Location: admindashboard.php');
            exit();
        }
        
        // Check for user in database
        $stmt = $pdo->prepare("SELECT id, username, password, role, phone FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_phone'] = $user['phone'] ?? null;
            
            if ($user['role'] === 'admin') {
                header('Location: admindashboard.php');
            } else {
                header('Location: userdashboard.php');
            }
            exit();
        }
        $error = "Invalid username or password!";
    } catch (PDOException $e) {
        $error = "Login error: " . $e->getMessage();
    }
}

$role = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Noise Monitoring Dashboard</title>
<link rel="stylesheet" href="styles.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .loading-screen {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-content {
        text-align: center;
        color: white;
    }
    
    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(255, 255, 255, 0.3);
        border-top: 5px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
</head>
<body class="login-page">

<!-- Loading Screen -->
<div class="loading-screen" id="loadingScreen">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Logging in...</p>
    </div>
</div>

<?php if (!$role): ?>
<div class="login-box">
    <h2>Login</h2>
    <form method="POST" onsubmit="return validateLogin();">
        <input type="text" name="username" placeholder="Username or Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="btn" type="submit">Login</button>
    </form>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <p style="text-align: center; margin-top: 15px;">Don't have an account? <a href="register.php" style="color: #0fa47f; text-decoration: none;">Create one here</a></p>
</div>
<?php else: ?>
<p>You are logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>. <a href="index.php?logout=true" onclick="return confirmLogout();">Logout</a></p>
<?php endif; ?>

<script>
function validateLogin() {
    let user = document.querySelector('input[name="username"]').value.trim();
    let pass = document.querySelector('input[name="password"]').value.trim();
    if (!user || !pass) { alert("Enter username and password!"); return false; }
    
    // Show loading screen
    document.getElementById('loadingScreen').style.display = 'flex';
    return true;
}

function confirmLogout() {
    Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to logout?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0fa47f',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Logout',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'index.php?logout=true';
        }
    });
    return false;
}
</script>

</body>
</html>
