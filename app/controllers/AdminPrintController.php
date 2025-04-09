<?php
/**
 * AdminPrintController - Painel administrativo centralizado para o sistema de impressão 3D
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
require_once __DIR__ . '/../models/PrinterModel.php';
require_once __DIR__ . '/../models/PrintJobModel.php';
require_once __DIR__ . '/../models/CustomerModelModel.php';

class AdminPrintController extends Controller {
    use InputValidationTrait;
    
    private $printQueueModel;
    private $printerModel;
    private $printJobModel;
    private $customerModelModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        $this->printQueueModel = new PrintQueueModel();
        $this->printerModel = new PrinterModel();
        $this->printJobModel = new PrintJobModel();
        $this->customerModelModel = new CustomerModelModel();
        
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
     * Dashboard principal do sistema de impressão
     */
    public function dashboard() {
        // Obter estatísticas dos modelos
        $queueStats = $this->printQueueModel->getQueueStatistics();
        
        // Obter estatísticas das impressoras
        $printerStats = $this->printerModel->getPrinterStatistics();
        
        // Obter estatísticas de trabalhos
        $jobStats = $this->printJobModel->getJobStatistics();
        
        // Obter trabalhos ativos
        $activeJobs = $this->printJobModel->getCurrentJobs();
        
        // Obter itens pendentes na fila
        $pendingQueueItems = $this->printQueueModel->getPendingQueueItems();
        
        // Obter impressoras disponíveis
        $availablePrinters = $this->printerModel->getAvailablePrinters();
        
        // Renderizar a view
        $this->render('admin/print_system/dashboard', [
            'pageTitle' => 'Dashboard do Sistema de Impressão 3D',
            'queueStats' => $queueStats,
            'printerStats' => $printerStats,
            'jobStats' => $jobStats,
            'activeJobs' => $activeJobs,
            'pendingQueueItems' => $pendingQueueItems,
            'availablePrinters' => $availablePrinters,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Gerenciamento da fila de impressão
     */
    public function queueManagement() {
        // Obter filtros da URL
        $status = $this->getValidatedParam('status', 'string', [
            'required' => false, 
            'allowedValues' => ['pending', 'assigned', 'printing', 'completed', 'cancelled', 'failed']
        ]);
        
        // Preparar filtros
        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }
        
        // Obter itens da fila
        $queueItems = $this->printQueueModel->getAllQueueItems($filters);
        
        // Obter estatísticas da fila
        $statistics = $this->printQueueModel->getQueueStatistics();
        
        // Renderizar a view
        $this->render('admin/print_system/queue_management', [
            'pageTitle' => 'Gerenciamento da Fila de Impressão',
            'queueItems' => $queueItems,
            'statistics' => $statistics,
            'currentFilters' => $filters,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Gerenciamento de impressoras
     */
    public function printerManagement() {
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
        $this->render('admin/print_system/printer_management', [
            'pageTitle' => 'Gerenciamento de Impressoras',
            'printers' => $printers,
            'statistics' => $statistics,
            'currentFilters' => $filters,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Gerenciamento de trabalhos de impressão
     */
    public function jobManagement() {
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
        
        // Obter trabalhos ativos
        $activeJobs = $this->printJobModel->getCurrentJobs();
        
        // Obter impressoras para o filtro
        $printers = $this->printerModel->getAllPrinters();
        
        // Obter estatísticas de trabalhos
        $statistics = $this->printJobModel->getJobStatistics();
        
        // Renderizar a view
        $this->render('admin/print_system/job_management', [
            'pageTitle' => 'Gerenciamento de Trabalhos de Impressão',
            'activeJobs' => $activeJobs,
            'printers' => $printers,
            'statistics' => $statistics,
            'currentFilters' => $filters,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Exibe relatórios e estatísticas do sistema de impressão
     */
    public function statistics() {
        // Obter filtros da URL
        $startDate = $this->getValidatedParam('start_date', 'string', [
            'required' => false,
            'default' => date('Y-m-d', strtotime('-30 days'))
        ]);
        
        $endDate = $this->getValidatedParam('end_date', 'string', [
            'required' => false,
            'default' => date('Y-m-d')
        ]);
        
        // Validar datas
        if (!$this->isValidDate($startDate)) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        
        if (!$this->isValidDate($endDate)) {
            $endDate = date('Y-m-d');
        }
        
        // Garantir que a data de início seja anterior à data de fim
        if (strtotime($startDate) > strtotime($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }
        
        // Obter estatísticas da fila
        $queueStats = $this->printQueueModel->getQueueStatistics();
        
        // Obter estatísticas de impressoras
        $printerStats = $this->printerModel->getPrinterStatistics();
        
        // Obter estatísticas de trabalhos
        $jobStats = $this->printJobModel->getJobStatistics();
        
        // Obter dados para gráficos (implementar métodos correspondentes)
        $timeSeriesData = []; // Implementar obtenção de dados de série temporal
        
        // Renderizar a view
        $this->render('admin/print_system/statistics', [
            'pageTitle' => 'Estatísticas do Sistema de Impressão',
            'queueStats' => $queueStats,
            'printerStats' => $printerStats,
            'jobStats' => $jobStats,
            'timeSeriesData' => $timeSeriesData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Configura parâmetros do sistema de impressão
     */
    public function settings() {
        // Se for uma requisição POST, processar atualizações
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
            if (!CsrfProtection::validateToken($csrfToken)) {
                $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
                $this->redirect('/admin/print-system/settings');
                return;
            }
            
            // Processar cada configuração
            $defaultPriority = $this->postValidatedParam('default_priority', 'integer', [
                'required' => true,
                'min' => 1,
                'max' => 10
            ]);
            $this->printQueueModel->updateSetting('default_priority', (string)$defaultPriority);
            
            $notifyOnStatusChange = $this->postValidatedParam('notify_on_status_change', 'boolean', [
                'required' => true
            ]);
            $this->printQueueModel->updateSetting('notify_on_status_change', $notifyOnStatusChange ? 'true' : 'false');
            
            $notifyOnCompletion = $this->postValidatedParam('notify_on_completion', 'boolean', [
                'required' => true
            ]);
            $this->printQueueModel->updateSetting('notify_on_completion', $notifyOnCompletion ? 'true' : 'false');
            
            $notifyOnFailure = $this->postValidatedParam('notify_on_failure', 'boolean', [
                'required' => true
            ]);
            $this->printQueueModel->updateSetting('notify_on_failure', $notifyOnFailure ? 'true' : 'false');
            
            $estimatedTimeBuffer = $this->postValidatedParam('estimated_time_buffer', 'integer', [
                'required' => true,
                'min' => 0,
                'max' => 100
            ]);
            $this->printQueueModel->updateSetting('estimated_time_buffer', (string)$estimatedTimeBuffer);
            
            $maxQueueItemsPerUser = $this->postValidatedParam('max_queue_items_per_user', 'integer', [
                'required' => true,
                'min' => 1,
                'max' => 100
            ]);
            $this->printQueueModel->updateSetting('max_queue_items_per_user', (string)$maxQueueItemsPerUser);
            
            $this->setFlashMessage('success', 'Configurações atualizadas com sucesso.');
            $this->redirect('/admin/print-system/settings');
            return;
        }
        
        // Obter configurações atuais
        $settings = $this->printQueueModel->getSettings();
        
        // Renderizar a view
        $this->render('admin/print_system/settings', [
            'pageTitle' => 'Configurações do Sistema de Impressão',
            'settings' => $settings,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Gerencia a atribuição de trabalhos em lote
     */
    public function batchAssignment() {
        // Obter itens pendentes na fila
        $pendingQueueItems = $this->printQueueModel->getPendingQueueItems();
        
        // Obter impressoras disponíveis
        $availablePrinters = $this->printerModel->getAvailablePrinters();
        
        // Se for uma requisição POST, processar atribuições em lote
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
            if (!CsrfProtection::validateToken($csrfToken)) {
                $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
                $this->redirect('/admin/print-system/batch-assignment');
                return;
            }
            
            // Obter atribuições
            $assignments = $this->postValidatedParam('assignments', 'array', ['required' => true]);
            
            // Processar cada atribuição
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($assignments as $assignment) {
                // Validar dados da atribuição
                if (!isset($assignment['queue_id']) || !isset($assignment['printer_id'])) {
                    $errorCount++;
                    continue;
                }
                
                $queueId = (int)$assignment['queue_id'];
                $printerId = (int)$assignment['printer_id'];
                $notes = isset($assignment['notes']) ? $assignment['notes'] : '';
                
                // Verificar se já existe um trabalho para este item
                $existingJob = $this->printJobModel->getJobByQueueId($queueId);
                if ($existingJob) {
                    $errorCount++;
                    continue;
                }
                
                // Criar o trabalho
                $result = $this->printJobModel->saveJob($queueId, $printerId, null, $notes);
                
                if ($result) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
            
            // Retornar mensagem de resultado
            if ($successCount > 0) {
                $message = $successCount . ' trabalho(s) atribuído(s) com sucesso.';
                if ($errorCount > 0) {
                    $message .= ' ' . $errorCount . ' erro(s) encontrado(s).';
                }
                $this->setFlashMessage('success', $message);
            } else {
                $this->setFlashMessage('error', 'Nenhum trabalho foi atribuído. Verifique os dados e tente novamente.');
            }
            
            $this->redirect('/admin/print-system/batch-assignment');
            return;
        }
        
        // Renderizar a view
        $this->render('admin/print_system/batch_assignment', [
            'pageTitle' => 'Atribuição em Lote',
            'pendingQueueItems' => $pendingQueueItems,
            'availablePrinters' => $availablePrinters,
            'csrfToken' => CsrfProtection::getToken()
        ]);
    }
    
    /**
     * Verifica se uma string é uma data válida no formato Y-m-d
     *
     * @param string $date Data a ser validada
     * @return bool True se for uma data válida
     */
    private function isValidDate($date) {
        if (!$date) {
            return false;
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
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
