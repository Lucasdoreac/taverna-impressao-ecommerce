<?php

class SecurityHeaders {
    public static function setSecureHeaders() {
        $config = require_once(ROOT_PATH . '/app/config/security.php');
        $headers = $config['headers'];

        foreach ($headers as $header => $value) {
            header("$header: $value");
        }

        // Ensure cookies are secure
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $config['session']['lifetime'],
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => $config['session']['secure_cookie'],
            'httponly' => $config['session']['http_only'],
            'samesite' => 'Strict'
        ]);

        // Enable session security features
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        
        if ($config['session']['regenerate_id']) {
            if (!isset($_SESSION['last_regeneration'])) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
}