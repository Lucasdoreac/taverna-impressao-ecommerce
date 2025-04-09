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
        
        // Carregar biblioteca de segurança
        require_once(LIB_PATH . '/Security/SecurityManager.php');
        
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
        
        // Sanitizar entrada
        $requestData = $this->sanitizeRequestData($requestData);
        
        // Autenticar impressora
        if (!$this->authenticatePrinter($requestData['api_key'], $requestData['printer_id'])) {
            $this->jsonResponse(['error' => 'Autenticação inválida'], 401);
            return;
        }
        
        // Verificar se o status existe
        $printStatusId = (int)$requestData['print_status_id'];
        $printStatus = $this->printStatusModel->find($printStatusId);
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
                $printStatusId,
                $requestData['status'],
                $requestData['progress'] ?? null,
                $statusMessage,
                'printer:' . $requestData['printer_id']
            );
        } elseif (isset($requestData['progress']) && $requestData['progress'] != $printStatus['progress_percentage']) {
            $updated = $this->printStatusModel->updateStatus(
                $printStatusId,
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
                $printStatusId,
                $messagePrefix . $requestData['message'],
                'info',
                true
            );
        }
        
        // Registrar métricas se fornecidas
        if (isset($requestData['metrics']) && !empty($requestData['metrics'])) {
            $this->printStatusModel->recordMetrics($printStatusId, $requestData['metrics']);
        }
        
        if ($updated) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Status atualizado com sucesso',
                'current_status' => $this->printStatusModel->getDetailedStatus($printStatusId)
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
        
        // Validar campos obrigatórios
        $requiredFields = ['api_key', 'printer_id', 'print_queue_id'];
        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field]) || empty($requestData[$field])) {
                $this->jsonResponse([
                    'error' => 'Parâmetro obrigatório ausente: ' . $field
                ], 400);
                return;
            }
        }
        
        // Sanitizar e validar tipos de dados
        $apiKey = $this->sanitizeInput($requestData['api_key']);
        $printerId = (int)$requestData['printer_id'];
        $printQueueId = (int)$requestData['print_queue_id'];
        
        if ($printerId <= 0 || $printQueueId <= 0) {
            $this->jsonResponse(['error' => 'IDs inválidos'], 400);
            return;
        }
        
        // Autenticar impressora
        if (!$this->authenticatePrinter($apiKey, $printerId)) {
            $this->jsonResponse(['error' => 'Autenticação inválida'], 401);
            return;
        }
        
        // Obter item da fila
        $queueItem = $this->printQueueModel->find($printQueueId);
        if (!$queueItem) {
            $this->jsonResponse(['error' => 'Item da fila não encontrado'], 404);
            return;
        }
        
        // Verificar se já existe um status para este item da fila
        $existingStatus = $this->printStatusModel->getByQueueId($printQueueId);
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
            $printQueueId,
            $printerId,
            [
                'status' => PrintStatusModel::STATUS_PREPARING,
                'progress_percentage' => 0.00,
                'notes' => 'Iniciado via API pela impressora ' . $printerId
            ]
        );
        
        if ($printStatusId) {
            // Adicionar mensagem inicial
            $this->printStatusModel->addStatusMessage(
                $printStatusId,
                'Impressão iniciada pela impressora ' . $printerId,
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
        $apiKey = $this->sanitizeInput(filter_input(INPUT_GET, 'api_key', FILTER_SANITIZE_STRING));
        $printerId = (int)filter_input(INPUT_GET, 'printer_id', FILTER_SANITIZE_NUMBER_INT);
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
        $printerId = (int)$printerId;
        if ($printerId <= 0) {
            $this->jsonResponse(['error' => 'ID de impressora inválido'], 400);
            return;
        }
        
        // Verificar autenticação
        $apiKey = $this->sanitizeInput(filter_input(INPUT_GET, 'api_key', FILTER_SANITIZE_STRING));
        if (empty($apiKey) || !$this->authenticatePrinter($apiKey, $printerId)) {
            $this->jsonResponse(['error' => 'Autenticação inválida'], 401);
            return;
        }
        
        // Consultar impressões ativas para esta impressora usando prepared statement
        $db = $this->printStatusModel->db();
        $sql = "SELECT * FROM print_status 
                WHERE printer_id = ? 
                AND status IN (?, ?, ?, ?)
                ORDER BY last_updated DESC";
        
        $activeStatuses = [
            'pending', 
            'preparing', 
            'printing', 
            'paused'
        ];
        
        $params = array_merge([$printerId], $activeStatuses);
        $activeJobs = $db->select($sql, $params);
        
        // Enriquecer dados com informações adicionais
        $enrichedJobs = [];
        foreach ($activeJobs as $job) {
            // Obter detalhes básicos do produto usando prepared statement
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
                'product_name' => $product ? $this->sanitizeOutput($product['name']) : 'Produto #' . $job['product_id'],
                'model_file' => $product ? $this->sanitizeOutput($product['model_file']) : null,
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
     * Faz upload de um arquivo relacionado a uma impressão 3D
     * 
     * Endpoint: /api/status/upload
     * Método: POST
     * Formato: multipart/form-data
     * 
     * Parâmetros esperados:
     * - api_key: Token de API da impressora
     * - printer_id: ID da impressora
     * - print_status_id: ID do status da impressão
     * - file: Arquivo a ser enviado
     * - file_type: Tipo de arquivo (image, model, log)
     */
    public function upload() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        // Verificar se há um arquivo sendo enviado
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = isset($_FILES['file']) ? 
                FileUploadManager::getUploadErrorMessage($_FILES['file']['error']) : 
                'Nenhum arquivo enviado';
            
            $this->jsonResponse(['error' => $errorMessage], 400);
            return;
        }
        
        // Validar e sanitizar parâmetros
        $apiKey = $this->sanitizeInput($_POST['api_key'] ?? '');
        $printerId = (int)($_POST['printer_id'] ?? 0);
        $printStatusId = (int)($_POST['print_status_id'] ?? 0);
        $fileType = $this->sanitizeInput($_POST['file_type'] ?? '');
        
        if (empty($apiKey) || $printerId <= 0 || $printStatusId <= 0 || empty($fileType)) {
            $this->jsonResponse(['error' => 'Parâmetros inválidos ou incompletos'], 400);
            return;
        }
        
        // Autenticar impressora
        if (!$this->authenticatePrinter($apiKey, $printerId)) {
            $this->jsonResponse(['error' => 'Autenticação inválida'], 401);
            return;
        }
        
        // Verificar se o status de impressão existe
        $printStatus = $this->printStatusModel->find($printStatusId);
        if (!$printStatus) {
            $this->jsonResponse(['error' => 'Status de impressão não encontrado'], 404);
            return;
        }
        
        // Configurar opções de upload com base no tipo de arquivo
        $uploadOptions = $this->getUploadOptions($fileType);
        if ($uploadOptions === false) {
            $this->jsonResponse(['error' => 'Tipo de arquivo não suportado'], 400);
            return;
        }
        
        // Processar upload de arquivo usando o FileUploadManager
        $uploadDir = 'printer_uploads/' . $printerId . '/' . $printStatusId . '/' . $fileType;
        $result = FileUploadManager::processUpload($_FILES['file'], $uploadDir, $uploadOptions);
        
        if (!$result['success']) {
            $this->jsonResponse(['error' => $result['message']], 400);
            return;
        }
        
        // Adicionar registro do arquivo no banco de dados
        $fileData = [
            'print_status_id' => $printStatusId,
            'file_type' => $fileType,
            'file_name' => $result['file']['name'],
            'original_name' => $result['file']['original_name'],
            'file_path' => $result['file']['path'],
            'file_size' => $result['file']['size'],
            'file_mime' => $result['file']['type'],
            'uploaded_by' => 'printer:' . $printerId,
            'uploaded_at' => date('Y-m-d H:i:s')
        ];
        
        $fileId = $this->printStatusModel->addFile($fileData);
        
        if ($fileId) {
            // Adicionar uma mensagem ao status
            $this->printStatusModel->addStatusMessage(
                $printStatusId,
                'Arquivo ' . $result['file']['original_name'] . ' enviado pela impressora ' . $printerId,
                'info',
                true
            );
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Arquivo enviado com sucesso',
                'file_id' => $fileId,
                'file' => [
                    'name' => $result['file']['name'],
                    'path' => $result['file']['path'],
                    'type' => $fileType
                ]
            ]);
        } else {
            // Se não conseguiu adicionar o registro, tenta remover o arquivo físico
            FileUploadManager::deleteFile($result['file']['path']);
            
            $this->jsonResponse([
                'error' => 'Erro ao registrar arquivo no banco de dados'
            ], 500);
        }
    }
    
    /**
     * Obtém os dados da requisição com validação e sanitização
     * 
     * @return array Dados da requisição
     */
    protected function getRequestData() {
        try {
            // Verificar tipo de conteúdo
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                // Ler dados JSON do corpo da requisição
                $json = file_get_contents('php://input');
                
                // Validar se é um JSON válido
                $data = json_decode($json, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('JSON inválido: ' . json_last_error_msg());
                }
                
                return is_array($data) ? $data : [];
            } else {
                // Ler dados do formulário POST com sanitização básica
                $sanitizedData = [];
                foreach ($_POST as $key => $value) {
                    $sanitizedData[$key] = $this->sanitizeInput($value);
                }
                
                return $sanitizedData;
            }
        } catch (Exception $e) {
            // Registrar erro e retornar array vazio
            error_log('Erro ao processar dados da requisição: ' . $e->getMessage());
            return [];
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
        if (!isset($data['api_key']) || empty($data['api_key']) || 
            !isset($data['printer_id']) || empty($data['printer_id']) || 
            !isset($data['print_status_id']) || empty($data['print_status_id'])) {
            return false;
        }
        
        // Verificar se pelo menos um parâmetro de atualização está presente
        if (!isset($data['status']) && !isset($data['progress']) && 
            !isset($data['message']) && !isset($data['metrics'])) {
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
        
        // Validar métricas se fornecidas
        if (isset($data['metrics']) && !is_array($data['metrics'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitiza os dados da requisição
     * 
     * @param array $data Dados da requisição
     * @return array Dados sanitizados
     */
    protected function sanitizeRequestData($data) {
        $sanitizedData = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recursivamente sanitizar arrays
                $sanitizedData[$key] = $this->sanitizeRequestData($value);
            } else if (is_string($value)) {
                // Sanitizar strings
                $sanitizedData[$key] = $this->sanitizeInput($value);
            } else {
                // Manter outros tipos de dados
                $sanitizedData[$key] = $value;
            }
        }
        
        return $sanitizedData;
    }
    
    /**
     * Sanitiza uma string de entrada
     * 
     * @param string $input String para sanitizar
     * @return string String sanitizada
     */
    protected function sanitizeInput($input) {
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }
    
    /**
     * Sanitiza uma string para saída
     * 
     * @param string $output String para sanitizar
     * @return string String sanitizada
     */
    protected function sanitizeOutput($output) {
        if (is_string($output)) {
            return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
        }
        return $output;
    }
    
    /**
     * Autentica uma impressora
     * 
     * @param string $apiKey Token de API
     * @param int $printerId ID da impressora
     * @return bool True se a autenticação for válida
     */
    protected function authenticatePrinter($apiKey, $printerId) {
        // Validar parâmetros
        if (empty($apiKey) || empty($printerId)) {
            return false;
        }
        
        // Usar prepared statement para prevenir SQL Injection
        $db = $this->printStatusModel->db();
        $sql = "SELECT * FROM printer_settings WHERE printer_id = ? AND api_key = ? AND is_active = 1";
        $result = $db->select($sql, [$printerId, $apiKey]);
        
        return !empty($result);
    }
    
    /**
     * Obtém as opções de upload com base no tipo de arquivo
     * 
     * @param string $fileType Tipo de arquivo
     * @return array|false Opções de upload ou false se tipo não suportado
     */
    protected function getUploadOptions($fileType) {
        switch ($fileType) {
            case 'image':
                return [
                    'maxSize' => 10 * 1024 * 1024, // 10MB
                    'allowedExtensions' => ['jpg', 'jpeg', 'png', 'gif'],
                    'allowedMimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                    'processImage' => true,
                    'maxWidth' => 2048,
                    'maxHeight' => 2048,
                    'preserveFileName' => false
                ];
                
            case 'model':
                return [
                    'maxSize' => 50 * 1024 * 1024, // 50MB
                    'allowedExtensions' => ['stl', 'obj', '3mf', 'gcode'],
                    'allowedMimeTypes' => [
                        'application/sla', 
                        'application/vnd.ms-pki.stl',
                        'application/octet-stream',
                        'text/plain',
                        'model/stl',
                        'model/obj',
                        'model/3mf',
                        'application/x-tgif'
                    ],
                    'preserveFileName' => true
                ];
                
            case 'log':
                return [
                    'maxSize' => 5 * 1024 * 1024, // 5MB
                    'allowedExtensions' => ['log', 'txt', 'json'],
                    'allowedMimeTypes' => ['text/plain', 'application/json', 'application/octet-stream'],
                    'preserveFileName' => true
                ];
                
            default:
                return false;
        }
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
        
        // Sanitizar dados recursivamente para evitar XSS em respostas JSON
        $data = $this->sanitizeResponseData($data);
        
        // Definir cabeçalhos para JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        
        // Enviar resposta
        echo json_encode($data);
        
        // Encerrar o script sem usar exit
        return;
    }
    
    /**
     * Sanitiza dados de resposta recursivamente
     * 
     * @param mixed $data Dados para sanitizar
     * @return mixed Dados sanitizados
     */
    protected function sanitizeResponseData($data) {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[$key] = $this->sanitizeResponseData($value);
            }
            return $sanitized;
        } elseif (is_string($data)) {
            return $this->sanitizeOutput($data);
        } else {
            return $data;
        }
    }
    
    /**
     * Registra uma exceção ou erro no log
     * 
     * @param Exception|string $error Exceção ou mensagem de erro
     * @param string $context Contexto adicional
     * @return void
     */
    protected function logError($error, $context = '') {
        $errorMessage = is_string($error) ? $error : $error->getMessage();
        $logMessage = '[PrintStatusApiController] ' . $errorMessage;
        
        if (!empty($context)) {
            $logMessage .= ' | Contexto: ' . $context;
        }
        
        if ($error instanceof Exception) {
            $logMessage .= ' | Arquivo: ' . $error->getFile() . ':' . $error->getLine();
        }
        
        error_log($logMessage);
    }
}
