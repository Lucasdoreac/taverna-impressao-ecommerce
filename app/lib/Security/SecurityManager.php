<?php
/**
 * SecurityManager
 * 
 * Classe responsável por gerenciar a segurança da aplicação,
 * incluindo autenticação, autorização, proteção CSRF e validação de entrada.
 */
class SecurityManager {
    /**
     * Verifica se o usuário está autenticado
     * 
     * @return bool True se o usuário estiver autenticado, false caso contrário
     */
    public static function checkAuthentication() {
        // Verificar sessão ativa
        if (!isset($_SESSION)) {
            session_start();
        }
        
        // Verificar se o usuário está logado (ID na sessão)
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            // Verificar tempo de expiração da sessão (opcional)
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
                // Sessão expirada (1 hora)
                self::logout();
                return false;
            }
            
            // Atualizar tempo da última atividade
            $_SESSION['last_activity'] = time();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Função legada para compatibilidade com código existente
     * 
     * @return bool True se o usuário estiver autenticado, false caso contrário
     */
    public static function isUserLoggedIn() {
        return self::checkAuthentication();
    }
    
    /**
     * Gera um token CSRF
     * 
     * @return string Token CSRF
     * @deprecated Use CsrfProtection::getToken() em vez disso
     */
    public static function generateCsrfToken() {
        // Delegando para a classe CsrfProtection
        require_once dirname(__FILE__) . '/CsrfProtection.php';
        return CsrfProtection::getToken();
    }
    
    /**
     * Valida um token CSRF
     * 
     * @param string $token Token CSRF a ser validado
     * @return bool True se o token for válido, false caso contrário
     * @deprecated Use CsrfProtection::validateToken() em vez disso
     */
    public static function validateCsrfToken($token) {
        // Delegando para a classe CsrfProtection
        require_once dirname(__FILE__) . '/CsrfProtection.php';
        return CsrfProtection::validateToken($token);
    }
    
    /**
     * Sanitiza uma string para saída segura em HTML
     * 
     * @param string $value Valor a ser sanitizado
     * @return string Valor sanitizado
     */
    public static function sanitize($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Efetua logout do usuário
     */
    public static function logout() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        // Limpar variáveis de sessão
        $_SESSION = array();
        
        // Destruir o cookie da sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir a sessão
        session_destroy();
    }
    
    /**
     * Processa upload de arquivo com segurança
     * 
     * @param array $fileData Dados do arquivo ($_FILES['campo'])
     * @param string $destinationDir Diretório de destino
     * @param array $options Opções de configuração
     * @return array Resultado do upload
     */
    public static function processFileUpload($fileData, $destinationDir, array $options = []) {
        // Verificar se o upload foi bem-sucedido
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo: ' . self::getUploadErrorMessage($fileData['error']));
        }

        // Configurar opções
        $allowedExtensions = $options['allowedExtensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'stl', 'obj', 'gcode'];
        $maxSize = $options['maxSize'] ?? 5 * 1024 * 1024; // 5MB padrão
        $validateContent = $options['validateContent'] ?? true;
        
        // Verificar tamanho
        if ($fileData['size'] > $maxSize) {
            throw new Exception('Arquivo muito grande. Tamanho máximo: ' . self::formatFileSize($maxSize));
        }
        
        // Verificar extensão
        $fileName = basename($fileData['name']);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Tipo de arquivo não permitido. Extensões aceitas: ' . implode(', ', $allowedExtensions));
        }
        
        // Sanitizar nome de arquivo
        $safeFileName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileName);
        $safeFileName = md5(uniqid() . time()) . '_' . $safeFileName;
        $destination = rtrim($destinationDir, '/') . '/' . $safeFileName;
        
        // Verificar tipo de conteúdo real se solicitado
        if ($validateContent) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $fileType = $finfo->file($fileData['tmp_name']);
            
            $allowedMimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                'zip' => 'application/zip',
                'stl' => 'application/octet-stream',
                'obj' => 'application/octet-stream',
                'gcode' => 'text/plain'
            ];
            
            if (isset($allowedMimeTypes[$extension]) && $fileType !== $allowedMimeTypes[$extension]) {
                throw new Exception('O conteúdo do arquivo não corresponde à extensão declarada.');
            }
        }
        
        // Mover arquivo
        if (!move_uploaded_file($fileData['tmp_name'], $destination)) {
            throw new Exception('Falha ao mover o arquivo carregado.');
        }
        
        return [
            'success' => true,
            'path' => $destination,
            'name' => $safeFileName,
            'original_name' => $fileName,
            'size' => $fileData['size'],
            'extension' => $extension
        ];
    }
    
    /**
     * Formata o tamanho de arquivo para exibição
     * 
     * @param int $size Tamanho em bytes
     * @return string Tamanho formatado
     */
    private static function formatFileSize($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Retorna mensagem de erro de upload
     * 
     * @param int $errorCode Código de erro do upload
     * @return string Mensagem de erro
     */
    private static function getUploadErrorMessage($errorCode) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo PHP.',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido pelo formulário.',
            UPLOAD_ERR_PARTIAL => 'O arquivo foi apenas parcialmente carregado.',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi carregado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever o arquivo em disco.',
            UPLOAD_ERR_EXTENSION => 'Uma extensão PHP interrompeu o upload.'
        ];
        
        return isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'Erro desconhecido no upload.';
    }
}