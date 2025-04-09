<?php
/**
 * ReportsController - Controlador para o sistema de relatórios
 * 
 * Este controlador gerencia a geração e exibição de relatórios sobre o sistema de impressão 3D,
 * incluindo estatísticas de fila, tempos de impressão e métricas de notificações.
 * 
 * @package App\Controllers
 * @version 1.0.0
 * @author Taverna da Impressão
 */
require_once dirname(__FILE__) . '/../lib/Controller.php';
require_once dirname(__FILE__) . '/../lib/Security/InputValidationTrait.php';
require_once dirname(__FILE__) . '/../lib/Security/SecurityManager.php';
require_once dirname(__FILE__) . '/../models/PrintQueueModel.php';
require_once dirname(__FILE__) . '/../models/PrintJobModel.php';
require_once dirname(__FILE__) . '/../models/PrinterModel.php';
require_once dirname(__FILE__) . '/../models/NotificationModel.php';
require_once dirname(__FILE__) . '/../models/NotificationPreferenceModel.php';

class ReportsController extends Controller {
    use InputValidationTrait;
    
    /**
     * Modelo de fila de impressão
     * 
     * @var PrintQueueModel
     */
    private $printQueueModel;
    
    /**
     * Modelo de trabalhos de impressão
     * 
     * @var PrintJobModel
     */
    private $printJobModel;
    
    /**
     * Modelo de impressoras
     * 
     * @var PrinterModel
     */
    private $printerModel;
    
    /**
     * Modelo de notificações
     * 
     * @var NotificationModel
     */
    private $notificationModel;
    
    /**
     * Modelo de preferências de notificação
     * 
     * @var NotificationPreferenceModel
     */
    private $notificationPreferenceModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        
        // Inicializar modelos
        $this->printQueueModel = new PrintQueueModel();
        $this->printJobModel = new PrintJobModel();
        $this->printerModel = new PrinterModel();
        $this->notificationModel = new NotificationModel();
        $this->notificationPreferenceModel = new NotificationPreferenceModel();
    }
    
    /**
     * Exibe a página principal de relatórios
     * 
     * @return void
     */
    public function index() {
        // Verificar permissões - apenas administradores
        if (!SecurityManager::hasAdminAccess()) {
            $this->redirect('/login', [
                'error' => 'Acesso não autorizado. Faça login como administrador.'
            ]);
            return;
        }
        
        // Configurar filtros a partir dos parâmetros GET
        $filters = $this->getReportFilters();
        
        // Obter dados para o relatório
        $reportData = $this->generateReportData($filters);
        
        // Obter lista de impressoras para o filtro
        $printers = $this->printerModel->getAllPrinters();
        
        // Definir dados para a view
        $viewData = [
            'pageTitle' => 'Relatórios do Sistema de Impressão 3D',
            'userRole' => 'admin',
            'filters' => $filters,
            'reportData' => $reportData,
            'printers' => $printers,
            'csrfToken' => SecurityManager::getCsrfToken()
        ];
        
        // Exibir view
        $this->view('admin/reports/index', $viewData);
    }
    
    /**
     * Exporta relatório em formato CSV
     * 
     * @return void
     */
    public function exportCsv() {
        // Verificar permissões - apenas administradores
        if (!SecurityManager::hasAdminAccess()) {
            $this->redirect('/login', [
                'error' => 'Acesso não autorizado. Faça login como administrador.'
            ]);
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->getValidatedParam('csrf_token', 'string');
        if (!SecurityManager::validateCsrfToken($csrfToken)) {
            $this->redirect('/admin/reports', [
                'error' => 'Erro de validação CSRF. Tente novamente.'
            ]);
            return;
        }
        
        // Configurar filtros a partir dos parâmetros GET
        $filters = $this->getReportFilters();
        
        // Obter dados para exportação
        $exportData = $this->getExportData($filters);
        
        // Preparar cabeçalho para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio-impressao-3d-' . date('Y-m-d') . '.csv');
        
        // Abrir output para escrita
        $output = fopen('php://output', 'w');
        
        // Definir cabeçalhos CSV
        fputcsv($output, [
            'ID do Trabalho',
            'Cliente',
            'Produto',
            'Data de Criação',
            'Status',
            'Impressora',
            'Tempo de Impressão (min)',
            'Tempo Estimado (min)',
            'Data de Conclusão'
        ]);
        
        // Escrever dados
        foreach ($exportData as $row) {
            fputcsv($output, [
                $row['job_id'],
                $row['customer_name'],
                $row['product_name'],
                $row['created_at'],
                $row['status'],
                $row['printer_name'],
                $row['print_time'],
                $row['estimated_time'],
                $row['completed_at']
            ]);
        }
        
        // Finalizar
        fclose($output);
        exit;
    }
    
    /**
     * Exporta relatório em formato PDF
     * 
     * @return void
     */
    public function exportPdf() {
        // Verificar permissões - apenas administradores
        if (!SecurityManager::hasAdminAccess()) {
            $this->redirect('/login', [
                'error' => 'Acesso não autorizado. Faça login como administrador.'
            ]);
            return;
        }
        
        // Validar token CSRF
        $csrfToken = $this->getValidatedParam('csrf_token', 'string');
        if (!SecurityManager::validateCsrfToken($csrfToken)) {
            $this->redirect('/admin/reports', [
                'error' => 'Erro de validação CSRF. Tente novamente.'
            ]);
            return;
        }
        
        // Configurar filtros a partir dos parâmetros GET
        $filters = $this->getReportFilters();
        
        // Obter dados para exportação
        $exportData = $this->getExportData($filters);
        $reportData = $this->generateReportData($filters);
        
        // Exibir view de PDF (será convertida para PDF pelo servidor)
        $viewData = [
            'pageTitle' => 'Relatório de Impressão 3D - ' . date('d/m/Y'),
            'filters' => $filters,
            'reportData' => $reportData,
            'exportData' => $exportData,
            'generatedAt' => date('d/m/Y H:i:s')
        ];
        
        $this->view('admin/reports/pdf_export', $viewData);
    }
    
    /**
     * Exibe relatório detalhado sobre taxas de sucesso
     * 
     * @return void
     */
    public function successRate() {
        // Verificar permissões - apenas administradores
        if (!SecurityManager::hasAdminAccess()) {
            $this->redirect('/login', [
                'error' => 'Acesso não autorizado. Faça login como administrador.'
            ]);
            return;
        }
        
        // Configurar filtros a partir dos parâmetros GET
        $filters = $this->getReportFilters();
        
        // Obter dados específicos para o relatório de taxa de sucesso
        $successRateData = $this->getSuccessRateData($filters);
        
        // Definir dados para a view
        $viewData = [
            'pageTitle' => 'Relatório de Taxa de Sucesso de Impressão 3D',
            'userRole' => 'admin',
            'filters' => $filters,
            'successRateData' => $successRateData,
            'csrfToken' => SecurityManager::getCsrfToken()
        ];
        
        // Exibir view
        $this->view('admin/reports/success_rate', $viewData);
    }
    
    /**
     * Exibe relatório detalhado sobre tempos de impressão
     * 
     * @return void
     */
    public function printTime() {
        // Verificar permissões - apenas administradores
        if (!SecurityManager::hasAdminAccess()) {
            $this->redirect('/login', [
                'error' => 'Acesso não autorizado. Faça login como administrador.'
            ]);
            return;
        }
        
        // Configurar filtros a partir dos parâmetros GET
        $filters = $this->getReportFilters();
        
        // Obter dados específicos para o relatório de tempo de impressão
        $printTimeData = $this->getPrintTimeData($filters);
        
        // Definir dados para a view
        $viewData = [
            'pageTitle' => 'Relatório de Tempo de Impressão 3D',
            'userRole' => 'admin',
            'filters' => $filters,
            'printTimeData' => $printTimeData,
            'csrfToken' => SecurityManager::getCsrfToken()
        ];
        
        // Exibir view
        $this->view('admin/reports/print_time', $viewData);
    }
    
    /**
     * Exibe relatório detalhado sobre falhas de impressão
     * 
     * @return void
     */
    public function failures() {
        // Verificar permissões - apenas administradores
        if (!SecurityManager::hasAdminAccess()) {
            $this->redirect('/login', [
                'error' => 'Acesso não autorizado. Faça login como administrador.'
            ]);
            return;
        }
        
        // Configurar filtros a partir dos parâmetros GET
        $filters = $this->getReportFilters();
        
        // Obter dados específicos para o relatório de falhas
        $failuresData = $this->getFailuresData($filters);
        
        // Definir dados para a view
        $viewData = [
            'pageTitle' => 'Relatório de Falhas de Impressão 3D',
            'userRole' => 'admin',
            'filters' => $filters,
            'failuresData' => $failuresData,
            'csrfToken' => SecurityManager::getCsrfToken()
        ];
        
        // Exibir view
        $this->view('admin/reports/failures', $viewData);
    }
    
    /**
     * Exibe relatório de métricas de notificações
     * 
     * @return void
     */
    public function notificationMetrics() {
        // Verificar permissões - apenas administradores
        if (!SecurityManager::hasAdminAccess()) {
            $this->redirect('/login', [
                'error' => 'Acesso não autorizado. Faça login como administrador.'
            ]);
            return;
        }
        
        // Obter dados de métricas de notificação
        $notificationMetrics = $this->notificationPreferenceModel->getPreferenceMetrics();
        
        // Definir dados para a view
        $viewData = [
            'pageTitle' => 'Métricas de Notificações',
            'userRole' => 'admin',
            'metrics' => $notificationMetrics,
            'csrfToken' => SecurityManager::getCsrfToken()
        ];
        
        // Exibir view
        $this->view('admin/notification_metrics', $viewData);
    }
    
    /**
     * Obtém filtros de relatório a partir dos parâmetros GET
     * 
     * @return array Filtros validados
     */
    private function getReportFilters() {
        $filters = [
            'date_from' => $this->getValidatedParam('date_from', 'date', [
                'default' => date('Y-m-d', strtotime('-30 days'))
            ]),
            'date_to' => $this->getValidatedParam('date_to', 'date', [
                'default' => date('Y-m-d')
            ]),
            'printer_id' => $this->getValidatedParam('printer_id', 'int'),
            'status' => $this->getValidatedParam('status', 'string', [
                'allowed' => ['queued', 'printing', 'completed', 'failed', 'cancelled']
            ])
        ];
        
        return $filters;
    }
    
    /**
     * Gera dados de relatório com base nos filtros
     * 
     * @param array $filters Filtros de relatório
     * @return array Dados de relatório para a view
     */
    private function generateReportData($filters) {
        // Inicializar array de dados
        $reportData = [];
        
        // Obter contagens de trabalhos
        $reportData['total_jobs'] = $this->printJobModel->countJobs($filters);
        $reportData['completed_jobs'] = $this->printJobModel->countJobsByStatus('completed', $filters);
        $reportData['failed_jobs'] = $this->printJobModel->countJobsByStatus('failed', $filters);
        
        // Obter tempo médio de impressão
        $reportData['average_print_time'] = $this->printJobModel->getAveragePrintTime($filters);
        
        // Obter resumo da fila
        $reportData['queue_summary'] = $this->printQueueModel->getQueueSummary($filters);
        
        // Obter utilização de impressoras
        $reportData['printer_utilization'] = $this->printerModel->getPrinterUtilization($filters);
        
        // Obter estatísticas diárias
        $reportData['daily_stats'] = $this->printJobModel->getDailyStats($filters);
        
        // Obter estatísticas de notificações
        $reportData['notification_stats'] = $this->getNotificationStats($filters);
        
        return $reportData;
    }
    
    /**
     * Obtém estatísticas de notificações
     * 
     * @param array $filters Filtros de relatório
     * @return array Estatísticas de notificações
     */
    private function getNotificationStats($filters) {
        // Esta função seria implementada no NotificationModel, aqui simulamos os dados
        return [
            'delivery' => [
                ['label' => 'Entregues', 'value' => 85, 'color' => '#28a745'],
                ['label' => 'Falhas', 'value' => 10, 'color' => '#dc3545'],
                ['label' => 'Pendentes', 'value' => 5, 'color' => '#ffc107']
            ],
            'channels' => [
                ['label' => 'Web', 'value' => 65, 'color' => '#007bff'],
                ['label' => 'Email', 'value' => 20, 'color' => '#6f42c1'],
                ['label' => 'Push', 'value' => 15, 'color' => '#fd7e14']
            ],
            'readRate' => 72,
            'averageResponse' => 45
        ];
    }
    
    /**
     * Obtém dados para exportação
     * 
     * @param array $filters Filtros de relatório
     * @return array Dados para exportação
     */
    private function getExportData($filters) {
        // Esta função obteria dados detalhados para exportação
        // do banco de dados através dos modelos
        
        // Por simplicidade, simulamos dados de exemplo
        return $this->printJobModel->getDetailedJobsReport($filters);
    }
    
    /**
     * Obtém dados para relatório de taxa de sucesso
     * 
     * @param array $filters Filtros de relatório
     * @return array Dados de taxa de sucesso
     */
    private function getSuccessRateData($filters) {
        // Esta função obteria dados detalhados sobre taxa de sucesso
        return $this->printJobModel->getSuccessRateData($filters);
    }
    
    /**
     * Obtém dados para relatório de tempo de impressão
     * 
     * @param array $filters Filtros de relatório
     * @return array Dados de tempo de impressão
     */
    private function getPrintTimeData($filters) {
        // Esta função obteria dados detalhados sobre tempo de impressão
        return $this->printJobModel->getPrintTimeData($filters);
    }
    
    /**
     * Obtém dados para relatório de falhas
     * 
     * @param array $filters Filtros de relatório
     * @return array Dados de falhas
     */
    private function getFailuresData($filters) {
        // Esta função obteria dados detalhados sobre falhas
        return $this->printJobModel->getFailuresData($filters);
    }
}
