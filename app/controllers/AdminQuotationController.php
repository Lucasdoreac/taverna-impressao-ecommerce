<?php
/**
 * AdminQuotationController - Controller para administração do sistema de cotação
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
require_once __DIR__ . '/../lib/Analysis/ModelComplexityAnalyzer.php';
require_once __DIR__ . '/../lib/Analysis/QuotationCalculator.php';

class AdminQuotationController extends Controller {
    use InputValidationTrait;
    
    /**
     * Gerenciador de cotações
     * @var QuotationManager
     */
    private $quotationManager;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        $this->quotationManager = new QuotationManager();
        
        // Verificar se o usuário é administrador antes de executar qualquer método
        $this->checkAdminAccess();
    }
    
    /**
     * Exibe o painel de gerenciamento de cotações
     */
    public function index() {
        // Obter estatísticas de cotações
        $statistics = $this->quotationManager->getQuotationStatistics();
        
        // Obter últimas cotações geradas (máximo 10)
        $latestQuotations = $this->quotationManager->listQuotations([], 10, 0);
        
        // Obter parâmetros de cotação atuais
        $quotationCalculator = new QuotationCalculator();
        $parameters = $quotationCalculator->getParameters();
        
        // Obter materiais disponíveis
        $materials = $quotationCalculator->getAvailableMaterials();
        
        // Renderizar a página de dashboard
        $this->render('admin/quotation/dashboard', [
            'pageTitle' => 'Gerenciamento de Cotações',
            'csrfToken' => CsrfProtection::getToken(),
            'statistics' => $statistics,
            'latestQuotations' => $latestQuotations,
            'parameters' => $parameters,
            'materials' => $materials
        ]);
    }
    
    /**
     * Lista cotações com suporte a filtros e paginação
     */
    public function listQuotations() {
        // Obter parâmetros de filtro
        $filters = [
            'user_id' => $this->getValidatedParam('user_id', 'int', ['required' => false]),
            'model_id' => $this->getValidatedParam('model_id', 'int', ['required' => false]),
            'material' => $this->getValidatedParam('material', 'string', ['required' => false]),
            'min_cost' => $this->getValidatedParam('min_cost', 'float', ['required' => false]),
            'max_cost' => $this->getValidatedParam('max_cost', 'float', ['required' => false]),
            'date_from' => $this->getValidatedParam('date_from', 'string', ['required' => false]),
            'date_to' => $this->getValidatedParam('date_to', 'string', ['required' => false])
        ];
        
        // Obter paginação
        $page = $this->getValidatedParam('page', 'int', ['required' => false, 'default' => 1, 'min' => 1]);
        $limit = $this->getValidatedParam('limit', 'int', ['required' => false, 'default' => 20, 'min' => 1, 'max' => 100]);
        $offset = ($page - 1) * $limit;
        
        // Obter cotações
        $quotations = $this->quotationManager->listQuotations($filters, $limit, $offset);
        
        // Obter contagem total para paginação
        $totalItems = count($this->quotationManager->listQuotations($filters, PHP_INT_MAX, 0));
        $totalPages = ceil($totalItems / $limit);
        
        // Obter materiais disponíveis para filtro
        $quotationCalculator = new QuotationCalculator();
        $materials = $quotationCalculator->getAvailableMaterials();
        
        // Renderizar a página de listagem
        $this->render('admin/quotation/list', [
            'pageTitle' => 'Listagem de Cotações',
            'csrfToken' => CsrfProtection::getToken(),
            'quotations' => $quotations,
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'materials' => $materials
        ]);
    }
    
    /**
     * Exibe detalhes de uma cotação específica
     * 
     * @param int $id ID da cotação
     */
    public function viewQuotation($id) {
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('error', 'ID de cotação inválido');
            $this->redirect('/admin/quotation');
            return;
        }
        
        // Obter detalhes da cotação
        $quotation = $this->quotationManager->getQuotation($id);
        
        if (!$quotation) {
            $this->setFlashMessage('error', 'Cotação não encontrada');
            $this->redirect('/admin/quotation');
            return;
        }
        
        // Renderizar a página de detalhes
        $this->render('admin/quotation/view', [
            'pageTitle' => 'Detalhes da Cotação #' . $id,
            'csrfToken' => CsrfProtection::getToken(),
            'quotation' => $quotation
        ]);
    }
    
    /**
     * Exibe o formulário de configuração de parâmetros de cotação
     */
    public function configureParameters() {
        // Obter parâmetros atuais
        $quotationCalculator = new QuotationCalculator();
        $parameters = $quotationCalculator->getParameters();
        
        // Renderizar a página de configuração
        $this->render('admin/quotation/parameters', [
            'pageTitle' => 'Configuração de Parâmetros de Cotação',
            'csrfToken' => CsrfProtection::getToken(),
            'parameters' => $parameters,
            'complexityLevels' => [
                ModelComplexityAnalyzer::COMPLEXITY_SIMPLE => 'Simples',
                ModelComplexityAnalyzer::COMPLEXITY_MODERATE => 'Moderada',
                ModelComplexityAnalyzer::COMPLEXITY_COMPLEX => 'Complexa',
                ModelComplexityAnalyzer::COMPLEXITY_VERY_COMPLEX => 'Muito Complexa'
            ],
            'materialTypes' => [
                QuotationCalculator::MATERIAL_PLA => 'PLA',
                QuotationCalculator::MATERIAL_ABS => 'ABS',
                QuotationCalculator::MATERIAL_PETG => 'PETG',
                QuotationCalculator::MATERIAL_FLEX => 'Flexível',
                QuotationCalculator::MATERIAL_RESIN => 'Resina'
            ]
        ]);
    }
    
    /**
     * Processa a atualização dos parâmetros de cotação
     */
    public function updateParameters() {
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/admin/quotation/parameters');
            return;
        }
        
        // Coletar parâmetros do formulário
        $parameters = [
            'materialCosts' => [
                QuotationCalculator::MATERIAL_PLA => $this->postValidatedParam('material_cost_pla', 'float', ['required' => true, 'min' => 0.01]),
                QuotationCalculator::MATERIAL_ABS => $this->postValidatedParam('material_cost_abs', 'float', ['required' => true, 'min' => 0.01]),
                QuotationCalculator::MATERIAL_PETG => $this->postValidatedParam('material_cost_petg', 'float', ['required' => true, 'min' => 0.01]),
                QuotationCalculator::MATERIAL_FLEX => $this->postValidatedParam('material_cost_flex', 'float', ['required' => true, 'min' => 0.01]),
                QuotationCalculator::MATERIAL_RESIN => $this->postValidatedParam('material_cost_resin', 'float', ['required' => true, 'min' => 0.01])
            ],
            'machineHourlyCost' => $this->postValidatedParam('machine_hourly_cost', 'float', ['required' => true, 'min' => 0.01]),
            'complexityFactors' => [
                ModelComplexityAnalyzer::COMPLEXITY_SIMPLE => $this->postValidatedParam('complexity_factor_simple', 'float', ['required' => true, 'min' => 0.1]),
                ModelComplexityAnalyzer::COMPLEXITY_MODERATE => $this->postValidatedParam('complexity_factor_moderate', 'float', ['required' => true, 'min' => 0.1]),
                ModelComplexityAnalyzer::COMPLEXITY_COMPLEX => $this->postValidatedParam('complexity_factor_complex', 'float', ['required' => true, 'min' => 0.1]),
                ModelComplexityAnalyzer::COMPLEXITY_VERY_COMPLEX => $this->postValidatedParam('complexity_factor_very_complex', 'float', ['required' => true, 'min' => 0.1])
            ],
            'baseMarkupPercentage' => $this->postValidatedParam('base_markup_percentage', 'float', ['required' => true, 'min' => 0]),
            'minimumQuoteValue' => $this->postValidatedParam('minimum_quote_value', 'float', ['required' => true, 'min' => 0]),
            'riskRatePercentage' => $this->postValidatedParam('risk_rate_percentage', 'float', ['required' => true, 'min' => 0]),
            'urgencyFactor' => $this->postValidatedParam('urgency_factor', 'float', ['required' => true, 'min' => 1])
        ];
        
        // Verificar se algum dos campos está vazio
        $validationFailed = false;
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if ($subValue === null) {
                        $validationFailed = true;
                        break 2;
                    }
                }
            } else if ($value === null) {
                $validationFailed = true;
                break;
            }
        }
        
        if ($validationFailed) {
            $this->setFlashMessage('error', 'Todos os campos são obrigatórios e devem conter valores válidos.');
            $this->redirect('/admin/quotation/parameters');
            return;
        }
        
        // Salvar parâmetros
        $success = $this->quotationManager->saveQuotationParameters($parameters);
        
        if ($success) {
            $this->setFlashMessage('success', 'Parâmetros de cotação atualizados com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Erro ao atualizar parâmetros de cotação.');
        }
        
        $this->redirect('/admin/quotation/parameters');
    }
    
    /**
     * Exibe o formulário de geração de cotação de teste
     */
    public function testQuotation() {
        // Carregar modelos aprovados para teste
        $sql = "SELECT id, original_name FROM customer_models WHERE status = 'approved' ORDER BY created_at DESC";
        $models = $this->db->fetchAll($sql);
        
        // Obter materiais disponíveis
        $quotationCalculator = new QuotationCalculator();
        $materials = $quotationCalculator->getAvailableMaterials();
        
        // Renderizar a página de teste
        $this->render('admin/quotation/test', [
            'pageTitle' => 'Cotação de Teste',
            'csrfToken' => CsrfProtection::getToken(),
            'models' => $models,
            'materials' => $materials
        ]);
    }
    
    /**
     * Processa a geração de uma cotação de teste
     */
    public function generateTestQuotation() {
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/admin/quotation/test');
            return;
        }
        
        // Obter parâmetros do formulário
        $modelId = $this->postValidatedParam('model_id', 'int', ['required' => true, 'min' => 1]);
        $material = $this->postValidatedParam('material', 'string', ['required' => true]);
        $isUrgent = $this->postValidatedParam('is_urgent', 'bool', ['required' => false, 'default' => false]);
        
        // Validar material
        $quotationCalculator = new QuotationCalculator();
        $availableMaterials = array_keys($quotationCalculator->getAvailableMaterials());
        
        if (!in_array($material, $availableMaterials)) {
            $this->setFlashMessage('error', 'Material inválido.');
            $this->redirect('/admin/quotation/test');
            return;
        }
        
        // Gerar cotação
        $quotation = $this->quotationManager->generateQuotation($modelId, $material, [
            'urgent' => $isUrgent,
            'save' => true
        ]);
        
        if (isset($quotation['error'])) {
            $this->setFlashMessage('error', 'Erro ao gerar cotação: ' . $quotation['error']);
            $this->redirect('/admin/quotation/test');
            return;
        }
        
        // Redirecionar para a visualização da cotação
        $this->setFlashMessage('success', 'Cotação gerada com sucesso.');
        $this->redirect('/admin/quotation/view/' . $quotation['quotation_id']);
    }
    
    /**
     * Verifica se o usuário é administrador
     */
    private function checkAdminAccess() {
        // Verificar se o usuário está autenticado
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar autenticado para acessar esta área.');
            $this->redirect('/login');
            exit;
        }
        
        // Verificar se o usuário é administrador
        if (!$this->isAdmin()) {
            $this->setFlashMessage('error', 'Acesso restrito a administradores.');
            $this->redirect('/');
            exit;
        }
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