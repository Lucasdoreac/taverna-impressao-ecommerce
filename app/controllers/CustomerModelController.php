<?php
/**
 * CustomerModelController
 * 
 * Controller responsável por gerenciar o upload e manipulação de modelos 3D
 * enviados por clientes.
 */
require_once __DIR__ . '/../lib/Controller.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Security/CsrfProtection.php';
require_once __DIR__ . '/../lib/Security/Model3DValidator.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../models/CustomerModelModel.php';

class CustomerModelController extends Controller {
    // Incluir o trait de validação para ter acesso aos métodos de validação
    use InputValidationTrait;
    
    /**
     * Diretório base onde os modelos serão armazenados
     * @var string
     */
    private $uploadBaseDir = 'uploads/models';
    
    /**
     * Diretório de quarentena para modelos não verificados
     * @var string
     */
    private $quarantineDir = 'uploads/models/quarantine';
    
    /**
     * Diretório para modelos verificados e aprovados
     * @var string
     */
    private $approvedDir = 'uploads/models/approved';
    
    /**
     * Diretório para modelos rejeitados
     * @var string
     */
    private $rejectedDir = 'uploads/models/rejected';
    
    /**
     * Extensões de arquivo permitidas
     * @var array
     */
    private $allowedExtensions = ['stl', 'obj', '3mf'];
    
    /**
     * Tamanho máximo de arquivo (50MB)
     * @var int
     */
    private $maxFileSize = 52428800; // 50MB
    
    /**
     * Modelo de dados para CustomerModel
     * @var CustomerModelModel
     */
    private $customerModelModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        $this->customerModelModel = new CustomerModelModel();
        $this->ensureDirectoriesExist();
    }
    
    /**
     * Garante que os diretórios de upload existam
     */
    private function ensureDirectoriesExist() {
        $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->uploadBaseDir;
        $quarantineDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->quarantineDir;
        $approvedDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->approvedDir;
        $rejectedDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->rejectedDir;
        
        // Criar os diretórios se não existirem
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }
        
        if (!is_dir($quarantineDir)) {
            mkdir($quarantineDir, 0755, true);
        }
        
        if (!is_dir($approvedDir)) {
            mkdir($approvedDir, 0755, true);
        }
        
        if (!is_dir($rejectedDir)) {
            mkdir($rejectedDir, 0755, true);
        }
        
        // Garantir permissões corretas nos diretórios
        chmod($baseDir, 0755);
        chmod($quarantineDir, 0755);
        chmod($approvedDir, 0755);
        chmod($rejectedDir, 0755);
    }
    
    /**
     * Exibe a página de upload de modelos
     */
    public function upload() {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para fazer upload de modelos.');
            $this->redirect('/login');
            return;
        }
        
        // Verificar se o usuário atingiu a cota de uploads
        $userId = $_SESSION['user_id'];
        $quotaCheck = $this->checkUserQuota($userId);
        
        if (!$quotaCheck['allowed']) {
            $this->setFlashMessage('error', $quotaCheck['message']);
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Verificar se o usuário atingiu o limite de uploads por período
        $rateLimitCheck = $this->checkUserRateLimit($userId);
        
        if (!$rateLimitCheck['allowed']) {
            $this->setFlashMessage('error', $rateLimitCheck['message']);
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Renderizar a página de upload com token CSRF
        $this->render('customer_models/upload', [
            'pageTitle' => 'Upload de Modelo 3D',
            'csrfToken' => CsrfProtection::getToken(),
            'maxFileSize' => $this->maxFileSize,
            'allowedExtensions' => $this->allowedExtensions,
            'quotaInfo' => $quotaCheck,
            'rateLimitInfo' => $rateLimitCheck
        ]);
    }
    
    /**
     * Processa o upload de um modelo 3D
     */
    public function processUpload() {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para fazer upload de modelos.');
            $this->redirect('/login');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/customer-models/upload');
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
        // Verificar cota e limite de taxa
        $quotaCheck = $this->checkUserQuota($userId);
        $rateLimitCheck = $this->checkUserRateLimit($userId);
        
        if (!$quotaCheck['allowed']) {
            $this->setFlashMessage('error', $quotaCheck['message']);
            $this->redirect('/customer-models/list');
            return;
        }
        
        if (!$rateLimitCheck['allowed']) {
            $this->setFlashMessage('error', $rateLimitCheck['message']);
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Verificar se há arquivo enviado
        if (!isset($_FILES['model_file']) || $_FILES['model_file']['error'] == UPLOAD_ERR_NO_FILE) {
            $this->setFlashMessage('error', 'Você deve selecionar um arquivo para upload.');
            $this->redirect('/customer-models/upload');
            return;
        }
        
        try {
            // Validar arquivo usando o Model3DValidator
            $validationResult = Model3DValidator::validate($_FILES['model_file'], [
                'maxSize' => $this->maxFileSize,
                'allowedExtensions' => $this->allowedExtensions
            ]);
            
            if (!$validationResult['valid']) {
                $this->setFlashMessage('error', 'Erro na validação do modelo: ' . $validationResult['message']);
                $this->redirect('/customer-models/upload');
                return;
            }
            
            // Obter notas adicionais
            $notes = $this->postValidatedParam('notes', 'string', [
                'required' => false,
                'default' => '',
                'maxLength' => 2000
            ]);
            
            // Gerar nome de arquivo seguro
            $originalName = $_FILES['model_file']['name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $safeFilename = $this->generateSecureFilename($userId, $extension);
            
            // Caminho para o diretório de quarentena
            $quarantineFilePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->quarantineDir . '/' . $safeFilename;
            
            // Mover o arquivo para o diretório de quarentena
            if (!move_uploaded_file($_FILES['model_file']['tmp_name'], $quarantineFilePath)) {
                throw new Exception("Falha ao mover o arquivo para o servidor.");
            }
            
            // Definir permissões restritas do arquivo
            chmod($quarantineFilePath, 0644);
            
            // Registrar o modelo no banco de dados
            $modelId = $this->customerModelModel->saveModel(
                $userId,
                $safeFilename,
                $originalName,
                $_FILES['model_file']['size'],
                $extension,
                $notes,
                'pending_validation',
                $validationResult['data']
            );
            
            if (!$modelId) {
                // Se não conseguir salvar no banco de dados, excluir o arquivo
                unlink($quarantineFilePath);
                throw new Exception("Erro ao registrar o modelo no sistema.");
            }
            
            // Registrar upload bem-sucedido no log
            $this->logModelUpload($userId, $modelId, $safeFilename, $originalName, $validationResult);
            
            // Redirecionar com mensagem de sucesso
            $this->setFlashMessage('success', 'Modelo enviado com sucesso! Ele será analisado em breve pela nossa equipe.');
            $this->redirect('/customer-models/list');
            
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Erro ao fazer upload do modelo: ' . $e->getMessage());
            $this->redirect('/customer-models/upload');
        }
    }
    
    /**
     * Lista os modelos do usuário atual
     */
    public function listUserModels() {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para visualizar seus modelos.');
            $this->redirect('/login');
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
        // Filtro de status (opcional)
        $status = $this->getValidatedParam('status', 'string', [
            'required' => false,
            'default' => null,
            'allowedValues' => ['pending_validation', 'approved', 'rejected', null]
        ]);
        
        // Obter modelos do usuário
        $models = $this->customerModelModel->getUserModels($userId, $status);
        
        // Obter informações de cota
        $quotaInfo = $this->checkUserQuota($userId);
        
        // Renderizar página de listagem
        $this->render('customer_models/list', [
            'pageTitle' => 'Meus Modelos 3D',
            'models' => $models,
            'status' => $status,
            'quotaInfo' => $quotaInfo
        ]);
    }
    
    /**
     * Exibe detalhes de um modelo específico
     * 
     * @param int $id ID do modelo
     */
    public function details($id) {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para visualizar detalhes do modelo.');
            $this->redirect('/login');
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
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
        
        // Verificar permissão (apenas o proprietário ou admin pode ver)
        if ($model['user_id'] != $userId && !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para visualizar este modelo.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Verificar se o modelo está aprovado para visualização 3D
        $canView3D = ($model['status'] === 'approved');
        
        // Renderizar página de detalhes
        $this->render('customer_models/details', [
            'pageTitle' => 'Detalhes do Modelo',
            'model' => $model,
            'canView3D' => $canView3D,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Exclui um modelo
     * 
     * @param int $id ID do modelo
     */
    public function delete($id) {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para excluir modelos.');
            $this->redirect('/login');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->getValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('error', 'ID de modelo inválido.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Verificar permissão (apenas o proprietário ou admin pode excluir)
        if (!$this->customerModelModel->isModelOwnedByUser($id, $userId) && !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para excluir este modelo.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Excluir o modelo
        $result = $this->customerModelModel->deleteModel($id);
        
        if ($result) {
            $this->setFlashMessage('success', 'Modelo excluído com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Erro ao excluir o modelo.');
        }
        
        $this->redirect('/customer-models/list');
    }
    
    /**
     * Lista modelos pendentes de validação (apenas para administradores)
     */
    public function pendingModels() {
        // Verificar se o usuário é administrador
        if (!SecurityManager::checkAuthentication() || !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar esta página.');
            $this->redirect('/');
            return;
        }
        
        // Obter modelos pendentes
        $models = $this->customerModelModel->getPendingModels();
        
        // Renderizar página de modelos pendentes
        $this->render('admin/pending_models', [
            'pageTitle' => 'Modelos Pendentes de Validação',
            'models' => $models
        ]);
    }
    
    /**
     * Atualiza o status de um modelo (apenas para administradores)
     * 
     * @param int $id ID do modelo
     */
    public function updateStatus($id) {
        // Verificar se o usuário é administrador
        if (!SecurityManager::checkAuthentication() || !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar esta função.');
            $this->redirect('/');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/admin/customer-models/pending');
            return;
        }
        
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('error', 'ID de modelo inválido.');
            $this->redirect('/admin/customer-models/pending');
            return;
        }
        
        // Obter novo status
        $status = $this->postValidatedParam('status', 'string', [
            'required' => true,
            'allowedValues' => ['approved', 'rejected']
        ]);
        
        // Obter notas de revisão
        $notes = $this->postValidatedParam('admin_notes', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 2000
        ]);
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($id);
        
        if (!$model) {
            $this->setFlashMessage('error', 'Modelo não encontrado.');
            $this->redirect('/admin/customer-models/pending');
            return;
        }
        
        // Verificar se o modelo está pendente
        if ($model['status'] !== 'pending_validation') {
            $this->setFlashMessage('error', 'Este modelo já foi revisado.');
            $this->redirect('/admin/customer-models/pending');
            return;
        }
        
        try {
            // Mover o arquivo para o diretório apropriado
            $sourceFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->quarantineDir . '/' . $model['file_name'];
            $targetDir = $status === 'approved' ? $this->approvedDir : $this->rejectedDir;
            $targetFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $targetDir . '/' . $model['file_name'];
            
            if (file_exists($sourceFile) && is_file($sourceFile)) {
                if (!copy($sourceFile, $targetFile)) {
                    throw new Exception('Erro ao mover o arquivo para o diretório de destino.');
                }
                
                // Definir permissões restritas
                chmod($targetFile, 0644);
                
                // Remover arquivo de quarentena
                unlink($sourceFile);
            }
            
            // Atualizar status no banco de dados
            $updateResult = $this->customerModelModel->updateStatus($id, $status, $notes);
            
            if (!$updateResult) {
                throw new Exception('Erro ao atualizar o status do modelo no banco de dados.');
            }
            
            // Registrar ação de revisão no log
            $this->logModelStatusUpdate($id, $status, $notes);
            
            $this->setFlashMessage('success', 'Status do modelo atualizado com sucesso.');
            $this->redirect('/admin/customer-models/pending');
            
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Erro ao atualizar o status do modelo: ' . $e->getMessage());
            $this->redirect('/admin/customer-models/pending');
        }
    }
    
    /**
     * Verifica a cota de armazenamento do usuário
     * 
     * @param int $userId ID do usuário
     * @return array Informações sobre a cota
     */
    private function checkUserQuota($userId) {
        // Obter modelos do usuário para calcular o uso
        $models = $this->customerModelModel->getUserModels($userId);
        
        // Definir cota máxima (200MB para usuários comuns)
        $isAdmin = $this->isAdmin();
        $maxQuota = $isAdmin ? 1073741824 : 209715200; // 1GB para admin, 200MB para usuários comuns
        
        // Calcular uso atual
        $usedSpace = 0;
        foreach ($models as $model) {
            $usedSpace += $model['file_size'];
        }
        
        // Calcular espaço restante
        $remainingSpace = $maxQuota - $usedSpace;
        
        return [
            'allowed' => $remainingSpace > 0,
            'message' => $remainingSpace <= 0 ? 'Você atingiu sua cota de armazenamento.' : '',
            'maxQuota' => $maxQuota,
            'usedSpace' => $usedSpace,
            'remainingSpace' => $remainingSpace,
            'percentUsed' => ($usedSpace / $maxQuota) * 100,
            'maxQuotaFormatted' => Model3DValidator::formatSize($maxQuota),
            'usedSpaceFormatted' => Model3DValidator::formatSize($usedSpace),
            'remainingSpaceFormatted' => Model3DValidator::formatSize($remainingSpace)
        ];
    }
    
    /**
     * Verifica limite de taxa de upload do usuário
     * 
     * @param int $userId ID do usuário
     * @return array Informações sobre limite de taxa
     */
    private function checkUserRateLimit($userId) {
        // Definir limites (5 uploads por hora para usuários comuns)
        $isAdmin = $this->isAdmin();
        $maxUploadsPerHour = $isAdmin ? 50 : 5;
        
        // Contar uploads na última hora
        // TODO: Implementar consulta real ao banco de dados
        $recentUploads = 0; // Temporariamente definido como 0
        
        return [
            'allowed' => $recentUploads < $maxUploadsPerHour,
            'message' => $recentUploads >= $maxUploadsPerHour ? 
                'Você excedeu o limite de uploads. Tente novamente mais tarde.' : '',
            'maxUploadsPerHour' => $maxUploadsPerHour,
            'recentUploads' => $recentUploads,
            'remainingUploads' => $maxUploadsPerHour - $recentUploads
        ];
    }
    
    /**
     * Gera um nome de arquivo seguro para armazenamento
     * 
     * @param int $userId ID do usuário
     * @param string $extension Extensão do arquivo
     * @return string Nome de arquivo seguro
     */
    private function generateSecureFilename($userId, $extension) {
        // Gerar string aleatória
        $randomStr = bin2hex(random_bytes(16));
        
        // Formar nome seguro: user_id + timestamp + random + extension
        $safeFilename = 'user_' . $userId . '_' . time() . '_' . $randomStr . '.' . $extension;
        
        return $safeFilename;
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
    
    /**
     * Registra o upload de um modelo no log
     * 
     * @param int $userId ID do usuário
     * @param int $modelId ID do modelo
     * @param string $filename Nome do arquivo
     * @param string $originalName Nome original do arquivo
     * @param array $validationResult Resultado da validação
     * @return void
     */
    private function logModelUpload($userId, $modelId, $filename, $originalName, $validationResult) {
        // TODO: Implementar log real
        error_log(sprintf(
            "Modelo 3D enviado: user_id=%d, model_id=%d, filename=%s, original_name=%s, validation=%s",
            $userId,
            $modelId,
            $filename,
            $originalName,
            json_encode($validationResult)
        ));
    }
    
    /**
     * Registra a atualização de status de um modelo no log
     * 
     * @param int $modelId ID do modelo
     * @param string $status Novo status
     * @param string $notes Notas de revisão
     * @return void
     */
    private function logModelStatusUpdate($modelId, $status, $notes) {
        // TODO: Implementar log real
        error_log(sprintf(
            "Status de modelo 3D atualizado: model_id=%d, status=%s, notes=%s",
            $modelId,
            $status,
            $notes
        ));
    }
}