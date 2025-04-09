# Login/Logout Logic Review

## Current Implementation Review

### Logout Implementation
Currently, the logout functionality is implemented across multiple components:

1. **AuthController.php (lines 107-115)**
- Handles remember token cleanup from cookies and database
- Does not include explicit logging of the logout event

2. **UserModel.php (lines 160-166)**
- Manages session cleanup
- Regenerates session ID for security
- No explicit logout logging

3. **Security.php**
- Has capability for security activity logging (logSecurityActivity method)
- Currently not utilized during logout operations

## Recommendations

1. **Add Logout Event Logging**
The system should log logout events for security and audit purposes. Implementation should:
- Track successful logouts
- Record timestamp, user ID, and IP address
- Use existing Security::logSecurityActivity method

2. **Enhanced Session Cleanup**
Consider additional security measures:
- Clear all session data, not just user-related
- Invalidate any existing session tokens
- Clear all related cookies

3. **Code Implementation Example**
```php
// In AuthController::logout()
public function logout() {
    // Get user info before session clear for logging
    $userId = $_SESSION['user_id'] ?? 'unknown';
    $userEmail = $_SESSION['user']['email'] ?? 'unknown';
    
    // Existing remember token cleanup
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        Database::getInstance()->query($sql, ['token' => $token]);
    }
    
    // Log the logout event
    Security::logSecurityActivity(
        'user_logout',
        "User ID: $userId, Email: $userEmail",
        'info'
    );
    
    // Perform logout in UserModel
    $this->userModel->logout();
}
```

## Security Impact
Adding proper logout logging would:
- Improve audit trails
- Help detect suspicious patterns
- Aid in security investigations
- Support compliance requirements

## Implementation Priority
Adding logout logging should be considered a medium-priority security enhancement to implement in the near term.