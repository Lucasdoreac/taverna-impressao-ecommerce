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
        
        // Obter notas do formulário (se houver)
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Salvar informações no banco de dados
        $modelId = $this->customerModelModel->saveModel(
            $userId,
            $newFileName,
            $fileName,
            $fileSize,
            $fileExt,
            $notes
        );
        
        if (!$modelId) {
            // Erro ao salvar no banco, remover arquivo
            unlink($destination);
            setFlashMessage('error', 'Erro ao registrar o modelo no sistema. Por favor, tente novamente.');
            redirect('customer-models/upload');
        }
        
        // Sucesso!
        app_log('Modelo 3D enviado com sucesso. ID: ' . $modelId . ', Usuário: ' . $userId);
        setFlashMessage('success', 'Modelo 3D enviado com sucesso! Aguarde a validação pela nossa equipe.');
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
}
