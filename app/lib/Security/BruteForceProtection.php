<?php
/**
 * BruteForceProtection - Classe para proteção contra ataques de força bruta
 * 
 * Esta classe implementa proteção contra ataques de força bruta limitando
 * o número de tentativas de login ou outras ações sensíveis por períodos de tempo.
 * 
 * @package     App\Lib\Security
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
class BruteForceProtection {
    
    /**
     * Número máximo de tentativas antes do bloqueio
     * 
     * @var int
     */
    private static $maxAttempts = 5;
    
    /**
     * Período de bloqueio em segundos (15 minutos)
     * 
     * @var int
     */
    private static $lockoutPeriod = 900;
    
    /**
     * Tempo de vida de tentativas em segundos (1 hora)
     * 
     * @var int
     */
    private static $attemptLifetime = 3600;
    
    /**
     * Nome da tabela para armazenar os dados
     * 
     * @var string
     */
    private static $tableName = 'login_attempts';
    
    /**
     * Instância do Banco de Dados
     * 
     * @var \Database
     */
    private static $db = null;
    
    /**
     * Registra uma tentativa de autenticação
     * 
     * @param string $identifier Identificador único (usuário, IP, etc.)
     * @param string $action Ação sendo protegida (login, redefinição de senha, etc.)
     * @param bool $success Se a tentativa foi bem-sucedida
     * @return void
     */
    public static function registerAttempt($identifier, $action = 'login', $success = false) {
        // Inicializar banco de dados
        self::initDb();
        
        if ($success) {
            // Se bem-sucedido, limpar tentativas anteriores
            self::clearAttempts($identifier, $action);
        } else {
            // Se falhou, registrar tentativa
            $now = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            
            // Inserir tentativa
            $stmt = self::$db->prepare(
                "INSERT INTO " . self::$tableName . " 
                (identifier, action, ip_address, user_agent, attempt_time, is_locked) 
                VALUES (?, ?, ?, ?, ?, 0)"
            );
            
            $stmt->execute([$identifier, $action, $ip, $userAgent, $now]);
            
            // Verificar se deve bloquear
            self::checkLockout($identifier, $action);
        }
    }
    
    /**
     * Verifica se o identificador está bloqueado para a ação
     * 
     * @param string $identifier Identificador único
     * @param string $action Ação sendo protegida
     * @return bool Verdadeiro se estiver bloqueado
     */
    public static function isBlocked($identifier, $action = 'login') {
        // Inicializar banco de dados
        self::initDb();
        
        // Limpar tentativas expiradas
        self::clearExpiredAttempts();
        
        // Verificar se está bloqueado
        $now = date('Y-m-d H:i:s');
        $blockExpiry = date('Y-m-d H:i:s', strtotime("-" . self::$lockoutPeriod . " seconds"));
        
        $stmt = self::$db->prepare(
            "SELECT COUNT(*) FROM " . self::$tableName . " 
            WHERE identifier = ? AND action = ? AND is_locked = 1 
            AND attempt_time > ?"
        );
        
        $stmt->execute([$identifier, $action, $blockExpiry]);
        $count = $stmt->fetchColumn();
        
        return $count > 0;
    }
    
    /**
     * Obtém o número de tentativas recentes para o identificador
     * 
     * @param string $identifier Identificador único
     * @param string $action Ação sendo protegida
     * @return int Número de tentativas recentes
     */
    public static function getAttemptCount($identifier, $action = 'login') {
        // Inicializar banco de dados
        self::initDb();
        
        // Limitar ao período de tentativas
        $cutoff = date('Y-m-d H:i:s', strtotime("-" . self::$attemptLifetime . " seconds"));
        
        $stmt = self::$db->prepare(
            "SELECT COUNT(*) FROM " . self::$tableName . " 
            WHERE identifier = ? AND action = ? AND attempt_time > ?"
        );
        
        $stmt->execute([$identifier, $action, $cutoff]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Limpa todas as tentativas para o identificador e ação
     * 
     * @param string $identifier Identificador único
     * @param string $action Ação sendo protegida
     * @return void
     */
    public static function clearAttempts($identifier, $action = 'login') {
        // Inicializar banco de dados
        self::initDb();
        
        $stmt = self::$db->prepare(
            "DELETE FROM " . self::$tableName . " 
            WHERE identifier = ? AND action = ?"
        );
        
        $stmt->execute([$identifier, $action]);
    }
    
    /**
     * Define o número máximo de tentativas
     * 
     * @param int $maxAttempts Número máximo de tentativas
     * @return void
     */
    public static function setMaxAttempts($maxAttempts) {
        if (is_int($maxAttempts) && $maxAttempts > 0) {
            self::$maxAttempts = $maxAttempts;
        }
    }
    
    /**
     * Define o período de bloqueio em segundos
     * 
     * @param int $seconds Período de bloqueio em segundos
     * @return void
     */
    public static function setLockoutPeriod($seconds) {
        if (is_int($seconds) && $seconds > 0) {
            self::$lockoutPeriod = $seconds;
        }
    }
    
    /**
     * Define o tempo de vida das tentativas em segundos
     * 
     * @param int $seconds Tempo de vida em segundos
     * @return void
     */
    public static function setAttemptLifetime($seconds) {
        if (is_int($seconds) && $seconds > 0) {
            self::$attemptLifetime = $seconds;
        }
    }
    
    /**
     * Retorna o tempo restante de bloqueio em segundos
     * 
     * @param string $identifier Identificador único
     * @param string $action Ação sendo protegida
     * @return int Tempo restante em segundos ou 0 se não estiver bloqueado
     */
    public static function getRemainingLockoutTime($identifier, $action = 'login') {
        // Se não estiver bloqueado, retorna 0
        if (!self::isBlocked($identifier, $action)) {
            return 0;
        }
        
        // Inicializar banco de dados
        self::initDb();
        
        // Obter tempo da última tentativa com bloqueio
        $stmt = self::$db->prepare(
            "SELECT MAX(attempt_time) FROM " . self::$tableName . " 
            WHERE identifier = ? AND action = ? AND is_locked = 1"
        );
        
        $stmt->execute([$identifier, $action]);
        $lastAttempt = $stmt->fetchColumn();
        
        if (!$lastAttempt) {
            return 0;
        }
        
        // Calcular tempo restante
        $lockExpiry = strtotime($lastAttempt) + self::$lockoutPeriod;
        $now = time();
        
        return max(0, $lockExpiry - $now);
    }
    
    /**
     * Retorna tempo restante de bloqueio em formato humanizado
     * 
     * @param string $identifier Identificador único
     * @param string $action Ação sendo protegida
     * @return string Tempo restante em formato humanizado
     */
    public static function getRemainingLockoutTimeFormatted($identifier, $action = 'login') {
        $seconds = self::getRemainingLockoutTime($identifier, $action);
        
        if ($seconds <= 0) {
            return 'Não bloqueado';
        }
        
        // Formatar tempo
        $minutes = floor($seconds / 60);
        $seconds %= 60;
        
        if ($minutes > 0) {
            return sprintf('%d minuto(s) e %d segundo(s)', $minutes, $seconds);
        } else {
            return sprintf('%d segundo(s)', $seconds);
        }
    }
    
    /**
     * Verifica e aplica o bloqueio se necessário
     * 
     * @param string $identifier Identificador único
     * @param string $action Ação sendo protegida
     * @return bool Verdadeiro se o bloqueio foi aplicado
     */
    private static function checkLockout($identifier, $action) {
        // Obter número de tentativas recentes
        $count = self::getAttemptCount($identifier, $action);
        
        // Se atingiu o máximo de tentativas, aplicar bloqueio
        if ($count >= self::$maxAttempts) {
            // Atualizar para bloquear
            $stmt = self::$db->prepare(
                "UPDATE " . self::$tableName . " 
                SET is_locked = 1 
                WHERE identifier = ? AND action = ? 
                ORDER BY attempt_time DESC LIMIT 1"
            );
            
            $stmt->execute([$identifier, $action]);
            
            // Registrar bloqueio
            self::logBlockEvent($identifier, $action);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Limpa tentativas expiradas
     * 
     * @return void
     */
    private static function clearExpiredAttempts() {
        // Calcular cutoff
        $cutoff = date('Y-m-d H:i:s', strtotime("-" . self::$attemptLifetime . " seconds"));
        
        // Excluir tentativas antigas
        $stmt = self::$db->prepare(
            "DELETE FROM " . self::$tableName . " 
            WHERE attempt_time < ?"
        );
        
        $stmt->execute([$cutoff]);
    }
    
    /**
     * Registra evento de bloqueio
     * 
     * @param string $identifier Identificador único
     * @param string $action Ação sendo protegida
     * @return void
     */
    private static function logBlockEvent($identifier, $action) {
        // Registrar em log de sistema
        $message = sprintf(
            'Ação "%s" bloqueada para "%s" após %d tentativas malsucedidas.',
            $action,
            $identifier,
            self::$maxAttempts
        );
        
        error_log($message);
        
        // Se existir função de log da aplicação, usá-la
        if (function_exists('app_log')) {
            app_log($message, 'security');
        }
    }
    
    /**
     * Inicializa a conexão com o banco de dados
     * 
     * @return void
     */
    private static function initDb() {
        if (self::$db === null) {
            // Usar a classe Database da aplicação
            require_once APP_PATH . '/core/Database.php';
            self::$db = new Database();
            
            // Garantir que a tabela existe
            self::ensureTableExists();
        }
    }
    
    /**
     * Garante que a tabela de tentativas existe
     * 
     * @return void
     */
    private static function ensureTableExists() {
        // Verificar se a tabela já existe
        try {
            $result = self::$db->query("SHOW TABLES LIKE '" . self::$tableName . "'");
            if ($result->rowCount() === 0) {
                // Criar tabela
                self::$db->exec(
                    "CREATE TABLE " . self::$tableName . " (
                        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        identifier VARCHAR(255) NOT NULL,
                        action VARCHAR(50) NOT NULL,
                        ip_address VARCHAR(45) NOT NULL,
                        user_agent TEXT,
                        attempt_time DATETIME NOT NULL,
                        is_locked TINYINT(1) NOT NULL DEFAULT 0,
                        INDEX (identifier, action),
                        INDEX (attempt_time)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
                );
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log('Erro ao criar tabela de proteção contra força bruta: ' . $e->getMessage());
        }
    }
}
