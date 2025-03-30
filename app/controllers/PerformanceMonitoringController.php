<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Controller para receber e processar dados de monitoramento de performance em produção
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceMonitoringController extends BaseController {
    /**
     * Verifica se o monitoramento está habilitado e se é um ambiente adequado
     */
    private function checkEnabled() {
        // Permitir apenas em ambiente de produção, a menos que o modo de debug esteja ativado
        if (!(defined('ENVIRONMENT') && ENVIRONMENT === 'production') && 
            !(defined('DEBUG_MONITORING') && DEBUG_MONITORING)) {
            http_response_code(403);
            echo json_encode(['error' => 'Monitoramento desabilitado no ambiente atual']);
            exit;
        }
    }
    
    /**
     * Endpoint para coletar métricas do cliente
     */
    public function collect() {
        $this->checkEnabled();
        
        // Verificar se a requisição é POST e contém JSON
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }
        
        // Obter corpo da requisição
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos']);
            exit;
        }
        
        // Passar dados para o helper de monitoramento
        if (class_exists('ProductionMonitoringHelper')) {
            // Adicionar informações do servidor
            $data['server'] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ];
            
            // Salvar métricas
            $this->saveMetrics($data);
            
            // Enviar resposta de sucesso
            echo json_encode(['status' => 'success', 'message' => 'Métricas recebidas com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Helper de monitoramento não disponível']);
        }
    }
    
    /**
     * Endpoint para finalizar monitoramento quando a página é descarregada
     */
    public function finalize() {
        $this->checkEnabled();
        
        // Verificar se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            exit;
        }
        
        // Registrar evento de finalização
        if (class_exists('ProductionMonitoringHelper')) {
            $additionalData = [
                'action' => 'page_unload',
                'timestamp' => date('Y-m-d H:i:s'),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? 'Unknown'
            ];
            
            // Finalizar monitoramento com dados adicionais
            ProductionMonitoringHelper::endMonitoring($additionalData);
            
            // Enviar resposta de sucesso (embora com Beacon API, o cliente provavelmente não a receberá)
            echo json_encode(['status' => 'success', 'message' => 'Monitoramento finalizado com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Helper de monitoramento não disponível']);
        }
    }
    
    /**
     * Salva as métricas coletadas
     * 
     * @param array $data Dados de métricas
     */
    private function saveMetrics($data) {
        // Criar diretório se não existir
        $logDir = ROOT_PATH . '/logs/performance_monitoring';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Nome do arquivo baseado na data atual
        $filename = $logDir . '/metrics_' . date('Y-m-d') . '.json';
        
        // Verificar se o arquivo já existe
        $existingData = [];
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            if (!empty($content)) {
                $existingData = json_decode($content, true) ?: [];
            }
        }
        
        // Adicionar novos dados
        $existingData[] = $data;
        
        // Salvar arquivo
        file_put_contents($filename, json_encode($existingData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Página de administração para visualizar relatórios de monitoramento
     */
    public function reports() {
        // Verificar se o usuário tem permissão de administrador
        if (!$this->isAdmin()) {
            $this->redirect('login');
            return;
        }
        
        // Obter datas disponíveis
        $logDir = ROOT_PATH . '/logs/performance_monitoring';
        $availableDates = [];
        
        if (file_exists($logDir)) {
            $files = glob($logDir . '/metrics_*.json');
            
            foreach ($files as $file) {
                $date = basename($file, '.json');
                $date = str_replace('metrics_', '', $date);
                $availableDates[] = $date;
            }
            
            // Ordenar datas (mais recentes primeiro)
            rsort($availableDates);
        }
        
        // Carregar análise se data for especificada
        $selectedDate = isset($_GET['date']) ? $_GET['date'] : (count($availableDates) > 0 ? $availableDates[0] : null);
        $analysis = null;
        
        if ($selectedDate && class_exists('ProductionMonitoringHelper')) {
            $analysis = ProductionMonitoringHelper::analyzeData($selectedDate, $selectedDate);
            
            // Obter recomendações
            $recommendations = ProductionMonitoringHelper::getRecommendations($analysis);
        }
        
        // Renderizar view
        $this->view->render('admin/performance_monitoring', [
            'availableDates' => $availableDates,
            'selectedDate' => $selectedDate,
            'analysis' => $analysis,
            'recommendations' => $recommendations ?? []
        ]);
    }
}
