<?php
/**
 * Viewer3DController - Controlador para visualização 3D de modelos aprovados
 * 
 * Responsável por renderizar a interface de visualização 3D e gerenciar
 * o acesso seguro aos modelos 3D aprovados.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Controllers
 * @version    1.0.0
 */

require_once __DIR__ . '/../lib/Controller.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Security/CsrfProtection.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../models/CustomerModelModel.php';

class Viewer3DController extends Controller {
    // Incluir o trait de validação para ter acesso aos métodos de validação
    use InputValidationTrait;
    
    /**
     * Modelo de dados para CustomerModel
     * @var CustomerModelModel
     */
    private $customerModelModel;
    
    /**
     * Diretório para modelos aprovados
     * @var string
     */
    private $approvedDir = 'uploads/models/approved';
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        $this->customerModelModel = new CustomerModelModel();
    }
    
    /**
     * Exibe a página de visualização 3D para um modelo específico
     * 
     * @param int $id ID do modelo
     */
    public function view($id) {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para visualizar modelos 3D.');
            $this->redirect('/login');
            return;
        }
        
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('error', 'ID de modelo inválido.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($id);
        
        if (!$model) {
            $this->setFlashMessage('error', 'Modelo não encontrado.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Verificar se o modelo está aprovado
        if ($model['status'] !== 'approved') {
            $this->setFlashMessage('error', 'Este modelo ainda não foi aprovado para visualização.');
            $this->redirect('/customer-models/details/' . $id);
            return;
        }
        
        // Verificar permissão (qualquer usuário pode ver modelos aprovados)
        $userId = $_SESSION['user_id'];
        $isOwner = ($model['user_id'] == $userId);
        $isAdmin = $this->isAdmin();
        
        // Renderizar página de visualização 3D
        $this->render('viewer3d/view', [
            'pageTitle' => 'Visualizador 3D - ' . htmlspecialchars($model['original_name']),
            'model' => $model,
            'isOwner' => $isOwner,
            'isAdmin' => $isAdmin,
            'csrfToken' => CsrfProtection::getToken(),
            'viewerConfig' => $this->getViewerConfig($model),
            'modelType' => pathinfo($model['file_name'], PATHINFO_EXTENSION)
        ]);
    }
    
    /**
     * Obtém a URL segura para o modelo 3D
     * 
     * @param int $id ID do modelo
     * @param string $token Token de segurança
     */
    public function getModel($id, $token) {
        // Validar ID
        $id = $this->validateInt($id, ['min' => 1, 'required' => true]);
        if ($id === null) {
            header('HTTP/1.1 400 Bad Request');
            echo 'ID de modelo inválido';
            exit;
        }
        
        // Validar token
        $token = $this->validateString($token, ['required' => true]);
        if ($token === null || !CsrfProtection::validateToken($token)) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Token de acesso inválido';
            exit;
        }
        
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            header('HTTP/1.1 401 Unauthorized');
            echo 'Autenticação necessária';
            exit;
        }
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($id);
        
        if (!$model) {
            header('HTTP/1.1 404 Not Found');
            echo 'Modelo não encontrado';
            exit;
        }
        
        // Verificar se o modelo está aprovado
        if ($model['status'] !== 'approved') {
            header('HTTP/1.1 403 Forbidden');
            echo 'Este modelo não está disponível para visualização';
            exit;
        }
        
        // Caminho para o arquivo no servidor
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->approvedDir . '/' . $model['file_name'];
        
        // Verificar se o arquivo existe
        if (!file_exists($filePath) || !is_file($filePath)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Arquivo de modelo não encontrado';
            exit;
        }
        
        // Registrar acesso ao modelo
        $this->logModelAccess($id, $_SESSION['user_id']);
        
        // Determinar o tipo MIME correto
        $extension = strtolower(pathinfo($model['file_name'], PATHINFO_EXTENSION));
        $mimeType = $this->getMimeTypeForExtension($extension);
        
        // Configurar headers de segurança
        SecurityManager::setSecureHeaders();
        
        // Headers específicos para servir o arquivo
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . basename($model['original_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: public, max-age=86400'); // Cache por 24 horas
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT');
        
        // Limpar qualquer saída anterior
        ob_clean();
        flush();
        
        // Ler e enviar o arquivo
        readfile($filePath);
        exit;
    }
    
    /**
     * Baixa um modelo 3D aprovado
     * 
     * @param int $id ID do modelo
     * @param string $token Token de segurança
     */
    public function download($id, $token) {
        // Validar ID
        $id = $this->validateInt($id, ['min' => 1, 'required' => true]);
        if ($id === null) {
            header('HTTP/1.1 400 Bad Request');
            echo 'ID de modelo inválido';
            exit;
        }
        
        // Validar token
        $token = $this->validateString($token, ['required' => true]);
        if ($token === null || !CsrfProtection::validateToken($token)) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Token de acesso inválido';
            exit;
        }
        
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            header('HTTP/1.1 401 Unauthorized');
            echo 'Autenticação necessária';
            exit;
        }
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($id);
        
        if (!$model) {
            header('HTTP/1.1 404 Not Found');
            echo 'Modelo não encontrado';
            exit;
        }
        
        // Verificar se o modelo está aprovado
        if ($model['status'] !== 'approved') {
            header('HTTP/1.1 403 Forbidden');
            echo 'Este modelo não está disponível para download';
            exit;
        }
        
        // Verificar permissão (apenas proprietário ou admin podem baixar)
        $userId = $_SESSION['user_id'];
        if ($model['user_id'] != $userId && !$this->isAdmin()) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Você não tem permissão para baixar este modelo';
            exit;
        }
        
        // Caminho para o arquivo no servidor
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->approvedDir . '/' . $model['file_name'];
        
        // Verificar se o arquivo existe
        if (!file_exists($filePath) || !is_file($filePath)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Arquivo de modelo não encontrado';
            exit;
        }
        
        // Registrar download
        $this->logModelDownload($id, $userId);
        
        // Determinar o tipo MIME correto
        $extension = strtolower(pathinfo($model['file_name'], PATHINFO_EXTENSION));
        $mimeType = $this->getMimeTypeForExtension($extension);
        
        // Configurar headers de segurança
        SecurityManager::setSecureHeaders();
        
        // Headers específicos para download do arquivo
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($model['original_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        
        // Limpar qualquer saída anterior
        ob_clean();
        flush();
        
        // Ler e enviar o arquivo
        readfile($filePath);
        exit;
    }
    
    /**
     * Obtém configurações para o visualizador 3D com base no modelo
     * 
     * @param array $model Dados do modelo
     * @return array Configurações do visualizador
     */
    private function getViewerConfig($model) {
        $config = [
            'modelUrl' => '/viewer3d/get-model/' . $model['id'] . '/' . CsrfProtection::getToken(),
            'backgroundColor' => '#f5f5f5',
            'gridEnabled' => true,
            'controlsEnabled' => true,
            'wireframeEnabled' => false,
            'autoRotate' => false,
            'defaultCameraPosition' => [0, 0, 5],
            'lightIntensity' => 1.0
        ];
        
        // Adicionar dimensões do modelo, se disponíveis
        $metadata = json_decode($model['metadata'], true);
        if (is_array($metadata) && isset($metadata['width']) && isset($metadata['height']) && isset($metadata['depth'])) {
            $maxDimension = max($metadata['width'], $metadata['height'], $metadata['depth']);
            
            // Ajustar posição da câmera com base no tamanho do modelo
            $config['defaultCameraPosition'] = [0, 0, $maxDimension * 2];
            
            // Adicionar informações de dimensão
            $config['dimensions'] = [
                'width' => $metadata['width'],
                'height' => $metadata['height'],
                'depth' => $metadata['depth']
            ];
        }
        
        return $config;
    }
    
    /**
     * Determina o tipo MIME para extensão de arquivo
     * 
     * @param string $extension Extensão do arquivo
     * @return string Tipo MIME
     */
    private function getMimeTypeForExtension($extension) {
        switch (strtolower($extension)) {
            case 'stl':
                return 'application/vnd.ms-pki.stl';
            case 'obj':
                return 'model/obj';
            case '3mf':
                return 'application/vnd.ms-package.3dmanufacturing-3dmodel+xml';
            default:
                return 'application/octet-stream';
        }
    }
    
    /**
     * Registra acesso ao modelo no log
     * 
     * @param int $modelId ID do modelo
     * @param int $userId ID do usuário
     * @return void
     */
    private function logModelAccess($modelId, $userId) {
        // TODO: Implementar registro de acesso ao modelo
        error_log(sprintf(
            "Acesso ao modelo 3D: model_id=%d, user_id=%d, timestamp=%s",
            $modelId,
            $userId,
            date('Y-m-d H:i:s')
        ));
    }
    
    /**
     * Registra download do modelo no log
     * 
     * @param int $modelId ID do modelo
     * @param int $userId ID do usuário
     * @return void
     */
    private function logModelDownload($modelId, $userId) {
        // TODO: Implementar registro de download do modelo
        error_log(sprintf(
            "Download de modelo 3D: model_id=%d, user_id=%d, timestamp=%s",
            $modelId,
            $userId,
            date('Y-m-d H:i:s')
        ));
    }
    
    /**
     * Verifica se o usuário atual é administrador
     * 
     * @return bool True se o usuário for administrador
     */
    private function isAdmin() {
        // TODO: Implementar verificação real de administrador
        // Temporariamente, apenas o usuário ID 1 é admin
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1;
    }
}
