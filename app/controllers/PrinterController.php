<?php
/**
 * PrinterController - Gerencia as impressoras para o sistema de fila de impressão
 * 
 * @package    Taverna da Impressão 3D
 * @author     Claude
 * @version    1.0.0
 */

require_once __DIR__ . '/../lib/Controller.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Security/CsrfProtection.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../models/PrinterModel.php';
require_once __DIR__ . '/../models/PrintJobModel.php';

class PrinterController extends Controller {
    use InputValidationTrait;
    
    private $printerModel;
    private $printJobModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        $this->printerModel = new PrinterModel();
        $this->printJobModel = new PrintJobModel();
        
        // Verificar autenticação e permissão de administrador
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para acessar esta página.');
            $this->redirect('/login');
            return;
        }
        
        // Verificar se o usuário é administrador
        if (!$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar esta área.');
            $this->redirect('/');
            return;
        }
    }
    
    /**
     * Lista todas as impressoras
     */
    public function index() {
        // Obter filtros da URL
        $status = $this->getValidatedParam('status', 'string', [
            'required' => false, 
            'allowedValues' => ['available', 'busy', 'maintenance', 'offline']
        ]);
        
        // Preparar filtros
        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }
        
        // Obter todas as impressoras
        $printers = $this->printerModel->getAllPrinters($filters);
        
        // Obter estatísticas de impressoras
        $statistics = $this->printerModel->getPrinterStatistics();
        
        // Renderizar a view
        $this->render('admin/printers/index', [
            'pageTitle' => 'Gerenciamento de Impressoras',
            'printers' => $printers,
            'statistics' => $statistics,
            'currentFilters' => $filters,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Exibe o formulário para adicionar uma nova impressora
     */
    public function add() {
        $this->render('admin/printers/add', [
            'pageTitle' => 'Adicionar Nova Impressora',
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Processa o formulário para adicionar uma nova impressora
     */
    public function create() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/printers');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/printers/add');
            return;
        }
        
        // Obter e validar parâmetros
        $name = $this->postValidatedParam('name', 'string', [
            'required' => true,
            'maxLength' => 100
        ]);
        
        $model = $this->postValidatedParam('model', 'string', [
            'required' => true,
            'maxLength' => 100
        ]);
        
        $notes = $this->postValidatedParam('notes', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 1000
        ]);
        
        // Capacidades da impressora
        $capabilities = [
            'max_width' => $this->postValidatedParam('max_width', 'float', [
                'required' => true,
                'min' => 1,
                'max' => 1000
            ]),
            'max_depth' => $this->postValidatedParam('max_depth', 'float', [
                'required' => true,
                'min' => 1,
                'max' => 1000
            ]),
            'max_height' => $this->postValidatedParam('max_height', 'float', [
                'required' => true,
                'min' => 1,
                'max' => 1000
            ]),
            'materials' => $this->postValidatedParam('materials', 'array', [
                'required' => true,
                'allowedValues' => ['PLA', 'ABS', 'PETG', 'TPU', 'Nylon']
            ]),
            'min_layer_height' => $this->postValidatedParam('min_layer_height', 'float', [
                'required' => false,
                'default' => 0.1,
                'min' => 0.01,
                'max' => 0.5
            ]),
            'max_layer_height' => $this->postValidatedParam('max_layer_height', 'float', [
                'required' => false,
                'default' => 0.3,
                'min' => 0.1,
                'max' => 1.0
            ]),
            'nozzle_diameter' => $this->postValidatedParam('nozzle_diameter', 'float', [
                'required' => false,
                'default' => 0.4,
                'min' => 0.1,
                'max' => 1.0
            ]),
            'heated_bed' => $this->postValidatedParam('heated_bed', 'boolean', [
                'required' => false,
                'default' => true
            ])
        ];
        
        // Criar a impressora
        $result = $this->printerModel->savePrinter($name, $model, $capabilities, $notes);
        
        if ($result) {
            $this->setFlashMessage('success', 'Impressora adicionada com sucesso.');
            $this->redirect('/printers');
        } else {
            $this->setFlashMessage('error', 'Não foi possível adicionar a impressora.');
            $this->redirect('/printers/add');
        }
    }
    
    /**
     * Exibe detalhes de uma impressora
     * 
     * @param int $id ID da impressora
     */
    public function details($id) {
        // Validar ID
        $id = (int)$id;
        
        // Obter informações da impressora
        $printer = $this->printerModel->getPrinterById($id);
        
        if (!$printer) {
            $this->setFlashMessage('error', 'Impressora não encontrada.');
            $this->redirect('/printers');
            return;
        }
        
        // Obter trabalhos associados a esta impressora
        $jobs = $this->printJobModel->getPrinterJobs($id);
        
        // Renderizar a view
        $this->render('admin/printers/details', [
            'pageTitle' => 'Detalhes da Impressora',
            'printer' => $printer,
            'jobs' => $jobs,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Exibe o formulário para editar uma impressora
     * 
     * @param int $id ID da impressora
     */
    public function edit($id) {
        // Validar ID
        $id = (int)$id;
        
        // Obter informações da impressora
        $printer = $this->printerModel->getPrinterById($id);
        
        if (!$printer) {
            $this->setFlashMessage('error', 'Impressora não encontrada.');
            $this->redirect('/printers');
            return;
        }
        
        // Renderizar a view
        $this->render('admin/printers/edit', [
            'pageTitle' => 'Editar Impressora',
            'printer' => $printer,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Processa o formulário para atualizar uma impressora
     */
    public function update() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/printers');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/printers');
            return;
        }
        
        // Obter ID da impressora
        $id = $this->postValidatedParam('id', 'integer', ['required' => true]);
        
        // Verificar se a impressora existe
        $printer = $this->printerModel->getPrinterById($id);
        if (!$printer) {
            $this->setFlashMessage('error', 'Impressora não encontrada.');
            $this->redirect('/printers');
            return;
        }
        
        // Obter dados para atualização
        $data = [
            'name' => $this->postValidatedParam('name', 'string', [
                'required' => true,
                'maxLength' => 100
            ]),
            'model' => $this->postValidatedParam('model', 'string', [
                'required' => true,
                'maxLength' => 100
            ]),
            'notes' => $this->postValidatedParam('notes', 'string', [
                'required' => false,
                'maxLength' => 1000
            ])
        ];
        
        // Status só pode ser alterado se a impressora não estiver ocupada
        if ($printer['status'] !== 'busy') {
            $data['status'] = $this->postValidatedParam('status', 'string', [
                'required' => true,
                'allowedValues' => ['available', 'maintenance', 'offline']
            ]);
        }
        
        // Capacidades da impressora
        $capabilities = [
            'max_width' => $this->postValidatedParam('max_width', 'float', [
                'required' => true,
                'min' => 1,
                'max' => 1000
            ]),
            'max_depth' => $this->postValidatedParam('max_depth', 'float', [
                'required' => true,
                'min' => 1,
                'max' => 1000
            ]),
            'max_height' => $this->postValidatedParam('max_height', 'float', [
                'required' => true,
                'min' => 1,
                'max' => 1000
            ]),
            'materials' => $this->postValidatedParam('materials', 'array', [
                'required' => true,
                'allowedValues' => ['PLA', 'ABS', 'PETG', 'TPU', 'Nylon']
            ]),
            'min_layer_height' => $this->postValidatedParam('min_layer_height', 'float', [
                'required' => false,
                'default' => 0.1,
                'min' => 0.01,
                'max' => 0.5
            ]),
            'max_layer_height' => $this->postValidatedParam('max_layer_height', 'float', [
                'required' => false,
                'default' => 0.3,
                'min' => 0.1,
                'max' => 1.0
            ]),
            'nozzle_diameter' => $this->postValidatedParam('nozzle_diameter', 'float', [
                'required' => false,
                'default' => 0.4,
                'min' => 0.1,
                'max' => 1.0
            ]),
            'heated_bed' => $this->postValidatedParam('heated_bed', 'boolean', [
                'required' => false,
                'default' => true
            ])
        ];
        
        $data['capabilities'] = $capabilities;
        
        // Atualizar a impressora
        $result = $this->printerModel->updatePrinter($id, $data);
        
        if ($result) {
            $this->setFlashMessage('success', 'Impressora atualizada com sucesso.');
            $this->redirect('/printers/details/' . $id);
        } else {
            $this->setFlashMessage('error', 'Não foi possível atualizar a impressora.');
            $this->redirect('/printers/edit/' . $id);
        }
    }
    
    /**
     * Atualiza o status de uma impressora
     */
    public function updateStatus() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/printers');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/printers');
            return;
        }
        
        // Obter parâmetros
        $id = $this->postValidatedParam('id', 'integer', ['required' => true]);
        $status = $this->postValidatedParam('status', 'string', [
            'required' => true,
            'allowedValues' => ['available', 'maintenance', 'offline']
        ]);
        
        // Verificar se a impressora existe
        $printer = $this->printerModel->getPrinterById($id);
        if (!$printer) {
            $this->setFlashMessage('error', 'Impressora não encontrada.');
            $this->redirect('/printers');
            return;
        }
        
        // Não permitir alteração se a impressora estiver ocupada
        if ($printer['status'] === 'busy') {
            $this->setFlashMessage('error', 'Não é possível alterar o status de uma impressora que está ocupada.');
            $this->redirect('/printers/details/' . $id);
            return;
        }
        
        // Atualizar status
        $result = $this->printerModel->updateStatus($id, $status);
        
        if ($result) {
            $this->setFlashMessage('success', 'Status da impressora atualizado com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível atualizar o status da impressora.');
        }
        
        $this->redirect('/printers/details/' . $id);
    }
    
    /**
     * Registra uma manutenção para uma impressora
     */
    public function registerMaintenance() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/printers');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/printers');
            return;
        }
        
        // Obter parâmetros
        $id = $this->postValidatedParam('id', 'integer', ['required' => true]);
        $notes = $this->postValidatedParam('maintenance_notes', 'string', [
            'required' => true,
            'maxLength' => 1000
        ]);
        
        // Verificar se a impressora existe
        $printer = $this->printerModel->getPrinterById($id);
        if (!$printer) {
            $this->setFlashMessage('error', 'Impressora não encontrada.');
            $this->redirect('/printers');
            return;
        }
        
        // Não permitir manutenção se a impressora estiver ocupada
        if ($printer['status'] === 'busy') {
            $this->setFlashMessage('error', 'Não é possível registrar manutenção para uma impressora que está ocupada.');
            $this->redirect('/printers/details/' . $id);
            return;
        }
        
        // Registrar manutenção
        $result = $this->printerModel->registerMaintenance($id, $notes);
        
        if ($result) {
            $this->setFlashMessage('success', 'Manutenção registrada com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível registrar a manutenção.');
        }
        
        $this->redirect('/printers/details/' . $id);
    }
    
    /**
     * Exclui uma impressora
     */
    public function delete() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/printers');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/printers');
            return;
        }
        
        // Obter ID da impressora
        $id = $this->postValidatedParam('id', 'integer', ['required' => true]);
        
        // Verificar se a impressora existe
        $printer = $this->printerModel->getPrinterById($id);
        if (!$printer) {
            $this->setFlashMessage('error', 'Impressora não encontrada.');
            $this->redirect('/printers');
            return;
        }
        
        // Não permitir exclusão se a impressora estiver ocupada
        if ($printer['status'] === 'busy') {
            $this->setFlashMessage('error', 'Não é possível excluir uma impressora que está ocupada.');
            $this->redirect('/printers');
            return;
        }
        
        // Excluir a impressora
        $result = $this->printerModel->deletePrinter($id);
        
        if ($result) {
            $this->setFlashMessage('success', 'Impressora excluída com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível excluir a impressora.');
        }
        
        $this->redirect('/printers');
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
