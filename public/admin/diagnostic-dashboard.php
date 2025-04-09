<?php
require_once __DIR__ . '/../../app/autoload.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: /admin/diagnostic-login.php');
    exit;
}

// Verify admin permissions
if (!Security::checkPermission('admin')) {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied. Admin privileges required.";
    Security::logSecurityActivity('diagnostic_access_denied', 'Unauthorized access attempt from IP: ' . $_SERVER['REMOTE_ADDR']);
    exit;
}

// Log successful access
Security::logSecurityActivity('diagnostic_dashboard_access', 'Admin diagnostic dashboard accessed by user ID: ' . $_SESSION['user']['id']);

try {
    $diagnosticData = require __DIR__ . '/../diagnostic.php';
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Diagnostic Dashboard</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="dashboard">
    <nav class="admin-nav">
        <div class="nav-brand">System Diagnostics</div>
        <div class="nav-user">
            Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
            <a href="/auth/logout" class="btn btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>System Diagnostic Dashboard</h1>
            <div class="actions">
                <a href="?format=json" class="btn btn-secondary">View as JSON</a>
                <button onclick="window.location.reload()" class="btn btn-primary">Refresh Data</button>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <h3>Error:</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php else: ?>
            <div class="diagnostic-sections">
                <?php foreach ($diagnosticData as $section => $data): ?>
                    <div class="diagnostic-section">
                        <h2><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $section))); ?></h2>
                        <div class="section-content">
                            <?php if (is_array($data)): ?>
                                <table class="data-table">
                                    <tbody>
                                    <?php foreach ($data as $key => $value): ?>
                                        <tr>
                                            <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></th>
                                            <td>
                                                <?php if (is_array($value)): ?>
                                                    <pre><?php echo htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)); ?></pre>
                                                <?php elseif (is_bool($value)): ?>
                                                    <span class="badge badge-<?php echo $value ? 'success' : 'danger'; ?>">
                                                        <?php echo $value ? 'Yes' : 'No'; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($value); ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p><?php echo htmlspecialchars($data); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh the page every 5 minutes
        setTimeout(() => window.location.reload(), 300000);
    </script>
</body>
</html>