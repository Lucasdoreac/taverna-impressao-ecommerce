<?php
/**
 * Sanitizer - Classe para sanitização de dados de entrada e saída
 * 
 * Esta classe fornece métodos estáticos para sanitizar diferentes tipos de dados,
 * ajudando a prevenir vulnerabilidades como XSS (Cross-Site Scripting).
 * 
 * @package     App\Lib\Security
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
class Sanitizer {
    
    /**
     * Sanitiza um texto para exibição segura em HTML
     * 
     * @param mixed $input Texto ou array a ser sanitizado
     * @param bool $stripTags Se deve remover todas as tags HTML (true) ou apenas escapar (false)
     * @return mixed Texto ou array sanitizado
     */
    public static function html($input, $stripTags = false) {
        if (is_array($input)) {
            return array_map(function($item) use ($stripTags) {
                return self::html($item, $stripTags);
            }, $input);
        }
        
        if ($stripTags) {
            // Remover todas as tags HTML
            $sanitized = strip_tags((string)$input);
        } else {
            // Converter caracteres especiais em entidades HTML
            $sanitized = htmlentities((string)$input, ENT_QUOTES, 'UTF-8');
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza um texto para uso em atributos HTML
     * 
     * @param string $input Texto a ser sanitizado
     * @return string Texto sanitizado
     */
    public static function htmlAttr($input) {
        return htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitiza um texto para uso em URLs
     * 
     * @param string $input Texto a ser sanitizado
     * @return string Texto sanitizado
     */
    public static function url($input) {
        return filter_var((string)$input, FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitiza um email
     * 
     * @param string $input Email a ser sanitizado
     * @return string Email sanitizado
     */
    public static function email($input) {
        return filter_var((string)$input, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitiza um valor inteiro
     * 
     * @param mixed $input Valor a ser sanitizado
     * @return int Valor inteiro sanitizado
     */
    public static function int($input) {
        return (int)$input;
    }
    
    /**
     * Sanitiza um valor float
     * 
     * @param mixed $input Valor a ser sanitizado
     * @return float Valor float sanitizado
     */
    public static function float($input) {
        // Garantir que o valor decimal usa ponto como separador
        $input = str_replace(',', '.', (string)$input);
        return (float)$input;
    }
    
    /**
     * Sanitiza um texto simples (remove tags HTML e caracteres especiais)
     * 
     * @param string $input Texto a ser sanitizado
     * @return string Texto sanitizado
     */
    public static function plainText($input) {
        return trim(strip_tags((string)$input));
    }
    
    /**
     * Sanitiza um nome de arquivo para uso seguro no sistema de arquivos
     * 
     * @param string $input Nome do arquivo a ser sanitizado
     * @return string Nome de arquivo sanitizado
     */
    public static function filename($input) {
        // Remover caracteres que podem ser problemáticos em nomes de arquivo
        $sanitized = preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string)$input);
        
        // Evitar nomes de arquivo que começam com ponto (ocultos no Unix)
        if (substr($sanitized, 0, 1) === '.') {
            $sanitized = '_' . $sanitized;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza um valor booleano
     * 
     * @param mixed $input Valor a ser convertido para booleano
     * @return bool Valor booleano
     */
    public static function boolean($input) {
        if (is_string($input)) {
            $input = strtolower($input);
            return in_array($input, ['true', '1', 'yes', 'y', 'on']);
        }
        
        return (bool)$input;
    }
    
    /**
     * Sanitiza uma string para uso em IDs ou classes CSS
     * 
     * @param string $input String a ser sanitizada
     * @return string String sanitizada
     */
    public static function cssIdentifier($input) {
        // Remover caracteres não permitidos em identificadores CSS
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$input);
    }
    
    /**
     * Aplica sanitização específica baseada no tipo de dado
     * 
     * @param mixed $input Valor a ser sanitizado
     * @param string $type Tipo de dado ('html', 'url', 'email', 'int', etc.)
     * @return mixed Valor sanitizado
     */
    public static function sanitize($input, $type = 'html') {
        switch (strtolower($type)) {
            case 'html':
                return self::html($input);
            case 'html_strip':
                return self::html($input, true);
            case 'html_attr':
                return self::htmlAttr($input);
            case 'url':
                return self::url($input);
            case 'email':
                return self::email($input);
            case 'int':
                return self::int($input);
            case 'float':
                return self::float($input);
            case 'plain_text':
                return self::plainText($input);
            case 'filename':
                return self::filename($input);
            case 'boolean':
                return self::boolean($input);
            case 'css_id':
            case 'css_class':
                return self::cssIdentifier($input);
            default:
                // Padrão é sanitizar como HTML
                return self::html($input);
        }
    }
}
