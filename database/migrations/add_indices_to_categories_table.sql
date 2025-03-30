-- Índices recomendados para a tabela categories
-- Estes índices vão melhorar significativamente a performance das consultas hierárquicas

-- Índice para parent_id - usado em consultas de subcategorias
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_parent_id (parent_id);

-- Índice para slug - usado em busca de categorias por slug
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_slug (slug);

-- Índice para is_active - usado em quase todas as consultas
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_is_active (is_active);

-- Índice para display_order - usado para ordenação em múltiplas consultas
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_display_order (display_order);

-- Índice para nested sets - essencial para consultas hierárquicas eficientes
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_nested_sets (left_value, right_value);

-- Índices para tabela de produtos - melhoram as consultas de produtos por categoria
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_category_id (category_id);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_is_active (is_active);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_tested_stock (is_tested, stock);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_price (price, sale_price);
