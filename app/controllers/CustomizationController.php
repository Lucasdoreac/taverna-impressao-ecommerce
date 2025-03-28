<?php
/**
 * CustomizationController - Controlador para personalização de produtos
 * 
 * Gerencia o fluxo de personalização de produtos, permitindo uploads, pré-visualização
 * e salvamento de configurações para produtos customizáveis.
 */
class CustomizationController {
    private $productModel;
    private $customizationModel;
    
    public function __construct() {
        $this->productModel = new ProductModel();
        $this->customizationModel = new CustomizationModel();
    }
    
    /**
     * Exibe a página de personalização de um produto
     * 
     * @param array $params Parâmetros da URL
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
        
        // Verificar se há uma configuração salva
        $savedConfig = null;
        if (isset($_SESSION['user_id'])) {
            $savedConfig = $this->customizationModel->getSavedConfig($_SESSION['user_id'], $product['id']);
        }
        
        // Renderizar a view
        require_once VIEWS_PATH . '/customization/index.php';
    }
    
    /**
     * Apresenta uma pré-visualização do produto personalizado
     * 
     * @param array $params Parâmetros da URL
     */
    public function preview() {
        // Verificar se é uma requisição Ajax
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso não permitido']);
            exit;
        }
        
        // Verificar dados enviados
        if (!isset($_POST['product_id']) || !isset($_POST['customization_data'])) {
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }
        
        $productId = (int)$_POST['product_id'];
        $customizationData = json_decode($_POST['customization_data'], true);
        
        if (!$productId || !is_array($customizationData)) {
            echo json_encode(['error' => 'Dados inválidos']);
            exit;
        }
        
        // Buscar produto
        $product = $this->productModel->find($productId);
        if (!$product) {
            echo json_encode(['error' => 'Produto não encontrado']);
            exit;
        }
        
        // Processar dados de personalização para preview
        $previewHtml = $this->generatePreviewHtml($product, $customizationData);
        
        echo json_encode([
            'success' => true,
            'preview' => $previewHtml,
            'product_name' => $product['name']
        ]);
    }
    
    /**
     * Salva a configuração de personalização para uso futuro
     */
    public function saveConfig() {
        // Verificar login
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'É necessário fazer login para salvar configurações']);
            exit;
        }
        
        // Verificar se é uma requisição Ajax
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso não permitido']);
            exit;
        }
        
        // Verificar dados enviados
        if (!isset($_POST['product_id']) || !isset($_POST['customization_data'])) {
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $productId = (int)$_POST['product_id'];
        $customizationData = $_POST['customization_data'];
        $configName = $_POST['config_name'] ?? 'Configuração ' . date('d/m/Y H:i');
        
        // Salvar configuração
        $saved = $this->customizationModel->saveConfig($userId, $productId, $configName, $customizationData);
        
        if ($saved) {
            echo json_encode([
                'success' => true,
                'message' => 'Configuração salva com sucesso'
            ]);
        } else {
            echo json_encode([
                'error' => 'Erro ao salvar configuração'
            ]);
        }
    }
    
    /**
     * Lista configurações salvas do usuário para um produto
     */
    public function listConfigs() {
        // Verificar login
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'É necessário fazer login para ver configurações salvas']);
            exit;
        }
        
        // Verificar se é uma requisição Ajax
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso não permitido']);
            exit;
        }
        
        // Verificar dados enviados
        if (!isset($_GET['product_id'])) {
            echo json_encode(['error' => 'Produto não especificado']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $productId = (int)$_GET['product_id'];
        
        // Buscar configurações salvas
        $configs = $this->customizationModel->getConfigs($userId, $productId);
        
        echo json_encode([
            'success' => true,
            'configs' => $configs
        ]);
    }
    
    /**
     * Carrega uma configuração salva
     */
    public function loadConfig() {
        // Verificar login
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'É necessário fazer login para carregar configurações']);
            exit;
        }
        
        // Verificar se é uma requisição Ajax
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso não permitido']);
            exit;
        }
        
        // Verificar dados enviados
        if (!isset($_GET['config_id'])) {
            echo json_encode(['error' => 'Configuração não especificada']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $configId = (int)$_GET['config_id'];
        
        // Buscar configuração
        $config = $this->customizationModel->getConfig($userId, $configId);
        
        if ($config) {
            echo json_encode([
                'success' => true,
                'config' => $config
            ]);
        } else {
            echo json_encode([
                'error' => 'Configuração não encontrada'
            ]);
        }
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
            // Processar o arquivo com o ImageHelper
            if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                // Se for uma imagem, criar uma versão otimizada e thumbnail
                $thumbDir = $uploadDir . 'thumbs/';
                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0755, true);
                }
                
                $thumbPath = $thumbDir . $uniqueName;
                
                // Usar o ImageHelper em vez do método interno
                ImageHelper::createThumbnail($uploadPath, $thumbPath, 300);
                $previewUrl = BASE_URL . 'uploads/customization/thumbs/' . $uniqueName;
                
                // Otimizar a imagem original
                $optimizedPath = $uploadDir . 'optimized_' . $uniqueName;
                ImageHelper::optimize($uploadPath, $optimizedPath, 1200);
                
                // Usar a versão otimizada como original
                rename($optimizedPath, $uploadPath);
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
     * Gera HTML de pré-visualização com base nos dados de personalização
     * 
     * @param array $product Dados do produto
     * @param array $customizationData Dados de personalização
     * @return string HTML da pré-visualização
     */
    private function generatePreviewHtml($product, $customizationData) {
        // Buscar opções de personalização do produto
        $customizationOptions = $this->customizationModel->getOptions($product['id']);
        
        // Iniciar HTML de pré-visualização
        $html = '<div class="preview-container">';
        $html .= '<h3>Pré-visualização do Produto</h3>';
        
        // Adicionar imagem do produto como base
        if (!empty($product['images'][0]['image'])) {
            $html .= '<div class="preview-product-image">';
            $html .= '<img src="' . BASE_URL . 'uploads/products/' . $product['images'][0]['image'] . '" alt="' . $product['name'] . '">';
            $html .= '</div>';
        }
        
        // Adicionar detalhes de personalização
        $html .= '<div class="preview-details">';
        $html .= '<h4>' . $product['name'] . ' - Personalizado</h4>';
        
        $html .= '<ul class="preview-customization-list">';
        
        foreach ($customizationOptions as $option) {
            $optionId = $option['id'];
            
            if (isset($customizationData[$optionId])) {
                $value = $customizationData[$optionId];
                
                $html .= '<li>';
                $html .= '<strong>' . $option['name'] . ':</strong> ';
                
                // Exibir valor com base no tipo de opção
                switch ($option['type']) {
                    case 'text':
                        $html .= htmlspecialchars($value);
                        break;
                        
                    case 'select':
                        $options = json_decode($option['options'], true);
                        $html .= htmlspecialchars($options[$value] ?? $value);
                        break;
                        
                    case 'upload':
                        if (!empty($value)) {
                            // Exibir miniatura para uploads
                            $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                            
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                $html .= '<img src="' . BASE_URL . 'uploads/customization/thumbs/' . $value . '" 
                                          alt="Arquivo enviado" class="preview-thumbnail">';
                            } else {
                                $html .= '<span class="file-name">' . $value . '</span>';
                            }
                        }
                        break;
                }
                
                $html .= '</li>';
            }
        }
        
        $html .= '</ul>';
        
        // Adicionar preço e detalhes finais
        $html .= '<div class="preview-price">';
        $html .= '<span>Preço: R$ ' . number_format($product['price'], 2, ',', '.') . '</span>';
        $html .= '</div>';
        
        $html .= '</div>'; // Fim de preview-details
        $html .= '</div>'; // Fim de preview-container
        
        return $html;
    }
}