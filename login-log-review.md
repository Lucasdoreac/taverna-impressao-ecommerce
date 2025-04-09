# Login/Logout Logging Implementation Review

## Current Implementation Analysis

### 1. Redundant Logging
The current implementation logs the logout event twice:

1. In AuthController.php:
```php
$this->loginLog->logLogout($userId, $userEmail, $_SERVER['REMOTE_ADDR']);
```

2. In UserModel.php:
```php
$securityLog = new SecurityLog();
$securityLog->log('logout', ...);
```

This creates duplicate entries for each logout event.

### 2. Implementation Flow
The current flow is:
1. AuthController captures user data
2. AuthController logs logout via LoginLogModel
3. AuthController calls UserModel.logout()
4. UserModel logs again via SecurityLog
5. UserModel performs session cleanup

### 3. Security Assessment
* Pros:
  - User data captured before session destruction
  - IP address logged for audit
  - Proper session cleanup
  - Remember token properly handled

* Cons:
  - Duplicate log entries
  - Potential inconsistency between logs
  - Unnecessary database load

## Recommendations

### 1. Consolidate Logging
Remove the duplicate logging by keeping only one implementation:

```php
// In AuthController.php
public function logout() {
    // Get user data before clearing session
    $userId = $_SESSION['user']['id'] ?? null;
    $userEmail = $_SESSION['user']['email'] ?? 'unknown';
    
    // Log the logout event
    $this->loginLog->logLogout($userId, $userEmail, $_SERVER['REMOTE_ADDR']);
    
    // Handle remember token
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        Database::getInstance()->query($sql, ['token' => $token]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Perform session cleanup
    $this->userModel->logout();
    
    header('Location: /');
    exit();
}
```

And in UserModel.php, remove the SecurityLog call:
```php
public function logout() {
    // Just handle session cleanup
    SessionManager::clearUserData();
    SessionManager::regenerateSession();
    SessionManager::destroy();
}
```

### 2. Standardize Log Format
Use LoginLogModel exclusively for authentication-related logging:
- Consistent data format
- Single source of truth
- Easier to maintain and audit

### 3. Error Handling
Add error handling for logging failures:
```php
public function logLogout($userId, $email, $ipAddress) {
    try {
        $data = [
            'user_id' => $userId,
            'email' => $email,
            'action' => 'logout',
            'status' => 'success',
            'ip_address' => $ipAddress,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert($this->table, $data);
    } catch (Exception $e) {
        // Log error but don't prevent logout
        error_log("Failed to log logout event: " . $e->getMessage());
        return false;
    }
}
```

## Migration Steps

1. Update AuthController.php and UserModel.php as shown above
2. Test logout flow thoroughly
3. Monitor logs for duplicate entries
4. Verify all required data is still captured
5. Check admin dashboard for proper display

## Additional Recommendations

1. Consider adding transaction logging around remember token cleanup
2. Add log rotation policy for login_logs table
3. Implement log archiving for older records
4. Add monitoring for failed logout attempts