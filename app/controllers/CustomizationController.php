<?php
/**
 * CustomizationController - Controlador para personalização de produtos
 */
class CustomizationController {
    private $productModel;
    
    public function __construct() {
        $this->productModel = new ProductModel();
    }
    
    /**
     * Exibe a página de personalização de um produto
     */
    public function index($params) {
        $slug = $params['slug'] ?? null;
        
        if (!$slug) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Buscar produto
        $product = $this->productModel->getBySlug($slug);
        
        if (!$product || !$product['is_customizable']) {
            header('Location: ' . BASE_URL . 'produto/' . $slug);
            exit;
        }
        
        // Renderizar a view
        require_once VIEWS_PATH . '/customization.php';
    }
    
    /**
     * Processa o upload de arquivo para personalização
     */
    public function upload() {
        // Verificar se é uma requisição Ajax
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso não permitido']);
            exit;
        }
        
        // Verificar se o arquivo foi enviado
        if (!isset($_FILES['custom_file']) || $_FILES['custom_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'Falha no upload do arquivo']);
            exit;
        }
        
        $file = $_FILES['custom_file'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Verificar extensão
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($fileExt, $allowedExts)) {
            echo json_encode(['error' => 'Tipo de arquivo não permitido. Apenas PDF, JPG e PNG são aceitos.']);
            exit;
        }
        
        // Verificar tamanho (max 10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            echo json_encode(['error' => 'Arquivo muito grande. O tamanho máximo é 10MB.']);
            exit;
        }
        
        // Criar diretório de upload se não existir
        $uploadDir = UPLOADS_PATH . '/customization/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Gerar nome único para o arquivo
        $uniqueName = uniqid() . '_' . $fileName;
        $uploadPath = $uploadDir . $uniqueName;
        
        // Mover arquivo
        if (move_uploaded_file($fileTmp, $uploadPath)) {
            // Se for uma imagem, criar uma versão thumbnail
            $previewUrl = '';
            if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                $thumbDir = $uploadDir . 'thumbs/';
                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0755, true);
                }
                
                $thumbPath = $thumbDir . $uniqueName;
                $this->createThumbnail($uploadPath, $thumbPath, 300);
                $previewUrl = BASE_URL . 'uploads/customization/thumbs/' . $uniqueName;
            } else {
                // Se for PDF, usar ícone genérico
                $previewUrl = BASE_URL . 'assets/images/pdf-icon.png';
            }
            
            echo json_encode([
                'success' => true,
                'fileName' => $uniqueName,
                'originalName' => $fileName,
                'previewUrl' => $previewUrl,
                'fileType' => $fileExt
            ]);
        } else {
            echo json_encode(['error' => 'Falha ao salvar o arquivo']);
        }
    }
    
    /**
     * Cria uma miniatura de uma imagem
     */
    private function createThumbnail($source, $destination, $width) {
        list($origWidth, $origHeight) = getimagesize($source);
        $ratio = $origHeight / $origWidth;
        $height = $width * $ratio;
        
        $thumb = imagecreatetruecolor($width, $height);
        
        $sourceExt = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        
        switch ($sourceExt) {
            case 'jpg':
            case 'jpeg':
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case 'png':
                $sourceImage = imagecreatefrompng($source);
                // Preservar transparência
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                break;
            default:
                return false;
        }
        
        imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
        
        switch ($sourceExt) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumb, $destination, 90);
                break;
            case 'png':
                imagepng($thumb, $destination);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($thumb);
        
        return true;
    }
}