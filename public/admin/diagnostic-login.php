<?php
require_once __DIR__ . '/../../app/autoload.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in and has admin rights, redirect to diagnostic dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && Security::checkPermission('admin')) {
    header('Location: /admin/diagnostic-dashboard.php');
    exit;
}

$errors = [];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!Security::validateCSRFToken($csrf_token)) {
        $errors[] = 'Invalid security token. Please try again.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        $userModel = new UserModel();
        $result = $userModel->login($email, $password);

        if ($result['success']) {
            if (Security::checkPermission('admin')) {
                Security::logSecurityActivity('diagnostic_login', 'Successful admin login from IP: ' . $_SERVER['REMOTE_ADDR']);
                header('Location: /admin/diagnostic-dashboard.php');
                exit;
            } else {
                $errors[] = 'Access denied. Admin privileges required.';
                Security::logSecurityActivity('diagnostic_login_denied', 'Non-admin login attempt from IP: ' . $_SERVER['REMOTE_ADDR']);
            }
        } else {
            $errors[] = 'Invalid email or password';
            Security::logSecurityActivity('diagnostic_login_failed', 'Failed login attempt from IP: ' . $_SERVER['REMOTE_ADDR']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Diagnostic Login</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1>Admin Diagnostic Login</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</body>
</html>