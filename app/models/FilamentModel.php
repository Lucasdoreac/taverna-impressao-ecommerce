<?php
/**
 * FilamentModel - Modelo para gerenciamento de filamentos
 */
class FilamentModel extends Model {
    protected $table = 'filament_colors';
    protected $fillable = [
        'name', 'hex_code', 'filament_type', 'is_active', 'display_order'
    ];
    
    /**
     * Obtém todos os filamentos disponíveis
     * 
     * @param int|null $limit Limite de registros (opcional)
     * @return array Lista de todos os filamentos
     */
    public function getAll($limit = null) {
        if ($limit !== null && is_numeric($limit)) {
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY filament_type, display_order LIMIT :limit";
            return $this->db()->select($sql, ['limit' => $limit]);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY filament_type, display_order";
            return $this->db()->select($sql);
        }
    }
    
    /**
     * Obtém os tipos de filamento disponíveis
     * 
     * @return array Tipos de filamento
     */
    public function getAvailableTypes() {
        $sql = "SELECT DISTINCT filament_type FROM {$this->table} WHERE is_active = 1 ORDER BY filament_type";
        return $this->db()->select($sql);
    }
    
    /**
     * Obtém as cores disponíveis para um tipo de filamento
     * 
     * @param string $type Tipo de filamento (PLA, PETG, etc)
     * @return array Cores disponíveis
     */
    public function getColors($type = 'PLA') {
        $sql = "SELECT * FROM {$this->table} WHERE filament_type = :type AND is_active = 1 ORDER BY display_order";
        return $this->db()->select($sql, ['type' => $type]);
    }
    
    /**
     * Adiciona uma nova cor de filamento
     * 
     * @param string $name Nome da cor
     * @param string $hexCode Código hexadecimal da cor
     * @param string $type Tipo de filamento
     * @param int $displayOrder Ordem de exibição
     * @return int ID da cor adicionada
     */
    public function addColor($name, $hexCode, $type = 'PLA', $displayOrder = 0) {
        return $this->create([
            'name' => $name,
            'hex_code' => $hexCode,
            'filament_type' => $type,
            'is_active' => 1,
            'display_order' => $displayOrder
        ]);
    }
    
    /**
     * Atualiza o status de uma cor de filamento
     * 
     * @param int $id ID da cor
     * @param bool $isActive Status de ativação
     * @return void
     */
    public function updateStatus($id, $isActive) {
        $this->update($id, ['is_active' => $isActive ? 1 : 0]);
    }
}