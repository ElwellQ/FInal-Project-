<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get zones
$zones = get_all_zone_noise(); // Get all zones with current noise
$THRESHOLD = 75;

// Get alerts/logs for this user
try {
    $logs_stmt = $pdo->query("SELECT * FROM alerts ORDER BY id DESC LIMIT 50");
    $logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Noise Monitoring</title>
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

  <a href="userdashboard.php" class="sidebar-link active">Dashboard</a>
  <a href="zones.php" class="sidebar-link">Zones</a>
  <a href="logs.php" class="sidebar-link">Logs</a>
  <a href="alert_settings.php" class="sidebar-link">SMS Alert Settings</a>

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
        <div class="brand-name">Noise Monitoring</div>
        <div class="brand-sub">User Dashboard</div>
      </div>
    </div>
  </div>
</header>

<!-- Main container -->
<div class="container">

  <!-- Zone Selection (FIRST - REQUIRED) -->
  <div class="card" style="border: 2px solid #0fa47f;">
    <h2>Zone selection</h2>
    <p class="sub">Select a zone to begin monitoring</p>
    
    <div style="margin: 15px 0;">
      <label for="zoneSelect" style="font-weight: 600; display: block; margin-bottom: 8px;">Select Zone:</label>
      <select id="zoneSelect" style="width: 100%; padding: 10px; border: 2px solid #0fa47f; border-radius: 6px; font-size: 14px;">
        <option value="">-- Choose a Zone --</option>
        <?php foreach ($zones as $zone): ?>
          <option value="<?= $zone['id'] ?>" data-location="<?= htmlspecialchars($zone['location']) ?>">
            <?= htmlspecialchars($zone['location']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="selectedZoneInfo" style="display: none; margin-top: 15px; padding: 15px; background: #d4edda; border-radius: 6px; border-left: 4px solid #28a745;">
      <p style="margin: 0 0 10px 0;"><strong>Currently Monitoring:</strong></p>
      <p id="currentZoneName" style="margin: 5px 0; font-size: 18px; color: #0fa47f; font-weight: bold;">--</p>
    </div>
  </div>

  <!-- Disabled State Message -->
  <div id="disabledMessage" class="card" style="background: #fff3cd; border-left: 4px solid #ff9800; display: none;">
    <p style="margin: 0; text-align: center; font-weight: bold; color: #ff6b6b;">
      Select a zone to enable controls
    </p>
  </div>

  <!-- Live Camera Card -->
  <div class="card" id="cameraCard" style="opacity: 0.6; pointer-events: none;">
    <h2>Camera feed</h2>
    <p class="sub">Click "Show Camera Now" to view the camera anytime. Automatically opens for 5 seconds when loud noise is detected.</p>
    <div class="camera-feed" id="cameraContainer" style="display:none;"></div>
    <div style="display: flex; gap: 10px; justify-content: center;">
      <button class="button" onclick="showCamera(false)" disabled>Show Camera Now</button>
      <button class="button" onclick="closeCamera()" disabled>Close Camera</button>
    </div>
  </div>

  <!-- Servo Control & Buzzer Card -->
  <div id="controlsCard" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; opacity: 0.6; pointer-events: none;">
    
    <!-- Servo Control Card -->
    <div class="card">
      <h2>Servo control</h2>
      <div class="controls">
        <input type="range" min="0" max="180" id="servoSlider" value="90" disabled>
        <span id="servoVal">90°</span>
      </div>
      <div class="status" id="status">Status: Not connected</div>
    </div>

    <!-- Buzzer Control Card -->
    <div class="card">
      <h2>Buzzer control</h2>
      <div style="display: flex; gap: 10px; flex-direction: column;">
        <button class="button" onclick="toggleBuzzer('on')" id="buzzerOnBtn" style="background: #0fa47f;" disabled>Buzzer ON</button>
        <button class="button" onclick="toggleBuzzer('off')" id="buzzerOffBtn" style="background: #ccc; color: #333;" disabled>Buzzer OFF</button>
      </div>
      <div id="buzzerStatus" style="margin-top: 10px; text-align: center; font-weight: 600; color: #0fa47f;">Status: ON</div>
    </div>
  </div>

  <!-- Noise Level Card -->
  <div class="card" id="noiseCard" style="opacity: 0.6; pointer-events: none;">
    <h2>Noise level</h2>
    <div class="decibel">
      <div id="decibelBar"></div>
    </div>
    <span id="dBText" class="dB-label">Quiet</span>
  </div>

  <!-- All Zones Status Card -->
  <div class="card">
    <h2>All zones status</h2>
    <div id="liveNoiseTable">
      <table>
        <thead>
          <tr>
            <th>Location</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($zones as $zone):
          $noise = $zone['current_db'] ?? 0;
          $status = ($noise >= $THRESHOLD) ? 'Loud' : 'Quiet';
          $color = ($noise >= $THRESHOLD) ? 'red' : 'green';
        ?>
          <tr>
            <td><?= htmlspecialchars($zone['location']) ?></td>
            <td style="color:<?= $color ?>; font-weight:bold;"><?= $status ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
    </div>

    <script>
const servoSlider = document.getElementById("servoSlider");
const servoVal = document.getElementById("servoVal");
const statusEl = document.getElementById("status");
const cameraContainer = document.getElementById("cameraContainer");
const decibelBar = document.getElementById("decibelBar");
const dBText = document.getElementById("dBText");

let cameraTimer = null;
let loudStart = 0;
let alertShown = false;
let selectedZoneId = null;
let manualOpen = false;

// Zone selection
const zoneSelect = document.getElementById('zoneSelect');
const selectedZoneInfo = document.getElementById('selectedZoneInfo');
const currentZoneName = document.getElementById('currentZoneName');
const disabledMessage = document.getElementById('disabledMessage');
const controlsCard = document.getElementById('controlsCard');
const cameraCard = document.getElementById('cameraCard');
const noiseCard = document.getElementById('noiseCard');

// Function to enable/disable controls
function updateControlsState() {
    if (selectedZoneId) {
        // Enable controls
        disabledMessage.style.display = 'none';
        controlsCard.style.opacity = '1';
        controlsCard.style.pointerEvents = 'auto';
        cameraCard.style.opacity = '1';
        cameraCard.style.pointerEvents = 'auto';
        noiseCard.style.opacity = '1';
        noiseCard.style.pointerEvents = 'auto';
        
        // Enable buttons
        document.querySelectorAll('#cameraCard button, #controlsCard button, #servoSlider').forEach(el => {
            el.disabled = false;
        });
    } else {
        // Disable controls
        disabledMessage.style.display = 'block';
        controlsCard.style.opacity = '0.6';
        controlsCard.style.pointerEvents = 'none';
        cameraCard.style.opacity = '0.6';
        cameraCard.style.pointerEvents = 'none';
        noiseCard.style.opacity = '0.6';
        noiseCard.style.pointerEvents = 'none';
        
        // Disable buttons
        document.querySelectorAll('#cameraCard button, #controlsCard button, #servoSlider').forEach(el => {
            el.disabled = true;
        });
    }
}

zoneSelect.addEventListener('change', function() {
    selectedZoneId = this.value;
    if (selectedZoneId) {
        const selectedOption = this.options[this.selectedIndex];
        const zoneName = selectedOption.getAttribute('data-location');
        currentZoneName.innerText = zoneName;
        selectedZoneInfo.style.display = 'block';
    } else {
        selectedZoneInfo.style.display = 'none';
        loudStart = 0;
        alertShown = false;
    }
    updateControlsState();
});

// Initialize on page load
updateControlsState();

// Servo slider control
servoSlider.addEventListener("input", () => {
    const val = servoSlider.value;
    servoVal.innerText = val + "°";
    fetch(`command.php?servo=${val}`)
        .then(res => res.text())
        .then(data => statusEl.innerText = "Status: " + data)
        .catch(() => statusEl.innerText = "Status: Error");
});

// Show camera stream - manual open stays open, automatic closes after 10 seconds
function showCamera(isAutomatic = false){
    if(!isAutomatic) {
        manualOpen = true;
        // Tell ESP32 to enable manual camera via PHP proxy
        fetch("camera_control.php")
            .then(res => res.json())
            .catch(err => console.log('Manual camera enable error:', err));
    }
    
    cameraContainer.style.display = "flex";  // Make sure display is flex, not auto
    cameraContainer.innerHTML = "";
    const iframe = document.createElement("iframe");
    iframe.src = "http://192.168.1.132/stream";
    iframe.style.width = "100%";
    iframe.style.height = "100%";
    cameraContainer.appendChild(iframe);
    
    if(isAutomatic) {
        cameraTimer = setTimeout(closeCamera, 10000);  // 10 seconds
    }
}

function closeCamera(){
    cameraContainer.style.display = "none";
    cameraContainer.innerHTML = "";
    manualOpen = false;
    if(cameraTimer) clearTimeout(cameraTimer);
    
    // Tell ESP32 to disable manual camera via PHP proxy
    if(manualOpen === false) {
        fetch("camera_control.php")
            .then(res => res.json())
            .catch(err => console.log('Manual camera disable error:', err));
    }
}

// Buzzer control
function toggleBuzzer(state) {
    const onBtn = document.getElementById('buzzerOnBtn');
    const offBtn = document.getElementById('buzzerOffBtn');
    const buzzerStatus = document.getElementById('buzzerStatus');

    fetch(`buzzer_control.php?action=${state}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (state === 'on') {
                    onBtn.style.background = '#0fa47f';
                    onBtn.style.color = 'white';
                    offBtn.style.background = '#ccc';
                    offBtn.style.color = '#333';
                    buzzerStatus.innerText = 'Status: ON';
                } else {
                    offBtn.style.background = '#d9534f';
                    offBtn.style.color = 'white';
                    onBtn.style.background = '#ccc';
                    onBtn.style.color = '#333';
                    buzzerStatus.innerText = 'Status: OFF';
                }
            } else {
                alert('Failed to control buzzer: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => console.log('Buzzer control error:', err));
}

// Poll sound every 250ms
function updateSound(){
    // Don't poll sound if no zone is selected
    if (!selectedZoneId) {
        decibelBar.style.width = "0%";
        dBText.innerText = "Quiet";
        loudStart = 0;
        alertShown = false;
        return;
    }
    
    fetch("command.php?sound")
    .then(res => res.text())
    .then(val => {
        val = parseInt(val);
        const perc = Math.min(val / 20, 100);
        decibelBar.style.width = perc + "%";

        const now = Date.now();
        if(val > <?= $THRESHOLD ?>){ // loud threshold
            dBText.innerText = "LOUD (" + val + "dB)";
            if(loudStart === 0) {
                loudStart = now;
                console.log('🔊 LOUD NOISE STARTED! Value:', val, 'dB');
            }
            
            const durationSeconds = (now - loudStart) / 1000; // Convert to seconds
            console.log('Loud duration:', durationSeconds.toFixed(1), 'seconds, Noise:', val, 'dB');
            
            // Open camera at 8 seconds (gives it 2 seconds to warm up before 10-second alert)
            if(durationSeconds >= 8 && !manualOpen) {
                showCamera(true); // true = automatically triggered
            }
            
            // Update ONLY the selected zone with current noise level (if zone is selected)
            if(selectedZoneId) {
                fetch('update_zone_noise.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        zone_id: selectedZoneId,
                        noise_level: val,
                        duration_seconds: durationSeconds
                    })
                }).catch(err => console.log('Zone update error:', err));
            }
            
            if(durationSeconds >= 10){
                if(!alertShown){
                    console.log('🔔 ALERT TRIGGERED! Duration:', durationSeconds, 'Selected Zone ID:', selectedZoneId);
                    
                    // Get zone name for alerts (use "No Zone Selected" as fallback)
                    let zoneName = 'No Zone Selected';
                    if(selectedZoneId) {
                        const zoneNameEl = document.querySelector('option[value="' + selectedZoneId + '"]');
                        zoneName = zoneNameEl ? zoneNameEl.textContent : 'Unknown';
                    }
                    
                    console.log('Zone Name:', zoneName);
                    
                    // Show Sweet Alert popup (nice looking)
                    Swal.fire({
                        title: '⚠️ Loud Noise Detected!',
                        text: `Zone: ${zoneName}`,
                        icon: 'warning',
                        allowOutsideClick: false,
                        confirmButtonText: 'OK'
                    });
                    
                    console.log('Alert shown in browser');
                    
                    // Log to selected zone only (in background - don't wait)
                    if(selectedZoneId) {
                        fetch('log_zone_alert.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                zone_id: selectedZoneId,
                                noise_level: val
                            })
                        }).catch(err => console.log('Log alert error:', err));
                    }
                    
                    // Send SMS alert (background - don't wait)
                    fetch('send_sms_alert_new.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            message: 'Loud noise detected at ' + zoneName,
                            noise_level: val
                        })
                    }).catch(err => console.log('SMS error:', err));
                    
                    // Send Telegram alert with picture (background - don't wait)
                    fetch('send_telegram_alert.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            noise_level: val,
                            zone: zoneName
                        })
                    }).catch(err => console.log('Telegram error:', err));
                    
                    alertShown = true;
                }
            }
        } else {
            dBText.innerText = "Quiet";
            loudStart = 0;
            alertShown = false;
            
            // Update ONLY the selected zone to quiet when no noise detected
            if(selectedZoneId) {
                fetch('update_zone_noise.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        zone_id: selectedZoneId,
                        noise_level: 0,
                        duration_seconds: 0
                    })
                }).catch(err => console.log('Zone update error:', err));
            }
            
            // Close auto-opened camera when noise returns to normal (only if not manually opened)
            if(!manualOpen) closeCamera();
        }
    })
    .catch(() => console.log("Error reading sound"));
}

setInterval(updateSound, 250);

// Update zone noise table in real-time
function updateZoneTable() {
    // Skip updates if camera is manually open (to avoid blocking)
    if(manualOpen) {
        return;
    }
    
    fetch('get_zones_json.php')
        .then(res => res.json())
        .then(zones => {
            const tableDiv = document.getElementById('liveNoiseTable');
            if (!tableDiv) return;
            
            const tbody = tableDiv.querySelector('tbody');
            if (!tbody) return;
            
            // Clear existing rows
            tbody.innerHTML = '';
            
            // Add updated rows - highlight selected zone
            zones.forEach(zone => {
                const row = document.createElement('tr');
                const color = zone.color || (zone.status === 'Loud' ? 'red' : 'green');
                
                // Highlight the currently selected zone
                const isSelected = selectedZoneId && selectedZoneId == zone.id;
                const bgColor = isSelected ? 'rgba(0, 102, 204, 0.1)' : 'transparent';
                const fontWeight = isSelected ? 'bold' : 'normal';
                
                row.style.backgroundColor = bgColor;
                row.innerHTML = `
                    <td style="font-weight: ${fontWeight};">${zone.location} ${isSelected ? '▶' : ''}</td>
                    <td style="color: ${color}; font-weight: bold;">${zone.status}</td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(err => console.log('Zone table update error:', err));
}

// Refresh zone table every 500ms for real-time updates
setInterval(updateZoneTable, 500);

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
