-- Script de criação de índices otimizados para módulo de relatórios
-- Taverna da Impressão 3D - v1.0.0
-- Data: 2025-04-05

-- Índices para tabela de pedidos (relatórios de vendas)
CREATE INDEX IF NOT EXISTS idx_orders_created_at_status ON orders (created_at, status);
CREATE INDEX IF NOT EXISTS idx_orders_user_id_created_at ON orders (user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_status_created_at ON orders (status, created_at);

-- Índices para tabela de itens de pedido (relatórios de produtos)
CREATE INDEX IF NOT EXISTS idx_order_items_product_id_price ON order_items (product_id, price);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id_product_id ON order_items (order_id, product_id);

-- Índices para tabela de produtos (relatórios de categorias)
CREATE INDEX IF NOT EXISTS idx_products_category_id_deleted ON products (category_id, deleted);
CREATE INDEX IF NOT EXISTS idx_products_deleted_stock ON products (deleted, stock);

-- Índices para tabela de usuários (relatórios de clientes)
CREATE INDEX IF NOT EXISTS idx_users_created_at_deleted ON users (created_at, deleted);

-- Índices para tabela de trabalhos de impressão (relatórios de impressão)
CREATE INDEX IF NOT EXISTS idx_print_jobs_created_at_status ON print_jobs (created_at, status);
CREATE INDEX IF NOT EXISTS idx_print_jobs_printer_id_created_at ON print_jobs (printer_id, created_at);
CREATE INDEX IF NOT EXISTS idx_print_jobs_material_id_created_at ON print_jobs (material_id, created_at);

-- Índices para tabela de falhas de impressão (relatórios de falhas)
CREATE INDEX IF NOT EXISTS idx_print_job_failures_type_created_at ON print_job_failures (failure_type, created_at);
CREATE INDEX IF NOT EXISTS idx_print_job_failures_print_job_id ON print_job_failures (print_job_id);

-- Índice para cálculo de retenção de clientes
CREATE INDEX IF NOT EXISTS idx_orders_user_id_status ON orders (user_id, status);

-- Índice para análise temporal em relatórios de tendências
CREATE INDEX IF NOT EXISTS idx_orders_year_month ON orders ((EXTRACT(YEAR_MONTH FROM created_at)));
