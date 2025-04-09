<?php
require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/helpers/Security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Verify admin permissions using Security helper
if (!Security::checkPermission('admin')) {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied. Admin privileges required.";
    Security::logSecurityActivity('diagnostic_access_denied', 'Unauthorized access attempt from IP: ' . $_SERVER['REMOTE_ADDR']);
    exit;
}

// Set content type based on request format
$format = isset($_GET['format']) ? $_GET['format'] : 'text';
if ($format === 'json') {
    header('Content-Type: application/json');
} else {
    header('Content-Type: text/plain');
}

// Log successful access
Security::logSecurityActivity('diagnostic_access', 'Admin diagnostic page accessed by user ID: ' . $_SESSION['user']['id']);

// Enable error reporting for diagnostic purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $diagnosticData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => getenv('APP_ENV') ?: 'production',
        'user' => [
            'id' => $_SESSION['user']['id'],
            'role' => $_SESSION['user']['role']
        ]
    ];

    // System Information
    $diagnosticData['system'] = [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_input_vars' => ini_get('max_input_vars'),
        'display_errors' => ini_get('display_errors')
    ];

    // Database Check
    $db = new Database();
    $connection = $db->getConnection();
    $diagnosticData['database'] = [
        'status' => 'Connected',
        'version' => $connection->getAttribute(PDO::ATTR_SERVER_VERSION),
        'client_info' => $connection->getAttribute(PDO::ATTR_CLIENT_VERSION)
    ];

    // Directory Permissions
    $criticalDirs = [
        '../app/config',
        '../app/logs',
        'uploads',
        '../database',
        '../cache'
    ];
    
    $diagnosticData['directories'] = [];
    foreach ($criticalDirs as $dir) {
        $fullPath = realpath(__DIR__ . '/' . $dir);
        if ($fullPath && is_dir($fullPath)) {
            $diagnosticData['directories'][$dir] = [
                'exists' => true,
                'writable' => is_writable($fullPath),
                'permissions' => substr(sprintf('%o', fileperms($fullPath)), -4),
                'free_space' => disk_free_space($fullPath)
            ];
        } else {
            $diagnosticData['directories'][$dir] = [
                'exists' => false,
                'error' => 'Directory not found'
            ];
        }
    }

    // Session Information
    $diagnosticData['session'] = [
        'id' => session_id(),
        'save_path' => session_save_path(),
        'gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
        'cookie_lifetime' => ini_get('session.cookie_lifetime')
    ];

    // Performance Metrics
    $diagnosticData['performance'] = [
        'peak_memory' => memory_get_peak_usage(true),
        'current_memory' => memory_get_usage(true),
        'loaded_extensions' => get_loaded_extensions()
    ];

    // Output based on format
    if ($format === 'json') {
        echo json_encode($diagnosticData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        // Text format output
        foreach ($diagnosticData as $section => $data) {
            echo "\n=== " . strtoupper($section) . " ===\n";
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        echo "$key:\n";
                        foreach ($value as $subkey => $subvalue) {
                            if (is_bool($subvalue)) {
                                $subvalue = $subvalue ? 'Yes' : 'No';
                            }
                            echo "  $subkey: $subvalue\n";
                        }
                    } else {
                        if (is_bool($value)) {
                            $value = $value ? 'Yes' : 'No';
                        }
                        echo "$key: $value\n";
                    }
                }
            } else {
                echo "$data\n";
            }
        }
    }

} catch (Exception $e) {
    $error = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];

    Security::logSecurityActivity('diagnostic_error', $e->getMessage(), 'error');

    if ($format === 'json') {
        echo json_encode($error, JSON_PRETTY_PRINT);
    } else {
        echo "Critical Error:\n";
        echo "Message: {$error['message']}\n";
        echo "Code: {$error['code']}\n";
        echo "File: {$error['file']} (line {$error['line']})\n";
        echo "\nStack trace:\n{$error['trace']}\n";
    }
}