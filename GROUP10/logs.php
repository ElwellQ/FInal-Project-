<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$is_admin = $_SESSION['role'] === 'admin';

// Handle account deletion (admin only)
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_account') {
        $account_id = intval($_POST['account_id'] ?? 0);
        if ($account_id > 0 && $account_id !== $user_id) { // Prevent deleting own account
            try {
                // Delete only the user's noise logs and the user record (NOT zones - they're shared!)
                $pdo->prepare("DELETE FROM noise_logs")->execute();
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$account_id]);
                $_SESSION['success_msg'] = "Account deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = "Error deleting account: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_msg'] = "Cannot delete this account.";
        }
    }
}

// Handle noise log deletion (user only)
if (!$is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete_all') {
            try {
                $pdo->query("DELETE FROM noise_logs");
                header('Location: logs.php?deleted=all');
                exit();
            } catch (PDOException $e) {
                $error = "Error deleting logs";
            }
        } elseif ($_POST['action'] === 'delete_filtered' && isset($_POST['zone'])) {
            try {
                $zone = $_POST['zone'];
                $stmt = $pdo->prepare("DELETE FROM noise_logs WHERE zone = ?");
                $stmt->execute([$zone]);
                header('Location: logs.php?deleted=filtered');
                exit();
            } catch (PDOException $e) {
                $error = "Error deleting filtered logs";
            }
        }
    }
}

// Get data based on role
if ($is_admin) {
    // Get all user accounts for admin
    try {
        $stmt = $pdo->query("SELECT id, username, phone, role, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $accounts = [];
    }
} else {
    // Get noise logs for user
    $zone_filter = $_GET['zone'] ?? '';
    try {
        if ($zone_filter) {
            $logs_stmt = $pdo->prepare("SELECT * FROM noise_logs WHERE zone = ? ORDER BY ts DESC LIMIT 500");
            $logs_stmt->execute([$zone_filter]);
        } else {
            $logs_stmt = $pdo->query("SELECT * FROM noise_logs ORDER BY ts DESC LIMIT 500");
        }
        $logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $logs = [];
    }
    
    $zones = get_all_zone_noise();
    $THRESHOLD = 75;
    $deleted_msg = isset($_GET['deleted']) ? $_GET['deleted'] : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_admin ? 'Accounts' : 'Logs' ?> - Noise Monitoring</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-page">

<!-- Mobile Menu Toggle -->
<button class="menu-toggle" id="menuToggle" onclick="toggleMenu()">
  <span></span>
  <span></span>
  <span></span>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span>Noise Monitoring</span>
  </div>

  <?php if ($is_admin): ?>
    <a href="admindashboard.php" class="sidebar-link">Dashboard</a>
    <a href="zones.php" class="sidebar-link">Zones</a>
    <a href="logs.php" class="sidebar-link active">Accounts</a>
    <a href="alert_settings.php" class="sidebar-link">Alert Settings</a>
  <?php else: ?>
    <a href="userdashboard.php" class="sidebar-link">Dashboard</a>
    <a href="zones.php" class="sidebar-link">Zones</a>
    <a href="logs.php" class="sidebar-link active">Logs</a>
    <a href="alert_settings.php" class="sidebar-link">SMS Alert Settings</a>
  <?php endif; ?>

  <div class="sidebar-bottom">
    <span class="sidebar-user">👤 <?= htmlspecialchars($username) ?></span>
    <a href="#" onclick="return confirmLogout();" class="sidebar-link logout">Logout</a>
  </div>
</div>

<!-- Header -->
<header>
  <div class="brand-wrapper">
    <div class="brand">
      <div class="brand-text">
        <div class="brand-name"><?= $is_admin ? 'Accounts' : 'Logs' ?></div>
        <div class="brand-sub"><?= $is_admin ? 'Manage user accounts' : 'View all alerts and activity logs' ?></div>
      </div>
    </div>
  </div>
</header>

<!-- Main container -->
<div class="container">

  <!-- Success/Error Messages -->
  <?php if (isset($_SESSION['success_msg'])): ?>
    <div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; color: #155724; margin-bottom: 20px;">
      ✓ <?= $_SESSION['success_msg'] ?>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['error_msg'])): ?>
    <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; color: #721c24; margin-bottom: 20px;">
      ✕ <?= $_SESSION['error_msg'] ?>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
  <?php endif; ?>

  <?php if ($is_admin): ?>
    <!-- ADMIN: ACCOUNT MANAGEMENT -->
    <div class="card">
      <h2>User Accounts Management</h2>
      <p class="sub">Manage all user accounts and delete users as needed</p>
    </div>

    <div class="card">
      <table class="logs">
        <thead>
          <tr>
            <th>Username</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($accounts) > 0): ?>
            <?php foreach ($accounts as $account): ?>
              <tr>
                <td><strong><?= htmlspecialchars($account['username']) ?></strong></td>
                <td><?= htmlspecialchars($account['phone'] ?? 'N/A') ?></td>
                <td><span style="background: #e8f5e9; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?= ucfirst($account['role']) ?></span></td>
                <td><?= date('M d, Y', strtotime($account['created_at'])) ?></td>
                <td>
                  <form method="POST" style="display: inline;" onsubmit="return confirm('Delete account <?= htmlspecialchars($account['username']) ?>? This will remove all associated data.');">
                    <input type="hidden" name="action" value="delete_account">
                    <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                    <button type="submit" class="button" style="background: #dc3545; color: white; padding: 6px 12px; font-size: 13px; border: none; border-radius: 4px; cursor: pointer;">🗑️ Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 40px; color: #999;">No user accounts found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Account Statistics</h2>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; text-align: center;">
          <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">Total Users</p>
          <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2196F3;"><?= count($accounts) ?></p>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- USER: NOISE LOGS -->
    <?php if (isset($_GET['deleted'])): ?>
      <div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; color: #155724; margin-bottom: 20px;">
        ✓ Logs deleted successfully!
      </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card">
      <h2>Filter & Manage Logs</h2>
      <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 15px; align-items: end;">
        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 8px;">Filter by Zone:</label>
          <form method="GET" style="display: flex; gap: 10px;">
            <select name="zone" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
              <option value="">-- All Zones --</option>
              <?php foreach ($zones as $zone): ?>
                <option value="<?= htmlspecialchars($zone['location']) ?>" <?= ($zone_filter === $zone['location']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($zone['location']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="button" style="background: #0fa47f;">Filter</button>
            <a href="logs.php" class="button" style="background: #ccc; color: #333; text-decoration: none; display: inline-block;">Clear</a>
          </form>
        </div>
        
        <div>
          <form method="POST" style="display: flex; gap: 10px;">
            <input type="hidden" name="action" value="delete_filtered">
            <input type="hidden" name="zone" value="<?= htmlspecialchars($zone_filter) ?>">
            <?php if ($zone_filter): ?>
              <button type="submit" class="button" style="background: #ff9800;" onclick="return confirm('Delete all logs for <?= htmlspecialchars($zone_filter) ?>?');">🗑️ Delete Zone</button>
            <?php endif; ?>
          </form>
        </div>

        <div>
          <form method="POST" style="display: flex;">
            <input type="hidden" name="action" value="delete_all">
            <button type="submit" class="button" style="background: #dc3545;" onclick="return confirm('Delete ALL logs? This cannot be undone!');">🗑️ Delete All</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Alert Statistics -->
    <div class="card">
      <h2>Alert Statistics</h2>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div style="padding: 15px; background: #e8f5e9; border-radius: 8px; text-align: center;">
          <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">Total Alerts</p>
          <p style="margin: 0; font-size: 32px; font-weight: bold; color: #28a745;"><?= count($logs) ?></p>
        </div>

        <div style="padding: 15px; background: #fef5e7; border-radius: 8px; text-align: center;">
          <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">High Alerts</p>
          <p style="margin: 0; font-size: 32px; font-weight: bold; color: #ff9800;">
            <?php 
              $highCount = 0;
              foreach ($logs as $log) {
                if (($log['db_value'] ?? 0) >= $THRESHOLD) $highCount++;
              }
              echo $highCount;
            ?>
          </p>
        </div>

        <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; text-align: center;">
          <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">Normal Logs</p>
          <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2196F3;">
            <?php 
              $normalCount = 0;
              foreach ($logs as $log) {
                if (($log['db_value'] ?? 0) < $THRESHOLD) $normalCount++;
              }
              echo $normalCount;
            ?>
          </p>
        </div>

        <div style="padding: 15px; background: #fff3e0; border-radius: 8px; text-align: center;">
          <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">Latest Alert</p>
          <p style="margin: 0; font-size: 14px; color: #ff5722;">
            <?php 
              if (count($logs) > 0) {
                echo date('M d, H:i', strtotime($logs[0]['ts']));
              } else {
                echo 'N/A';
              }
            ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Alert Logs Table -->
    <div class="card">
      <h2>All Alerts</h2>
      <?php if (count($logs) > 0): ?>
        <div style="overflow-x: auto;">
          <table class="logs">
            <thead>
              <tr>
                <th>Zone</th>
                <th>Noise Level</th>
                <th>Timestamp</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log):
                $noiseLevel = $log['db_value'] ?? 'N/A';
                $isAlert = ($noiseLevel >= $THRESHOLD);
                $status = $isAlert ? 'ALERT' : 'Normal';
                $statusColor = $isAlert ? '#dc3545' : '#28a745';
              ?>
                <tr style="<?= $isAlert ? 'background-color: rgba(220, 53, 69, 0.05);' : '' ?>">
                  <td><strong><?= htmlspecialchars($log['zone'] ?? 'Unknown') ?></strong></td>
                  <td>
                    <span style="background: <?= $isAlert ? '#ffe5e5' : '#e5f5e5' ?>; padding: 5px 10px; border-radius: 4px;">
                      <?= $noiseLevel ?> dB
                    </span>
                  </td>
                  <td><?= date('M d, Y H:i:s', strtotime($log['ts'])) ?></td>
                  <td>
                    <span style="color: <?= $statusColor ?>; font-weight: bold; background: <?= $isAlert ? '#ffd6d6' : '#d6f5d6' ?>; padding: 5px 10px; border-radius: 4px; display: inline-block;">
                      <?= $isAlert ? '🔴 ' : '🟢 ' ?><?= $status ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px;">
          <p style="font-size: 16px; color: #666; margin: 0;">No alerts logged yet.</p>
          <p style="font-size: 14px; color: #999; margin: 10px 0 0 0;">Alerts will appear here when noise is detected.</p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<script>
// Mobile menu toggle
function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

// Logout confirmation
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

// Close menu when clicking on a link
document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.remove('active');
        }
    });
});

// Close menu when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    if (window.innerWidth <= 768 && 
        !sidebar.contains(event.target) && 
        !menuToggle.contains(event.target)) {
        sidebar.classList.remove('active');
    }
});
</script>

</body>
</html>

