<?php
/**
 * PrintJobController - Gerencia os trabalhos de impressão 3D
 * 
 * @package    Taverna da Impressão 3D
 * @author     Claude
 * @version    1.0.0
 */

require_once __DIR__ . '/../lib/Controller.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Security/CsrfProtection.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../models/PrintJobModel.php';
require_once __DIR__ . '/../models/PrinterModel.php';
require_once __DIR__ . '/../models/PrintQueueModel.php';

class PrintJobController extends Controller {
    use InputValidationTrait;
    
    private $printJobModel;
    private $printerModel;
    private $printQueueModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        $this->printJobModel = new PrintJobModel();
        $this->printerModel = new PrinterModel();
        $this->printQueueModel = new PrintQueueModel();
        
        // Verificar autenticação
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para acessar esta página.');
            $this->redirect('/login');
            return;
        }
        
        // Verificar permissão para ações administrativas
        $adminActions = ['index', 'assign', 'start', 'updateProgress', 'complete', 'fail'];
        $currentAction = isset($_GET['action']) ? $this->validateString($_GET['action']) : 'index';
        
        if (in_array($currentAction, $adminActions) && !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar esta funcionalidade.');
            $this->redirect('/');
            return;
        }
    }
    
    /**
     * Lista todos os trabalhos de impressão (admin)
     */
    public function index() {
        // Obter filtros da URL
        $status = $this->getValidatedParam('status', 'string', [
            'required' => false, 
            'allowedValues' => ['pending', 'preparing', 'printing', 'post-processing', 'completed', 'failed']
        ]);
        
        $printerId = $this->getValidatedParam('printer_id', 'integer', ['required' => false]);
        
        // Preparar filtros
        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }
        if ($printerId) {
            $filters['printer_id'] = $printerId;
        }
        
        // Obter trabalhos com os filtros aplicados
        // Implementar método adequado no modelo
        $jobs = []; // Temporário
        
        // Obter todas as impressoras para o filtro
        $printers = $this->printerModel->getAllPrinters();
        
        // Obter estatísticas de trabalhos
        $statistics = $this->printJobModel->getJobStatistics();
        
        // Renderizar a view
        $this->render('admin/print_jobs/index', [
            'pageTitle' => 'Gerenciamento de Trabalhos de Impressão',
            'jobs' => $jobs,
            'printers' => $printers,
            'statistics' => $statistics,
            'currentFilters' => $filters,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Lista os trabalhos de impressão do usuário atual
     */
    public function userJobs() {
        // Obter ID do usuário atual
        $userId = $_SESSION['user_id'];
        
        // Obter filtros da URL
        $status = $this->getValidatedParam('status', 'string', [
            'required' => false, 
            'allowedValues' => ['pending', 'preparing', 'printing', 'post-processing', 'completed', 'failed']
        ]);
        
        // Obter trabalhos do usuário
        $jobs = $this->printJobModel->getUserJobs($userId, $status);
        
        // Renderizar a view
        $this->render('customer/print_jobs/index', [
            'pageTitle' => 'Meus Trabalhos de Impressão',
            'jobs' => $jobs,
            'currentStatus' => $status
        ]);
    }
    
    /**
     * Exibe detalhes de um trabalho de impressão
     * 
     * @param int $id ID do trabalho
     */
    public function details($id) {
        // Validar ID
        $id = (int)$id;
        
        // Obter trabalho
        $job = $this->printJobModel->getJobById($id);
        
        if (!$job) {
            $this->setFlashMessage('error', 'Trabalho de impressão não encontrado.');
            $this->redirect($this->isAdmin() ? '/print-jobs' : '/user/print-jobs');
            return;
        }
        
        // Verificar permissão (proprietário ou admin)
        if ($job['user_id'] != $_SESSION['user_id'] && !$this->isAdmin()) {
            $this->setFlashMessage('error', 'Você não tem permissão para visualizar este trabalho.');
            $this->redirect('/user/print-jobs');
            return;
        }
        
        // Obter item da fila relacionado
        $queueItem = $this->printQueueModel->getQueueItemById($job['queue_id']);
        
        // Obter impressora
        $printer = $this->printerModel->getPrinterById($job['printer_id']);
        
        // Renderizar a view
        $this->render($this->isAdmin() ? 'admin/print_jobs/details' : 'customer/print_jobs/details', [
            'pageTitle' => 'Detalhes do Trabalho de Impressão',
            'job' => $job,
            'queueItem' => $queueItem,
            'printer' => $printer,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Atribui um item da fila a uma impressora (admin)
     */
    public function assign() {
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
        $printerId = $this->postValidatedParam('printer_id', 'integer', ['required' => true]);
        $scheduledStartTime = $this->postValidatedParam('scheduled_start_time', 'string', ['required' => false]);
        $notes = $this->postValidatedParam('notes', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 1000
        ]);
        
        // Verificar se o item na fila existe
        $queueItem = $this->printQueueModel->getQueueItemById($queueId);
        if (!$queueItem) {
            $this->setFlashMessage('error', 'Item não encontrado na fila de impressão.');
            $this->redirect('/print-queue');
            return;
        }
        
        // Verificar se o item está pendente
        if ($queueItem['status'] !== 'pending') {
            $this->setFlashMessage('error', 'Apenas itens pendentes podem ser atribuídos a uma impressora.');
            $this->redirect('/print-queue/details/' . $queueId);
            return;
        }
        
        // Verificar se a impressora existe e está disponível
        $printer = $this->printerModel->getPrinterById($printerId);
        if (!$printer) {
            $this->setFlashMessage('error', 'Impressora não encontrada.');
            $this->redirect('/print-queue/details/' . $queueId);
            return;
        }
        
        if ($printer['status'] !== 'available') {
            $this->setFlashMessage('error', 'Esta impressora não está disponível no momento.');
            $this->redirect('/print-queue/details/' . $queueId);
            return;
        }
        
        // Criar trabalho de impressão
        $result = $this->printJobModel->saveJob($queueId, $printerId, $scheduledStartTime, $notes);
        
        if ($result) {
            $this->setFlashMessage('success', 'Trabalho de impressão criado com sucesso.');
            $this->redirect('/print-jobs/details/' . $result);
        } else {
            $this->setFlashMessage('error', 'Não foi possível criar o trabalho de impressão.');
            $this->redirect('/print-queue/details/' . $queueId);
        }
    }
    
    /**
     * Inicia um trabalho de impressão (admin)
     */
    public function start() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/print-jobs');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/print-jobs');
            return;
        }
        
        // Obter parâmetros
        $jobId = $this->postValidatedParam('job_id', 'integer', ['required' => true]);
        $notes = $this->postValidatedParam('notes', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 1000
        ]);
        
        // Verificar se o trabalho existe
        $job = $this->printJobModel->getJobById($jobId);
        if (!$job) {
            $this->setFlashMessage('error', 'Trabalho de impressão não encontrado.');
            $this->redirect('/print-jobs');
            return;
        }
        
        // Verificar se o trabalho está pendente ou em preparação
        if ($job['status'] !== 'pending' && $job['status'] !== 'preparing') {
            $this->setFlashMessage('error', 'Este trabalho não pode ser iniciado porque já está em andamento ou concluído.');
            $this->redirect('/print-jobs/details/' . $jobId);
            return;
        }
        
        // Iniciar o trabalho
        $result = $this->printJobModel->updateStatus($jobId, 'printing', $notes);
        
        if ($result) {
            $this->setFlashMessage('success', 'Trabalho de impressão iniciado com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível iniciar o trabalho de impressão.');
        }
        
        $this->redirect('/print-jobs/details/' . $jobId);
    }
    
    /**
     * Atualiza o progresso de um trabalho de impressão (admin)
     */
    public function updateProgress() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/print-jobs');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/print-jobs');
            return;
        }
        
        // Obter parâmetros
        $jobId = $this->postValidatedParam('job_id', 'integer', ['required' => true]);
        $progress = $this->postValidatedParam('progress', 'float', [
            'required' => true,
            'min' => 0,
            'max' => 100
        ]);
        
        // Verificar se o trabalho existe
        $job = $this->printJobModel->getJobById($jobId);
        if (!$job) {
            $this->setFlashMessage('error', 'Trabalho de impressão não encontrado.');
            $this->redirect('/print-jobs');
            return;
        }
        
        // Verificar se o trabalho está em andamento
        if ($job['status'] !== 'printing') {
            $this->setFlashMessage('error', 'Este trabalho não está em impressão no momento.');
            $this->redirect('/print-jobs/details/' . $jobId);
            return;
        }
        
        // Atualizar o progresso
        $result = $this->printJobModel->updateProgress($jobId, $progress);
        
        if ($result) {
            $this->setFlashMessage('success', 'Progresso atualizado com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível atualizar o progresso.');
        }
        
        $this->redirect('/print-jobs/details/' . $jobId);
    }
    
    /**
     * Marca um trabalho como concluído (admin)
     */
    public function complete() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/print-jobs');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/print-jobs');
            return;
        }
        
        // Obter parâmetros
        $jobId = $this->postValidatedParam('job_id', 'integer', ['required' => true]);
        $materialUsed = $this->postValidatedParam('material_used', 'float', [
            'required' => false,
            'min' => 0
        ]);
        $notes = $this->postValidatedParam('notes', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 1000
        ]);
        
        // Verificar se o trabalho existe
        $job = $this->printJobModel->getJobById($jobId);
        if (!$job) {
            $this->setFlashMessage('error', 'Trabalho de impressão não encontrado.');
            $this->redirect('/print-jobs');
            return;
        }
        
        // Verificar se o trabalho está em andamento
        if ($job['status'] !== 'printing' && $job['status'] !== 'post-processing') {
            $this->setFlashMessage('error', 'Este trabalho não pode ser marcado como concluído.');
            $this->redirect('/print-jobs/details/' . $jobId);
            return;
        }
        
        // Se foi informado o uso de material, atualizá-lo
        if ($materialUsed !== null) {
            $this->printJobModel->setMaterialUsed($jobId, $materialUsed);
        }
        
        // Marcar o trabalho como concluído
        $result = $this->printJobModel->updateStatus($jobId, 'completed', $notes);
        
        if ($result) {
            $this->setFlashMessage('success', 'Trabalho de impressão concluído com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível concluir o trabalho de impressão.');
        }
        
        $this->redirect('/print-jobs/details/' . $jobId);
    }
    
    /**
     * Marca um trabalho como falho (admin)
     */
    public function fail() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/print-jobs');
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!CsrfProtection::validateToken($csrfToken)) {
            $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
            $this->redirect('/print-jobs');
            return;
        }
        
        // Obter parâmetros
        $jobId = $this->postValidatedParam('job_id', 'integer', ['required' => true]);
        $reason = $this->postValidatedParam('reason', 'string', [
            'required' => true,
            'maxLength' => 1000
        ]);
        
        // Verificar se o trabalho existe
        $job = $this->printJobModel->getJobById($jobId);
        if (!$job) {
            $this->setFlashMessage('error', 'Trabalho de impressão não encontrado.');
            $this->redirect('/print-jobs');
            return;
        }
        
        // Verificar se o trabalho está em andamento
        if ($job['status'] === 'completed' || $job['status'] === 'failed') {
            $this->setFlashMessage('error', 'Este trabalho não pode ser marcado como falho.');
            $this->redirect('/print-jobs/details/' . $jobId);
            return;
        }
        
        // Marcar o trabalho como falho
        $result = $this->printJobModel->updateStatus($jobId, 'failed', $reason);
        
        if ($result) {
            $this->setFlashMessage('success', 'Trabalho de impressão marcado como falho.');
        } else {
            $this->setFlashMessage('error', 'Não foi possível marcar o trabalho como falho.');
        }
        
        $this->redirect('/print-jobs/details/' . $jobId);
    }
    
    /**
     * Exibe o dashboard de trabalhos em andamento (admin)
     */
    public function dashboard() {
        // Obter trabalhos em andamento
        $activeJobs = $this->printJobModel->getCurrentJobs();
        
        // Obter estatísticas
        $statistics = $this->printJobModel->getJobStatistics();
        
        // Renderizar a view
        $this->render('admin/print_jobs/dashboard', [
            'pageTitle' => 'Dashboard de Impressão',
            'activeJobs' => $activeJobs,
            'statistics' => $statistics,
            'csrfToken' => CsrfProtection::getToken()
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
