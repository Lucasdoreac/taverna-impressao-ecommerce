<?php
/**
 * ImageHelper - Classe para otimização e manipulação de imagens
 * 
 * Fornece funcionalidades para manipular, otimizar e redimensionar imagens
 * para uso eficiente no sistema TAVERNA DA IMPRESSÃO
 */
class ImageHelper {
    /**
     * Otimiza uma imagem redimensionando e comprimindo
     * 
     * @param string $sourcePath Caminho da imagem original
     * @param string $destinationPath Caminho para salvar a imagem otimizada
     * @param int $maxWidth Largura máxima da imagem redimensionada
     * @param int $quality Qualidade da compressão (0-100)
     * @return bool True se a otimização for bem-sucedida, False caso contrário
     */
    public static function optimize($sourcePath, $destinationPath, $maxWidth = 1200, $quality = 85) {
        // Verificar se o arquivo existe
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        // Obter informações da imagem
        list($width, $height, $type) = getimagesize($sourcePath);
        
        // Verificar se é um tipo de imagem suportado
        if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            return false;
        }
        
        // Calcular novas dimensões mantendo a proporção
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = ($height / $width) * $maxWidth;
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        // Criar nova imagem redimensionada
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Lidar com transparência para PNG
        if ($type == IMAGETYPE_PNG) {
            // Ativar suporte a transparência
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Criar recurso de imagem baseado no tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
        }
        
        // Redimensionar a imagem
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Criar diretório de destino se não existir
        $dir = dirname($destinationPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Salvar a imagem otimizada baseado no tipo
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($newImage, $destinationPath, $quality);
                break;
            case IMAGETYPE_PNG:
                // Converter qualidade de 0-100 para 0-9
                $pngQuality = 9 - round(($quality / 100) * 9);
                $result = imagepng($newImage, $destinationPath, $pngQuality);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($newImage, $destinationPath);
                break;
        }
        
        // Liberar memória
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return $result;
    }
    
    /**
     * Cria uma miniatura da imagem
     * 
     * @param string $sourcePath Caminho da imagem original
     * @param string $destinationPath Caminho para salvar a miniatura
     * @param int $width Largura da miniatura
     * @param int $height Altura da miniatura (opcional, para corte quadrado)
     * @param bool $crop Se verdadeiro, corta a imagem para caber nas dimensões
     * @return bool True se a criação for bem-sucedida, False caso contrário
     */
    public static function createThumbnail($sourcePath, $destinationPath, $width = 300, $height = null, $crop = false) {
        // Verificar se o arquivo existe
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        // Obter informações da imagem
        list($origWidth, $origHeight, $type) = getimagesize($sourcePath);
        
        // Verificar se é um tipo de imagem suportado
        if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            return false;
        }
        
        // Definir altura proporcional se não fornecida
        if ($height === null) {
            $height = ($origHeight / $origWidth) * $width;
        }
        
        // Criar nova imagem
        $newImage = imagecreatetruecolor($width, $height);
        
        // Lidar com transparência para PNG
        if ($type == IMAGETYPE_PNG) {
            // Ativar suporte a transparência
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
        }
        
        // Criar recurso de imagem baseado no tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
        }
        
        if ($crop) {
            // Calcular proporções para corte centrado
            $ratio_orig = $origWidth / $origHeight;
            $ratio_thumb = $width / $height;
            
            if ($ratio_orig > $ratio_thumb) {
                // A imagem original é mais larga
                $srcHeight = $origHeight;
                $srcWidth = $origHeight * $ratio_thumb;
                $srcX = ($origWidth - $srcWidth) / 2;
                $srcY = 0;
            } else {
                // A imagem original é mais alta
                $srcWidth = $origWidth;
                $srcHeight = $origWidth / $ratio_thumb;
                $srcX = 0;
                $srcY = ($origHeight - $srcHeight) / 2;
            }
            
            // Redimensionar com corte
            imagecopyresampled($newImage, $sourceImage, 0, 0, $srcX, $srcY, $width, $height, $srcWidth, $srcHeight);
        } else {
            // Redimensionar sem corte
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
        }
        
        // Criar diretório de destino se não existir
        $dir = dirname($destinationPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Salvar a miniatura baseado no tipo
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($newImage, $destinationPath, 90);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($newImage, $destinationPath, 6);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($newImage, $destinationPath);
                break;
        }
        
        // Liberar memória
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return $result;
    }
    
    /**
     * Adiciona marca d'água a uma imagem
     * 
     * @param string $sourcePath Caminho da imagem original
     * @param string $destinationPath Caminho para salvar a imagem com marca d'água
     * @param string $watermarkPath Caminho da imagem de marca d'água
     * @param string $position Posição da marca d'água: 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'
     * @param int $opacity Opacidade da marca d'água (0-100)
     * @return bool True se a adição da marca d'água for bem-sucedida, False caso contrário
     */
    public static function addWatermark($sourcePath, $destinationPath, $watermarkPath, $position = 'bottom-right', $opacity = 50) {
        // Verificar se os arquivos existem
        if (!file_exists($sourcePath) || !file_exists($watermarkPath)) {
            return false;
        }
        
        // Obter informações da imagem original
        list($origWidth, $origHeight, $origType) = getimagesize($sourcePath);
        
        // Obter informações da marca d'água
        list($watermarkWidth, $watermarkHeight) = getimagesize($watermarkPath);
        
        // Verificar se são tipos de imagem suportados
        if (!in_array($origType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            return false;
        }
        
        // Criar recursos de imagem
        switch ($origType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
        }
        
        // Criar recurso da marca d'água
        $watermarkImage = imagecreatefrompng($watermarkPath);
        
        // Calcular posição da marca d'água
        switch ($position) {
            case 'top-left':
                $x = 10;
                $y = 10;
                break;
            case 'top-right':
                $x = $origWidth - $watermarkWidth - 10;
                $y = 10;
                break;
            case 'bottom-left':
                $x = 10;
                $y = $origHeight - $watermarkHeight - 10;
                break;
            case 'bottom-right':
                $x = $origWidth - $watermarkWidth - 10;
                $y = $origHeight - $watermarkHeight - 10;
                break;
            case 'center':
                $x = ($origWidth - $watermarkWidth) / 2;
                $y = ($origHeight - $watermarkHeight) / 2;
                break;
            default:
                $x = 10;
                $y = 10;
        }
        
        // Definir opacidade da marca d'água
        imagealphablending($watermarkImage, false);
        imagesavealpha($watermarkImage, true);
        
        // Aplicar marca d'água
        imagecopymerge($sourceImage, $watermarkImage, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight, $opacity);
        
        // Criar diretório de destino se não existir
        $dir = dirname($destinationPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Salvar a imagem com marca d'água
        $result = false;
        switch ($origType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($sourceImage, $destinationPath, 90);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($sourceImage, $destinationPath, 6);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($sourceImage, $destinationPath);
                break;
        }
        
        // Liberar memória
        imagedestroy($sourceImage);
        imagedestroy($watermarkImage);
        
        return $result;
    }
    
    /**
     * Verifica se um arquivo é uma imagem válida
     * 
     * @param string $filePath Caminho do arquivo
     * @return bool True se o arquivo for uma imagem válida, False caso contrário
     */
    public static function isValidImage($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $info = getimagesize($filePath);
        
        if ($info === false) {
            return false;
        }
        
        // Verificar se é um tipo de imagem suportado
        return in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF]);
    }
    
    /**
     * Obtém informações detalhadas sobre uma imagem
     * 
     * @param string $filePath Caminho do arquivo
     * @return array|false Informações da imagem ou false se não for uma imagem válida
     */
    public static function getImageInfo($filePath) {
        if (!self::isValidImage($filePath)) {
            return false;
        }
        
        $info = getimagesize($filePath);
        $fileSize = filesize($filePath);
        
        // Mapear tipos de imagem
        $types = [
            IMAGETYPE_JPEG => 'JPEG',
            IMAGETYPE_PNG => 'PNG',
            IMAGETYPE_GIF => 'GIF'
        ];
        
        return [
            'width' => $info[0],
            'height' => $info[1],
            'type' => $types[$info[2]] ?? 'Unknown',
            'mime' => $info['mime'],
            'size' => $fileSize,
            'size_readable' => self::formatBytes($fileSize)
        ];
    }
    
    /**
     * Formata bytes para unidades legíveis
     * 
     * @param int $bytes Número de bytes
     * @param int $precision Precisão das casas decimais
     * @return string Tamanho formatado com unidade
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}