<?php
/**
 * AddressModel - Modelo para endereços de usuários
 */
class AddressModel extends Model {
    protected $table = 'addresses';
    protected $fillable = [
        'user_id', 'address', 'number', 'complement', 'neighborhood', 
        'city', 'state', 'zipcode', 'is_default'
    ];
    
    /**
     * Obtém todos os endereços de um usuário
     */
    public function getAddressesByUser($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY is_default DESC, id ASC";
        return $this->db()->select($sql, ['user_id' => $userId]);
    }
    
    /**
     * Obtém o endereço padrão de um usuário
     */
    public function getDefaultAddress($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND is_default = 1 LIMIT 1";
        $result = $this->db()->select($sql, ['user_id' => $userId]);
        return $result ? $result[0] : null;
    }
    
    /**
     * Define um endereço como padrão, removendo a marcação dos demais
     */
    public function setDefault($addressId, $userId) {
        // Remover flag de padrão de todos os endereços do usuário
        $sql = "UPDATE {$this->table} SET is_default = 0 WHERE user_id = :user_id";
        $this->db()->query($sql, ['user_id' => $userId]);
        
        // Definir o endereço selecionado como padrão
        $sql = "UPDATE {$this->table} SET is_default = 1 WHERE id = :id AND user_id = :user_id";
        $this->db()->query($sql, [
            'id' => $addressId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Adiciona um novo endereço
     */
    public function addAddress($data) {
        // Se for o primeiro endereço do usuário, definir como padrão
        $userAddresses = $this->getAddressesByUser($data['user_id']);
        if (empty($userAddresses)) {
            $data['is_default'] = 1;
        }
        
        // Se o endereço estiver sendo marcado como padrão, remover a marcação dos demais
        if (isset($data['is_default']) && $data['is_default']) {
            $sql = "UPDATE {$this->table} SET is_default = 0 WHERE user_id = :user_id";
            $this->db()->query($sql, ['user_id' => $data['user_id']]);
        }
        
        return $this->create($data);
    }
    
    /**
     * Atualiza um endereço
     */
    public function updateAddress($addressId, $data) {
        // Se o endereço estiver sendo marcado como padrão, remover a marcação dos demais
        if (isset($data['is_default']) && $data['is_default']) {
            $sql = "UPDATE {$this->table} SET is_default = 0 WHERE user_id = :user_id";
            $this->db()->query($sql, ['user_id' => $data['user_id']]);
        }
        
        return $this->update($addressId, $data);
    }
    
    /**
     * Remove todos os endereços de um usuário
     */
    public function deleteByUser($userId) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        $this->db()->query($sql, ['user_id' => $userId]);
    }
}