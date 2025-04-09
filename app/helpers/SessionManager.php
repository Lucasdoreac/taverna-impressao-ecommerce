<?php

/**
 * Classe para gerenciamento seguro de sessões
 */
class SessionManager {
    /**
     * Inicializar a sessão se ainda não estiver ativa
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Regenerar o ID da sessão para prevenir ataques de fixação de sessão
     * 
     * @param bool $deleteOldSession Se deve deletar a sessão antiga após regenerar o ID
     */
    public static function regenerateSession($deleteOldSession = true) {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Definir um valor na sessão
     * 
     * @param string $key Chave a ser definida
     * @param mixed $value Valor a ser armazenado
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Obter um valor da sessão
     * 
     * @param string $key Chave a ser obtida
     * @param mixed $default Valor padrão se a chave não existir
     * @return mixed Valor armazenado ou valor padrão
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Destruir a sessão atual
     */
    public static function destroy() {
        $_SESSION = array();

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
    }

    /**
     * Limpar dados do usuário mantendo outras informações na sessão
     */
    public static function clearUserData() {
        $preserveKeys = ['flash_messages', 'csrf_token'];
        foreach ($_SESSION as $key => $value) {
            if (!in_array($key, $preserveKeys)) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Definir mensagem flash que será exibida apenas uma vez
     * 
     * @param string $type Tipo da mensagem (success, error, warning, info)
     * @param string $message Texto da mensagem
     */
    public static function setFlashMessage($type, $message) {
        $_SESSION['flash_messages'][$type][] = $message;
    }

    /**
     * Obter todas as mensagens flash e removê-las da sessão
     * 
     * @return array Mensagens flash
     */
    public static function getFlashMessages() {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    
    /**
     * Verificar se um usuário está autenticado
     * 
     * @return bool Verdadeiro se o usuário estiver autenticado
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }
    
    /**
     * Verificar se um usuário tem determinado papel
     * 
     * @param string $role Papel a ser verificado
     * @return bool Verdadeiro se o usuário tiver o papel especificado
     */
    public static function hasRole($role) {
        return self::isAuthenticated() && $_SESSION['user']['role'] === $role;
    }
}