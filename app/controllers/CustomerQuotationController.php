<?php
/**
 * CustomerQuotationController - Controller para clientes solicitarem cotações
 * 
 * @package     App\Controllers
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
require_once __DIR__ . '/../lib/Controller.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Security/CsrfProtection.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../lib/Analysis/QuotationManager.php';
require_once __DIR__ . '/../lib/Analysis/QuotationCalculator.php';
require_once __DIR__ . '/../models/CustomerModelModel.php';

class CustomerQuotationController extends Controller {
    use InputValidationTrait;
    
    /**
     * Gerenciador de cotações
     * @var QuotationManager
     */
    private $quotationManager;
    
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
        $this->quotationManager = new QuotationManager();
        $this->customerModelModel = new CustomerModelModel();
    }
    
    /**
     * Exibe o formulário de solicitação de cotação para um modelo
     * 
     * @param int $modelId ID do modelo
     */
    public function requestQuotation($modelId) {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para solicitar cotações.');
            $this->redirect('/login');
            return;
        }
        
        // Validar ID
        $modelId = intval($modelId);
        if ($modelId <= 0) {
            $this->setFlashMessage('error', 'ID de modelo inválido.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($modelId);
        
        if (!$model) {
            $this->setFlashMessage('error', 'Modelo não encontrado.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Verificar se o modelo está aprovado
        if ($model['status'] !== 'approved') {
            $this->setFlashMessage('error', 'Apenas modelos aprovados podem ser cotados.');
            $this->redirect('/customer-models/details/' . $modelId);
            return;
        }
        
        // Verificar permissão (qualquer usuário pode cotar modelos aprovados)
        $userId = $_SESSION['user_id'];
        $isOwner = ($model['user_id'] == $userId);
        
        // Obter materiais disponíveis
        $quotationCalculator = new QuotationCalculator();
        $materials = $quotationCalculator->getAvailableMaterials();
        
        // Renderizar o formulário de cotação
        $this->render('customer_quotation/request', [
            'pageTitle' => 'Solicitar Cotação para ' . htmlspecialchars($model['original_name']),
            'csrfToken' => CsrfProtection::getToken(),
            'model' => $model,
            'isOwner' => $isOwner,
            'materials' => $materials
        ]);
    }
    
    /**
     * Processa a solicitação de cotação
     */
    public function processRequest() {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para solicitar cotações.');
            $this->redirect('/login');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Obter parâmetros do formulário
        $modelId = $this->postValidatedParam('model_id', 'int', ['required' => true, 'min' => 1]);
        $material = $this->postValidatedParam('material', 'string', ['required' => true]);
        $isUrgent = $this->postValidatedParam('is_urgent', 'bool', ['required' => false, 'default' => false]);
        $notes = $this->postValidatedParam('notes', 'string', ['required' => false, 'default' => '', 'maxLength' => 1000]);
        
        // Verificar se o modelo existe e está aprovado
        $model = $this->customerModelModel->getModelById($modelId);
        
        if (!$model || $model['status'] !== 'approved') {
            $this->setFlashMessage('error', 'Modelo inválido ou não aprovado para cotação.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Validar material
        $quotationCalculator = new QuotationCalculator();
        $availableMaterials = array_keys($quotationCalculator->getAvailableMaterials());
        
        if (!in_array($material, $availableMaterials)) {
            $this->setFlashMessage('error', 'Material inválido.');
            $this->redirect('/customer-quotation/request/' . $modelId);
            return;
        }
        
        // Gerar cotação
        $quotation = $this->quotationManager->generateQuotation($modelId, $material, [
            'urgent' => $isUrgent,
            'save' => true,
            'notes' => $notes
        ]);
        
        if (isset($quotation['error'])) {
            $this->setFlashMessage('error', 'Erro ao gerar cotação: ' . $quotation['error']);
            $this->redirect('/customer-quotation/request/' . $modelId);
            return;
        }
        
        // Redirecionar para a visualização da cotação
        $this->setFlashMessage('success', 'Cotação gerada com sucesso.');
        $this->redirect('/customer-quotation/view/' . $quotation['quotation_id']);
    }
    
    /**
     * Exibe uma cotação específica
     * 
     * @param int $id ID da cotação
     */
    public function viewQuotation($id) {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para visualizar cotações.');
            $this->redirect('/login');
            return;
        }
        
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('error', 'ID de cotação inválido.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Obter detalhes da cotação
        $quotation = $this->quotationManager->getQuotation($id);
        
        if (!$quotation) {
            $this->setFlashMessage('error', 'Cotação não encontrada.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Verificar permissão (usuário só pode ver suas próprias cotações, admin pode ver todas)
        $userId = $_SESSION['user_id'];
        if ($quotation['user_id'] != $userId && !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para visualizar esta cotação.');
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Obter dados do modelo
        $model = $this->customerModelModel->getModelById($quotation['model_id']);
        
        // Renderizar a página de detalhes da cotação
        $this->render('customer_quotation/view', [
            'pageTitle' => 'Cotação para ' . htmlspecialchars($model['original_name']),
            'csrfToken' => CsrfProtection::getToken(),
            'quotation' => $quotation,
            'model' => $model
        ]);
    }
    
    /**
     * Lista as cotações do usuário atual
     */
    public function myQuotations() {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para visualizar suas cotações.');
            $this->redirect('/login');
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Obter filtros
        $materialFilter = $this->getValidatedParam('material', 'string', ['required' => false]);
        $dateFromFilter = $this->getValidatedParam('date_from', 'string', ['required' => false]);
        $dateToFilter = $this->getValidatedParam('date_to', 'string', ['required' => false]);
        
        // Montar filtros
        $filters = [
            'user_id' => $userId
        ];
        
        if ($materialFilter) {
            $filters['material'] = $materialFilter;
        }
        
        if ($dateFromFilter) {
            $filters['date_from'] = $dateFromFilter;
        }
        
        if ($dateToFilter) {
            $filters['date_to'] = $dateToFilter;
        }
        
        // Obter paginação
        $page = $this->getValidatedParam('page', 'int', ['required' => false, 'default' => 1, 'min' => 1]);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Obter cotações do usuário
        $quotations = $this->quotationManager->listQuotations($filters, $limit, $offset);
        
        // Contar total para paginação
        $totalItems = count($this->quotationManager->listQuotations($filters, PHP_INT_MAX, 0));
        $totalPages = ceil($totalItems / $limit);
        
        // Obter materiais disponíveis para filtro
        $quotationCalculator = new QuotationCalculator();
        $materials = $quotationCalculator->getAvailableMaterials();
        
        // Renderizar a página de listagem
        $this->render('customer_quotation/list', [
            'pageTitle' => 'Minhas Cotações',
            'csrfToken' => CsrfProtection::getToken(),
            'quotations' => $quotations,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filters' => [
                'material' => $materialFilter,
                'date_from' => $dateFromFilter,
                'date_to' => $dateToFilter
            ],
            'materials' => $materials
        ]);
    }
    
    /**
     * Exibe a página de confirmação de ordem de serviço a partir de uma cotação
     * 
     * @param int $id ID da cotação
     */
    public function confirmOrder($id) {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para confirmar pedidos.');
            $this->redirect('/login');
            return;
        }
        
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('error', 'ID de cotação inválido.');
            $this->redirect('/customer-quotation/my');
            return;
        }
        
        // Obter detalhes da cotação
        $quotation = $this->quotationManager->getQuotation($id);
        
        if (!$quotation) {
            $this->setFlashMessage('error', 'Cotação não encontrada.');
            $this->redirect('/customer-quotation/my');
            return;
        }
        
        // Verificar permissão (usuário só pode confirmar suas próprias cotações)
        $userId = $_SESSION['user_id'];
        if ($quotation['user_id'] != $userId) {
            $this->setFlashMessage('error', 'Você não tem permissão para confirmar esta cotação.');
            $this->redirect('/customer-quotation/my');
            return;
        }
        
        // Obter dados do modelo
        $model = $this->customerModelModel->getModelById($quotation['model_id']);
        
        // Renderizar a página de confirmação
        $this->render('customer_quotation/confirm', [
            'pageTitle' => 'Confirmar Pedido',
            'csrfToken' => CsrfProtection::getToken(),
            'quotation' => $quotation,
            'model' => $model
        ]);
    }
    
    /**
     * Processa a confirmação da ordem de serviço
     */
    public function processOrder() {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para confirmar pedidos.');
            $this->redirect('/login');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/customer-quotation/my');
            return;
        }
        
        // Obter parâmetros do formulário
        $quotationId = $this->postValidatedParam('quotation_id', 'int', ['required' => true, 'min' => 1]);
        $terms = $this->postValidatedParam('terms', 'bool', ['required' => true]);
        
        // Verificar aceitação dos termos
        if (!$terms) {
            $this->setFlashMessage('error', 'Você precisa aceitar os termos de serviço para confirmar o pedido.');
            $this->redirect('/customer-quotation/confirm/' . $quotationId);
            return;
        }
        
        // Verificar se a cotação existe
        $quotation = $this->quotationManager->getQuotation($quotationId);
        
        if (!$quotation) {
            $this->setFlashMessage('error', 'Cotação não encontrada.');
            $this->redirect('/customer-quotation/my');
            return;
        }
        
        // Verificar permissão (usuário só pode confirmar suas próprias cotações)
        $userId = $_SESSION['user_id'];
        if ($quotation['user_id'] != $userId) {
            $this->setFlashMessage('error', 'Você não tem permissão para confirmar esta cotação.');
            $this->redirect('/customer-quotation/my');
            return;
        }
        
        // TODO: Criar ordem de serviço baseada na cotação e redirecionar para página de pagamento
        // Por enquanto, apenas exibir mensagem de sucesso
        
        $this->setFlashMessage('success', 'Pedido confirmado com sucesso. Em breve você receberá instruções para pagamento.');
        $this->redirect('/customer-quotation/my');
    }
    
    /**
     * Gera uma cotação rápida (simulação) para um modelo
     * 
     * @param int $modelId ID do modelo
     */
    public function quickQuote($modelId) {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para solicitar cotações.');
            $this->redirect('/login');
            return;
        }
        
        // Validar ID
        $modelId = intval($modelId);
        if ($modelId <= 0) {
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($modelId);
        
        if (!$model || $model['status'] !== 'approved') {
            $this->redirect('/customer-models/list');
            return;
        }
        
        // Gerar cotação rápida com PLA padrão
        $quotation = $this->quotationManager->generateQuotation($modelId, QuotationCalculator::MATERIAL_PLA, [
            'urgent' => false,
            'save' => false // Não salvar no banco
        ]);
        
        if (isset($quotation['error'])) {
            echo json_encode(['error' => $quotation['error']]);
            exit;
        }
        
        // Retornar apenas informações essenciais
        $result = [
            'success' => true,
            'total_cost' => $quotation['total_cost'],
            'material' => $quotation['material'],
            'delivery_days' => $quotation['estimated_delivery_days'],
            'complexity_level' => $quotation['complexity_level']
        ];
        
        // Retornar como JSON
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
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