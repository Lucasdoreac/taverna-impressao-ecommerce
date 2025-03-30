-- Migration para atualizar a tabela de categorias com suporte a hierarquia
-- Usando o algoritmo Nested Sets para otimizar consultas em árvores de categorias

-- Verificar se os campos necessários já existem
SET @exists_level = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'level');
                     
SET @exists_left_value = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'left_value');
                          
SET @exists_right_value = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'right_value');

-- Adicionar campo 'level' se não existir
SET @query = IF(@exists_level = 0, 
                'ALTER TABLE categories ADD COLUMN level INT DEFAULT 0 AFTER display_order', 
                'SELECT "Campo level já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar campo 'left_value' se não existir
SET @query = IF(@exists_left_value = 0, 
                'ALTER TABLE categories ADD COLUMN left_value INT DEFAULT 0 AFTER level', 
                'SELECT "Campo left_value já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar campo 'right_value' se não existir
SET @query = IF(@exists_right_value = 0, 
                'ALTER TABLE categories ADD COLUMN right_value INT DEFAULT 0 AFTER left_value', 
                'SELECT "Campo right_value já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índices para otimizar consultas de árvore
SET @exists_index_tree = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                          WHERE TABLE_NAME = 'categories' AND INDEX_NAME = 'idx_tree');
                          
SET @query = IF(@exists_index_tree = 0, 
                'CREATE INDEX idx_tree ON categories(left_value, right_value)', 
                'SELECT "Índice idx_tree já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para parent_id
SET @exists_index_parent = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                            WHERE TABLE_NAME = 'categories' AND INDEX_NAME = 'idx_parent');
                            
SET @query = IF(@exists_index_parent = 0, 
                'CREATE INDEX idx_parent ON categories(parent_id)', 
                'SELECT "Índice idx_parent já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Criar função GetCategoryAncestors se não existir
DROP FUNCTION IF EXISTS GetCategoryAncestors;

DELIMITER //
CREATE FUNCTION GetCategoryAncestors(category_id INT) 
RETURNS TEXT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE ancestors TEXT DEFAULT '';
    DECLARE current_id INT;
    DECLARE parent INT;
    DECLARE done INT DEFAULT 0;
    
    SET current_id = category_id;
    
    WHILE current_id IS NOT NULL AND done = 0 DO
        SELECT parent_id INTO parent FROM categories WHERE id = current_id;
        IF parent IS NOT NULL THEN
            SET ancestors = CONCAT_WS(',', ancestors, parent);
            SET current_id = parent;
        ELSE
            SET done = 1;
        END IF;
    END WHILE;
    
    RETURN ancestors;
END //
DELIMITER ;

-- Inicializar valores left_value e right_value para categorias existentes usando procedimento armazenado

DELIMITER //
CREATE PROCEDURE RebuildCategoryTree()
BEGIN
    DECLARE counter INT DEFAULT 1;
    
    -- Limpar valores existentes
    UPDATE categories SET left_value = 0, right_value = 0, level = 0;
    
    -- Iniciar reconstrução recursiva da árvore
    CALL RebuildCategoryBranch(NULL, counter, 0);
    
    -- Verificar se todos os nós foram atualizados corretamente
    UPDATE categories
    SET left_value = 1, right_value = 2
    WHERE left_value = 0 AND right_value = 0;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE RebuildCategoryBranch(IN parent_id INT, INOUT counter INT, IN current_level INT)
BEGIN
    DECLARE node_id INT;
    DECLARE node_left INT;
    DECLARE done INT DEFAULT 0;
    DECLARE cur CURSOR FOR 
        SELECT id FROM categories 
        WHERE (parent_id IS NULL AND parent_id IS NULL) OR (parent_id IS NOT NULL AND parent_id = parent_id) 
        ORDER BY display_order, name;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    
    OPEN cur;
    
    category_loop: LOOP
        FETCH cur INTO node_id;
        IF done THEN
            LEAVE category_loop;
        END IF;
        
        -- Definir valor esquerdo
        SET node_left = counter;
        SET counter = counter + 1;
        
        -- Atualizar nível
        UPDATE categories SET level = current_level WHERE id = node_id;
        
        -- Processar filhos
        CALL RebuildCategoryBranch(node_id, counter, current_level + 1);
        
        -- Atualizar valores left e right
        UPDATE categories SET left_value = node_left, right_value = counter WHERE id = node_id;
        SET counter = counter + 1;
    END LOOP;
    
    CLOSE cur;
END //
DELIMITER ;

-- Executar a reconstrução da árvore
CALL RebuildCategoryTree();

-- Limpar procedimentos temporários
DROP PROCEDURE IF EXISTS RebuildCategoryTree;
DROP PROCEDURE IF EXISTS RebuildCategoryBranch;
