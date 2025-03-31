<?php
/**
 * NotificationPreferenceModel - Modelo para gerenciamento de preferências de notificação
 * 
 * Este modelo gerencia as preferências de notificação para usuários, permitindo a personalização
 * dos tipos de notificações que desejam receber, através de quais canais, e com qual frequência.
 * 
 * Características principais:
 * - Gerenciamento de preferências por usuário, tipo de notificação e canal
 * - Garantia de que notificações críticas não sejam desativadas
 * - Configuração de frequência de entrega (tempo real, diária, semanal)
 * - Integração com o sistema de notificação existente
 * - Análise de engajamento e estatísticas de uso
 * 
 * @version 1.0.0
 * @author Taverna da Impressão
 */
class NotificationPreferenceModel {
    private $db;
    private $cache = []; // Cache para otimização de consultas repetidas
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém todos os tipos de notificação disponíveis
     * 
     * @param string $category Filtrar por categoria (opcional)
     * @return array Lista de tipos de notificação
     */
    public function getAllNotificationTypes($category = null) {
        try {
            // Verificar cache
            $cacheKey = "notification_types" . ($category ? "_" . $category : "");
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $sql = "SELECT * FROM notification_types";
            $params = [];
            
            if ($category) {
                $sql .= " WHERE category = :category";
                $params['category'] = $category;
            }
            
            $sql .= " ORDER BY category, name";
            
            $result = $this->db->select($sql, $params);
            
            // Armazenar em cache
            $this->cache[$cacheKey] = $result;
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar tipos de notificação: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém um tipo de notificação específico por seu código
     * 
     * @param string $code Código do tipo de notificação
     * @return array|null Dados do tipo de notificação ou null se não encontrado
     */
    public function getNotificationTypeByCode($code) {
        try {
            // Verificar cache
            $cacheKey = "notification_type_" . $code;
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $sql = "SELECT * FROM notification_types WHERE code = :code";
            $params = ['code' => $code];
            
            $result = $this->db->select($sql, $params);
            
            $type = !empty($result) ? $result[0] : null;
            
            // Armazenar em cache
            if ($type) {
                $this->cache[$cacheKey] = $type;
            }
            
            return $type;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar tipo de notificação por código: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém todos os canais de notificação disponíveis
     * 
     * @param bool $onlyActive Filtrar apenas canais ativos
     * @return array Lista de canais de notificação
     */
    public function getAllNotificationChannels($onlyActive = true) {
        try {
            // Verificar cache
            $cacheKey = "notification_channels" . ($onlyActive ? "_active" : "");
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $sql = "SELECT * FROM notification_channels";
            
            if ($onlyActive) {
                $sql .= " WHERE is_active = 1";
            }
            
            $sql .= " ORDER BY name";
            
            $result = $this->db->select($sql);
            
            // Armazenar em cache
            $this->cache[$cacheKey] = $result;
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar canais de notificação: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém um canal de notificação específico por seu código
     * 
     * @param string $code Código do canal de notificação
     * @return array|null Dados do canal de notificação ou null se não encontrado
     */
    public function getNotificationChannelByCode($code) {
        try {
            // Verificar cache
            $cacheKey = "notification_channel_" . $code;
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $sql = "SELECT * FROM notification_channels WHERE code = :code";
            $params = ['code' => $code];
            
            $result = $this->db->select($sql, $params);
            
            $channel = !empty($result) ? $result[0] : null;
            
            // Armazenar em cache
            if ($channel) {
                $this->cache[$cacheKey] = $channel;
            }
            
            return $channel;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar canal de notificação por código: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém as preferências de notificação de um usuário
     * 
     * @param int $userId ID do usuário
     * @param bool $groupedByType Agrupar por tipo de notificação
     * @return array Preferências do usuário
     */
    public function getUserPreferences($userId, $groupedByType = true) {
        try {
            // Verificar cache
            $cacheKey = "user_{$userId}_preferences" . ($groupedByType ? "_grouped" : "");
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            // Buscar preferências existentes
            $sql = "SELECT unp.*, nt.code as type_code, nt.name as type_name, nt.category, nt.is_critical,
                          nc.code as channel_code, nc.name as channel_name
                   FROM user_notification_preferences unp
                   INNER JOIN notification_types nt ON unp.notification_type_id = nt.id
                   INNER JOIN notification_channels nc ON unp.notification_channel_id = nc.id
                   WHERE unp.user_id = :userId
                   ORDER BY nt.category, nt.name, nc.name";
            
            $params = ['userId' => $userId];
            
            $result = $this->db->select($sql, $params);
            
            if ($groupedByType) {
                // Agrupar por tipo de notificação
                $grouped = [];
                foreach ($result as $row) {
                    $typeCode = $row['type_code'];
                    if (!isset($grouped[$typeCode])) {
                        $grouped[$typeCode] = [
                            'id' => $row['notification_type_id'],
                            'code' => $typeCode,
                            'name' => $row['type_name'],
                            'category' => $row['category'],
                            'is_critical' => $row['is_critical'],
                            'channels' => []
                        ];
                    }
                    
                    $grouped[$typeCode]['channels'][$row['channel_code']] = [
                        'id' => $row['notification_channel_id'],
                        'code' => $row['channel_code'],
                        'name' => $row['channel_name'],
                        'is_enabled' => $row['is_enabled'],
                        'frequency' => $row['frequency']
                    ];
                }
                
                $result = $grouped;
            }
            
            // Armazenar em cache
            $this->cache[$cacheKey] = $result;
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar preferências do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica se o usuário tem preferências configuradas
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se o usuário já tem preferências configuradas
     */
    public function hasUserPreferences($userId) {
        try {
            $sql = "SELECT COUNT(*) as total FROM user_notification_preferences WHERE user_id = :userId";
            $params = ['userId' => $userId];
            
            $result = $this->db->select($sql, $params);
            
            return isset($result[0]['total']) && $result[0]['total'] > 0;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao verificar preferências do usuário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Inicializa as preferências padrão para um usuário
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function initializeDefaultPreferences($userId) {
        try {
            // Verificar se já existem preferências
            if ($this->hasUserPreferences($userId)) {
                return true; // Já inicializado
            }
            
            // Obter todos os tipos de notificação
            $types = $this->getAllNotificationTypes();
            
            // Obter canais ativos
            $channels = $this->getAllNotificationChannels(true);
            
            // Iniciar transação
            $this->db->beginTransaction();
            
            $success = true;
            
            // Criar preferências padrão
            foreach ($types as $type) {
                foreach ($channels as $channel) {
                    // Por padrão, habilitar todas as notificações críticas e desabilitar as não críticas para canais diferentes de 'website'
                    $isEnabled = $type['is_critical'] == 1 || $channel['code'] == 'website';
                    
                    // Criar a preferência
                    $data = [
                        'user_id' => $userId,
                        'notification_type_id' => $type['id'],
                        'notification_channel_id' => $channel['id'],
                        'is_enabled' => $isEnabled ? 1 : 0,
                        'frequency' => 'realtime' // Frequência padrão
                    ];
                    
                    $sql = "INSERT INTO user_notification_preferences (
                        user_id, notification_type_id, notification_channel_id, is_enabled, frequency
                    ) VALUES (
                        :user_id, :notification_type_id, :notification_channel_id, :is_enabled, :frequency
                    )";
                    
                    $result = $this->db->insert($sql, $data);
                    
                    if (!$result) {
                        $success = false;
                        break 2; // Sair dos dois loops
                    }
                }
            }
            
            // Finalizar transação
            if ($success) {
                $this->db->commit();
                
                // Limpar cache após inserção
                $this->clearUserCache($userId);
                
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao inicializar preferências padrão: ' . $e->getMessage());
            
            // Garantir que a transação seja revertida em caso de erro
            $this->db->rollback();
            
            return false;
        }
    }
    
    /**
     * Atualiza a preferência de notificação de um usuário
     * 
     * @param int $userId ID do usuário
     * @param int $typeId ID do tipo de notificação
     * @param int $channelId ID do canal de notificação
     * @param bool $isEnabled Se a notificação está habilitada
     * @param string $frequency Frequência de entrega (realtime, daily, weekly)
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function updatePreference($userId, $typeId, $channelId, $isEnabled, $frequency = 'realtime') {
        try {
            // Verificar se o tipo de notificação é crítico
            $sql = "SELECT is_critical FROM notification_types WHERE id = :typeId";
            $result = $this->db->select($sql, ['typeId' => $typeId]);
            
            if (!empty($result) && $result[0]['is_critical'] == 1 && !$isEnabled) {
                // Não permitir desativar notificações críticas
                app_log('WARNING', 'Tentativa de desativar notificação crítica: ' . $typeId);
                return false;
            }
            
            // Verificar se a preferência já existe
            $sql = "SELECT id FROM user_notification_preferences 
                   WHERE user_id = :userId 
                   AND notification_type_id = :typeId 
                   AND notification_channel_id = :channelId";
            
            $params = [
                'userId' => $userId,
                'typeId' => $typeId,
                'channelId' => $channelId
            ];
            
            $existingPreference = $this->db->select($sql, $params);
            
            if (empty($existingPreference)) {
                // Criar nova preferência
                $sql = "INSERT INTO user_notification_preferences (
                    user_id, notification_type_id, notification_channel_id, is_enabled, frequency
                ) VALUES (
                    :userId, :typeId, :channelId, :isEnabled, :frequency
                )";
                
                $params['isEnabled'] = $isEnabled ? 1 : 0;
                $params['frequency'] = $frequency;
                
                $result = $this->db->insert($sql, $params);
            } else {
                // Atualizar preferência existente
                $sql = "UPDATE user_notification_preferences 
                       SET is_enabled = :isEnabled, frequency = :frequency
                       WHERE user_id = :userId 
                       AND notification_type_id = :typeId 
                       AND notification_channel_id = :channelId";
                
                $params['isEnabled'] = $isEnabled ? 1 : 0;
                $params['frequency'] = $frequency;
                
                $result = $this->db->update($sql, $params);
            }
            
            // Limpar cache após atualização
            $this->clearUserCache($userId);
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao atualizar preferência de notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza múltiplas preferências de notificação de uma vez
     * 
     * @param int $userId ID do usuário
     * @param array $preferences Lista de preferências no formato [['type_id' => X, 'channel_id' => Y, 'is_enabled' => Z, 'frequency' => W], ...]
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function updateMultiplePreferences($userId, $preferences) {
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            $success = true;
            
            foreach ($preferences as $pref) {
                $result = $this->updatePreference(
                    $userId,
                    $pref['type_id'],
                    $pref['channel_id'],
                    $pref['is_enabled'],
                    isset($pref['frequency']) ? $pref['frequency'] : 'realtime'
                );
                
                if (!$result) {
                    $success = false;
                    break;
                }
            }
            
            // Finalizar transação
            if ($success) {
                $this->db->commit();
                
                // Limpar cache após atualizações
                $this->clearUserCache($userId);
                
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao atualizar múltiplas preferências: ' . $e->getMessage());
            
            // Garantir que a transação seja revertida em caso de erro
            $this->db->rollback();
            
            return false;
        }
    }
    
    /**
     * Verifica se o usuário deseja receber um tipo específico de notificação por um canal específico
     * 
     * @param int $userId ID do usuário
     * @param string $typeCode Código do tipo de notificação
     * @param string $channelCode Código do canal de notificação
     * @return bool Verdadeiro se o usuário deseja receber este tipo de notificação por este canal
     */
    public function shouldSendNotification($userId, $typeCode, $channelCode) {
        try {
            // Verificar cache
            $cacheKey = "user_{$userId}_should_receive_{$typeCode}_{$channelCode}";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            // Obter IDs dos tipos e canais
            $type = $this->getNotificationTypeByCode($typeCode);
            $channel = $this->getNotificationChannelByCode($channelCode);
            
            if (!$type || !$channel) {
                return false;
            }
            
            // Se o tipo é crítico e o canal está ativo, o usuário DEVE receber
            if ($type['is_critical'] == 1 && $channel['is_active'] == 1) {
                $this->cache[$cacheKey] = true;
                return true;
            }
            
            // Verificar preferência específica
            $sql = "SELECT is_enabled FROM user_notification_preferences 
                   WHERE user_id = :userId 
                   AND notification_type_id = :typeId 
                   AND notification_channel_id = :channelId";
            
            $params = [
                'userId' => $userId,
                'typeId' => $type['id'],
                'channelId' => $channel['id']
            ];
            
            $result = $this->db->select($sql, $params);
            
            // Se não há preferência específica, verificar se há preferências para o usuário
            if (empty($result)) {
                // Se não há preferências para o usuário, inicializar padrão
                if (!$this->hasUserPreferences($userId)) {
                    $this->initializeDefaultPreferences($userId);
                    
                    // Verificar novamente após inicialização
                    return $this->shouldSendNotification($userId, $typeCode, $channelCode);
                }
                
                // Se há preferências para outros tipos ou canais, mas não para este, considerar como não habilitado
                $this->cache[$cacheKey] = false;
                return false;
            }
            
            $isEnabled = (bool)$result[0]['is_enabled'];
            
            // Armazenar em cache
            $this->cache[$cacheKey] = $isEnabled;
            
            return $isEnabled;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao verificar preferência de envio: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém as métricas de configuração de preferências
     * 
     * @return array Dados estatísticos sobre as preferências configuradas
     */
    public function getPreferenceMetrics() {
        try {
            $metrics = [
                'userCount' => 0,
                'withPreferencesCount' => 0,
                'channelStats' => [],
                'typeStats' => [],
                'frequencyStats' => []
            ];
            
            // Contar usuários
            $sql = "SELECT COUNT(DISTINCT id) as total FROM users";
            $result = $this->db->select($sql);
            $metrics['userCount'] = isset($result[0]['total']) ? $result[0]['total'] : 0;
            
            // Contar usuários com preferências
            $sql = "SELECT COUNT(DISTINCT user_id) as total FROM user_notification_preferences";
            $result = $this->db->select($sql);
            $metrics['withPreferencesCount'] = isset($result[0]['total']) ? $result[0]['total'] : 0;
            
            // Estatísticas por canal
            $sql = "SELECT nc.code, nc.name, 
                          COUNT(*) as total_preferences, 
                          SUM(unp.is_enabled) as enabled_count
                   FROM notification_channels nc
                   LEFT JOIN user_notification_preferences unp ON nc.id = unp.notification_channel_id
                   GROUP BY nc.id
                   ORDER BY enabled_count DESC";
            
            $metrics['channelStats'] = $this->db->select($sql);
            
            // Estatísticas por tipo
            $sql = "SELECT nt.code, nt.name, nt.category, 
                          COUNT(*) as total_preferences, 
                          SUM(unp.is_enabled) as enabled_count
                   FROM notification_types nt
                   LEFT JOIN user_notification_preferences unp ON nt.id = unp.notification_type_id
                   GROUP BY nt.id
                   ORDER BY enabled_count DESC";
            
            $metrics['typeStats'] = $this->db->select($sql);
            
            // Estatísticas por frequência
            $sql = "SELECT frequency, COUNT(*) as total
                   FROM user_notification_preferences
                   WHERE is_enabled = 1
                   GROUP BY frequency
                   ORDER BY total DESC";
            
            $metrics['frequencyStats'] = $this->db->select($sql);
            
            return $metrics;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao obter métricas de preferências: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Registra a entrega de uma notificação
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário
     * @param int $typeId ID do tipo de notificação
     * @param int $channelId ID do canal de notificação
     * @param string $status Status da entrega (sent, failed, delivered, read)
     * @param string $errorMessage Mensagem de erro (se houver)
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function logDelivery($notificationId, $userId, $typeId, $channelId, $status, $errorMessage = null) {
        try {
            $sql = "INSERT INTO notification_delivery_logs (
                notification_id, user_id, notification_type_id, notification_channel_id, 
                status, sent_at, error_message
            ) VALUES (
                :notification_id, :user_id, :type_id, :channel_id, 
                :status, NOW(), :error_message
            )";
            
            $params = [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'type_id' => $typeId,
                'channel_id' => $channelId,
                'status' => $status,
                'error_message' => $errorMessage
            ];
            
            return $this->db->insert($sql, $params);
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao registrar entrega de notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de uma entrega de notificação
     * 
     * @param int $notificationId ID da notificação
     * @param int $channelId ID do canal de notificação
     * @param string $status Novo status (delivered, read)
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function updateDeliveryStatus($notificationId, $channelId, $status) {
        try {
            $sql = '';
            $params = [
                'notification_id' => $notificationId,
                'channel_id' => $channelId
            ];
            
            if ($status === 'delivered') {
                $sql = "UPDATE notification_delivery_logs 
                       SET status = 'delivered', delivered_at = NOW()
                       WHERE notification_id = :notification_id 
                       AND notification_channel_id = :channel_id";
            } else if ($status === 'read') {
                $sql = "UPDATE notification_delivery_logs 
                       SET status = 'read', read_at = NOW()
                       WHERE notification_id = :notification_id 
                       AND notification_channel_id = :channel_id";
            }
            
            if (empty($sql)) {
                return false;
            }
            
            return $this->db->update($sql, $params);
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao atualizar status de entrega: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpa o cache de preferências relacionado a um usuário específico
     * 
     * @param int $userId ID do usuário
     */
    private function clearUserCache($userId) {
        // Limpar cache de preferências
        unset($this->cache["user_{$userId}_preferences"]);
        unset($this->cache["user_{$userId}_preferences_grouped"]);
        
        // Limpar cache de verificações de envio
        foreach ($this->cache as $key => $value) {
            if (strpos($key, "user_{$userId}_should_receive_") === 0) {
                unset($this->cache[$key]);
            }
        }
    }
}
