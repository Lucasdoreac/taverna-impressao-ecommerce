<?php
/**
 * FileUploadManager - Classe para gerenciamento seguro de uploads de arquivos
 * 
 * Esta classe fornece métodos para validar, processar e armazenar 
 * uploads de arquivos de forma segura, prevenindo vulnerabilidades comuns.
 * 
 * @package     App\Lib\Security
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
class FileUploadManager {
    
    /**
     * Diretório base para uploads de arquivos
     * 
     * @var string
     */
    private static $baseUploadDir = 'uploads';
    
    /**
     * Tamanho máximo padrão para arquivos (5MB)
     * 
     * @var int
     */
    private static $defaultMaxSize = 5242880;
    
    /**
     * Lista padrão de extensões permitidas
     * 
     * @var array
     */
    private static $defaultAllowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    
    /**
     * Lista padrão de tipos MIME permitidos
     * 
     * @var array
     */
    private static $defaultAllowedMimeTypes = [
        'image/jpeg', 
        'image/png', 
        'image/gif', 
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];
    
    /**
     * Valida um upload de arquivo
     * 
     * @param array $file Informação do arquivo ($_FILES['campo'])
     * @param array $options Opções de validação (maxSize, allowedExtensions, allowedMimeTypes)
     * @return array Status de validação ['success' => bool, 'message' => string]
     */
    public static function validate($file, array $options = []) {
        // Verificar se o arquivo foi enviado
        if (!isset($file) || !is_array($file) || !isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Nenhum arquivo enviado ou falha no upload'];
        }
        
        // Verificar erros de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => self::getUploadErrorMessage($file['error'])];
        }
        
        // Obter opções de validação
        $maxSize = isset($options['maxSize']) ? $options['maxSize'] : self::$defaultMaxSize;
        $allowedExtensions = isset($options['allowedExtensions']) ? $options['allowedExtensions'] : self::$defaultAllowedExtensions;
        $allowedMimeTypes = isset($options['allowedMimeTypes']) ? $options['allowedMimeTypes'] : self::$defaultAllowedMimeTypes;
        
        // Validação de tamanho
        if ($file['size'] > $maxSize) {
            return [
                'success' => false, 
                'message' => 'O arquivo excede o tamanho máximo permitido de ' . self::formatSize($maxSize)
            ];
        }
        
        // Validação de extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, array_map('strtolower', $allowedExtensions))) {
            return [
                'success' => false, 
                'message' => 'Extensão de arquivo não permitida. Extensões permitidas: ' . implode(', ', $allowedExtensions)
            ];
        }
        
        // Validação de tipo MIME
        $fileMimeType = mime_content_type($file['tmp_name']);
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            return [
                'success' => false, 
                'message' => 'Tipo de arquivo não permitido'
            ];
        }
        
        // Verificações adicionais para tipos específicos
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && !self::isValidImage($file['tmp_name'])) {
            return [
                'success' => false, 
                'message' => 'O arquivo não é uma imagem válida'
            ];
        }
        
        return ['success' => true, 'message' => 'Arquivo válido'];
    }
    
    /**
     * Processa o upload de um arquivo
     * 
     * @param array $file Informação do arquivo ($_FILES['campo'])
     * @param string $destinationDir Diretório de destino dentro do diretório de uploads
     * @param array $options Opções de validação e processamento
     * @return array Resultado do processamento ['success' => bool, 'message' => string, 'file' => array]
     */
    public static function processUpload($file, $destinationDir = '', array $options = []) {
        // Validar arquivo
        $validation = self::validate($file, $options);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Configurar o diretório de destino
        $uploadDir = self::getUploadPath($destinationDir);
        
        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return [
                    'success' => false, 
                    'message' => 'Erro ao criar diretório de destino'
                ];
            }
        }
        
        // Gerar nome de arquivo seguro
        $filename = self::generateSecureFilename($file['name'], isset($options['preserveFileName']) ? $options['preserveFileName'] : false);
        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        
        // Mover o arquivo para o destino
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return [
                'success' => false, 
                'message' => 'Erro ao mover arquivo para destino'
            ];
        }
        
        // Configurar permissões
        chmod($fullPath, 0644);
        
        // Processar imagem se necessário
        if (isset($options['processImage']) && $options['processImage'] && self::isImage($file['name'])) {
            $processed = self::processImage($fullPath, $options);
            if (!$processed['success']) {
                // Remover arquivo se processamento falhar
                unlink($fullPath);
                return $processed;
            }
        }
        
        // Retornar informações do arquivo processado
        return [
            'success' => true, 
            'message' => 'Arquivo enviado com sucesso',
            'file' => [
                'name' => $filename,
                'original_name' => $file['name'],
                'path' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $fullPath),
                'full_path' => $fullPath,
                'size' => $file['size'],
                'type' => $file['type'],
                'extension' => pathinfo($file['name'], PATHINFO_EXTENSION)
            ]
        ];
    }
    
    /**
     * Deleta um arquivo do sistema de arquivos
     * 
     * @param string $filePath Caminho relativo do arquivo dentro do diretório de uploads
     * @return bool Verdadeiro se o arquivo foi excluído
     */
    public static function deleteFile($filePath) {
        // Obter caminho completo
        $fullPath = self::getUploadPath() . DIRECTORY_SEPARATOR . $filePath;
        
        // Validar se o arquivo existe e está dentro do diretório de uploads
        if (!file_exists($fullPath) || !is_file($fullPath) || !self::isWithinUploadDir($fullPath)) {
            return false;
        }
        
        // Excluir arquivo
        return unlink($fullPath);
    }
    
    /**
     * Gera um nome de arquivo seguro para armazenamento
     * 
     * @param string $originalName Nome original do arquivo
     * @param bool $preserveFileName Se deve preservar o nome original (apenas sanitizado)
     * @return string Nome de arquivo seguro
     */
    public static function generateSecureFilename($originalName, $preserveFileName = false) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        if ($preserveFileName) {
            // Sanitizar nome de arquivo original
            $basename = pathinfo($originalName, PATHINFO_FILENAME);
            $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
            $basename = trim($basename, '_');
            
            // Garantir que o nome não está vazio
            if (empty($basename)) {
                $basename = 'file';
            }
            
            // Adicionar timestamp para evitar colisões
            $basename .= '_' . time();
        } else {
            // Gerar nome aleatório
            $basename = bin2hex(random_bytes(16));
        }
        
        return $basename . '.' . $extension;
    }
    
    /**
     * Configura o diretório base para uploads
     * 
     * @param string $dir Caminho do diretório de uploads
     * @return void
     */
    public static function setBaseUploadDir($dir) {
        if (!empty($dir)) {
            self::$baseUploadDir = rtrim($dir, '/\\');
        }
    }
    
    /**
     * Obtém o caminho completo do diretório de uploads
     * 
     * @param string $subdir Subdiretório opcional
     * @return string Caminho completo
     */
    public static function getUploadPath($subdir = '') {
        $baseDir = defined('APP_PATH') ? APP_PATH . DIRECTORY_SEPARATOR . self::$baseUploadDir : $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . self::$baseUploadDir;
        
        if (!empty($subdir)) {
            return $baseDir . DIRECTORY_SEPARATOR . trim($subdir, '/\\');
        }
        
        return $baseDir;
    }
    
    /**
     * Verifica se um arquivo está dentro do diretório de uploads
     * 
     * @param string $filePath Caminho do arquivo
     * @return bool Verdadeiro se o arquivo está dentro do diretório de uploads
     */
    public static function isWithinUploadDir($filePath) {
        $uploadDir = self::getUploadPath();
        $realUploadDir = realpath($uploadDir);
        $realFilePath = realpath($filePath);
        
        if ($realFilePath === false || $realUploadDir === false) {
            return false;
        }
        
        return strpos($realFilePath, $realUploadDir) === 0;
    }
    
    /**
     * Verifica se um arquivo é uma imagem
     * 
     * @param string $fileName Nome do arquivo
     * @return bool Verdadeiro se o arquivo é uma imagem
     */
    public static function isImage($fileName) {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }
    
    /**
     * Verifica se um arquivo é uma imagem válida
     * 
     * @param string $filePath Caminho do arquivo
     * @return bool Verdadeiro se o arquivo é uma imagem válida
     */
    public static function isValidImage($filePath) {
        // Verificar se o arquivo é acessível
        if (!is_readable($filePath)) {
            return false;
        }
        
        // Obter informações da imagem
        $imageInfo = getimagesize($filePath);
        
        // Verificar se getimagesize retornou informações válidas
        if ($imageInfo === false) {
            return false;
        }
        
        // Verificar se o tipo de imagem é válido
        $validImageTypes = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_GIF,
            IMAGETYPE_BMP,
            IMAGETYPE_WEBP
        ];
        
        return in_array($imageInfo[2], $validImageTypes);
    }
    
    /**
     * Processa uma imagem (redimensiona, otimiza)
     * 
     * @param string $filePath Caminho completo do arquivo
     * @param array $options Opções de processamento
     * @return array Resultado do processamento ['success' => bool, 'message' => string]
     */
    public static function processImage($filePath, array $options = []) {
        // Verificar se o arquivo existe e é uma imagem
        if (!file_exists($filePath) || !self::isValidImage($filePath)) {
            return [
                'success' => false, 
                'message' => 'Arquivo não existe ou não é uma imagem válida'
            ];
        }
        
        // Verificar se GD está disponível
        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
            return [
                'success' => false, 
                'message' => 'A biblioteca GD não está disponível para processamento de imagens'
            ];
        }
        
        // Obter informações da imagem
        $imageInfo = getimagesize($filePath);
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Configurar opções de redimensionamento
        $maxWidth = isset($options['maxWidth']) ? $options['maxWidth'] : null;
        $maxHeight = isset($options['maxHeight']) ? $options['maxHeight'] : null;
        $quality = isset($options['quality']) ? $options['quality'] : 85;
        
        // Verificar se redimensionamento é necessário
        if (($maxWidth !== null && $width > $maxWidth) || ($maxHeight !== null && $height > $maxHeight)) {
            // Calcular novas dimensões mantendo proporção
            $ratio = $width / $height;
            
            if ($maxWidth !== null && $maxHeight !== null) {
                if ($width / $maxWidth > $height / $maxHeight) {
                    $newWidth = $maxWidth;
                    $newHeight = floor($newWidth / $ratio);
                } else {
                    $newHeight = $maxHeight;
                    $newWidth = floor($newHeight * $ratio);
                }
            } elseif ($maxWidth !== null) {
                $newWidth = $maxWidth;
                $newHeight = floor($newWidth / $ratio);
            } else {
                $newHeight = $maxHeight;
                $newWidth = floor($newHeight * $ratio);
            }
            
            // Criar imagem de origem
            $srcImage = null;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $srcImage = imagecreatefromjpeg($filePath);
                    break;
                case IMAGETYPE_PNG:
                    $srcImage = imagecreatefrompng($filePath);
                    break;
                case IMAGETYPE_GIF:
                    $srcImage = imagecreatefromgif($filePath);
                    break;
                default:
                    return [
                        'success' => false, 
                        'message' => 'Tipo de imagem não suportado para redimensionamento'
                    ];
            }
            
            if (!$srcImage) {
                return [
                    'success' => false, 
                    'message' => 'Erro ao criar imagem de origem'
                ];
            }
            
            // Criar imagem de destino
            $dstImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparência para PNG
            if ($type === IMAGETYPE_PNG) {
                imagecolortransparent($dstImage, imagecolorallocate($dstImage, 0, 0, 0));
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
            }
            
            // Redimensionar imagem
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Salvar imagem
            $success = false;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $success = imagejpeg($dstImage, $filePath, $quality);
                    break;
                case IMAGETYPE_PNG:
                    // PNG usa escala de 0-9, converter de qualidade em porcentagem
                    $pngQuality = 9 - round(($quality / 100) * 9);
                    $success = imagepng($dstImage, $filePath, $pngQuality);
                    break;
                case IMAGETYPE_GIF:
                    $success = imagegif($dstImage, $filePath);
                    break;
            }
            
            // Liberar memória
            imagedestroy($srcImage);
            imagedestroy($dstImage);
            
            if (!$success) {
                return [
                    'success' => false, 
                    'message' => 'Erro ao salvar imagem redimensionada'
                ];
            }
        }
        
        return [
            'success' => true, 
            'message' => 'Imagem processada com sucesso'
        ];
    }
    
    /**
     * Retorna uma mensagem de erro para códigos de erro de upload
     * 
     * @param int $errorCode Código de erro do upload
     * @return string Mensagem de erro
     */
    private static function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'O arquivo excede o tamanho máximo permitido pelo PHP (upload_max_filesize)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'O arquivo excede o tamanho máximo permitido pelo formulário (MAX_FILE_SIZE)';
            case UPLOAD_ERR_PARTIAL:
                return 'O arquivo foi enviado parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo foi enviado';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta uma pasta temporária no servidor';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao gravar arquivo no disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Uma extensão PHP interrompeu o upload do arquivo';
            default:
                return 'Erro desconhecido no upload';
        }
    }
    
    /**
     * Formata o tamanho do arquivo para exibição
     * 
     * @param int $bytes Tamanho em bytes
     * @param int $precision Precisão de casas decimais
     * @return string Tamanho formatado
     */
    private static function formatSize($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
