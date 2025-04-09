<?php
/**
 * ApiResponse - Utilitário para respostas de API padronizadas
 * 
 * @package App\Lib\Http
 * @category Security
 * @author Taverna da Impressão 3D Dev Team
 */

namespace App\Lib\Http;

use App\Lib\Security\SecurityHeaders;

class ApiResponse
{
    /**
     * Envia resposta de sucesso
     * 
     * @param mixed $data Dados da resposta
     * @param int $statusCode Código HTTP da resposta
     * @return void
     */
    public static function success($data, int $statusCode = 200): void
    {
        self::sendResponse([
            'success' => true,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Envia resposta de erro
     * 
     * @param string $message Mensagem de erro
     * @param int $statusCode Código HTTP da resposta
     * @param string|null $errorCode Código de erro interno (opcional)
     * @return void
     */
    public static function error(string $message, int $statusCode = 400, ?string $errorCode = null): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }
        
        self::sendResponse($response, $statusCode);
    }
    
    /**
     * Envia uma resposta JSON com cabeçalhos de segurança
     * 
     * @param array $data Dados da resposta
     * @param int $statusCode Código HTTP da resposta
     * @return void
     */
    private static function sendResponse(array $data, int $statusCode): void
    {
        // Definir cabeçalhos de segurança
        SecurityHeaders::apply();
        
        // Definir cabeçalhos padrão de resposta API
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        
        // Anti-CSRF - Não permitir embedding em outros sites
        header('X-Frame-Options: DENY');
        
        // Prevenir MIME-sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Prevenir vazamento de informações em referrer
        header('Referrer-Policy: no-referrer-when-downgrade');
        
        // Limpar buffer de saída para evitar conflitos
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Adicionar nonce para evitar cache de respostas sensíveis
        $data['_nonce'] = bin2hex(random_bytes(8));
        
        // Enviar resposta JSON sanitizada
        echo self::sanitizeJsonOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
        exit;
    }
    
    /**
     * Sanitiza saída JSON para evitar vulnerabilidades JSON injection
     * 
     * @param string $json String JSON
     * @return string JSON sanitizado
     */
    private static function sanitizeJsonOutput(string $json): string
    {
        // Prevenir ataques JSON Hijacking
        // Veja: https://haacked.com/archive/2009/06/25/json-hijacking.aspx/
        return ")]}',\n" . $json;
    }
}
