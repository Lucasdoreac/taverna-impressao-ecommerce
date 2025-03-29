<?php
/**
 * CustomerModelController - Gerencia o upload e validação de modelos 3D enviados pelos clientes
 */
class CustomerModelController {
    private $customerModelModel;
    private $uploadDir;
    private $maxFileSize;
    private $allowedExtensions;
    
    public function __construct() {
        $this->customerModelModel = new CustomerModelModel();
        $this->uploadDir = UPLOADS_PATH . '/3d_models/';
        $this->maxFileSize = 52428800; // 50MB em bytes
        $this->allowedExtensions = ['stl', 'obj'];
        
        // Garantir que o diretório de upload exista
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Exibe a página de upload de modelos 3D
     */
    public function upload() {
        // Verificar se o usuário está logado
        if (!isUserLoggedIn()) {
            setFlashMessage('error', 'Você precisa estar logado para enviar modelos 3D.');
            redirect('login');
        }
        
        // Carregar a view de upload
        $pageTitle = 'Upload de Modelo 3D';
        $page = 'customer_models/upload';
        
        render($page, compact('pageTitle'));
    }
    
    /**
     * Processa o upload de modelos 3D
     */
    public function processUpload() {
        // Verificar se o usuário está logado
        if (!isUserLoggedIn()) {
            setFlashMessage('error', 'Você precisa estar logado para enviar modelos 3D.');
            redirect('login');
        }
        
        // Verificar se é um POST e se tem arquivo
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['model_file'])) {
            setFlashMessage('error', 'Nenhum arquivo enviado ou método inválido.');
            redirect('customer-models/upload');
        }
        
        // Obter informações do arquivo
        $file = $_FILES['model_file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // Verificar erros no upload
        if ($fileError !== 0) {
            $errorMessage = $this->getUploadErrorMessage($fileError);
            setFlashMessage('error', 'Erro no upload: ' . $errorMessage);
            redirect('customer-models/upload');
        }
        
        // Verificar tamanho do arquivo
        if ($fileSize > $this->maxFileSize) {
            setFlashMessage('error', 'O arquivo é muito grande. O tamanho máximo permitido é 50MB.');
            redirect('customer-models/upload');
        }
        
        // Obter a extensão do arquivo
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Verificar se a extensão é permitida
        if (!in_array($fileExt, $this->allowedExtensions)) {
            setFlashMessage('error', 'Extensão de arquivo não permitida. São permitidos apenas arquivos STL e OBJ.');
            redirect('customer-models/upload');
        }
        
        // Gerar nome único para o arquivo
        $userId = $_SESSION['user_id'];
        $newFileName = 'model_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
        $destination = $this->uploadDir . $newFileName;
        
        // Mover o arquivo para o destino
        if (!move_uploaded_file($fileTmpName, $destination)) {
            setFlashMessage('error', 'Erro ao salvar o arquivo. Por favor, tente novamente.');
            redirect('customer-models/upload');
        }
        
        // Realizar validação avançada do modelo 3D
        $validationResult = $this->validateModel($destination, $fileExt);
        
        // Verificar se o modelo é válido para impressão
        if (!$validationResult['is_valid']) {
            // Remover o arquivo (modelo inválido)
            unlink($destination);
            
            // Obter a primeira mensagem de erro
            $errorMessage = reset($validationResult['errors']);
            
            setFlashMessage('error', 'O modelo 3D é inválido para impressão: ' . $errorMessage);
            redirect('customer-models/upload');
        }
        
        // Obter notas do formulário (se houver)
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Adicionar resultados da validação às notas
        $notes .= "\n\n--- Resultados da Validação ---\n";
        
        // Adicionar estatísticas
        if (!empty($validationResult['stats'])) {
            $notes .= "Estatísticas:\n";
            foreach ($validationResult['stats'] as $key => $value) {
                if (is_array($value)) {
                    if ($key === 'dimensions') {
                        $notes .= "- Dimensões: " . number_format($value['width'], 2) . "mm x " . 
                                  number_format($value['height'], 2) . "mm x " . 
                                  number_format($value['depth'], 2) . "mm\n";
                    }
                } else {
                    $notes .= "- $key: $value\n";
                }
            }
        }
        
        // Adicionar avisos
        if (!empty($validationResult['warnings'])) {
            $notes .= "\nAvisos:\n";
            foreach ($validationResult['warnings'] as $warning) {
                $notes .= "- $warning\n";
            }
        }
        
        // Adicionar sugestões
        if (!empty($validationResult['repair_suggestions'])) {
            $notes .= "\nSugestões:\n";
            foreach ($validationResult['repair_suggestions'] as $suggestion) {
                $notes .= "- $suggestion\n";
            }
        }
        
        // Limitar o tamanho das notas para evitar problemas no banco de dados
        $notes = substr($notes, 0, 2000);
        
        // Definir o status inicial baseado nos resultados da validação
        $initialStatus = 'pending_validation';
        if (!empty($validationResult['warnings'])) {
            // Se houver avisos, marcar para uma revisão mais cuidadosa
            $initialStatus = 'pending_validation';
        }
        
        // Salvar informações no banco de dados
        $modelId = $this->customerModelModel->saveModel(
            $userId,
            $newFileName,
            $fileName,
            $fileSize,
            $fileExt,
            $notes,
            $initialStatus,
            $validationResult
        );
        
        if (!$modelId) {
            // Erro ao salvar no banco, remover arquivo
            unlink($destination);
            setFlashMessage('error', 'Erro ao registrar o modelo no sistema. Por favor, tente novamente.');
            redirect('customer-models/upload');
        }
        
        // Sucesso!
        app_log('Modelo 3D enviado com sucesso. ID: ' . $modelId . ', Usuário: ' . $userId);
        
        // Mensagem personalizada com base nos resultados da validação
        if (!empty($validationResult['warnings'])) {
            setFlashMessage('success', 'Modelo 3D enviado com sucesso! Alguns avisos foram detectados e o modelo passará por uma validação adicional pela nossa equipe.');
        } else {
            setFlashMessage('success', 'Modelo 3D enviado com sucesso! O modelo passará por uma validação técnica pela nossa equipe.');
        }
        
        redirect('customer-models/list');
    }
    
    /**
     * Lista os modelos 3D do usuário logado
     */
    public function listUserModels() {
        // Verificar se o usuário está logado
        if (!isUserLoggedIn()) {
            setFlashMessage('error', 'Você precisa estar logado para visualizar seus modelos 3D.');
            redirect('login');
        }
        
        $userId = $_SESSION['user_id'];
        
        // Obter o status de filtro (se houver)
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // Obter modelos do usuário
        $models = $this->customerModelModel->getUserModels($userId, $status);
        
        // Carregar a view de listagem
        $pageTitle = 'Meus Modelos 3D';
        $page = 'customer_models/list';
        
        render($page, compact('pageTitle', 'models', 'status'));
    }
    
    /**
     * Exibe os detalhes de um modelo 3D específico
     */
    public function details($modelId) {
        // Verificar se o usuário está logado
        if (!isUserLoggedIn()) {
            setFlashMessage('error', 'Você precisa estar logado para visualizar detalhes de modelos 3D.');
            redirect('login');
        }
        
        $userId = $_SESSION['user_id'];
        $isAdmin = isAdmin();
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($modelId);
        
        // Verificar se o modelo existe
        if (!$model) {
            setFlashMessage('error', 'Modelo 3D não encontrado.');
            redirect('customer-models/list');
        }
        
        // Verificar permissões (o usuário deve ser o dono do modelo ou um administrador)
        if ($model['user_id'] != $userId && !$isAdmin) {
            setFlashMessage('error', 'Você não tem permissão para visualizar este modelo 3D.');
            redirect('customer-models/list');
        }
        
        // Carregar a view de detalhes
        $pageTitle = 'Detalhes do Modelo 3D';
        $page = 'customer_models/details';
        
        render($page, compact('pageTitle', 'model', 'isAdmin'));
    }
    
    /**
     * Remove um modelo 3D do usuário
     */
    public function delete($modelId) {
        // Verificar se o usuário está logado
        if (!isUserLoggedIn()) {
            setFlashMessage('error', 'Você precisa estar logado para remover modelos 3D.');
            redirect('login');
        }
        
        $userId = $_SESSION['user_id'];
        $isAdmin = isAdmin();
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($modelId);
        
        // Verificar se o modelo existe
        if (!$model) {
            setFlashMessage('error', 'Modelo 3D não encontrado.');
            redirect('customer-models/list');
        }
        
        // Verificar permissões (o usuário deve ser o dono do modelo ou um administrador)
        if ($model['user_id'] != $userId && !$isAdmin) {
            setFlashMessage('error', 'Você não tem permissão para remover este modelo 3D.');
            redirect('customer-models/list');
        }
        
        // Remover o modelo
        $result = $this->customerModelModel->deleteModel($modelId);
        
        if (!$result) {
            setFlashMessage('error', 'Erro ao remover o modelo 3D. Por favor, tente novamente.');
        } else {
            setFlashMessage('success', 'Modelo 3D removido com sucesso!');
            app_log('Modelo 3D removido. ID: ' . $modelId . ', Usuário: ' . $userId);
        }
        
        redirect('customer-models/list');
    }
    
    /**
     * Lista todos os modelos 3D pendentes de validação (somente admin)
     */
    public function pendingModels() {
        // Verificar se o usuário é administrador
        if (!isAdmin()) {
            setFlashMessage('error', 'Acesso restrito a administradores.');
            redirect('home');
        }
        
        // Obter modelos pendentes
        $models = $this->customerModelModel->getPendingModels();
        
        // Carregar a view de modelos pendentes
        $pageTitle = 'Modelos 3D Pendentes';
        $page = 'admin/customer_models/pending';
        
        render($page, compact('pageTitle', 'models'));
    }
    
    /**
     * Atualiza o status de um modelo 3D (somente admin)
     */
    public function updateStatus($modelId) {
        // Verificar se o usuário é administrador
        if (!isAdmin()) {
            setFlashMessage('error', 'Acesso restrito a administradores.');
            redirect('home');
        }
        
        // Verificar se é um POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('error', 'Método inválido.');
            redirect('admin/customer-models/pending');
        }
        
        // Obter informações do formulário
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Validar status
        if (!in_array($status, ['approved', 'rejected'])) {
            setFlashMessage('error', 'Status inválido.');
            redirect('admin/customer-models/pending');
        }
        
        // Atualizar status do modelo
        $result = $this->customerModelModel->updateStatus($modelId, $status, $notes);
        
        if (!$result) {
            setFlashMessage('error', 'Erro ao atualizar o status do modelo 3D. Por favor, tente novamente.');
        } else {
            $statusText = $status == 'approved' ? 'aprovado' : 'rejeitado';
            setFlashMessage('success', 'O modelo 3D foi ' . $statusText . ' com sucesso!');
            app_log('Status do modelo 3D atualizado. ID: ' . $modelId . ', Status: ' . $status);
        }
        
        redirect('admin/customer-models/pending');
    }
    
    /**
     * Retorna mensagem de erro para códigos de erro de upload
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'O arquivo excede o tamanho máximo permitido pelo servidor.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'O arquivo excede o tamanho máximo permitido pelo formulário.';
            case UPLOAD_ERR_PARTIAL:
                return 'O upload do arquivo foi parcial.';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo foi enviado.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta a pasta temporária.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao escrever o arquivo no disco.';
            case UPLOAD_ERR_EXTENSION:
                return 'Uma extensão PHP interrompeu o upload do arquivo.';
            default:
                return 'Erro desconhecido no upload.';
        }
    }
    
    /**
     * Valida um modelo 3D usando o ModelValidator
     * 
     * @param string $filePath Caminho para o arquivo do modelo
     * @param string $fileExtension Extensão do arquivo
     * @return array Resultado da validação
     */
    private function validateModel($filePath, $fileExtension) {
        // Verificar se o ModelValidator está disponível
        if (!class_exists('ModelValidator')) {
            require_once(HELPERS_PATH . '/ModelValidator.php');
        }
        
        // Configurações de validação específicas
        $config = [
            'max_vertices' => 1000000,   // 1 milhão de vértices
            'max_polygons' => 500000,    // 500 mil polígonos
            'min_dimensions' => [1, 1, 1], // Dimensões mínimas em mm
            'max_dimensions' => [220, 220, 250], // Dimensões máximas em mm (Ender 3)
            'check_watertight' => true,  // Verificar se o modelo é estanque
            'check_normals' => true,     // Verificar orientação normal das faces
            'auto_repair' => false       // Não reparar automaticamente
        ];
        
        // Inicializar o validador
        $validator = new ModelValidator($config);
        
        // Executar validação
        $validator->validate($filePath);
        
        // Obter resultado da validação
        return $validator->getValidationResult();
    }
    
    /**
     * Executa validação adicional em um modelo 3D (somente admin)
     */
    public function revalidateModel($modelId) {
        // Verificar se o usuário é administrador
        if (!isAdmin()) {
            setFlashMessage('error', 'Acesso restrito a administradores.');
            redirect('home');
        }
        
        // Obter informações do modelo
        $model = $this->customerModelModel->getModelById($modelId);
        
        // Verificar se o modelo existe
        if (!$model) {
            setFlashMessage('error', 'Modelo 3D não encontrado.');
            redirect('admin/customer-models/pending');
        }
        
        // Caminho para o arquivo
        $filePath = $this->uploadDir . $model['file_name'];
        
        // Verificar se o arquivo existe
        if (!file_exists($filePath)) {
            setFlashMessage('error', 'Arquivo do modelo 3D não encontrado no servidor.');
            redirect('admin/customer-models/pending');
        }
        
        // Executar validação avançada
        $validationResult = $this->validateModel($filePath, $model['file_type']);
        
        // Atualizar as notas do modelo com os resultados da validação
        $notes = $model['notes'] ?? '';
        $notes .= "\n\n--- Revalidação " . date('Y-m-d H:i:s') . " ---\n";
        
        // Adicionar resultado da validação
        $notes .= "Validação: " . ($validationResult['is_valid'] ? 'Passou' : 'Falhou') . "\n";
        
        // Adicionar estatísticas
        if (!empty($validationResult['stats'])) {
            $notes .= "Estatísticas:\n";
            foreach ($validationResult['stats'] as $key => $value) {
                if (is_array($value)) {
                    if ($key === 'dimensions') {
                        $notes .= "- Dimensões: " . number_format($value['width'], 2) . "mm x " . 
                                  number_format($value['height'], 2) . "mm x " . 
                                  number_format($value['depth'], 2) . "mm\n";
                    }
                } else {
                    $notes .= "- $key: $value\n";
                }
            }
        }
        
        // Adicionar erros
        if (!empty($validationResult['errors'])) {
            $notes .= "\nErros:\n";
            foreach ($validationResult['errors'] as $error) {
                $notes .= "- $error\n";
            }
        }
        
        // Adicionar avisos
        if (!empty($validationResult['warnings'])) {
            $notes .= "\nAvisos:\n";
            foreach ($validationResult['warnings'] as $warning) {
                $notes .= "- $warning\n";
            }
        }
        
        // Adicionar sugestões
        if (!empty($validationResult['repair_suggestions'])) {
            $notes .= "\nSugestões:\n";
            foreach ($validationResult['repair_suggestions'] as $suggestion) {
                $notes .= "- $suggestion\n";
            }
        }
        
        // Limitar o tamanho das notas para evitar problemas no banco de dados
        $notes = substr($notes, 0, 2000);
        
        // Atualizar as notas do modelo
        $this->customerModelModel->updateNotes($modelId, $notes);
        
        // Sucesso!
        setFlashMessage('success', 'Modelo 3D revalidado com sucesso! ' . 
            ($validationResult['is_valid'] ? 'O modelo é válido para impressão.' : 'O modelo apresenta problemas que precisam ser corrigidos.'));
        
        // Redirecionar para a página de detalhes do modelo
        redirect('admin/customer-model/' . $modelId);
    }
}
