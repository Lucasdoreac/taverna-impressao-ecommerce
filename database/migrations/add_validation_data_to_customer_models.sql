-- Migração para adicionar a coluna validation_data à tabela customer_models
-- Esta coluna armazenará os resultados detalhados da validação dos modelos 3D

-- Verificar se a coluna já existe
SET @colExists = 0;
SELECT COUNT(*) INTO @colExists FROM information_schema.columns 
WHERE table_name = 'customer_models' AND column_name = 'validation_data';

-- Adicionar a coluna apenas se ela não existir
SET @statement = IF(@colExists = 0,
    'ALTER TABLE customer_models ADD COLUMN validation_data TEXT COMMENT "Dados JSON detalhados da validação do modelo 3D" AFTER notes',
    'SELECT "Column validation_data already exists in customer_models table."');

PREPARE stmt FROM @statement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualizar as configurações da coluna status para incluir novos valores
ALTER TABLE customer_models 
MODIFY COLUMN status ENUM('pending_validation', 'approved', 'rejected', 'needs_repair') 
DEFAULT 'pending_validation' 
COMMENT 'Status do modelo: pendente de validação, aprovado, rejeitado ou precisa de reparo';
