<?php
/**
 * SMS Alert Log Viewer
 * See all SMS sending attempts and their results
 */

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Admin access required');
}

$log_file = __DIR__ . '/sms_alerts.log';
$logs = [];

if (file_exists($log_file)) {
    $lines = array_reverse(file($log_file));
    foreach ($lines as $line) {
        $logs[] = trim($line);
    }
} else {
    $logs = ['No logs found yet'];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>SMS Alert Logs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #569cd6; margin-bottom: 20px; text-align: center; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #0e639c; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #1177bb; }
        .btn-danger { background: #d13438; }
        .btn-danger:hover { background: #f14c56; }
        .log-container { background: #252526; padding: 20px; border-radius: 8px; border: 1px solid #3e3e42; }
        .log-entry { padding: 10px; margin: 5px 0; border-left: 3px solid #999; border-radius: 2px; font-size: 12px; line-height: 1.4; }
        .log-entry.attempt { border-left-color: #0066cc; background: #1a2332; }
        .log-entry.success { border-left-color: #4ec9b0; background: #1a2a1f; }
        .log-entry.error { border-left-color: #f48771; background: #2a1a1a; }
        .log-entry.info { border-left-color: #ce9178; background: #2a1f1a; }
        .time { color: #858585; }
        .status { font-weight: bold; }
        .status.sent { color: #4ec9b0; }
        .status.failed { color: #f48771; }
        .status.pending { color: #ce9178; }
        .count { background: #3e3e42; padding: 10px 15px; border-radius: 4px; }
        .empty { text-align: center; color: #858585; padding: 40px; }
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 10px; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📋 SMS Alert Logs</h1>
        <div>
            <button class="btn" onclick="location.reload()">🔄 Refresh</button>
            <?php if (file_exists($log_file)): ?>
                <button class="btn btn-danger" onclick="if(confirm('Clear all logs?')) { fetch('clear_sms_logs.php', {method: 'POST'}).then(() => location.reload()); }">🗑️ Clear</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="log-container">
        <?php if (count($logs) === 0 || (count($logs) === 1 && $logs[0] === 'No logs found yet')): ?>
            <div class="empty">
                <p>No SMS logs yet. When you send an SMS, it will appear here.</p>
            </div>
        <?php else: ?>
            <div class="count">Total entries: <strong><?php echo count($logs); ?></strong></div>
            <?php foreach ($logs as $log): ?>
                <?php 
                    $class = 'info';
                    $status_text = '';
                    
                    if (strpos($log, 'HTTP Code:') !== false) {
                        if (strpos($log, 'HTTP Code: 20') !== false) {
                            $class = 'success';
                            $status_text = ' ✓ SUCCESS';
                        } elseif (strpos($log, 'HTTP Code: 0') !== false || strpos($log, 'Error:') !== false && strpos($log, 'Error: ') !== false) {
                            $class = 'error';
                            $status_text = ' ✗ ERROR';
                        } else {
                            $class = 'error';
                            $status_text = ' ✗ FAILED';
                        }
                    } elseif (strpos($log, 'Sending to:') !== false) {
                        $class = 'attempt';
                        $status_text = ' → SENDING';
                    }
                ?>
                <div class="log-entry <?php echo $class; ?>">
                    <span class="time"><?php echo htmlspecialchars(substr($log, 0, 19)); ?></span>
                    <?php if ($status_text): ?><span class="status"><?php echo $status_text; ?></span><?php endif; ?>
                    <br>
                    <span><?php echo htmlspecialchars($log); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <a href="admindashboard.php" class="btn">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>
