<?php
/**
 * Security - Classe para funções de segurança
 */
class Security {
    /**
     * Sanitiza entrada de texto
     * @param string|array $input Texto ou array a ser sanitizado
     * @return string|array Texto ou array sanitizado
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitizeInput($value);
            }
            return $input;
        }
        
        // Remover tags HTML, exceto as permitidas
        $input = strip_tags($input, '<p><br><strong><em><ul><ol><li>');
        
        // Converter caracteres especiais em entidades HTML
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Verifica token CSRF
     * @return bool Token válido ou não
     */
    public static function validateCSRFToken() {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
    
    /**
     * Gera token CSRF
     * @return string Token gerado
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verifica permissão de usuário
     * @param string $requiredRole Papel requerido
     * @return bool Usuário tem permissão ou não
     */
    public static function checkPermission($requiredRole = 'admin') {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role'])) {
            return false;
        }
        
        return $_SESSION['user']['role'] === $requiredRole;
    }
    
    /**
     * Filtra entrada SQL para prevenir injeção
     * @param string $value Valor a ser filtrado
     * @return string Valor filtrado
     */
    public static function filterSQLInput($value) {
        // Remover comentários de estilo SQL
        $value = preg_replace('/\/\*.*?\*\//', '', $value);
        
        // Remover quebras de linha, tabs e retornos que podem ser usados para injeção
        $value = preg_replace('/\s+/', ' ', $value);
        
        // Remover strings que podem ser usadas em ataques de SQL injection
        $patterns = [
            '/\bUNION\b/i', 
            '/\bSELECT\b/i', 
            '/\bINSERT\b/i', 
            '/\bUPDATE\b/i', 
            '/\bDELETE\b/i', 
            '/\bDROP\b/i', 
            '/\bALTER\b/i',
            '/\bEXEC\b/i',
            '/--/'
        ];
        
        return preg_replace($patterns, '', $value);
    }
    
    /**
     * Registra atividade de segurança
     * @param string $action Ação realizada
     * @param string $details Detalhes adicionais
     * @param string $level Nível do log (info, warning, error)
     */
    public static function logSecurityActivity($action, $details = '', $level = 'info') {
        $logPath = ROOT_PATH . '/logs';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        $logFile = $logPath . '/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 'guest';
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
        
        $logMessage = sprintf(
            "[%s] [%s] [User: %s] [IP: %s] %s - %s\n",
            $timestamp,
            strtoupper($level),
            $userId,
            $ip,
            $action,
            $details
        );
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Detecta tentativas de força bruta
     * @param string $username Nome de usuário sendo usado
     * @return bool True se for detectado força bruta
     */
    public static function detectBruteForce($username) {
        $failedLoginsFile = ROOT_PATH . '/logs/failed_logins.json';
        $maxAttempts = 5; // Máximo de tentativas permitidas
        $timeWindow = 15 * 60; // Janela de tempo (15 minutos em segundos)
        
        // Inicializar ou carregar dados de logins falhos
        if (file_exists($failedLoginsFile)) {
            $failedLogins = json_decode(file_get_contents($failedLoginsFile), true);
        } else {
            $failedLogins = [];
        }
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $currentTime = time();
        $key = $username . '_' . $ip;
        
        // Limpar entradas antigas
        foreach ($failedLogins as $k => $data) {
            if ($currentTime - $data['time'] > $timeWindow) {
                unset($failedLogins[$k]);
            }
        }
        
        // Verificar tentativas existentes
        if (isset($failedLogins[$key])) {
            $attempts = $failedLogins[$key]['attempts'];
            $lastTime = $failedLogins[$key]['time'];
            
            // Verificar se está dentro da janela de tempo
            if ($currentTime - $lastTime <= $timeWindow) {
                // Incrementar tentativas
                $failedLogins[$key] = [
                    'attempts' => $attempts + 1,
                    'time' => $currentTime
                ];
                
                // Verificar se excedeu o limite
                if ($attempts + 1 >= $maxAttempts) {
                    // Registrar atividade suspeita
                    self::logSecurityActivity(
                        'Brute Force Detection',
                        "Multiple failed login attempts for user {$username} from IP {$ip}",
                        'warning'
                    );
                    
                    // Salvar dados atualizados
                    file_put_contents($failedLoginsFile, json_encode($failedLogins));
                    return true;
                }
            } else {
                // Reset se fora da janela de tempo
                $failedLogins[$key] = [
                    'attempts' => 1,
                    'time' => $currentTime
                ];
            }
        } else {
            // Primeiro login falho
            $failedLogins[$key] = [
                'attempts' => 1,
                'time' => $currentTime
            ];
        }
        
        // Salvar dados atualizados
        file_put_contents($failedLoginsFile, json_encode($failedLogins));
        return false;
    }
    
    /**
     * Reseta contador de tentativas de login
     * @param string $username Nome de usuário
     */
    public static function resetLoginAttempts($username) {
        $failedLoginsFile = ROOT_PATH . '/logs/failed_logins.json';
        
        if (file_exists($failedLoginsFile)) {
            $failedLogins = json_decode(file_get_contents($failedLoginsFile), true);
            $ip = $_SERVER['REMOTE_ADDR'];
            $key = $username . '_' . $ip;
            
            if (isset($failedLogins[$key])) {
                unset($failedLogins[$key]);
                file_put_contents($failedLoginsFile, json_encode($failedLogins));
            }
        }
    }
}