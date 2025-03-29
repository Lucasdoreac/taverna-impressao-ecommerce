<?php
/**
 * ImageHelper - Funções auxiliares para gerenciamento de imagens
 */
class ImageHelper {
    /**
     * Retorna o HTML para uma imagem, usando fallback para placeholder se necessário
     * 
     * @param string|null $imagePath Caminho da imagem
     * @param string $type Tipo de imagem (product, category, user, banner)
     * @param string $altText Texto alternativo para acessibilidade
     * @return string HTML da imagem ou placeholder
     */
    public static function getImage($imagePath, $type = 'product', $altText = '') {
        $placeholderClass = "placeholder-{$type}";
        $fullPath = $imagePath ? UPLOADS_PATH . '/' . $imagePath : null;
        
        if ($imagePath && file_exists($fullPath)) {
            return '<img src="' . BASE_URL . 'uploads/' . $imagePath . '" alt="' . htmlspecialchars($altText) . '" class="img-fluid">';
        } else {
            return '<div class="' . $placeholderClass . '" role="img" aria-label="' . htmlspecialchars($altText) . '"></div>';
        }
    }
    
    /**
     * Retorna apenas o URL da imagem ou vazio se não existir
     * 
     * @param string|null $imagePath Caminho da imagem
     * @return string URL da imagem ou string vazia
     */
    public static function getImageUrl($imagePath) {
        $fullPath = $imagePath ? UPLOADS_PATH . '/' . $imagePath : null;
        
        if ($imagePath && file_exists($fullPath)) {
            return BASE_URL . 'uploads/' . $imagePath;
        }
        
        return '';
    }
    
    /**
     * Processa upload de imagem
     * 
     * @param array $file Dados do arquivo ($_FILES['campo'])
     * @param string $destination Subdiretório de destino em uploads/
     * @param string $filename Nome personalizado (opcional)
     * @return string|false Nome do arquivo salvo ou false em caso de erro
     */
    public static function uploadImage($file, $destination = 'products', $filename = null) {
        // Verificar se há erro no upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Verificar extensão
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        // Criar diretório se não existir
        $uploadDir = UPLOADS_PATH . '/' . $destination;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Gerar nome único se não fornecido
        if (!$filename) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
        }
        
        $uploadPath = $uploadDir . '/' . $filename;
        
        // Salvar arquivo
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $destination . '/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Redimensiona uma imagem
     * 
     * @param string $imagePath Caminho completo da imagem
     * @param int $maxWidth Largura máxima
     * @param int $maxHeight Altura máxima
     * @param string $targetPath Caminho para salvar a nova imagem
     * @return bool Sucesso ou falha
     */
    public static function resizeImage($imagePath, $maxWidth, $maxHeight, $targetPath) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        list($width, $height, $type) = getimagesize($imagePath);
        
        // Calcular novas dimensões mantendo proporção
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;
        
        // Criar nova imagem redimensionada
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Criar imagem de origem com base no tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($imagePath);
                break;
            default:
                return false;
        }
        
        // Redimensionar
        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Salvar nova imagem
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($newImage, $targetPath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($newImage, $targetPath, 8);
                break;
            case IMAGETYPE_WEBP:
                $result = imagewebp($newImage, $targetPath, 85);
                break;
        }
        
        // Liberar memória
        imagedestroy($source);
        imagedestroy($newImage);
        
        return $result;
    }
    
    /**
     * Exclui uma imagem
     * 
     * @param string $imagePath Caminho relativo da imagem
     * @return bool Sucesso ou falha
     */
    public static function deleteImage($imagePath) {
        $fullPath = UPLOADS_PATH . '/' . $imagePath;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
}