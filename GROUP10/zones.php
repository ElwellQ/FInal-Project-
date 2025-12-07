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

// Handle zone operations for admin
if ($is_admin) {
    // Add new zone
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $location = trim($_POST['location'] ?? '');
        if (!empty($location)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO zones (location) VALUES (?)");
                $stmt->execute([$location]);
                $_SESSION['success_msg'] = "Zone created successfully!";
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = "Error creating zone: " . $e->getMessage();
            }
        }
    }
    
    // Update zone
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
        $zone_id = intval($_POST['zone_id'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        if ($zone_id > 0 && !empty($location)) {
            try {
                $stmt = $pdo->prepare("UPDATE zones SET location = ? WHERE id = ?");
                $stmt->execute([$location, $zone_id]);
                $_SESSION['success_msg'] = "Zone updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = "Error updating zone: " . $e->getMessage();
            }
        }
    }
    
    // Delete zone
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $zone_id = intval($_POST['zone_id'] ?? 0);
        if ($zone_id > 0) {
            try {
                // Delete related records first
                $pdo->prepare("DELETE FROM noise_logs WHERE zone_id = ?")->execute([$zone_id]);
                $pdo->prepare("DELETE FROM zones WHERE id = ?")->execute([$zone_id]);
                $_SESSION['success_msg'] = "Zone deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = "Error deleting zone: " . $e->getMessage();
            }
        }
    }
}

// Get all zones
$zones = get_all_zone_noise();
$THRESHOLD = 75;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zones - Noise Monitoring</title>
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
    <a href="zones.php" class="sidebar-link active">Zones</a>
    <a href="logs.php" class="sidebar-link">Accounts</a>
    <a href="alert_settings.php" class="sidebar-link">Alert Settings</a>
  <?php else: ?>
    <a href="userdashboard.php" class="sidebar-link">Dashboard</a>
    <a href="zones.php" class="sidebar-link active">Zones</a>
    <a href="logs.php" class="sidebar-link">Logs</a>
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
        <div class="brand-name">Zones</div>
        <div class="brand-sub"><?= $is_admin ? 'Manage and monitor all zones' : 'Monitor all zones' ?></div>
      </div>
    </div>
  </div>
</header>

<!-- Main container -->
<div class="container">
  
  <?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success" style="padding: 12px 16px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 6px; margin-bottom: 20px;">
      ✓ <?= $_SESSION['success_msg'] ?>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-error" style="padding: 12px 16px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 6px; margin-bottom: 20px;">
      ✕ <?= $_SESSION['error_msg'] ?>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
  <?php endif; ?>
  
  <!-- Admin Zone Management -->
  <?php if ($is_admin): ?>
  <div class="card">
    <h2>Add new zone</h2>
    <form method="POST" style="display: flex; gap: 10px;">
      <input type="hidden" name="action" value="add">
      <input type="text" name="location" placeholder="Enter zone name (e.g., Main Hall, Room 101)" required style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
      <button type="submit" class="button" style="background: #0fa47f; color: white;">+ Add Zone</button>
    </form>
  </div>
  <?php endif; ?>
  
  <!-- Zones Grid -->
  <div class="card">
    <h2>Zone overview</h2>
    <?php if (count($zones) > 0): ?>
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">
        <?php foreach ($zones as $zone): 
          $noise = $zone['current_db'] ?? 0;
          $status = ($noise >= $THRESHOLD) ? 'Alert' : 'Normal';
          $bgColor = ($noise >= $THRESHOLD) ? '#fff3cd' : '#d4edda';
          $textColor = ($noise >= $THRESHOLD) ? '#ff6b6b' : '#28a745';
          $borderColor = ($noise >= $THRESHOLD) ? '#ff6b6b' : '#28a745';
        ?>
          <div style="border-left: 3px solid <?= $borderColor ?>; background: white; padding: 12px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
            <h3 style="margin: 0 0 8px 0; color: #333; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($zone['location']) ?></h3>
            
            <div style="font-size: 32px; font-weight: bold; color: <?= $textColor ?>; text-align: center; margin: 8px 0;">
              <?= $noise ?>
            </div>
            
            <div style="text-align: center; padding: 6px; border-radius: 4px; background-color: <?= $bgColor ?>; margin: 6px 0;">
              <span style="color: <?= $textColor ?>; font-weight: bold; font-size: 11px;">
                <?= ($noise >= $THRESHOLD) ? '🔴' : '🟢' ?> <?= $status ?>
              </span>
            </div>

            <p style="margin: 6px 0 0 0; font-size: 10px; color: #999; text-align: center;">ID: <?= $zone['id'] ?></p>
            
            <?php if ($is_admin): ?>
            <div style="margin-top: auto; display: flex; gap: 6px; padding-top: 8px;">
              <button onclick="editZone(<?= $zone['id'] ?>, '<?= addslashes($zone['location']) ?>')" class="button" style="flex: 1; background: #007bff; color: white; font-size: 11px; padding: 6px; border: none; border-radius: 4px; cursor: pointer;">✏️ Edit</button>
              <form method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Delete this zone?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="zone_id" value="<?= $zone['id'] ?>">
                <button type="submit" class="button" style="width: 100%; background: #dc3545; color: white; font-size: 11px; padding: 6px; border: none; border-radius: 4px; cursor: pointer;">🗑️ Delete</button>
              </form>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px;">
        <p style="font-size: 16px; color: #666; margin: 0;">No zones available.</p>
        <p style="font-size: 14px; color: #999; margin: 10px 0 0 0;"><?= $is_admin ? 'Create a zone above to start monitoring.' : 'Please ask your admin to create zones.' ?></p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Zone Statistics -->
  <div class="card">
    <h2>Zone statistics</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
      <div style="padding: 15px; background: #e8f5e9; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">Total Zones</p>
        <p style="margin: 0; font-size: 32px; font-weight: bold; color: #28a745;"><?= count($zones) ?></p>
      </div>

      <div style="padding: 15px; background: #fef5e7; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">Alert Zones</p>
        <p style="margin: 0; font-size: 32px; font-weight: bold; color: #ff9800;">
          <?php 
            $alertCount = 0;
            foreach ($zones as $zone) {
              if (($zone['current_db'] ?? 0) >= $THRESHOLD) $alertCount++;
            }
            echo $alertCount;
          ?>
        </p>
      </div>

      <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">Normal Zones</p>
        <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2196F3;">
          <?php 
            $normalCount = 0;
            foreach ($zones as $zone) {
              if (($zone['current_db'] ?? 0) < $THRESHOLD) $normalCount++;
            }
            echo $normalCount;
          ?>
        </p>
      </div>

      <div style="padding: 15px; background: #fff3e0; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold;">Avg Noise Level</p>
        <p style="margin: 0; font-size: 32px; font-weight: bold; color: #ff5722;">
          <?php 
            $avgNoise = count($zones) > 0 ? round(array_sum(array_map(fn($z) => $z['current_db'] ?? 0, $zones)) / count($zones), 1) : 0;
            echo $avgNoise;
          ?>
        </p>
      </div>
    </div>
  </div>

  <!-- Live Zone Table -->
  <div class="card">
    <h2>Live zone status</h2>
    <table class="logs">
      <thead>
        <tr>
          <th>Location</th>
          <th>Current Level</th>
          <th>Status</th>
          <th>Threshold</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($zones as $zone):
          $noise = $zone['current_db'] ?? 0;
          $status = ($noise >= $THRESHOLD) ? 'ALERT' : 'Normal';
          $color = ($noise >= $THRESHOLD) ? 'red' : 'green';
        ?>
          <tr>
            <td><strong><?= htmlspecialchars($zone['location']) ?></strong></td>
            <td><?= $noise ?> dB</td>
            <td style="color:<?= $color ?>; font-weight:bold;"><?= $status ?></td>
            <td><?= $THRESHOLD ?> dB</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- Edit Zone Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: white; padding: 30px; border-radius: 12px; max-width: 400px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
    <h2 style="margin: 0 0 20px 0; color: #333;">Edit Zone</h2>
    <form method="POST" id="editForm">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="zone_id" id="editZoneId">
      
      <div style="margin-bottom: 20px;">
        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Zone Name:</label>
        <input type="text" name="location" id="editLocation" placeholder="Enter zone name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
      </div>
      
      <div style="display: flex; gap: 10px;">
        <button type="submit" class="button" style="flex: 1; background: #0fa47f; color: white;">Save Changes</button>
        <button type="button" onclick="closeEditModal()" class="button" style="flex: 1; background: #ccc; color: #333;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editZone(zoneId, zoneName) {
    document.getElementById('editZoneId').value = zoneId;
    document.getElementById('editLocation').value = zoneName;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

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

