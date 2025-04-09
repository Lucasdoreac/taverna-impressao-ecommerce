<?php
/**
 * AsyncProcessNotificationHandler - Manipulador de notificações para processos assíncronos
 * 
 * Componente responsável por integrar o sistema de processamento assíncrono 
 * com o sistema de notificações, permitindo alertas em tempo real para 
 * mudanças de status, conclusões e falhas em processos de longa duração.
 * 
 * @package App\Lib\Notification
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */

namespace App\Lib\Notification;

use App\Lib\Security\InputValidationTrait;
use App\Lib\Security\SecurityManager;
use App\Models\AsyncProcess\StatusRepository;

class AsyncProcessNotificationHandler {
    use InputValidationTrait;
    
    /**
     * @var NotificationManager
     */
    private $notificationManager;
    
    /**
     * @var StatusRepository
     */
    private $statusRepository;
    
    /**
     * @var array
     */
    private $config;
    
    /**
     * @var \PDO
     */
    private $pdo;
    
    /**
     * Mapeamento de status para tipos de notificação
     * 
     * @var array
     */
    private static $statusToNotificationType = [
        'pending' => 'process_pending',
        'processing' => 'process_processing',
        'completed' => 'process_completed',
        'failed' => 'process_failed',
        'cancelled' => 'process_cancelled'
    ];
    
    /**
     * Constructor
     * 
     * @param NotificationManager $notificationManager Gerenciador de notificações
     * @param StatusRepository $statusRepository Repositório de status de processos
     * @param \PDO $pdo Conexão PDO com o banco de dados
     * @param array $config Configurações adicionais
     */
    public function __construct(
        NotificationManager $notificationManager,
        StatusRepository $statusRepository,
        \PDO $pdo = null,
        array $config = []
    ) {
        $this->notificationManager = $notificationManager;
        $this->statusRepository = $statusRepository;
        $this->pdo = $pdo;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }
    
    /**
     * Manipula notificações para alterações de status de processos assíncronos
     * 
     * @param string $processToken Token do processo
     * @param string $oldStatus Status anterior
     * @param string $newStatus Novo status
     * @param int $userId ID do usuário dono do processo
     * @param array $context Informações de contexto adicionais
     * @return bool Sucesso na operação
     */
    public function handleStatusChange($processToken, $oldStatus, $newStatus, $userId, array $context = []) {
        try {
            // Validar parâmetros
            $processToken = $this->validateString($processToken, ['pattern' => '/^[a-zA-Z0-9]{32}$/']);
            $oldStatus = $this->validateString($oldStatus, ['maxLength' => 50]);
            $newStatus = $this->validateString($newStatus, ['maxLength' => 50]);
            $userId = intval($userId);
            
            if ($processToken === null || $newStatus === null || $userId <= 0) {
                error_log('AsyncProcessNotificationHandler: Parâmetros inválidos para handleStatusChange');
                return false;
            }
            
            // Verificar se o usuário existe
            if (!$this->validateUser($userId)) {
                error_log("AsyncProcessNotificationHandler: Usuário inválido ID {$userId}");
                return false;
            }
            
            // Verificar se o processo existe
            $process = $this->statusRepository->getProcessStatus($processToken);
            if (!$process) {
                error_log("AsyncProcessNotificationHandler: Processo não encontrado Token {$processToken}");
                return false;
            }
            
            // Verificar se o usuário tem permissão para o processo
            if (!$this->statusRepository->userCanAccessProcess($processToken, $userId)) {
                error_log("AsyncProcessNotificationHandler: Usuário {$userId} não tem acesso ao processo {$processToken}");
                return false;
            }
            
            // Verificar se o usuário deseja receber este tipo de notificação
            if (!$this->shouldSendStatusNotification($userId, $newStatus)) {
                error_log("AsyncProcessNotificationHandler: Usuário {$userId} optou por não receber notificações de status '{$newStatus}'");
                return true; // Retornar como sucesso já que foi uma decisão do usuário
            }
            
            // Definir tipo de notificação e prioridade com base no status
            $notificationType = $this->getNotificationTypeForStatus($newStatus);
            $priority = $this->getNotificationPriorityForStatus($newStatus);
            
            // Obter o título do processo para incluir na notificação
            $processTitle = $process['title'] ?? "Processo #{$processToken}";
            
            // Criar título e mensagem para a notificação
            $title = $this->createNotificationTitle($newStatus, $processTitle);
            $message = $this->createNotificationMessage($newStatus, $oldStatus, $processTitle, $context);
            
            // Determinar canais de entrega com base na prioridade e configurações
            $channels = $this->determineChannels($newStatus, $priority);
            
            // Mesclar contexto recebido com informações adicionais
            $notificationContext = array_merge($context, [
                'process_token' => $processToken,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'priority' => $priority,
                'process_type' => $process['type'] ?? 'unknown',
                'completion_percentage' => $process['completion_percentage'] ?? 0,
                'timestamp' => time()
            ]);
            
            // Criar uma URL para visualizar o processo
            $url = $this->createProcessUrl($processToken, $process);
            if ($url) {
                $notificationContext['url'] = $url;
            }
            
            // Enviar notificação
            $notificationId = $this->notificationManager->createNotification(
                $userId,
                $title,
                $message,
                $this->mapStatusToNotificationType($newStatus),
                $notificationContext,
                $channels
            );
            
            // Enviar alertas para administradores em caso de falha
            if ($newStatus === 'failed' && $this->config['alert_admins_on_failure']) {
                $this->sendAdminFailureAlert($processToken, $processTitle, $context);
            }
            
            // Registrar entrega de notificação
            if ($notificationId) {
                $this->logNotificationDelivery($notificationId, $userId, $processToken, $newStatus, $channels);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao processar notificação de status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notifica sobre progresso significativo em um processo
     * 
     * @param string $processToken Token do processo
     * @param int $percentComplete Percentual de conclusão (0-100)
     * @param int $userId ID do usuário dono do processo
     * @param array $context Informações de contexto adicionais
     * @return bool Sucesso na operação
     */
    public function handleProgressUpdate($processToken, $percentComplete, $userId, array $context = []) {
        try {
            // Validar parâmetros
            $processToken = $this->validateString($processToken, ['pattern' => '/^[a-zA-Z0-9]{32}$/']);
            $percentComplete = min(100, max(0, intval($percentComplete)));
            $userId = intval($userId);
            
            if ($processToken === null || $userId <= 0) {
                error_log('AsyncProcessNotificationHandler: Parâmetros inválidos para handleProgressUpdate');
                return false;
            }
            
            // Verificar se o progresso merece uma notificação
            // Somente notificar em marcos significativos para evitar spam
            $milestones = $this->config['progress_milestones'];
            $shouldNotify = false;
            
            foreach ($milestones as $milestone) {
                // Se o percentual está dentro de uma margem pequena do marco (por exemplo, 25% ± 2%)
                if (abs($percentComplete - $milestone) <= $this->config['milestone_margin']) {
                    $shouldNotify = true;
                    break;
                }
            }
            
            if (!$shouldNotify) {
                return true; // Sem ação necessária, não é um marco de progresso
            }
            
            // Verificar se o usuário deseja receber notificações de progresso
            if (!$this->shouldSendProgressNotification($userId)) {
                return true; // Usuário optou por não receber
            }
            
            // Obter detalhes do processo
            $process = $this->statusRepository->getProcessStatus($processToken);
            if (!$process) {
                error_log("AsyncProcessNotificationHandler: Processo não encontrado Token {$processToken}");
                return false;
            }
            
            // Obter o título do processo
            $processTitle = $process['title'] ?? "Processo #{$processToken}";
            
            // Criar título e mensagem para a notificação
            $title = "Progresso: {$processTitle}";
            $message = "Seu processo '{$processTitle}' atingiu {$percentComplete}% de conclusão.";
            
            // Estimar tempo restante, se disponível
            if (isset($context['estimated_completion_time'])) {
                $estimatedTime = $this->formatTimeRemaining($context['estimated_completion_time']);
                $message .= " Tempo restante estimado: {$estimatedTime}.";
            }
            
            // Determinar canais - progresso normalmente é menos prioritário
            $channels = ['database'];
            if ($percentComplete >= 75 && $this->config['send_push_on_high_progress']) {
                $channels[] = 'push';
            }
            
            // Mesclar contexto
            $notificationContext = array_merge($context, [
                'process_token' => $processToken,
                'percent_complete' => $percentComplete,
                'priority' => 'low',
                'process_type' => $process['type'] ?? 'unknown',
                'timestamp' => time()
            ]);
            
            // Criar uma URL para visualizar o processo
            $url = $this->createProcessUrl($processToken, $process);
            if ($url) {
                $notificationContext['url'] = $url;
            }
            
            // Enviar notificação
            return $this->notificationManager->createNotification(
                $userId,
                $title,
                $message,
                'process_progress',
                $notificationContext,
                $channels
            ) !== false;
            
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao processar notificação de progresso: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notifica sobre resultados disponíveis de um processo
     * 
     * @param string $processToken Token do processo
     * @param int $userId ID do usuário dono do processo
     * @param array $results Resultados do processo
     * @param array $context Informações de contexto adicionais
     * @return bool Sucesso na operação
     */
    public function handleResultsAvailable($processToken, $userId, array $results, array $context = []) {
        try {
            // Validar parâmetros
            $processToken = $this->validateString($processToken, ['pattern' => '/^[a-zA-Z0-9]{32}$/']);
            $userId = intval($userId);
            
            if ($processToken === null || $userId <= 0) {
                error_log('AsyncProcessNotificationHandler: Parâmetros inválidos para handleResultsAvailable');
                return false;
            }
            
            // Verificar se o usuário deseja receber notificações de resultados
            if (!$this->shouldSendResultsNotification($userId)) {
                return true; // Usuário optou por não receber
            }
            
            // Obter detalhes do processo
            $process = $this->statusRepository->getProcessStatus($processToken);
            if (!$process) {
                error_log("AsyncProcessNotificationHandler: Processo não encontrado Token {$processToken}");
                return false;
            }
            
            // Obter o título do processo
            $processTitle = $process['title'] ?? "Processo #{$processToken}";
            
            // Criar título e mensagem para a notificação
            $title = "Resultados Disponíveis: {$processTitle}";
            $message = "Os resultados do seu processo '{$processTitle}' estão disponíveis.";
            
            // Adicionar detalhes se fornecidos
            if (!empty($results['summary'])) {
                $message .= " " . $this->validateString($results['summary'], ['maxLength' => 200]);
            }
            
            // Determinar canais - resultados são alta prioridade
            $channels = ['database', 'push'];
            if ($this->config['send_email_with_results']) {
                $channels[] = 'email';
            }
            
            // Preparar contexto
            $notificationContext = array_merge($context, [
                'process_token' => $processToken,
                'priority' => 'high',
                'process_type' => $process['type'] ?? 'unknown',
                'has_download' => !empty($results['download_url']),
                'timestamp' => time()
            ]);
            
            // Incluir informações de download se disponíveis
            if (!empty($results['download_url'])) {
                $notificationContext['download_url'] = $results['download_url'];
            }
            
            // Criar uma URL para visualizar o processo/resultados
            $url = $this->createProcessUrl($processToken, $process, 'results');
            if ($url) {
                $notificationContext['url'] = $url;
            }
            
            // Enviar notificação
            return $this->notificationManager->createNotification(
                $userId,
                $title,
                $message,
                'process_results',
                $notificationContext,
                $channels
            ) !== false;
            
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao processar notificação de resultados: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se um processo está próximo de expirar e envia notificação
     * 
     * @param string $processToken Token do processo
     * @param int $userId ID do usuário dono do processo
     * @param \DateTime $expiresAt Data/hora de expiração
     * @return bool Sucesso na operação
     */
    public function handleExpirationWarning($processToken, $userId, \DateTime $expiresAt) {
        try {
            // Validar parâmetros
            $processToken = $this->validateString($processToken, ['pattern' => '/^[a-zA-Z0-9]{32}$/']);
            $userId = intval($userId);
            
            if ($processToken === null || $userId <= 0) {
                error_log('AsyncProcessNotificationHandler: Parâmetros inválidos para handleExpirationWarning');
                return false;
            }
            
            // Verificar se o usuário deseja receber notificações de expiração
            if (!$this->shouldSendExpirationNotification($userId)) {
                return true; // Usuário optou por não receber
            }
            
            // Calcular tempo restante em horas
            $now = new \DateTime();
            $timeRemaining = $now->diff($expiresAt);
            $hoursRemaining = ($timeRemaining->days * 24) + $timeRemaining->h;
            
            // Verificar se está dentro da janela de aviso configurada
            if ($hoursRemaining > $this->config['expiration_warning_hours']) {
                return true; // Ainda não está próximo o suficiente para alertar
            }
            
            // Obter detalhes do processo
            $process = $this->statusRepository->getProcessStatus($processToken);
            if (!$process) {
                error_log("AsyncProcessNotificationHandler: Processo não encontrado Token {$processToken}");
                return false;
            }
            
            // Obter o título do processo
            $processTitle = $process['title'] ?? "Processo #{$processToken}";
            
            // Formatar tempo restante
            $formattedTime = $this->formatExpirationTime($expiresAt);
            
            // Criar título e mensagem para a notificação
            $title = "Aviso de Expiração: {$processTitle}";
            $message = "Seu processo '{$processTitle}' expirará em {$formattedTime}. ";
            
            if ($process['status'] === 'completed') {
                $message .= "Por favor, baixe os resultados antes que expirem.";
            } else {
                $message .= "Verifique o status e tome as medidas necessárias.";
            }
            
            // Determinar canais - expiração é prioridade média
            $channels = ['database', 'push'];
            
            // Preparar contexto
            $notificationContext = [
                'process_token' => $processToken,
                'priority' => 'medium',
                'process_type' => $process['type'] ?? 'unknown',
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'hours_remaining' => $hoursRemaining,
                'timestamp' => time()
            ];
            
            // Criar uma URL para visualizar o processo
            $url = $this->createProcessUrl($processToken, $process);
            if ($url) {
                $notificationContext['url'] = $url;
            }
            
            // Enviar notificação
            return $this->notificationManager->createNotification(
                $userId,
                $title,
                $message,
                'process_expiration',
                $notificationContext,
                $channels
            ) !== false;
            
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao processar notificação de expiração: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia uma notificação de alerta aos administradores quando um processo falha
     * 
     * @param string $processToken Token do processo
     * @param string $processTitle Título do processo
     * @param array $context Informações de contexto do erro
     * @return bool Sucesso na operação
     */
    private function sendAdminFailureAlert($processToken, $processTitle, array $context) {
        try {
            // Obter IDs de administradores do sistema
            $adminIds = $this->getAdminUserIds();
            
            if (empty($adminIds)) {
                error_log("AsyncProcessNotificationHandler: Nenhum administrador encontrado para notificação de falha");
                return false;
            }
            
            // Extrair informações de erro do contexto
            $errorMessage = $context['error_message'] ?? 'Erro desconhecido';
            $errorCode = $context['error_code'] ?? 'UNKNOWN';
            
            // Criar título e mensagem para a notificação
            $title = "ALERTA: Falha em Processo Assíncrono";
            $message = "O processo '{$processTitle}' (token: {$processToken}) falhou com o erro: {$errorMessage} (código: {$errorCode}).";
            
            // Determinar canais
            $channels = ['database', 'push'];
            
            // Preparar contexto
            $notificationContext = array_merge($context, [
                'process_token' => $processToken,
                'priority' => 'high',
                'timestamp' => time(),
                'admin_notification' => true
            ]);
            
            // Criar URL para painel administrativo
            $notificationContext['url'] = "/admin/async-processes/details/{$processToken}";
            
            // Enviar notificação para cada administrador
            $success = true;
            foreach ($adminIds as $adminId) {
                $result = $this->notificationManager->createNotification(
                    $adminId,
                    $title,
                    $message,
                    'admin_process_failure',
                    $notificationContext,
                    $channels
                );
                
                $success = $success && ($result !== false);
            }
            
            return $success;
            
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao enviar alerta de falha para admins: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra log de entrega de notificação
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário
     * @param string $processToken Token do processo
     * @param string $status Status do processo
     * @param array $channels Canais de entrega
     * @return void
     */
    private function logNotificationDelivery($notificationId, $userId, $processToken, $status, array $channels) {
        try {
            if (!$this->pdo) {
                return;
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO async_notification_logs 
                (notification_id, user_id, process_token, process_status, delivery_channels, timestamp)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $notificationId,
                $userId,
                $processToken,
                $status,
                json_encode($channels)
            ]);
            
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao registrar log de notificação: " . $e->getMessage());
        }
    }
    
    /**
     * Cria um título de notificação com base no status
     * 
     * @param string $status Status do processo
     * @param string $processTitle Título do processo
     * @return string Título da notificação
     */
    private function createNotificationTitle($status, $processTitle) {
        switch ($status) {
            case 'pending':
                return "Processo Iniciado: {$processTitle}";
            case 'processing':
                return "Processamento em Andamento: {$processTitle}";
            case 'completed':
                return "Processo Concluído: {$processTitle}";
            case 'failed':
                return "Falha no Processo: {$processTitle}";
            case 'cancelled':
                return "Processo Cancelado: {$processTitle}";
            default:
                return "Atualização de Status: {$processTitle}";
        }
    }
    
    /**
     * Cria uma mensagem de notificação com base no status
     * 
     * @param string $status Status atual
     * @param string $oldStatus Status anterior
     * @param string $processTitle Título do processo
     * @param array $context Informações de contexto
     * @return string Mensagem da notificação
     */
    private function createNotificationMessage($status, $oldStatus, $processTitle, array $context) {
        switch ($status) {
            case 'pending':
                return "Seu processo '{$processTitle}' foi recebido e está aguardando processamento.";
                
            case 'processing':
                return "Seu processo '{$processTitle}' está sendo processado ativamente. Você receberá uma notificação quando for concluído.";
                
            case 'completed':
                return "Seu processo '{$processTitle}' foi concluído com sucesso! Você já pode acessar os resultados.";
                
            case 'failed':
                $errorMessage = $context['error_message'] ?? 'Ocorreu um erro inesperado.';
                return "Seu processo '{$processTitle}' falhou. Erro: {$errorMessage}";
                
            case 'cancelled':
                $cancelReason = $context['cancel_reason'] ?? 'Nenhuma razão especificada.';
                return "Seu processo '{$processTitle}' foi cancelado. Motivo: {$cancelReason}";
                
            default:
                return "O status do seu processo '{$processTitle}' foi atualizado de '{$oldStatus}' para '{$status}'.";
        }
    }
    
    /**
     * Determina os canais de notificação baseado no status e prioridade
     * 
     * @param string $status Status do processo
     * @param string $priority Prioridade da notificação
     * @return array Canais de notificação
     */
    private function determineChannels($status, $priority) {
        // Todos recebem notificação no banco de dados
        $channels = ['database'];
        
        // Enviar push para notificações de maior prioridade
        if ($priority === 'high' || $status === 'completed' || $status === 'failed') {
            $channels[] = 'push';
        }
        
        // Enviar e-mail apenas para conclusões e falhas (se configurado)
        if (($status === 'completed' || $status === 'failed') && $this->config['send_email_notifications']) {
            $channels[] = 'email';
        }
        
        return $channels;
    }
    
    /**
     * Obtém o tipo de notificação baseado no status
     * 
     * @param string $status Status do processo
     * @return string Tipo de notificação
     */
    private function getNotificationTypeForStatus($status) {
        return self::$statusToNotificationType[$status] ?? 'process_status_change';
    }
    
    /**
     * Mapeia o status do processo para um tipo de notificação
     * 
     * @param string $status Status do processo
     * @return string Tipo de notificação
     */
    private function mapStatusToNotificationType($status) {
        switch ($status) {
            case 'completed':
                return 'process_completed';
            case 'failed':
                return 'process_failed';
            case 'processing':
                return 'process_processing';
            case 'cancelled':
                return 'process_cancelled';
            default:
                return 'process_status';
        }
    }
    
    /**
     * Obtém a prioridade da notificação baseado no status
     * 
     * @param string $status Status do processo
     * @return string Prioridade da notificação
     */
    private function getNotificationPriorityForStatus($status) {
        switch ($status) {
            case 'completed':
            case 'failed':
                return 'high';
            case 'processing':
            case 'cancelled':
                return 'medium';
            default:
                return 'low';
        }
    }
    
    /**
     * Cria uma URL para acessar o processo no sistema
     * 
     * @param string $processToken Token do processo
     * @param array $process Dados do processo
     * @param string $view Vista específica (detalhes, resultados, etc)
     * @return string URL do processo
     */
    private function createProcessUrl($processToken, array $process, $view = 'details') {
        // Determinar tipo de processo para URL correta
        $processType = $process['type'] ?? 'generic';
        
        switch ($processType) {
            case 'quotation':
                return "/customer/quotation/{$processToken}/{$view}";
            case 'model_validation':
                return "/customer/models/{$processToken}/{$view}";
            case 'print_job':
                return "/customer/print-jobs/{$processToken}/{$view}";
            case 'report':
                return "/customer/reports/{$processToken}/{$view}";
            default:
                return "/status-tracking?token={$processToken}";
        }
    }
    
    /**
     * Verifica se um usuário existe
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se o usuário existe
     */
    private function validateUser($userId) {
        try {
            if (!$this->pdo) {
                return true; // Sem PDO, não podemos verificar
            }
            
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao validar usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário deseja receber notificações de status
     * 
     * @param int $userId ID do usuário
     * @param string $status Status do processo
     * @return bool Verdadeiro se deve enviar notificação
     */
    private function shouldSendStatusNotification($userId, $status) {
        // Mapear status para tipo de notificação
        $notificationType = $this->getNotificationTypeForStatus($status);
        
        // Se for um status crítico como 'completed' ou 'failed', sempre enviar
        if ($status === 'completed' || $status === 'failed') {
            return true;
        }
        
        // Para outros status, verificar preferências do usuário
        return $this->checkUserPreference($userId, $notificationType);
    }
    
    /**
     * Verifica se o usuário deseja receber notificações de progresso
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se deve enviar notificação
     */
    private function shouldSendProgressNotification($userId) {
        return $this->checkUserPreference($userId, 'process_progress');
    }
    
    /**
     * Verifica se o usuário deseja receber notificações de resultados
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se deve enviar notificação
     */
    private function shouldSendResultsNotification($userId) {
        // Resultados são considerados críticos, então sempre enviar por padrão
        return true;
    }
    
    /**
     * Verifica se o usuário deseja receber notificações de expiração
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se deve enviar notificação
     */
    private function shouldSendExpirationNotification($userId) {
        return $this->checkUserPreference($userId, 'process_expiration');
    }
    
    /**
     * Verifica a preferência do usuário para um tipo de notificação
     * 
     * @param int $userId ID do usuário
     * @param string $notificationType Tipo de notificação
     * @return bool Verdadeiro se o usuário aceita este tipo
     */
    private function checkUserPreference($userId, $notificationType) {
        try {
            if (!$this->pdo) {
                return true; // Sem PDO, assumir preferência padrão (permitir)
            }
            
            // Verificar nas preferências do usuário
            $stmt = $this->pdo->prepare("
                SELECT unp.is_enabled
                FROM user_notification_preferences unp
                JOIN notification_types nt ON unp.notification_type_id = nt.id
                WHERE unp.user_id = ? AND nt.code = ?
                LIMIT 1
            ");
            
            $stmt->execute([$userId, $notificationType]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Se não houver preferência específica, usar padrão (permitir)
            if (!$result) {
                return true;
            }
            
            return (bool)$result['is_enabled'];
            
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao verificar preferências: " . $e->getMessage());
            return true; // Em caso de erro, assumir que pode enviar
        }
    }
    
    /**
     * Obtém IDs de usuários administradores
     * 
     * @return array Array de IDs de usuários admins
     */
    private function getAdminUserIds() {
        try {
            if (!$this->pdo) {
                return $this->config['default_admin_ids']; // Fallback para configuração estática
            }
            
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
            $stmt->execute();
            
            $adminIds = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $adminIds[] = (int)$row['id'];
            }
            
            return $adminIds;
            
        } catch (\Exception $e) {
            error_log("AsyncProcessNotificationHandler: Erro ao obter administradores: " . $e->getMessage());
            return $this->config['default_admin_ids'];
        }
    }
    
    /**
     * Formata o tempo restante para expiração
     * 
     * @param \DateTime $expiresAt Data/hora de expiração
     * @return string Tempo formatado
     */
    private function formatExpirationTime(\DateTime $expiresAt) {
        $now = new \DateTime();
        $diff = $now->diff($expiresAt);
        
        if ($diff->days > 0) {
            return "{$diff->days} dias e {$diff->h} horas";
        } elseif ($diff->h > 0) {
            return "{$diff->h} horas e {$diff->i} minutos";
        } else {
            return "{$diff->i} minutos";
        }
    }
    
    /**
     * Formata o tempo restante estimado
     * 
     * @param int $seconds Segundos restantes
     * @return string Tempo formatado
     */
    private function formatTimeRemaining($seconds) {
        $seconds = (int)$seconds;
        
        if ($seconds < 60) {
            return "menos de um minuto";
        }
        
        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            return $minutes . " " . ($minutes == 1 ? "minuto" : "minutos");
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes == 0) {
            return $hours . " " . ($hours == 1 ? "hora" : "horas");
        }
        
        return $hours . " " . ($hours == 1 ? "hora" : "horas") . " e " . 
               $remainingMinutes . " " . ($remainingMinutes == 1 ? "minuto" : "minutos");
    }
    
    /**
     * Retorna configurações padrão
     * 
     * @return array Configurações padrão
     */
    private function getDefaultConfig() {
        return [
            'progress_milestones' => [25, 50, 75, 90], // Pontos de progresso para notificar
            'milestone_margin' => 2, // Margem de erro para marcos (ex: 25% ± 2%)
            'send_email_notifications' => true,
            'send_push_on_high_progress' => true,
            'send_email_with_results' => true,
            'alert_admins_on_failure' => true,
            'expiration_warning_hours' => 24, // Alertar sobre expiração 24h antes
            'default_admin_ids' => [1], // IDs de admin fallback
        ];
    }
}
