<?php
/**
 * PrintStatusApiController - Controlador da API para atualizações de status de impressões 3D
 * 
 * Este controlador gerencia as APIs para receber atualizações de impressoras 3D
 * e outros dispositivos, permitindo atualizações em tempo real do status das impressões.
 * 
 * @package Controllers
 */
class PrintStatusApiController extends Controller {
    
    /**
     * Propriedades do controlador
     */
    protected $printStatusModel;
    protected $printQueueModel;
    protected $authModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        
        // Carregar modelos necessários
        $this->printStatusModel = new PrintStatusModel();
        $this->printQueueModel = new PrintQueueModel();
        $this->authModel = new AuthModel();
        
        // API não requer autenticação de sessão, mas verificará tokens de API
    }
    
    /**
     * Processa atualizações de status vindas de impressoras 3D
     * 
     * Endpoint: /api/status/update
     * Método: POST
     * Formato: application/json
     * 
     * Parâmetros esperados:
     * - api_key: Token de API da impressora
     * - printer_id: ID da impressora
     * - print_status_id: ID do status da impressão
     * - status: Status atual (opcional)
     * - progress: Progresso atual (opcional)
     * - message: Mensagem opcional (opcional)
     * - metrics: Métricas da impressão (opcional)
     */
    public function update() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        // Obter e validar parâmetros
        $requestData = $this->getRequestData();
        
        if (!$this->validateUpdateRequest($requestData)) {
            $this->jsonResponse(['error' => 'Parâmetros inválidos ou incompletos'], 400);
            return;
        }
        
        // Autenticar impressora
        if (!$this->authenticatePrinter($requestData['api_key'], $requestData['printer_id'])) {
            $this->jsonResponse(['error' => 'Autenticação inválida'], 401);
            return;
        }
        
        // Verificar se o status existe
        $printStatus = $this->printStatusModel->find($requestData['print_status_id']);
        if (!$printStatus) {
            $this->jsonResponse(['error' => 'Status de impressão não encontrado'], 404);
            return;
        }
        
        // Atualizar status se fornecido
        $updated = true;
        $statusMessage = '';
        
        if (isset($requestData['status']) && $requestData['status'] !== $printStatus['status']) {
            $statusMessage = sprintf(
                'Status atualizado pela impressora %s: %s -> %s', 
                $requestData['printer_id'],
                $printStatus['status'],
                $requestData['status']
            );
            
            $updated = $this->printStatusModel->updateStatus(
                $requestData['print_status_id'],
                $requestData['status'],
                $requestData['progress'] ?? null,
                $statusMessage,
                'printer:' . $requestData['printer_id']
            );
        } elseif (isset($requestData['progress']) && $requestData['progress'] != $printStatus['progress_percentage']) {
            $updated = $this->printStatusModel->updateStatus(
                $requestData['print_status_id'],
                $printStatus['status'],
                $requestData['progress'],
                null,
                'printer:' . $requestData['printer_id']
            );
        }
        
        // Adicionar mensagem se fornecida
        if (isset($requestData['message']) && !empty($requestData['message'])) {
            $messagePrefix = '[Impressora ' . $requestData['printer_id'] . '] ';
            $this->printStatusModel->addStatusMessage(
                $requestData['print_status_id'],
                $messagePrefix . $requestData['message'],
                'info',
                true
            );
        }
        
        // Registrar métricas se fornecidas
        if (isset($requestData['metrics']) && !empty($requestData['metrics'])) {
            $this->printStatusModel->recordMetrics($requestData['print_status_id'], $requestData['metrics']);
        }
        
        if ($updated) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Status atualizado com sucesso',
                'current_status' => $this->printStatusModel->getDetailedStatus($requestData['print_status_id'])
            ]);
        } else {
            $this->jsonResponse(['error' => 'Erro ao atualizar status'], 500);
        }
    }
    
    /**
     * Inicia uma nova impressão via API
     * 
     * Endpoint: /api/status/start
     * Método: POST
     * Formato: application/json
     * 
     * Parâmetros esperados:
     * - api_key: Token de API da impressora
     * - printer_id: ID da impressora
     * - print_queue_id: ID da fila de impressão
     */
    public function start() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        // Obter e validar parâmetros
        $requestData = $this->getRequestData();
        
        if (!isset($requestData['api_key']) || !isset($requestData['printer_id']) || !isset($requestData['print_queue_id'])) {
            $this->jsonResponse(['error' => 'Parâmetros inválidos ou incompletos'], 400);
            return;
        }
        
        // Autenticar impressora
        if (!$this->authenticatePrinter($requestData['api_key'], $requestData['printer_id'])) {
            $this->jsonResponse(['error' => 'Autenticação inválida'], 401);
            return;
        }
        
        // Obter item da fila
        $queueItem = $this->printQueueModel->find($requestData['print_queue_id']);
        if (!$queueItem) {
            $this->jsonResponse(['error' => 'Item da fila não encontrado'], 404);
            return;
        }
        
        // Verificar se já existe um status para este item da fila
        $existingStatus = $this->printStatusModel->getByQueueId($requestData['print_queue_id']);
        if ($existingStatus) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Já existe um status para este item da fila',
                'print_status_id' => $existingStatus['id']
            ], 409);
            return;
        }
        
        // Criar novo status
        $printStatusId = $this->printStatusModel->createStatus(
            $queueItem['order_id'],
            $queueItem['product_id'],
            $requestData['print_queue_id'],
            $requestData['printer_id'],
            [
                'status' => PrintStatusModel::STATUS_PREPARING,
                'progress_percentage' => 0.00,
                'notes' => 'Iniciado via API pela impressora ' . $requestData['printer_id']
            ]
        );
        
        if ($printStatusId) {
            // Adicionar mensagem inicial
            $this->printStatusModel->addStatusMessage(
                $printStatusId,
                'Impressão iniciada pela impressora ' . $requestData['printer_id'],
                'info',
                true
            );
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Status de impressão criado com sucesso',
                'print_status_id' => $printStatusId
            ]);
        } else {
            $this->jsonResponse(['error' => 'Erro ao criar status de impressão'], 500);
        }
    }
    
    /**
     * Busca detalhes de um status de impressão
     * 
     * Endpoint: /api/status/{id}
     * Método: GET
     * 
     * Parâmetros esperados na URL:
     * - id: ID do status de impressão
     * 
     * Parâmetros opcionais na query string:
     * - api_key: Token de API (se não fornecido, apenas dados básicos serão retornados)
     * - printer_id: ID da impressora (para autenticação)
     */
    public function get($id) {
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
            return;
        }
        
        // Verificar se o status existe
        $printStatus = $this->printStatusModel->find($id);
        if (!$printStatus) {
            $this->jsonResponse(['error' => 'Status de impressão não encontrado'], 404);
            return;
        }
        
        // Verificar autenticação para dados completos
        $apiKey = filter_input(INPUT_GET, 'api_key');
        $printerId = filter_input(INPUT_GET, 'printer_id');
        $isAuthenticated = $apiKey && $printerId && $this->authenticatePrinter($apiKey, $printerId);
        
        // Preparar resposta baseada no nível de autenticação
        if ($isAuthenticated) {
            // Dados completos para impressora autenticada
            $detailedStatus = $this->printStatusModel->getDetailedStatus($id);
            $this->jsonResponse($detailedStatus);
        } else {
            // Dados básicos para acesso público
            $basicStatus = [
                'id' => $printStatus['id'],
                'status' => $printStatus['status'],
                'progress_percentage' => $printStatus['progress_percentage'],
                'started_at' => $printStatus['started_at'],
                'estimated_completion' => $printStatus['estimated_completion'],
                'formatted_status' => PrintStatusModel::getAvailableStatuses()[$printStatus['status']] ?? $printStatus['status']
            ];
            $this->jsonResponse($basicStatus);
        }
    }
    
    /**
     * Lista impressões ativas para uma impressora específica
     * 
     * Endpoint: /api/status/printer/{printer_id}
     * Método: GET
     * 
     * Parâmetros esperados na URL:
     * - printer_id: ID da impressora
     * 
     * Parâmetros obrigatórios na query string:
     * - api_key: Token de API
     */
    public function printerJobs($printerId) {
        // Validar parâmetros
        if (empty($printerId)) {
            $this->jsonResponse(['error' => 'ID de impressora inválido'], 400);
            return;
        }
        
        // Verificar autenticação
        $apiKey = filter_input(INPUT_GET, 'api_key');
        if (!$apiKey || !$this->authenticatePrinter($apiKey, $printerId)) {
            $this->jsonResponse(['error' => 'Autenticação inválida'], 401);
            return;
        }
        
        // Consultar impressões ativas para esta impressora
        $db = $this->printStatusModel->db();
        $sql = "SELECT * FROM print_status 
                WHERE printer_id = ? 
                AND status IN ('pending', 'preparing', 'printing', 'paused')
                ORDER BY last_updated DESC";
        
        $activeJobs = $db->select($sql, [$printerId]);
        
        // Enriquecer dados com informações adicionais
        $enrichedJobs = [];
        foreach ($activeJobs as $job) {
            // Obter detalhes básicos do produto
            $db = $this->printStatusModel->db();
            $productSql = "SELECT name, model_file FROM products WHERE id = ?";
            $productResult = $db->select($productSql, [$job['product_id']]);
            $product = !empty($productResult) ? $productResult[0] : null;
            
            $enrichedJob = [
                'print_status_id' => $job['id'],
                'status' => $job['status'],
                'formatted_status' => PrintStatusModel::getAvailableStatuses()[$job['status']] ?? $job['status'],
                'progress_percentage' => $job['progress_percentage'],
                'product_id' => $job['product_id'],
                'product_name' => $product ? $product['name'] : 'Produto #' . $job['product_id'],
                'model_file' => $product ? $product['model_file'] : null,
                'order_id' => $job['order_id'],
                'started_at' => $job['started_at'],
                'last_updated' => $job['last_updated']
            ];
            
            $enrichedJobs[] = $enrichedJob;
        }
        
        $this->jsonResponse([
            'printer_id' => $printerId,
            'active_jobs_count' => count($enrichedJobs),
            'jobs' => $enrichedJobs
        ]);
    }
    
    /**
     * Obtém os dados da requisição
     * 
     * @return array Dados da requisição
     */
    protected function getRequestData() {
        // Verificar tipo de conteúdo
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // Ler dados JSON do corpo da requisição
            $json = file_get_contents('php://input');
            return json_decode($json, true) ?? [];
        } else {
            // Ler dados do formulário POST
            return $_POST;
        }
    }
    
    /**
     * Valida os parâmetros da requisição de atualização
     * 
     * @param array $data Dados da requisição
     * @return bool True se os parâmetros forem válidos
     */
    protected function validateUpdateRequest($data) {
        // Verificar parâmetros obrigatórios
        if (!isset($data['api_key']) || !isset($data['printer_id']) || !isset($data['print_status_id'])) {
            return false;
        }
        
        // Verificar se pelo menos um parâmetro de atualização está presente
        if (!isset($data['status']) && !isset($data['progress']) && !isset($data['message']) && !isset($data['metrics'])) {
            return false;
        }
        
        // Validar status se fornecido
        if (isset($data['status'])) {
            $validStatuses = array_keys(PrintStatusModel::getAvailableStatuses());
            if (!in_array($data['status'], $validStatuses)) {
                return false;
            }
        }
        
        // Validar progresso se fornecido
        if (isset($data['progress'])) {
            $progress = floatval($data['progress']);
            if ($progress < 0 || $progress > 100) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Autentica uma impressora
     * 
     * @param string $apiKey Token de API
     * @param string $printerId ID da impressora
     * @return bool True se a autenticação for válida
     */
    protected function authenticatePrinter($apiKey, $printerId) {
        // Implementação simplificada para verificar API key
        // Em um ambiente de produção, você pode querer implementar uma lógica mais robusta
        $db = $this->printStatusModel->db();
        $sql = "SELECT * FROM printer_settings WHERE printer_id = ? AND api_key = ? AND is_active = 1";
        $result = $db->select($sql, [$printerId, $apiKey]);
        
        return !empty($result);
    }
    
    /**
     * Envia uma resposta JSON
     * 
     * @param array $data Dados para enviar como JSON
     * @param int $statusCode Código de status HTTP (opcional)
     */
    protected function jsonResponse($data, $statusCode = 200) {
        // Definir código de status HTTP
        http_response_code($statusCode);
        
        // Definir cabeçalhos para JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        // Enviar resposta
        echo json_encode($data);
        exit;
    }
}
