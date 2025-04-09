<?php
/**
 * CsrfProtection - Classe para proteção contra ataques CSRF
 * 
 * Esta classe fornece métodos para gerar, validar e gerenciar tokens CSRF,
 * ajudando a proteger a aplicação contra ataques Cross-Site Request Forgery.
 * 
 * @package     App\Lib\Security
 * @version     1.1.0
 * @author      Taverna da Impressão
 */
class CsrfProtection {
    
    /**
     * Nome do token nos formulários e na sessão
     * 
     * @var string
     */
    private static $tokenName = 'csrf_token';
    
    /**
     * Tempo de vida do token em segundos (padrão: 2 horas)
     * 
     * @var int
     */
    private static $tokenLifetime = 7200;
    
    /**
     * Gera um novo token CSRF e o armazena na sessão
     * 
     * @return string Token CSRF gerado
     */
    public static function generateToken() {
        // Verificar se a sessão foi iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Gerar token aleatório com entropia adequada
        $token = bin2hex(random_bytes(32));
        
        // Armazenar token e timestamp na sessão
        $_SESSION[self::$tokenName] = [
            'token' => $token,
            'timestamp' => time()
        ];
        
        return $token;
    }
    
    /**
     * Obtém o token CSRF atual, gerando um novo se necessário
     * 
     * @param bool $forceNew Se deve forçar a geração de um novo token
     * @return string Token CSRF
     */
    public static function getToken($forceNew = false) {
        // Verificar se a sessão foi iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se o token existe, é válido e não expirou
        if ($forceNew || 
            !isset($_SESSION[self::$tokenName]) || 
            !isset($_SESSION[self::$tokenName]['token']) ||
            !isset($_SESSION[self::$tokenName]['timestamp']) ||
            (time() - $_SESSION[self::$tokenName]['timestamp']) > self::$tokenLifetime) {
            
            // Gerar um novo token
            return self::generateToken();
        }
        
        // Retornar token existente
        return $_SESSION[self::$tokenName]['token'];
    }
    
    /**
     * Valida o token CSRF fornecido com verificações rigorosas
     * Utiliza hash_equals para evitar ataques de timing
     * 
     * @param string $token Token a ser validado
     * @param bool $regenerateOnSuccess Se deve regenerar o token após validação bem-sucedida
     * @return bool Verdadeiro se o token for válido
     */
    public static function validateToken($token, $regenerateOnSuccess = true) {
        // Verificar se a sessão foi iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificação preliminar da existência de tokens na sessão
        if (!isset($_SESSION[self::$tokenName]) || 
            !isset($_SESSION[self::$tokenName]['token']) ||
            !isset($_SESSION[self::$tokenName]['timestamp'])) {
            return false;
        }
        
        // Verificar validade do token fornecido
        if (!is_string($token) || empty($token)) {
            return false;
        }
        
        // Validar formato (somente caracteres hexadecimais)
        if (!preg_match('/^[a-f0-9]+$/i', $token)) {
            return false;
        }
        
        // Verificar tamanho do token (deve ter 64 caracteres para tokens gerados com bin2hex(random_bytes(32)))
        if (strlen($token) !== 64) {
            return false;
        }
        
        // Verificar validade do token armazenado
        $storedToken = $_SESSION[self::$tokenName]['token'];
        if (!is_string($storedToken) || empty($storedToken)) {
            return false;
        }
        
        // Verificar se o token expirou
        if ((time() - $_SESSION[self::$tokenName]['timestamp']) > self::$tokenLifetime) {
            return false;
        }
        
        // Comparar tokens com função time-safe para evitar timing attacks
        $valid = hash_equals($storedToken, $token);
        
        // Regenerar token se validação for bem-sucedida e solicitado
        if ($valid && $regenerateOnSuccess) {
            self::generateToken();
        }
        
        return $valid;
    }
    
    /**
     * Valida o token CSRF da requisição atual (POST, GET, HEADER ou JSON)
     * 
     * @param bool $regenerateOnSuccess Se deve regenerar o token após validação bem-sucedida
     * @return bool Verdadeiro se o token for válido
     */
    public static function validateRequest($regenerateOnSuccess = true) {
        // Buscar token na requisição
        $token = self::getTokenFromRequest();
        
        if ($token === null) {
            return false;
        }
        
        return self::validateToken($token, $regenerateOnSuccess);
    }
    
    /**
     * Obtém o token CSRF da requisição atual (POST, GET, HEADER ou JSON)
     * com verificação de tipo e formato
     * 
     * @return string|null Token CSRF ou null se não encontrado ou inválido
     */
    public static function getTokenFromRequest() {
        $token = null;
        
        // Verificar em $_POST
        if (isset($_POST[self::$tokenName])) {
            $token = $_POST[self::$tokenName];
        }
        // Verificar em $_GET
        elseif (isset($_GET[self::$tokenName])) {
            $token = $_GET[self::$tokenName];
        }
        // Verificar no header
        else {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            if (isset($headers['X-CSRF-Token'])) {
                $token = $headers['X-CSRF-Token'];
            }
            // Verificar em JSON (para requisições AJAX)
            else {
                $jsonInput = file_get_contents('php://input');
                if (!empty($jsonInput)) {
                    $json = json_decode($jsonInput, true);
                    if (is_array($json) && isset($json[self::$tokenName])) {
                        $token = $json[self::$tokenName];
                    }
                }
            }
        }
        
        // Verificação básica de formato e tipo antes de retornar
        if (!is_string($token) || empty($token)) {
            return null;
        }
        
        return $token;
    }
    
    /**
     * Retorna um campo de formulário HTML com o token CSRF
     * 
     * @param bool $forceNew Se deve forçar a geração de um novo token
     * @return string HTML do campo input com o token CSRF
     */
    public static function getFormField($forceNew = false) {
        $token = self::getToken($forceNew);
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Retorna o token CSRF como um par nome-valor para uso em Ajax
     * 
     * @param bool $forceNew Se deve forçar a geração de um novo token
     * @return array Array associativo com o token CSRF
     */
    public static function getAjaxToken($forceNew = false) {
        $token = self::getToken($forceNew);
        return [self::$tokenName => $token];
    }
    
    /**
     * Define o tempo de vida do token CSRF em segundos
     * 
     * @param int $seconds Tempo de vida em segundos
     * @return void
     */
    public static function setTokenLifetime($seconds) {
        if (is_int($seconds) && $seconds > 0) {
            self::$tokenLifetime = $seconds;
        }
    }
    
    /**
     * Define o nome do token CSRF
     * 
     * @param string $name Nome do token
     * @return void
     */
    public static function setTokenName($name) {
        if (!empty($name)) {
            self::$tokenName = $name;
        }
    }
    
    /**
     * Adiciona headers de proteção CSRF à resposta
     * 
     * @return void
     */
    public static function addCsrfHeaders() {
        // Obter token atual
        $token = self::getToken();
        
        // Definir header com o token
        header('X-CSRF-Token: ' . $token);
        
        // Adicionar headers de segurança relacionados
        header('X-Content-Type-Options: nosniff');
    }
}