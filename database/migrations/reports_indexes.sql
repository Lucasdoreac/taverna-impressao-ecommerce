-- Migração para adicionar índices específicos para consultas de relatórios
-- Este arquivo deve ser executado por um administrador de banco de dados
-- Data: 2025-04-05

-- Índices para tabela de pedidos (orders)
-- Melhora consultas de relatórios de vendas e análises temporais
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_orders_user_id_created_at ON orders(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_status_created_at ON orders(status, created_at);

-- Índices para itens de pedido (order_items)
-- Melhora consultas de relatórios de produtos e análises de vendas por produto
CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id_product_id ON order_items(order_id, product_id);

-- Índices para produtos (products)
-- Melhora consultas de estoque e categorização
CREATE INDEX IF NOT EXISTS idx_products_category_id ON products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_stock_status ON products(stock, deleted);

-- Índices para usuários (users)
-- Melhora consultas de relatórios de clientes
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);
CREATE INDEX IF NOT EXISTS idx_users_active_status ON users(active, deleted);

-- Índices para trabalhos de impressão (print_jobs)
-- Melhora relatórios de impressão 3D e análise de utilização
CREATE INDEX IF NOT EXISTS idx_print_jobs_created_at ON print_jobs(created_at);
CREATE INDEX IF NOT EXISTS idx_print_jobs_printer_id_created_at ON print_jobs(printer_id, created_at);
CREATE INDEX IF NOT EXISTS idx_print_jobs_material_id ON print_jobs(material_id);
CREATE INDEX IF NOT EXISTS idx_print_jobs_status ON print_jobs(status);

-- Índice composto para análise de falhas de impressão
CREATE INDEX IF NOT EXISTS idx_print_job_failures_type_created_at ON print_job_failures(failure_type, created_at);

-- Índices específicos para análise de vendas por data
-- Facilita consultas de tendências, sazonalidade e previsões
CREATE INDEX IF NOT EXISTS idx_orders_year_month ON orders((EXTRACT(YEAR_MONTH FROM created_at)));
CREATE INDEX IF NOT EXISTS idx_orders_year_quarter ON orders((YEAR(created_at)), (QUARTER(created_at)));
CREATE INDEX IF NOT EXISTS idx_orders_year ON orders((YEAR(created_at)));

-- Índice para análise de vendas por região
CREATE INDEX IF NOT EXISTS idx_customer_addresses_region ON customer_addresses(region);
