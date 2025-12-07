<?php
session_start();
require_once __DIR__ . '/functions.php';

// Only admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$zones = get_all_zone_noise(); // Get all zones with current noise
$THRESHOLD = 75; // Hardcoded threshold
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="styles.css">
<!-- Sweet Alert -->
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

  <a href="admindashboard.php" class="sidebar-link active">Dashboard</a>
  <a href="zones.php" class="sidebar-link">Zones</a>
  <a href="logs.php" class="sidebar-link">Accounts</a>
  <a href="alert_settings.php" class="sidebar-link">Alert Settings</a>

  <div class="sidebar-bottom">
    <span class="sidebar-user">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
    <a href="#" onclick="return confirmLogout();" class="sidebar-link logout">Logout</a>
  </div>
</div>

<!-- Header -->
<header>
  <div class="brand-wrapper">
    <div class="brand">
      <div class="brand-text">
        <div class="brand-name">Noise Monitoring</div>
        <div class="brand-sub">Dashboard</div>
      </div>
    </div>
  </div>
</header>

<!-- Main container -->
<div class="container">

  <!-- Welcome Card -->
  <div class="card">
    <h2>Welcome to Admin Dashboard</h2>
    <p class="sub">Manage zones, view logs, and configure alert settings from the sidebar navigation.</p>
  </div>

  <!-- Quick Stats Card -->
  <div class="card">
    <h2>System overview</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <div style="padding: 20px; background: #f0f9f7; border-radius: 8px; text-align: center;">
        <div style="font-size: 24px; font-weight: 700; color: #0fa47f;">
          <?php 
            $count = count($zones);
            echo $count;
          ?>
        </div>
        <div style="font-size: 14px; color: #666; margin-top: 5px;">Total Zones</div>
      </div>
      <div style="padding: 20px; background: #f0f9f7; border-radius: 8px; text-align: center;">
        <div style="font-size: 24px; font-weight: 700; color: #0fa47f;">
          <?php 
            $loud_count = count(array_filter($zones, fn($z) => ($z['current_db'] ?? 0) >= $THRESHOLD));
            echo $loud_count;
          ?>
        </div>
        <div style="font-size: 14px; color: #666; margin-top: 5px;">Loud Zones</div>
      </div>
    </div>
  </div>

  <!-- Navigation Info Card -->
  <div class="card">
    <h2>Quick navigation</h2>
    <p class="sub" style="margin-bottom: 15px;">Use the sidebar to access:</p>
    <ul style="list-style: none; padding: 0; margin: 0;">
      <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
        <strong>📍 Zones</strong> - View and manage all monitoring zones
      </li>
      <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
        <strong>📋 Logs</strong> - View alert history and noise recordings
      </li>
      <li style="padding: 10px 0;">
        <strong>⚙️ Alert Settings</strong> - Configure SMS and Telegram notifications
      </li>
    </ul>
  </div>

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
