<?php
/**
 * SettingsModel - Modelo para gerenciamento de configurações do sistema
 */
class SettingsModel {
    private $db;
    private $cache = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadCache();
    }
    
    /**
     * Obtém uma configuração pelo nome
     * 
     * @param string $key Nome da configuração
     * @param mixed $default Valor padrão caso a configuração não exista
     * @return mixed Valor da configuração
     */
    public function getSetting($key, $default = null) {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        try {
            $sql = "SELECT setting_value FROM settings WHERE setting_key = :key";
            $params = ['key' => $key];
            
            $result = $this->db->select($sql, $params);
            
            if (!empty($result)) {
                $this->cache[$key] = $result[0]['setting_value'];
                return $result[0]['setting_value'];
            } else {
                return $default;
            }
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar configuração: ' . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Define uma configuração
     * 
     * @param string $key Nome da configuração
     * @param mixed $value Valor da configuração
     * @param string $group Grupo da configuração (opcional)
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function setSetting($key, $value, $group = 'general') {
        try {
            // Verificar se a configuração já existe
            $sql = "SELECT id FROM settings WHERE setting_key = :key";
            $params = ['key' => $key];
            
            $result = $this->db->select($sql, $params);
            
            if (empty($result)) {
                // Inserir nova configuração
                $sql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (:key, :value, :group)";
                $params = [
                    'key' => $key,
                    'value' => $value,
                    'group' => $group
                ];
                
                $result = $this->db->insert($sql, $params);
            } else {
                // Atualizar configuração existente
                $sql = "UPDATE settings SET setting_value = :value WHERE setting_key = :key";
                $params = [
                    'key' => $key,
                    'value' => $value
                ];
                
                $result = $this->db->update($sql, $params);
            }
            
            // Atualizar cache
            $this->cache[$key] = $value;
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao definir configuração: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui uma configuração
     * 
     * @param string $key Nome da configuração
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function deleteSetting($key) {
        try {
            $sql = "DELETE FROM settings WHERE setting_key = :key";
            $params = ['key' => $key];
            
            $result = $this->db->delete($sql, $params);
            
            // Remover do cache
            if (isset($this->cache[$key])) {
                unset($this->cache[$key]);
            }
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao excluir configuração: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todas as configurações de um grupo
     * 
     * @param string $group Grupo das configurações
     * @return array Lista de configurações
     */
    public function getSettingsByGroup($group) {
        try {
            $sql = "SELECT * FROM settings WHERE setting_group = :group";
            $params = ['group' => $group];
            
            $result = $this->db->select($sql, $params);
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar configurações por grupo: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todas as configurações
     * 
     * @return array Lista de configurações
     */
    public function getAllSettings() {
        try {
            $sql = "SELECT * FROM settings ORDER BY setting_group, setting_key";
            
            $result = $this->db->select($sql);
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar todas as configurações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Carrega todas as configurações para o cache
     */
    private function loadCache() {
        try {
            $sql = "SELECT setting_key, setting_value FROM settings";
            
            $result = $this->db->select($sql);
            
            foreach ($result as $row) {
                $this->cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao carregar configurações para o cache: ' . $e->getMessage());
        }
    }
}