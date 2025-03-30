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
        try {
            $this->productModel = new ProductModel();
            $this->customizationModel = new CustomizationModel();
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao inicializar CustomizationController");
        }
    }
    
    /**
     * Lista todos os produtos personalizáveis
     */
    public function list() {
        try {
            // Buscar produtos personalizáveis
            $customizableProducts = $this->productModel->getCustomizableProducts(24);
            
            // Verificar se a view existe
            if (file_exists(VIEWS_PATH . '/customization/list.php')) {
                // Renderizar a view dedicada
                require_once VIEWS_PATH . '/customization/list.php';
            } else {
                // Criar uma view na pasta principal caso não exista na subpasta
                // Verificar se a view alternativa existe
                if (!file_exists(VIEWS_PATH . '/personalizados.php')) {
                    // Criar uma view temporária baseada no template de products.php
                    $this->createPersonalizadosView();
                }
                
                // Renderizar a view temporária
                require_once VIEWS_PATH . '/personalizados.php';
            }
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao listar produtos personalizáveis");
        }
    }
    
    /**
     * Cria a view personalizados.php caso ela não exista
     */
    private function createPersonalizadosView() {
        try {
            // Verificar se a view de produtos existe para usar como base
            if (file_exists(VIEWS_PATH . '/products.php')) {
                $productsViewContent = file_get_contents(VIEWS_PATH . '/products.php');
                
                // Substituir o título e adicionar informações específicas para produtos personalizáveis
                $personalizadosViewContent = str_replace(
                    ['<h1 class="h2 mb-4">Produtos</h1>', '<h1 class="h2">Produtos</h1>'],
                    '<h1 class="h2 mb-4">Produtos Personalizáveis</h1>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Aqui você encontra todos os produtos que podem ser personalizados. 
                        Escolha um produto e clique em "Personalizar" para configurar de acordo com suas necessidades.
                    </div>',
                    $productsViewContent
                );
                
                // Salvar a view temporária
                file_put_contents(VIEWS_PATH . '/personalizados.php', $personalizadosViewContent);
                
                error_log("View personalizados.php criada com sucesso");
            } else {
                throw new Exception("View template products.php não encontrada em " . VIEWS_PATH);
            }
        } catch (Exception $e) {
            error_log("Erro ao criar view personalizados.php: " . $e->getMessage());
            // Se falhar em criar a view, vamos criar uma simples
            $this->createSimplePersonalizadosView();
        }
    }
    
    /**
     * Cria uma view simples para personalizados caso não consiga adaptar a de produtos
     */
    private function createSimplePersonalizadosView() {
        $viewContent = <<<HTML
<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <h1 class="h2 mb-4">Produtos Personalizáveis</h1>
    
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Aqui você encontra todos os produtos que podem ser personalizados. 
        Escolha um produto e clique em "Personalizar" para configurar de acordo com suas necessidades.
    </div>
    
    <?php if (empty(\$customizableProducts)): ?>
    <div class="alert alert-warning">
        Nenhum produto personalizável encontrado no momento.
    </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php foreach (\$customizableProducts as \$product): ?>
        <div class="col">
            <div class="card h-100 product-card border-0 shadow-sm">
                <div class="position-relative">
                    <?php if (isset(\$product['sale_price']) && \$product['sale_price'] && \$product['sale_price'] < \$product['price']): ?>
                    <span class="position-absolute badge bg-danger top-0 start-0 m-2">OFERTA</span>
                    <?php endif; ?>
                    
                    <?php if (isset(\$product['availability'])): ?>
                    <span class="position-absolute badge <?= \$product['availability'] === 'Pronta Entrega' ? 'bg-success' : 'bg-primary' ?> top-0 end-0 m-2">
                        <?= \$product['availability'] ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (isset(\$product['image']) && !empty(\$product['image']) && file_exists(UPLOADS_PATH . '/products/' . \$product['image'])): ?>
                    <img src="<?= BASE_URL ?>uploads/products/<?= \$product['image'] ?>" class="card-img-top" alt="<?= \$product['name'] ?>">
                    <?php else: ?>
                    <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars(\$product['name']) ?>"></div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <h2 class="card-title h6"><?= \$product['name'] ?></h2>
                    
                    <?php if (isset(\$product['short_description'])): ?>
                    <p class="card-text small"><?= mb_strimwidth(\$product['short_description'], 0, 60, '...') ?></p>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <?php if (isset(\$product['sale_price']) && \$product['sale_price'] && \$product['sale_price'] < \$product['price']): ?>
                            <span class="text-decoration-line-through text-muted small">R$ <?= number_format(\$product['price'], 2, ',', '.') ?></span>
                            <span class="ms-1 text-danger fw-bold">R$ <?= number_format(\$product['sale_price'], 2, ',', '.') ?></span>
                            <?php else: ?>
                            <span class="fw-bold">R$ <?= number_format(\$product['price'], 2, ',', '.') ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="<?= BASE_URL ?>personalizar/<?= \$product['slug'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-brush me-1"></i> Personalizar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
HTML;

        // Salvar a view
        file_put_contents(VIEWS_PATH . '/personalizados.php', $viewContent);
        error_log("View simples personalizados.php criada com sucesso");
    }
    
    /**
     * Exibe a página de personalização de um produto
     * 
     * @param array $params Parâmetros da URL
     */
    public function index($params) {
        try {
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
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/customization/index.php')) {
                // Tentar visualização alternativa
                if (file_exists(VIEWS_PATH . '/customization.php')) {
                    require_once VIEWS_PATH . '/customization.php';
                } else {
                    throw new Exception("View de customização não encontrada");
                }
            } else {
                // Renderizar a view
                require_once VIEWS_PATH . '/customization/index.php';
            }
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao carregar página de personalização");
        }
    }
    
    /**
     * Apresenta uma pré-visualização do produto personalizado
     * 
     * @param array $params Parâmetros da URL
     */
    public function preview() {
        try {
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
        } catch (Exception $e) {
            $this->returnJsonError($e, "Erro ao gerar pré-visualização");
        }
    }
    
    /**
     * Salva a configuração de personalização para uso futuro
     */
    public function saveConfig() {
        try {
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
        } catch (Exception $e) {
            $this->returnJsonError($e, "Erro ao salvar configuração");
        }
    }
    
    /**
     * Lista configurações salvas do usuário para um produto
     */
    public function listConfigs() {
        try {
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
        } catch (Exception $e) {
            $this->returnJsonError($e, "Erro ao listar configurações");
        }
    }
    
    /**
     * Carrega uma configuração salva
     */
    public function loadConfig() {
        try {
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
        } catch (Exception $e) {
            $this->returnJsonError($e, "Erro ao carregar configuração");
        }
    }
    
    /**
     * Processa o upload de arquivo para personalização
     */
    public function upload() {
        try {
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
                if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                    $thumbDir = $uploadDir . 'thumbs/';
                    if (!is_dir($thumbDir)) {
                        mkdir($thumbDir, 0755, true);
                    }
                    
                    $thumbPath = $thumbDir . $uniqueName;
                    
                    // Verificar se ImageHelper existe
                    if (class_exists('ImageHelper')) {
                        ImageHelper::createThumbnail($uploadPath, $thumbPath, 300);
                    } else {
                        $this->createThumbnail($uploadPath, $thumbPath, 300);
                    }
                    
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
        } catch (Exception $e) {
            $this->returnJsonError($e, "Erro ao processar upload");
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
    
    /**
     * Cria uma miniatura de uma imagem
     */
    private function createThumbnail($source, $destination, $width) {
        if (!file_exists($source)) {
            throw new Exception("Arquivo de origem não encontrado: $source");
        }
        
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
                throw new Exception("Tipo de imagem não suportado: $sourceExt");
        }
        
        if (!$sourceImage) {
            throw new Exception("Falha ao criar imagem a partir do arquivo: $source");
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
    
    /**
     * Tratamento de erros centralizado para respostas JSON
     */
    private function returnJsonError(Exception $e, $context = '') {
        // Registrar erro no log
        error_log("$context: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Responder com erro
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro no servidor',
            'message' => ENVIRONMENT === 'development' ? $e->getMessage() : 'Ocorreu um erro ao processar sua solicitação.'
        ]);
        exit;
    }
    
    /**
     * Tratamento de erros centralizado para respostas HTML
     */
    private function handleError(Exception $e, $context = '') {
        // Registrar erro no log
        error_log("$context: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Variáveis para a view de erro (visíveis apenas em ambiente de desenvolvimento)
        $error_message = $e->getMessage();
        $error_trace = $e->getTraceAsString();
        
        // Renderizar página de erro
        header("HTTP/1.0 500 Internal Server Error");
        include VIEWS_PATH . '/errors/500.php';
        exit;
    }
}