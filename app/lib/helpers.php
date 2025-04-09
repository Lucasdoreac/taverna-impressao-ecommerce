<?php
/**
 * Funções auxiliares - Taverna da Impressão 3D
 * 
 * Este arquivo contém funções auxiliares que podem ser usadas
 * em todo o sistema.
 */

/**
 * Redireciona para uma URL específica
 * 
 * @param string $url URL para redirecionamento
 * @return void
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * URL base da aplicação
 * 
 * @param string $path Caminho relativo opcional
 * @return string URL completa
 */
function base_url($path = '') {
    return config('BASE_URL', '/') . ltrim($path, '/');
}

/**
 * Sanitiza uma string para saída em HTML
 * 
 * @param string $value Valor a ser sanitizado
 * @return string Valor sanitizado
 */
function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica se o usuário está autenticado
 * 
 * @return bool True se o usuário estiver autenticado, false caso contrário
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Gera um token CSRF
 * 
 * @return string Token CSRF
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Renderiza um campo de token CSRF para formulários
 * 
 * @return string Campo de input com o token CSRF
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verifica se uma requisição tem um token CSRF válido
 * 
 * @return bool True se o token for válido, false caso contrário
 */
function csrf_verify() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Flash messages
 * 
 * @param string $type Tipo da mensagem (success, error, warning, info)
 * @param string $message Conteúdo da mensagem
 * @return void
 */
function flash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obter e limpar as mensagens flash
 * 
 * @return array Mensagens flash
 */
function get_flash_messages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    
    return $messages;
}

/**
 * Renderizar mensagens flash
 * 
 * @return string HTML com as mensagens flash
 */
function render_flash_messages() {
    $messages = get_flash_messages();
    
    if (empty($messages)) {
        return '';
    }
    
    $html = '<div class="flash-messages">';
    
    foreach ($messages as $message) {
        $type = h($message['type']);
        $content = h($message['message']);
        
        $html .= "<div class=\"flash-message flash-{$type}\">{$content}</div>";
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Formatar preço
 * 
 * @param float $price Preço a ser formatado
 * @return string Preço formatado
 */
function format_price($price) {
    return 'R$ ' . number_format($price, 2, ',', '.');
}

/**
 * Formatar data
 * 
 * @param string $date Data no formato Y-m-d ou timestamp
 * @param string $format Formato de saída (padrão d/m/Y)
 * @return string Data formatada
 */
function format_date($date, $format = 'd/m/Y') {
    if (is_numeric($date)) {
        return date($format, $date);
    }
    
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Limitar comprimento de texto
 * 
 * @param string $text Texto original
 * @param int $length Comprimento máximo
 * @param string $append Texto a ser anexado quando limitado
 * @return string Texto limitado
 */
function str_limit($text, $length = 100, $append = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $append;
}

/**
 * Gerar slug a partir de um texto
 * 
 * @param string $text Texto original
 * @return string Slug
 */
function slugify($text) {
    // Remover acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    
    // Converter para minúsculas
    $text = strtolower($text);
    
    // Remover caracteres especiais
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Substituir espaços por hífens
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Remover hífens no início e no fim
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Log de erros personalizado
 * 
 * @param string $message Mensagem de erro
 * @param string $level Nível de log (error, warning, info)
 * @return bool True se o log foi salvo, false caso contrário
 */
function log_error($message, $level = 'error') {
    if (!config('ERROR_LOG')) {
        return false;
    }
    
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] [$level] $message" . PHP_EOL;
    
    return error_log($logMessage, 3, config('ERROR_LOG'));
}

/**
 * Verificar se uma string é uma URL válida
 * 
 * @param string $url URL para verificar
 * @return bool True se for uma URL válida, false caso contrário
 */
function is_valid_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Verificar se uma string é um email válido
 * 
 * @param string $email Email para verificar
 * @return bool True se for um email válido, false caso contrário
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
