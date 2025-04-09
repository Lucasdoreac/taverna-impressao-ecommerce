<?php
/**
 * PrintQueueNotificationHandler - Manipulador de notificações para eventos da fila de impressão
 * 
 * Esta classe integra o sistema de fila de impressão com o sistema de notificações,
 * permitindo o envio automático de notificações em resposta a eventos da fila.
 * 
 * @package App\Lib\Notification
 * @version 1.0.0
 * @author Taverna da Impressão
 */
require_once dirname(__FILE__) . '/NotificationManager.php';
require_once dirname(dirname(__FILE__)) . '/../models/NotificationModel.php';
require_once dirname(dirname(__FILE__)) . '/../models/NotificationPreferenceModel.php';
require_once dirname(dirname(__FILE__)) . '/../models/PrintJobModel.php';
require_once dirname(dirname(__FILE__)) . '/../models/PrintQueueModel.php';
require_once dirname(dirname(__FILE__)) . '/../models/UserModel.php';

class PrintQueueNotificationHandler {
    /**
     * Instância singleton
     * 
     * @var PrintQueueNotificationHandler
     */
    private static $instance;
    
    /**
     * Gerenciador de notificações
     * 
     * @var NotificationManager
     */
    private $notificationManager;
    
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
    private $preferenceModel;
    
    /**
     * Modelo de trabalhos de impressão
     * 
     * @var PrintJobModel
     */
    private $printJobModel;
    
    /**
     * Modelo de fila de impressão
     * 
     * @var PrintQueueModel
     */
    private $printQueueModel;
    
    /**
     * Modelo de usuários
     * 
     * @var UserModel
     */
    private $userModel;
    
    /**
     * Construtor privado (padrão singleton)
     * 
     * @param \PDO $pdo Conexão com o banco de dados (opcional)
     */
    private function __construct(\PDO $pdo = null) {
        // Inicializar modelos e gerenciadores
        $this->notificationManager = NotificationManager::getInstance($pdo);
        $this->notificationModel = new NotificationModel();
        $this->preferenceModel = new NotificationPreferenceModel();
        $this->printJobModel = new PrintJobModel();
        $this->printQueueModel = new PrintQueueModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * Obtém a instância singleton
     * 
     * @param \PDO $pdo Conexão com o banco de dados (opcional)
     * @return PrintQueueNotificationHandler
     */
    public static function getInstance(\PDO $pdo = null) {
        if (self::$instance === null) {
            self::$instance = new self($pdo);
        }
        
        return self::$instance;
    }
    
    /**
     * Manipula notificações para alterações de status de trabalho de impressão
     * 
     * @param int $jobId ID do trabalho de impressão
     * @param string $oldStatus Status anterior
     * @param string $newStatus Novo status
     * @param int $adminId ID do administrador que fez a alteração (opcional)
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function handleJobStatusChange($jobId, $oldStatus, $newStatus, $adminId = null) {
        try {
            // Obter informações do trabalho
            $job = $this->printJobModel->getJobById($jobId);
            
            if (!$job) {
                app_log('ERROR', "Trabalho de impressão #{$jobId} não encontrado ao tentar enviar notificação");
                return false;
            }
            
            // Obter informações do usuário
            $userId = $job['user_id'];
            
            // Verificar se o usuário deseja receber este tipo de notificação
            if (!$this->shouldSendJobStatusNotification($userId, $newStatus)) {
                app_log('INFO', "Usuário #{$userId} optou por não receber notificações de status de impressão '{$newStatus}'");
                return true; // Retornar como se tivesse sido enviado
            }
            
            // Definir tipo de notificação com base no status
            $notificationType = $this->getNotificationTypeForStatus($newStatus);
            
            // Definir prioridade da notificação
            $priority = $this->getNotificationPriorityForStatus($newStatus);
            
            // Obter produto e detalhes de impressão
            $productId = $job['product_id'];
            $productName = $this->getProductName($productId);
            $estimatedTime = $job['estimated_print_time_hours'];
            
            // Criar mensagem baseada no status
            $title = $this->getNotificationTitleForStatus($newStatus);
            $message = $this->getNotificationMessageForStatus($newStatus, $productName, $estimatedTime, $jobId);
            
            // Criar link para detalhes do trabalho
            $link = "/minha-conta/impressoes/{$jobId}";
            
            // Enviar notificação através do gerenciador
            $notificationResult = $this->notificationManager->sendUserNotification(
                $userId,
                $notificationType,
                $title,
                $message,
                [
                    'link' => $link,
                    'priority' => $priority,
                    'job_id' => $jobId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'product_id' => $productId,
                    'admin_id' => $adminId
                ]
            );
            
            // Registrar notificação específica de fila no modelo de notificações
            $modelResult = $this->notificationModel->createQueueStatusNotification(
                $job['queue_item_id'],
                $newStatus,
                $userId,
                $adminId ?? 0
            );
            
            // Enviar alerta para administradores em caso de falha
            if ($newStatus === 'failed') {
                $this->notificationManager->sendAdminNotification(
                    'print_failure',
                    "Falha na Impressão #{$jobId}",
                    "O trabalho de impressão #{$jobId} ({$productName}) falhou e requer atenção.",
                    [
                        'link' => "/admin/print-jobs/details/{$jobId}",
                        'priority' => 'high'
                    ]
                );
            }
            
            return $notificationResult || $modelResult;
            
        } catch (\Exception $e) {
            app_log('ERROR', "Erro ao processar notificação de status de impressão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Manipula notificações para atribuição de impressora
     * 
     * @param int $jobId ID do trabalho de impressão
     * @param int $printerId ID da impressora
     * @param int $adminId ID do administrador que fez a atribuição (opcional)
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function handlePrinterAssignment($jobId, $printerId, $adminId = null) {
        try {
            // Obter informações do trabalho
            $job = $this->printJobModel->getJobById($jobId);
            
            if (!$job) {
                app_log('ERROR', "Trabalho de impressão #{$jobId} não encontrado ao tentar enviar notificação");
                return false;
            }
            
            // Obter informações do usuário
            $userId = $job['user_id'];
            
            // Verificar se o usuário deseja receber este tipo de notificação
            if (!$this->shouldSendPrinterAssignmentNotification($userId)) {
                app_log('INFO', "Usuário #{$userId} optou por não receber notificações de atribuição de impressora");
                return true; // Retornar como se tivesse sido enviado
            }
            
            // Obter informações da impressora
            $printer = $this->printQueueModel->getPrinterById($printerId);
            $printerName = $printer ? $printer['name'] : "Impressora #{$printerId}";
            
            // Obter produto e detalhes de impressão
            $productId = $job['product_id'];
            $productName = $this->getProductName($productId);
            
            // Criar notificação
            $title = "Impressora Atribuída";
            $message = "Seu produto '{$productName}' foi atribuído à impressora '{$printerName}' e será impresso em breve.";
            $link = "/minha-conta/impressoes/{$jobId}";
            
            // Enviar notificação através do gerenciador
            $notificationResult = $this->notificationManager->sendUserNotification(
                $userId,
                'printer_assignment',
                $title,
                $message,
                [
                    'link' => $link,
                    'priority' => 'normal',
                    'job_id' => $jobId,
                    'printer_id' => $printerId,
                    'product_id' => $productId,
                    'admin_id' => $adminId
                ]
            );
            
            // Registrar notificação específica de atribuição no modelo de notificações
            $modelResult = $this->notificationModel->createPrinterAssignmentNotification(
                $job['queue_item_id'],
                $printerId,
                $userId,
                $adminId ?? 0
            );
            
            return $notificationResult || $modelResult;
            
        } catch (\Exception $e) {
            app_log('ERROR', "Erro ao processar notificação de atribuição de impressora: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Manipula notificações para conclusão estimada de impressão
     * 
     * @param int $jobId ID do trabalho de impressão
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function handleEstimatedCompletionReminder($jobId) {
        try {
            // Obter informações do trabalho
            $job = $this->printJobModel->getJobById($jobId);
            
            if (!$job) {
                app_log('ERROR', "Trabalho de impressão #{$jobId} não encontrado ao tentar enviar notificação");
                return false;
            }
            
            // Verificar se o trabalho está em impressão
            if ($job['status'] !== 'printing') {
                return true; // Não enviar lembrete se não estiver em impressão
            }
            
            // Obter informações do usuário
            $userId = $job['user_id'];
            
            // Verificar se o usuário deseja receber este tipo de notificação
            if (!$this->shouldSendCompletionReminderNotification($userId)) {
                app_log('INFO', "Usuário #{$userId} optou por não receber notificações de lembretes de conclusão");
                return true; // Retornar como se tivesse sido enviado
            }
            
            // Obter produto e detalhes de impressão
            $productId = $job['product_id'];
            $productName = $this->getProductName($productId);
            
            // Calcular tempo restante estimado (em minutos)
            $startTime = strtotime($job['print_started_at']);
            $estimatedDuration = $job['estimated_print_time_minutes'];
            $currentTime = time();
            $elapsedTime = ($currentTime - $startTime) / 60; // em minutos
            $remainingTime = max(0, $estimatedDuration - $elapsedTime);
            
            // Formatar tempo restante
            $formattedTime = $this->formatRemainingTime($remainingTime);
            
            // Criar notificação
            $title = "Atualização de Impressão 3D";
            $message = "Seu produto '{$productName}' está sendo impresso. Tempo restante estimado: {$formattedTime}.";
            $link = "/minha-conta/impressoes/{$jobId}";
            
            // Enviar notificação através do gerenciador
            return $this->notificationManager->sendUserNotification(
                $userId,
                'print_progress',
                $title,
                $message,
                [
                    'link' => $link,
                    'priority' => 'low',
                    'job_id' => $jobId,
                    'product_id' => $productId,
                    'remaining_time' => $remainingTime
                ]
            );
            
        } catch (\Exception $e) {
            app_log('ERROR', "Erro ao processar notificação de lembrete de conclusão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Manipula notificações para configuração de preferências
     * 
     * @param int $userId ID do usuário
     * @param array $preferences Novas preferências
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function handlePreferenceUpdate($userId, $preferences) {
        try {
            // Verificar se o usuário existe
            $user = $this->userModel->getUserById($userId);
            
            if (!$user) {
                app_log('ERROR', "Usuário #{$userId} não encontrado ao tentar atualizar preferências de notificação");
                return false;
            }
            
            // Atualizar preferências em lote
            $result = $this->preferenceModel->updateMultiplePreferences($userId, $preferences);
            
            if ($result) {
                // Enviar notificação de confirmação
                $this->notificationManager->sendUserNotification(
                    $userId,
                    'system_notification',
                    'Preferências de Notificação Atualizadas',
                    'Suas preferências de notificação foram atualizadas com sucesso.',
                    [
                        'link' => '/minha-conta/notification-preferences',
                        'priority' => 'low'
                    ]
                );
            }
            
            return $result;
            
        } catch (\Exception $e) {
            app_log('ERROR', "Erro ao processar atualização de preferências de notificação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se deve enviar notificação de status de trabalho
     * 
     * @param int $userId ID do usuário
     * @param string $status Status do trabalho
     * @return bool Verdadeiro se deve enviar notificação
     */
    private function shouldSendJobStatusNotification($userId, $status) {
        // Mapear status para código de tipo de notificação
        $typeCode = "print_status_{$status}";
        
        // Verificar canais disponíveis
        $webNotification = $this->preferenceModel->shouldSendNotification($userId, $typeCode, 'website');
        $pushNotification = $this->preferenceModel->shouldSendNotification($userId, $typeCode, 'push');
        $emailNotification = $this->preferenceModel->shouldSendNotification($userId, $typeCode, 'email');
        
        // Se pelo menos um canal estiver habilitado, enviar notificação
        return $webNotification || $pushNotification || $emailNotification;
    }
    
    /**
     * Verifica se deve enviar notificação de atribuição de impressora
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se deve enviar notificação
     */
    private function shouldSendPrinterAssignmentNotification($userId) {
        // Verificar canais disponíveis
        $webNotification = $this->preferenceModel->shouldSendNotification($userId, 'printer_assignment', 'website');
        $pushNotification = $this->preferenceModel->shouldSendNotification($userId, 'printer_assignment', 'push');
        $emailNotification = $this->preferenceModel->shouldSendNotification($userId, 'printer_assignment', 'email');
        
        // Se pelo menos um canal estiver habilitado, enviar notificação
        return $webNotification || $pushNotification || $emailNotification;
    }
    
    /**
     * Verifica se deve enviar notificação de lembrete de conclusão
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se deve enviar notificação
     */
    private function shouldSendCompletionReminderNotification($userId) {
        // Verificar canais disponíveis
        $webNotification = $this->preferenceModel->shouldSendNotification($userId, 'print_progress', 'website');
        $pushNotification = $this->preferenceModel->shouldSendNotification($userId, 'print_progress', 'push');
        $emailNotification = $this->preferenceModel->shouldSendNotification($userId, 'print_progress', 'email');
        
        // Se pelo menos um canal estiver habilitado, enviar notificação
        return $webNotification || $pushNotification || $emailNotification;
    }
    
    /**
     * Obtém o título da notificação com base no status
     * 
     * @param string $status Status do trabalho
     * @return string Título da notificação
     */
    private function getNotificationTitleForStatus($status) {
        switch ($status) {
            case 'queued':
                return 'Trabalho de Impressão na Fila';
            case 'validating':
                return 'Validação de Modelo 3D Iniciada';
            case 'approved':
                return 'Modelo 3D Aprovado';
            case 'rejected':
                return 'Modelo 3D Rejeitado';
            case 'printing':
                return 'Impressão 3D Iniciada';
            case 'paused':
                return 'Impressão 3D Pausada';
            case 'resumed':
                return 'Impressão 3D Retomada';
            case 'completed':
                return 'Impressão 3D Concluída';
            case 'failed':
                return 'Falha na Impressão 3D';
            case 'cancelled':
                return 'Impressão 3D Cancelada';
            default:
                return 'Atualização do Status da Impressão 3D';
        }
    }
    
    /**
     * Obtém a mensagem da notificação com base no status
     * 
     * @param string $status Status do trabalho
     * @param string $productName Nome do produto
     * @param float $estimatedTime Tempo estimado (em horas)
     * @param int $jobId ID do trabalho
     * @return string Mensagem da notificação
     */
    private function getNotificationMessageForStatus($status, $productName, $estimatedTime, $jobId) {
        switch ($status) {
            case 'queued':
                return "Seu produto '{$productName}' foi adicionado à fila de impressão. Você receberá uma notificação quando a impressão começar.";
            case 'validating':
                return "Seu modelo para o produto '{$productName}' está sendo validado para impressão 3D. Você receberá uma notificação quando a validação for concluída.";
            case 'approved':
                return "Seu modelo para o produto '{$productName}' foi aprovado para impressão 3D e está aguardando na fila.";
            case 'rejected':
                return "Seu modelo para o produto '{$productName}' foi rejeitado para impressão 3D. Por favor, verifique os detalhes e entre em contato com nossa equipe.";
            case 'printing':
                return "Seu produto '{$productName}' começou a ser impresso em 3D. Tempo estimado de impressão: {$estimatedTime} horas.";
            case 'paused':
                return "A impressão do seu produto '{$productName}' foi temporariamente pausada. Você receberá uma notificação quando for retomada.";
            case 'resumed':
                return "A impressão do seu produto '{$productName}' foi retomada e está em andamento.";
            case 'completed':
                return "A impressão 3D do seu produto '{$productName}' foi concluída com sucesso. Seu pedido agora entrará na fase de acabamento.";
            case 'failed':
                return "Ocorreu um problema durante a impressão 3D do seu produto '{$productName}'. Nossa equipe entrará em contato para resolver a situação.";
            case 'cancelled':
                return "A impressão 3D do seu produto '{$productName}' foi cancelada. Entre em contato com nossa equipe para mais informações.";
            default:
                return "O status da impressão 3D do seu produto '{$productName}' foi atualizado para '{$status}'.";
        }
    }
    
    /**
     * Obtém o tipo de notificação com base no status
     * 
     * @param string $status Status do trabalho
     * @return string Tipo de notificação
     */
    private function getNotificationTypeForStatus($status) {
        switch ($status) {
            case 'completed':
                return 'print_completed';
            case 'failed':
                return 'print_failed';
            case 'printing':
                return 'print_started';
            default:
                return 'print_status';
        }
    }
    
    /**
     * Obtém a prioridade da notificação com base no status
     * 
     * @param string $status Status do trabalho
     * @return string Prioridade da notificação
     */
    private function getNotificationPriorityForStatus($status) {
        switch ($status) {
            case 'completed':
            case 'failed':
                return 'high';
            case 'printing':
            case 'paused':
                return 'normal';
            default:
                return 'low';
        }
    }
    
    /**
     * Obtém o nome do produto pelo ID
     * 
     * @param int $productId ID do produto
     * @return string Nome do produto
     */
    private function getProductName($productId) {
        // Esta função deveria obter o nome do produto do banco de dados
        // Simulamos um nome genérico para simplificar
        return "Produto #{$productId}";
    }
    
    /**
     * Formata o tempo restante em formato legível
     * 
     * @param float $minutes Minutos restantes
     * @return string Tempo formatado
     */
    private function formatRemainingTime($minutes) {
        if ($minutes < 1) {
            return "menos de 1 minuto";
        }
        
        if ($minutes < 60) {
            return round($minutes) . " minutos";
        }
        
        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);
        
        if ($mins == 0) {
            return "{$hours} " . ($hours == 1 ? "hora" : "horas");
        }
        
        return "{$hours} " . ($hours == 1 ? "hora" : "horas") . " e {$mins} minutos";
    }
}
