<?php
session_start();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/sms_config.php';
require_once __DIR__ . '/telegram_config.php';

// Check if user is logged in (admin or regular user)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'user'])) {
    header("Location: index.php");
    exit();
}

$is_admin = $_SESSION['role'] === 'admin';
$user_phone = $_SESSION['user_phone'] ?? null;

// Handle SMS form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sms'])) {
    $phone = $_POST['phone_number'] ?? '';
    $api_key = $_POST['api_key'] ?? '';
    $sender_id = $_POST['sender_id'] ?? '';
    $sms_enabled = isset($_POST['sms_enabled']) ? 1 : 0;
    
    try {
        if (updateSMSSettings($phone, $api_key, $sender_id, $sms_enabled)) {
            $sms_success_msg = "SMS settings updated successfully!";
        } else {
            $sms_error_msg = "Error updating SMS settings!";
        }
    } catch (Exception $e) {
        $sms_error_msg = "Error: " . $e->getMessage();
    }
}

// Handle Telegram form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_telegram'])) {
    $bot_token = $_POST['bot_token'] ?? '';
    $chat_id = $_POST['chat_id'] ?? '';
    $telegram_enabled = isset($_POST['telegram_enabled']) ? 1 : 0;
    
    try {
        if (updateTelegramSettings($bot_token, $chat_id, $telegram_enabled)) {
            $telegram_success_msg = "Telegram settings updated successfully!";
        } else {
            $telegram_error_msg = "Error updating Telegram settings!";
        }
    } catch (Exception $e) {
        $telegram_error_msg = "Error: " . $e->getMessage();
    }
}

// Get current settings
$sms_settings = getSMSSettings();
$telegram_settings = getTelegramSettings();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Settings</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .settings-container {
            max-width: 900px;
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
        
        .settings-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .settings-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title span {
            font-size: 24px;
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
        
        .form-group-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .form-group-checkbox input {
            width: auto;
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
    <?php if ($is_admin): ?>
        <a href="admindashboard.php" class="sidebar-link">Dashboard</a>
        <a href="zones.php" class="sidebar-link">Zones</a>
        <a href="logs.php" class="sidebar-link">Accounts</a>
        <a href="alert_settings.php" class="sidebar-link active">Alert Settings</a>
    <?php else: ?>
        <a href="userdashboard.php" class="sidebar-link">Dashboard</a>
        <a href="zones.php" class="sidebar-link">Zones</a>
        <a href="logs.php" class="sidebar-link">Logs</a>
        <a href="alert_settings.php" class="sidebar-link active">SMS Alert Settings</a>
    <?php endif; ?>
    <div class="sidebar-bottom">
        <span class="sidebar-user">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="#" onclick="return confirmLogout();" class="sidebar-link logout">Logout</a>
    </div>
</div>

<header>
    <div class="brand-wrapper">
        <div class="brand">
            <div class="brand-text">
                <div class="brand-name">Alert Settings</div>
                <div class="brand-sub">Configure SMS and Telegram notifications</div>
            </div>
        </div>
    </div>
</header>

<div class="settings-container">
    <div class="settings-card">
        <div class="card-header">
            <h2>🔔 Alert Settings</h2>
            <p>Configure where to send noise alert notifications</p>
        </div>
        
        <div class="card-body">
            <?php if (isset($sms_success_msg)): ?>
                <div class="alert alert-success">
                    <span>✓</span>
                    <span><?= $sms_success_msg ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($sms_error_msg)): ?>
                <div class="alert alert-error">
                    <span>✗</span>
                    <span><?= $sms_error_msg ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($telegram_success_msg)): ?>
                <div class="alert alert-success">
                    <span>✓</span>
                    <span><?= $telegram_success_msg ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($telegram_error_msg)): ?>
                <div class="alert alert-error">
                    <span>✗</span>
                    <span><?= $telegram_error_msg ?></span>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <strong>ℹ️ How it works:</strong><br>
                When noise is detected for 10+ seconds, alerts will be sent to your configured channels (SMS, Telegram, or both).
            </div>

            <!-- SMS Form -->
            <form method="POST">
                <!-- SMS Section -->
                <div class="settings-section">
                    <div class="section-title">
                        <span>📱</span> SMS Alerts
                    </div>

                    <?php if (!$is_admin): ?>
                        <div class="info-box">
                            <strong>ℹ️ SMS Recipient:</strong><br>
                            Your SMS alerts will be sent to your registered phone number: <strong><?= htmlspecialchars($user_phone ?? 'Not set') ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="form-group-checkbox">
                            <input type="checkbox" id="sms_enabled" name="sms_enabled" 
                                   <?= $sms_settings['enabled'] ?? 0 ? 'checked' : '' ?>>
                            <label for="sms_enabled" style="margin: 0; font-weight: 600;">Enable SMS notifications</label>
                        </div>

                        <div class="form-group">
                            <label for="phone_number">
                                Default Recipient Phone Number <span class="required-mark">*</span>
                            </label>
                            <input type="text" id="phone_number" name="phone_number" 
                                   value="<?= htmlspecialchars($sms_settings['phone_number']) ?>" 
                                   placeholder="09123456789">
                            <div class="help-text">
                                Format: 09XXXXXXXXX (10 digits) or 639XXXXXXXXX (12 digits)<br>
                                <em>Note: Individual users' SMS alerts will be sent to their registered phone numbers.</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="api_key">
                                iProGSMS API Token <span class="required-mark">*</span>
                            </label>
                            <input type="text" id="api_key" name="api_key" 
                                   value="<?= htmlspecialchars($sms_settings['api_key']) ?>" 
                                   placeholder="Paste your API Token here">
                            <div class="help-text">
                                Get from iProGSMS account dashboard<br>
                                Endpoint: https://sms.iprogtech.com/api/v1/sms_messages
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sender_id">Sender ID (Optional)</label>
                            <input type="text" id="sender_id" name="sender_id" 
                                   value="<?= htmlspecialchars($sms_settings['sender_id']) ?>" 
                                   placeholder="Leave empty or use default">
                            <div class="help-text">Custom sender name for SMS</div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_admin): ?>
                <div class="btn-group">
                    <button type="submit" name="submit_sms" class="btn btn-save">💾 Save SMS Settings</button>
                </div>
                <?php endif; ?>
            </form>

            <!-- Telegram Form (Admin Only) -->
            <?php if ($is_admin): ?>
            <form method="POST">
                <!-- Telegram Section -->
                <div class="settings-section">
                    <div class="section-title">
                        <span>📸</span> Telegram Alerts (with Camera Picture)
                    </div>

                    <div class="form-group-checkbox">
                        <input type="checkbox" id="telegram_enabled" name="telegram_enabled" 
                               <?= $telegram_settings['enabled'] ?? 0 ? 'checked' : '' ?>>
                        <label for="telegram_enabled" style="margin: 0; font-weight: 600;">Enable Telegram notifications</label>
                    </div>

                    <div class="form-group">
                        <label for="bot_token">
                            Telegram Bot Token <span class="required-mark">*</span>
                        </label>
                        <input type="text" id="bot_token" name="bot_token" 
                               value="<?= htmlspecialchars($telegram_settings['bot_token']) ?>" 
                               placeholder="Paste your bot token here">
                        <div class="help-text">
                            Get from @BotFather on Telegram<br>
                            Format: 123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="chat_id">
                            Chat ID <span class="required-mark">*</span>
                        </label>
                        <input type="text" id="chat_id" name="chat_id" 
                               value="<?= htmlspecialchars($telegram_settings['chat_id']) ?>" 
                               placeholder="Your Telegram chat ID">
                        <div class="help-text">
                            Find it: Send /start to @userinfobot
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="submit_telegram" class="btn btn-save">💾 Save Telegram Settings</button>
                    <a href="admindashboard.php" class="btn btn-back">← Back</a>
                </div>
            </form>
            <?php else: ?>
            <div class="btn-group">
                <a href="userdashboard.php" class="btn btn-back">← Back to Dashboard</a>
            </div>
            <?php endif; ?>
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
