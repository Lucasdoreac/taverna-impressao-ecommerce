<?php
/**
 * RateLimiter - Sistema de limitação de taxa de requisições
 * 
 * @package App\Lib\Security
 * @category Security
 * @author Taverna da Impressão 3D Dev Team
 */

namespace App\Lib\Security;

class RateLimiter
{
    /**
     * @var \PDO
     */
    private $db;
    
    /**
     * @var array
     */
    private $config;
    
    /**
     * @var \Redis|null
     */
    private $redis;
    
    /**
     * Constructor
     * 
     * @param \PDO $db Conexão com o banco de dados
     * @param array $config Configurações do rate limiter
     * @param \Redis|null $redis Cliente Redis (opcional para armazenamento distribuído)
     */
    public function __construct(\PDO $db, array $config = [], ?\Redis $redis = null)
    {
        $this->db = $db;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->redis = $redis;
    }
    
    /**
     * Verifica se uma ação está dentro dos limites de taxa
     * 
     * @param string $key Chave da ação (ex: 'api_status_check', 'login_attempt')
     * @param int $windowSeconds Janela de tempo em segundos
     * @param int $maxAttempts Número máximo de tentativas na janela
     * @param string|null $identifier Identificador do cliente (IP, user ID, etc)
     * @return bool True se dentro do limite, False se excedido
     */
    public function check(string $key, int $windowSeconds, int $maxAttempts, ?string $identifier = null): bool
    {
        // Se identificador não for fornecido, usar IP do cliente
        if ($identifier === null) {
            $identifier = $this->getClientIp();
        }
        
        // Criar chave composta
        $compositeKey = "rate_limit:{$key}:{$identifier}";
        
        // Verificar configurações específicas para esta chave
        if (isset($this->config['limits'][$key])) {
            $windowSeconds = $this->config['limits'][$key]['window'] ?? $windowSeconds;
            $maxAttempts = $this->config['limits'][$key]['max_attempts'] ?? $maxAttempts;
        }
        
        // Se Redis estiver disponível, usar para armazenamento distribuído
        if ($this->redis !== null) {
            return $this->checkRedis($compositeKey, $windowSeconds, $maxAttempts);
        }
        
        // Caso contrário, usar banco de dados
        return $this->checkDatabase($compositeKey, $windowSeconds, $maxAttempts);
    }
    
    /**
     * Implementação de rate limiting usando Redis
     * 
     * @param string $key Chave composta
     * @param int $windowSeconds Janela de tempo
     * @param int $maxAttempts Tentativas máximas
     * @return bool True se dentro do limite
     */
    private function checkRedis(string $key, int $windowSeconds, int $maxAttempts): bool
    {
        $currentCount = $this->redis->incr($key);
        
        // Se é a primeira requisição, definir TTL
        if ($currentCount === 1) {
            $this->redis->expire($key, $windowSeconds);
        }
        
        // Verificar se excedeu o limite
        if ($currentCount > $maxAttempts) {
            // Registrar violação de limite
            $this->logLimitViolation($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Implementação de rate limiting usando banco de dados
     * 
     * @param string $key Chave composta
     * @param int $windowSeconds Janela de tempo
     * @param int $maxAttempts Tentativas máximas
     * @return bool True se dentro do limite
     */
    private function checkDatabase(string $key, int $windowSeconds, int $maxAttempts): bool
    {
        // Limpar entradas expiradas
        $this->cleanupExpiredEntries();
        
        // Verificar contagem atual
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM rate_limit_entries 
            WHERE 
                rate_key = ? AND 
                created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $stmt->execute([$key, $windowSeconds]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $currentCount = $result['count'] ?? 0;
        
        // Verificar se excedeu o limite
        if ($currentCount >= $maxAttempts) {
            // Registrar violação de limite
            $this->logLimitViolation($key);
            return false;
        }
        
        // Registrar nova entrada
        $this->insertEntry($key);
        return true;
    }
    
    /**
     * Insere nova entrada de rate limit no banco
     * 
     * @param string $key Chave do rate limit
     * @return void
     */
    private function insertEntry(string $key): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO rate_limit_entries (rate_key, created_at, ip_address)
            VALUES (?, NOW(), ?)
        ");
        
        $stmt->execute([$key, $this->getClientIp()]);
    }
    
    /**
     * Limpa entradas expiradas da tabela
     * 
     * @return void
     */
    private function cleanupExpiredEntries(): void
    {
        // Remove entradas mais antigas que o limite máximo configurado
        $maxWindowSeconds = $this->config['max_window_seconds'];
        
        $stmt = $this->db->prepare("
            DELETE FROM rate_limit_entries 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $stmt->execute([$maxWindowSeconds]);
    }
    
    /**
     * Registra violação de limite para análise de segurança
     * 
     * @param string $key Chave violada
     * @return void
     */
    private function logLimitViolation(string $key): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO rate_limit_violations (
                rate_key, 
                ip_address, 
                user_agent, 
                occurred_at
            ) VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $key,
            $this->getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Registrar para monitoramento de segurança
        if ($this->config['log_violations']) {
            error_log("RATE LIMIT VIOLATION: {$key} from IP {$this->getClientIp()}");
        }
    }
    
    /**
     * Obtém o IP do cliente de forma segura
     * 
     * @return string Endereço IP
     */
    private function getClientIp(): string
    {
        // Verificar proxy confiável se configurado
        if (!empty($this->config['trusted_proxies'])) {
            $headers = $this->config['ip_headers'];
            
            foreach ($headers as $header) {
                if (!empty($_SERVER[$header])) {
                    $ips = explode(',', $_SERVER[$header]);
                    $ip = trim($ips[0]);
                    
                    // Validar formato de IP
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        
        // Fallback para REMOTE_ADDR (mais confiável)
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Retorna as configurações padrão do rate limiter
     * 
     * @return array Configurações
     */
    private function getDefaultConfig(): array
    {
        return [
            'max_window_seconds' => 86400, // 24 horas para cleanup
            'log_violations' => true,
            'trusted_proxies' => [],
            'ip_headers' => [
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP',
                'HTTP_X_REAL_IP'
            ],
            'limits' => [
                'api_status_check' => [
                    'window' => 60,
                    'max_attempts' => 30
                ],
                'login_attempt' => [
                    'window' => 300,
                    'max_attempts' => 5
                ],
                'password_reset' => [
                    'window' => 3600,
                    'max_attempts' => 3
                ]
            ]
        ];
    }
}
