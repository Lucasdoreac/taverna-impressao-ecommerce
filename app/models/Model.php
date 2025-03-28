<?php
/**
 * Model - Classe base para modelos
 */
abstract class Model {
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    
    protected function db() {
        return Database::getInstance();
    }
    
    public function all() {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db()->select($sql);
    }
    
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $result = $this->db()->select($sql, ['id' => $id]);
        return $result ? $result[0] : null;
    }
    
    public function findBy($column, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        $result = $this->db()->select($sql, ['value' => $value]);
        return $result ? $result[0] : null;
    }
    
    public function where($conditions, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions}";
        return $this->db()->select($sql, $params);
    }
    
    public function create($data) {
        // Filtrar apenas campos permitidos
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        return $this->db()->insert($this->table, $filteredData);
    }
    
    public function update($id, $data) {
        // Filtrar apenas campos permitidos
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        $this->db()->update(
            $this->table, 
            $filteredData, 
            "{$this->primaryKey} = :id", 
            ['id' => $id]
        );
    }
    
    public function delete($id) {
        $this->db()->delete($this->table, "{$this->primaryKey} = :id", ['id' => $id]);
    }
    
    public function paginate($page = 1, $limit = 10, $conditions = '1=1', $params = []) {
        $offset = ($page - 1) * $limit;
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$conditions}";
        $countResult = $this->db()->select($countSql, $params);
        $total = $countResult[0]['total'];
        
        // Buscar registros paginados
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions} LIMIT {$offset}, {$limit}";
        $items = $this->db()->select($sql, $params);
        
        return [
            'items' => $items,
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $limit,
            'lastPage' => ceil($total / $limit),
            'from' => $offset + 1,
            'to' => min($offset + $limit, $total)
        ];
    }
}