<?php
// Redirect to the new combined Alert Settings page
header("Location: alert_settings.php");
exit;
?>


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone_number'] ?? '';
    $api_key = $_POST['api_key'] ?? ''; // Now getting this from form
    $sender_id = $_POST['sender_id'] ?? ''; // Now getting this from form
    $enabled = 1; 
    
    if (updateSMSSettings($phone, $api_key, $sender_id, $enabled)) {
        $success_msg = "Settings updated successfully!";
    } else {
        $error_msg = "Error updating settings!";
    }
}

// Get current SMS settings (load after update to show new values)
$settings = getSMSSettings();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Settings</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .settings-container {
            max-width: 700px;
            margin: 100px auto 30px;
            margin-left: calc(220px + 50px);
            padding: 0 20px;
        }
        
        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #0fa47f 0%, #0d8b6f 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .card-header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #0fa47f;
            box-shadow: 0 0 0 3px rgba(15, 164, 127, 0.1);
        }
        
        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
            line-height: 1.5;
        }
        
        .info-box {
            background: #f0f9f7;
            border-left: 4px solid #0fa47f;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #333;
        }
        
        .info-box strong {
            color: #0fa47f;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 35px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #0fa47f 0%, #0d8b6f 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(15, 164, 127, 0.3);
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(15, 164, 127, 0.4);
        }
        
        .btn-back {
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .required-mark {
            color: #e74c3c;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .settings-container {
                margin: 80px 0 20px;
                margin-left: 0;
                padding: 0 15px;
            }
            
            .card-body {
                padding: 25px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .card-header {
                padding: 25px 20px;
            }
            
            .card-header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body class="admin-page">

<button class="menu-toggle" id="menuToggle" onclick="toggleMenu()">
  <span></span><span></span><span></span>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><span>Noise Monitoring</span></div>
    <a href="admindashboard.php" class="sidebar-link">Dashboard</a>
    <a href="zones.php" class="sidebar-link">Zones</a>
    <a href="logs.php" class="sidebar-link">Logs</a>
    <a href="sms_settings.php" class="sidebar-link active">SMS Settings</a>
    <div class="sidebar-bottom">
        <span class="sidebar-user">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="#" onclick="return confirmLogout();" class="sidebar-link logout">Logout</a>
    </div>
</div>

<header>
    <div class="brand-wrapper">
        <div class="brand">
            <div class="brand-text">
                <div class="brand-name">SMS Settings</div>
                <div class="brand-sub">Configure SMS notifications</div>
            </div>
        </div>
    </div>
</header>

<div class="settings-container">
    <div class="settings-card">
        <div class="card-header">
            <h2>📱 SMS Settings</h2>
            <p>Configure where to send noise alert notifications</p>
        </div>
        
        <div class="card-body">
            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success">
                    <span>✓</span>
                    <span><?= $success_msg ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-error">
                    <span>✗</span>
                    <span><?= $error_msg ?></span>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <strong>ℹ️ How it works:</strong><br>
                When noise is detected for 10+ seconds, an SMS will be sent to the phone number you configure below.
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="phone_number">
                        Recipient Phone Number <span class="required-mark">*</span>
                    </label>
                    <input type="text" id="phone_number" name="phone_number" 
                           value="<?= htmlspecialchars($settings['phone_number']) ?>" 
                           placeholder="09123456789" required>
                    <div class="help-text">
                        Where SMS alerts will be sent<br>
                        Format: 09XXXXXXXXX (10 digits) or 639XXXXXXXXX (12 digits)
                    </div>
                </div>

                <div class="form-group">
                    <label for="api_key">
                        iProGSMS API Token <span class="required-mark">*</span>
                    </label>
                    <input type="text" id="api_key" name="api_key" 
                           value="<?= htmlspecialchars($settings['api_key']) ?>" 
                           placeholder="Paste your API Token here" required>
                    <div class="help-text">
                        Get this from your iProGSMS account dashboard<br>
                        Endpoint: https://sms.iprogtech.com/api/v1/sms_messages
                    </div>
                </div>

                <div class="form-group">
                    <label for="sender_id">Sender ID (Optional)</label>
                    <input type="text" id="sender_id" name="sender_id" 
                           value="<?= htmlspecialchars($settings['sender_id']) ?>" 
                           placeholder="Leave empty or use default">
                    <div class="help-text">Custom sender name for SMS (if your account supports it)</div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-save">💾 Save Settings</button>
                    <a href="admindashboard.php" class="btn btn-back">← Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleMenu() { document.getElementById('sidebar').classList.toggle('active'); }

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

document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', () => { if(window.innerWidth<=768) document.getElementById('sidebar').classList.remove('active'); });
});
document.addEventListener('click', e => {
    const sb = document.getElementById('sidebar');
    const mt = document.getElementById('menuToggle');
    if(window.innerWidth<=768 && !sb.contains(e.target) && !mt.contains(e.target)) sb.classList.remove('active');
});
</script>
</body>
</html>
