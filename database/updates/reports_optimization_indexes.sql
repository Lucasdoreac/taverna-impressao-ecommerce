-- =========================================================================
-- OTIMIZAÇÃO DE ÍNDICES PARA RELATÓRIOS
-- Versão: 1.0.0
-- Data: 05/04/2025
-- =========================================================================
-- Este arquivo adiciona índices específicos para otimizar as consultas
-- utilizadas no módulo de relatórios administrativos.
-- =========================================================================

-- Desativar verificação de chaves estrangeiras durante a atualização
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================================
-- ÍNDICES PARA RELATÓRIOS DE VENDAS
-- =========================================================================

-- Índice para agrupamento por data em relatórios de venda (dia, mês, trimestre, ano)
SET @exists_index_orders_date = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                         WHERE TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_orders_reporting_date');
                    
SET @query = IF(@exists_index_orders_date = 0, 
                'CREATE INDEX idx_orders_reporting_date ON orders(created_at, status, total)', 
                'SELECT "Índice idx_orders_reporting_date já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para relatórios de itens mais vendidos
SET @exists_index_order_items_product = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                         WHERE TABLE_NAME = 'order_items' AND INDEX_NAME = 'idx_order_items_product_quantity');
                    
SET @query = IF(@exists_index_order_items_product = 0, 
                'CREATE INDEX idx_order_items_product_quantity ON order_items(product_id, quantity, price)', 
                'SELECT "Índice idx_order_items_product_quantity já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice composto para junção eficiente entre orders e order_items
SET @exists_index_order_items_join = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                            WHERE TABLE_NAME = 'order_items' AND INDEX_NAME = 'idx_order_items_order_id_product_id');
                    
SET @query = IF(@exists_index_order_items_join = 0, 
                'CREATE INDEX idx_order_items_order_id_product_id ON order_items(order_id, product_id)', 
                'SELECT "Índice idx_order_items_order_id_product_id já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================================
-- ÍNDICES PARA RELATÓRIOS DE CLIENTES
-- =========================================================================

-- Índice para análise de novos clientes por período
SET @exists_index_users_created = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                          WHERE TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_created_at');
                    
SET @query = IF(@exists_index_users_created = 0, 
                'CREATE INDEX idx_users_created_at ON users(created_at, role, id)', 
                'SELECT "Índice idx_users_created_at já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para análise de atividade do cliente
SET @exists_index_orders_user_date = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                            WHERE TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_orders_user_id_created_at');
                    
SET @query = IF(@exists_index_orders_user_date = 0, 
                'CREATE INDEX idx_orders_user_id_created_at ON orders(user_id, created_at, status, total)', 
                'SELECT "Índice idx_orders_user_id_created_at já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================================
-- ÍNDICES PARA RELATÓRIOS DE IMPRESSÃO 3D
-- =========================================================================

-- Índice para análise de uso de impressoras
SET @exists_index_print_jobs_printer = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                              WHERE TABLE_NAME = 'print_jobs' AND INDEX_NAME = 'idx_print_jobs_printer_dates');
                    
SET @query = IF(@exists_index_print_jobs_printer = 0, 
                'CREATE INDEX idx_print_jobs_printer_dates ON print_jobs(printer_id, created_at, status, print_time_minutes, filament_usage_grams)', 
                'SELECT "Índice idx_print_jobs_printer_dates já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para análise de uso de filamento
SET @exists_index_print_jobs_material = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                              WHERE TABLE_NAME = 'print_jobs' AND INDEX_NAME = 'idx_print_jobs_material_usage');
                    
SET @query = IF(@exists_index_print_jobs_material = 0, 
                'CREATE INDEX idx_print_jobs_material_usage ON print_jobs(material_id, created_at, filament_usage_grams)', 
                'SELECT "Índice idx_print_jobs_material_usage já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para análise de tempo de impressão por categoria
SET @exists_index_products_category = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                            WHERE TABLE_NAME = 'products' AND INDEX_NAME = 'idx_products_category_id_print_time');
                    
SET @query = IF(@exists_index_products_category = 0, 
                'CREATE INDEX idx_products_category_id_print_time ON products(category_id, print_time_hours, filament_usage_grams)', 
                'SELECT "Índice idx_products_category_id_print_time já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para análise de falhas de impressão
SET @exists_index_print_job_failures = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                              WHERE TABLE_NAME = 'print_job_failures' AND INDEX_NAME = 'idx_print_job_failures_type_date');
                    
SET @query = IF(@exists_index_print_job_failures = 0, 
                'CREATE INDEX idx_print_job_failures_type_date ON print_job_failures(failure_type, created_at, print_job_id)', 
                'SELECT "Índice idx_print_job_failures_type_date já existe"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================================
-- REATIVAR VERIFICAÇÃO DE CHAVES ESTRANGEIRAS
-- =========================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- Mensagem de conclusão
SELECT 'Índices otimizados para relatórios criados com sucesso' AS 'Status';