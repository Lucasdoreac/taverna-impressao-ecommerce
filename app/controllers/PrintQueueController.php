<?php
/**
 * PrintQueueController - Gerencia a fila de impressão 3D
 * 
 * @package    Taverna da Impressão 3D
 * @author     Claude
 * @version    1.0.0
 */

require_once __DIR__ . '/../lib/Controller.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Security/CsrfProtection.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../models/PrintQueueModel.php';
require_once __DIR__ . '/../models/CustomerModelModel.php';
require_once __DIR__ . '/../models/NotificationModel.php';

class PrintQueueController extends Controller {
    use InputValidationTrait;
    
    private $printQueueModel;
    private $customerModelModel;
    private $notificationModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        $this->printQueueModel = new PrintQueueModel();
        $this->customerModelModel = new CustomerModelModel();
        $this->notificationModel = new NotificationModel();
        
        // Verificar autenticação para todas as ações exceto as públicas
        $publicActions = ['trackJob'];
        $currentAction = isset($_GET['action']) ? $this->validateString($_GET['action']) : 'index';
        
        if (!in_array($currentAction, $publicActions)) {
            if (!SecurityManager::checkAuthentication()) {
                $this->setFlashMessage('error', 'Você precisa estar logado para acessar esta página.');
                $this->redirect('/login');
                return;
            }
        }
    }
    
    /**
     * Página inicial da fila de impressão (admin)
     */
    public function index() {
        // Verificar se o usuário é administrador
        if (!$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar esta página.');
            $this->redirect('/');
            return;
        }
        
        // Obter filtros da URL
        $status = $this->getValidatedParam('status', 'string', [
            'required' => false, 
            'allowedValues' => ['pending', 'assigned', 'printing', 'completed', 'cancelled', 'failed']
        ]);
        
        $userId = $this->getValidatedParam('user_id', 'integer', ['required' => false]);
        
        // Preparar filtros
        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }
        if ($userId) {
            $filters['user_id'] = $userId;
        }
        
        // Obter itens da fila com os filtros aplicados
        $queueItems = $this->printQueueModel->getAllQueueItems($filters);
        
        // Obter estatísticas da fila
        $statistics = $this->printQueueModel->getQueueStatistics();
        
        // Renderizar a view
        $this->render('admin/print_queue/index', [
            'pageTitle' => 'Gerenciamento da Fila de Impressão',
            'queueItems' => $queueItems,
            'statistics' => $statistics,
            'currentFilters' => $filters,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Adiciona um modelo à fila de impressão
     */
    public function addToQueue() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Obter parâmetros
        $modelId = $this->postValidatedParam('model_id', 'integer', ['required' => true]);
        $priority = $this->postValidatedParam('priority', 'integer', [
            'required' => false,
            'default' => 5,
            'min' => 1,
            'max' => 10
        ]);
        $notes = $this->postValidatedParam('notes', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 1000
        ]);
        
        // Obter configurações de impressão
        $printSettings = [];
        
        $scale = $this->postValidatedParam('scale', 'float', [
            'required' => false,
            'default' => 1.0,
            'min' => 0.1,
            'max' => 5.0
        ]);
        $printSettings['scale'] = $scale;
        
        $infill = $this->postValidatedParam('infill', 'integer', [
            'required' => false,
            'default' => 20,
            'min' => 5,
            'max' => 100
        ]);
        $printSettings['infill'] = $infill;
        
        $layerHeight = $this->postValidatedParam('layer_height', 'float', [
            'required' => false,
            'default' => 0.2,
            'min' => 0.05,
            'max' => 0.4
        ]);
        $printSettings['layer_height'] = $layerHeight;
        
        $supports = $this->postValidatedParam('supports', 'boolean', [
            'required' => false,
            'default' => true
        ]);
        $printSettings['supports'] = $supports;
        
        $material = $this->postValidatedParam('material', 'string', [
            'required' => false,
            'default' => 'PLA',
            'allowedValues' => ['PLA', 'ABS', 'PETG', 'TPU', 'Nylon']
        ]);
        $printSettings['material'] = $material;
        
        $color = $this->postValidatedParam('color', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 50
        ]);
        $printSettings['color'] = $color;
        
        // Calcular tempo estimado de impressão com base no tamanho do modelo
        $modelInfo = $this->customerModelModel->getModelById($modelId);
        if ($modelInfo && isset($modelInfo['validation_data']['size'])) {
            $volume = $modelInfo['validation_data']['size']['volume'] ?? 0;
            // Fórmula simples: 1 hora para cada 30cm³ de volume, ajustado pela escala
            $estimatedHours = ($volume * ($scale ** 3)) / 30;
            $printSettings['estimated_print_time_hours'] = max(0.5, $estimatedHours); // No mínimo 30 minutos
        } else {
            $printSettings['estimated_print_time_hours'] = 2.0; // Valor padrão se não puder calcular
        }
        
        // Obter ID do usuário atual
        $userId = $_SESSION['user_id'];
        
        // Adicionar à fila
        $result = $this->printQueueModel->saveQueueItem($modelId, $userId, $priority, $notes, $printSettings);
        
        if ($result) {
            $this->setFlashMessage('success', 'Modelo adicionado à fila de impressão com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível adicionar o modelo à fila de impressão.');
        }
        
        $this->redirect('/customer-models/list');
    }
    
    /**
     * Exibe detalhes de um item na fila
     * 
     * @param int $id ID do item na fila
     */
    public function details($id) {
        // Validar ID
        $id = (int)$id;
        
        // Obter item da fila
        $queueItem = $this->printQueueModel->getQueueItemById($id);
        
        if (!$queueItem) {
            $this->setFlashMessage('error', 'Item não encontrado na fila de impressão.');
            $this->redirect('/print-queue');
            return;
        }
        
        // Verificar permissão (proprietário ou admin)
        if ($queueItem['user_id'] != $_SESSION['user_id'] && !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para visualizar este item.');
            $this->redirect('/');
            return;
        }
        
        // Obter histórico de eventos
        $history = $this->printQueueModel->getQueueItemHistory($id);
        
        // Obter detalhes do modelo 3D
        $model = $this->customerModelModel->getModelById($queueItem['model_id']);
        
        // Renderizar a view
        $this->render($this->isAdmin() ? 'admin/print_queue/details' : 'customer/print_queue/details', [
            'pageTitle' => 'Detalhes do Item na Fila',
            'queueItem' => $queueItem,
            'model' => $model,
            'history' => $history,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Cancela um item na fila
     */
    public function cancel() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/print-queue');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/print-queue');
            return;
        }
        
        // Obter parâmetros
        $queueId = $this->postValidatedParam('queue_id', 'integer', ['required' => true]);
        $notes = $this->postValidatedParam('notes', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 1000
        ]);
        
        // Obter item da fila
        $queueItem = $this->printQueueModel->getQueueItemById($queueId);
        
        if (!$queueItem) {
            $this->setFlashMessage('error', 'Item não encontrado na fila de impressão.');
            $this->redirect('/print-queue');
            return;
        }
        
        // Verificar permissão (proprietário ou admin)
        if ($queueItem['user_id'] != $_SESSION['user_id'] && !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para cancelar este item.');
            $this->redirect('/print-queue');
            return;
        }
        
        // Verificar se o item pode ser cancelado (não está concluído ou já cancelado)
        if ($queueItem['status'] === 'completed' || $queueItem['status'] === 'cancelled') {
            $this->setFlashMessage('error', 'Este item não pode ser cancelado porque já está ' . 
                                          ($queueItem['status'] === 'completed' ? 'concluído.' : 'cancelado.'));
            $this->redirect('/print-queue/details/' . $queueId);
            return;
        }
        
        // Cancelar o item
        $result = $this->printQueueModel->updateStatus($queueId, 'cancelled', $_SESSION['user_id'], $notes);
        
        if ($result) {
            $this->setFlashMessage('success', 'Item cancelado com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível cancelar o item.');
        }
        
        // Redirecionar de volta
        if ($this->isAdmin()) {
            $this->redirect('/print-queue');
        } else {
            $this->redirect('/user/print-queue');
        }
    }
    
    /**
     * Atualiza a prioridade de um item na fila (apenas admin)
     */
    public function updatePriority() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/print-queue');
            return;
        }
        
        // Verificar se o usuário é administrador
        if (!$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar esta funcionalidade.');
            $this->redirect('/');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/print-queue');
            return;
        }
        
        // Obter parâmetros
        $queueId = $this->postValidatedParam('queue_id', 'integer', ['required' => true]);
        $priority = $this->postValidatedParam('priority', 'integer', [
            'required' => true,
            'min' => 1,
            'max' => 10
        ]);
        
        // Atualizar prioridade
        $result = $this->printQueueModel->updatePriority($queueId, $priority, $_SESSION['user_id']);
        
        if ($result) {
            $this->setFlashMessage('success', 'Prioridade atualizada com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível atualizar a prioridade.');
        }
        
        $this->redirect('/print-queue/details/' . $queueId);
    }
    
    /**
     * Lista os itens na fila para o usuário atual
     */
    public function userQueue() {
        // Obter ID do usuário atual
        $userId = $_SESSION['user_id'];
        
        // Obter filtros da URL
        $status = $this->getValidatedParam('status', 'string', [
            'required' => false, 
            'allowedValues' => ['pending', 'assigned', 'printing', 'completed', 'cancelled', 'failed']
        ]);
        
        // Obter itens da fila do usuário
        $queueItems = $this->printQueueModel->getUserQueueItems($userId, $status);
        
        // Obter modelos aprovados do usuário que ainda não estão na fila
        $approvedModels = $this->customerModelModel->getUserModels($userId, 'approved');
        
        // Filtrar modelos que já estão na fila
        $queueModelIds = array_column($queueItems, 'model_id');
        $availableModels = array_filter($approvedModels, function($model) use ($queueModelIds) {
            return !in_array($model['id'], $queueModelIds);
        });
        
        // Renderizar a view
        $this->render('customer/print_queue/index', [
            'pageTitle' => 'Minha Fila de Impressão',
            'queueItems' => $queueItems,
            'availableModels' => $availableModels,
            'currentStatus' => $status,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Rastreamento público de um trabalho de impressão
     * 
     * @param string $code Código de rastreamento
     */
    public function trackJob($code = null) {
        // Se o código não foi fornecido pela URL, verificar se foi fornecido pelo formulário
        if (!$code) {
            $code = $this->getValidatedParam('code', 'string', ['required' => false]);
        }
        
        // Se ainda não tiver código, mostrar formulário de rastreamento
        if (!$code) {
            $this->render('public/print_queue/track_form', [
                'pageTitle' => 'Rastrear Impressão 3D',
                'csrfToken' => CsrfProtection::getToken()
            ]);
            return;
        }
        
        // Validar formato do código
        $code = $this->validateString($code, ['pattern' => '/^[A-Z0-9]{10}$/']);
        
        if (!$code) {
            $this->setFlashMessage('error', 'Código de rastreamento inválido.');
            $this->render('public/print_queue/track_form', [
                'pageTitle' => 'Rastrear Impressão 3D',
                'csrfToken' => CsrfProtection::getToken()
            ]);
            return;
        }
        
        // Buscar item da fila pelo código
        // Isso exigirá adicionar um campo de tracking_code à tabela print_queue
        // ou criar uma tabela separada para rastreamento
        // Por enquanto, vamos simular um resultado
        
        $queueItem = null; // Substituir por uma consulta real
        $history = [];
        
        // Renderizar a view de resultado
        $this->render('public/print_queue/track_result', [
            'pageTitle' => 'Resultado do Rastreamento',
            'queueItem' => $queueItem,
            'history' => $history,
            'trackingCode' => $code
        ]);
    }
    
    /**
     * Verifica se o usuário atual é administrador
     * 
     * @return bool True se o usuário for administrador
     */
    private function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
