-- Criação de tabelas para o sistema de monitoramento e alertas de performance
-- Este script deve ser executado por um administrador de banco de dados ou via migrations

-- Tabela para armazenar processos monitorados
CREATE TABLE IF NOT EXISTS monitored_processes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    process_id VARCHAR(64) NOT NULL,
    start_time INT NOT NULL COMMENT 'Timestamp de início do processo',
    max_duration INT NOT NULL COMMENT 'Duração máxima esperada em segundos',
    last_check INT NOT NULL COMMENT 'Timestamp da última verificação',
    active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Flag indicando se o monitoramento está ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_process_id (process_id)
) ENGINE=InnoDB COMMENT='Armazena processos assíncronos sob monitoramento';

-- Tabela para registro de alertas de performance
CREATE TABLE IF NOT EXISTS performance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(32) NOT NULL COMMENT 'Tipo do alerta (performance, timeout, slow_progress, error)',
    alert_data TEXT NOT NULL COMMENT 'Dados do alerta em formato JSON',
    severity VARCHAR(16) NOT NULL COMMENT 'Severidade (info, warning, error, critical)',
    acknowledged TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag indicando se o alerta foi reconhecido',
    acknowledged_by INT NULL COMMENT 'ID do usuário que reconheceu o alerta',
    acknowledged_at TIMESTAMP NULL COMMENT 'Timestamp do reconhecimento',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alert_type_severity (alert_type, severity),
    INDEX idx_created_at (created_at),
    INDEX idx_acknowledged (acknowledged)
) ENGINE=InnoDB COMMENT='Registro de alertas de performance';

-- Tabela para limiares de alerta configuráveis
CREATE TABLE IF NOT EXISTS performance_thresholds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    context VARCHAR(64) NOT NULL COMMENT 'Contexto da medição (ex: checkout_process)',
    metric VARCHAR(64) NOT NULL COMMENT 'Nome da métrica (ex: execution_time)',
    threshold_value FLOAT NOT NULL COMMENT 'Valor do limiar',
    warning_multiplier FLOAT NULL COMMENT 'Multiplicador para nível warning',
    error_multiplier FLOAT NULL COMMENT 'Multiplicador para nível error',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Flag indicando se o limiar está ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL COMMENT 'ID do usuário que criou o limiar',
    UNIQUE KEY uk_context_metric (context, metric)
) ENGINE=InnoDB COMMENT='Limiares configuráveis para alertas de performance';

-- Tabela para logs de verificações
CREATE TABLE IF NOT EXISTS performance_check_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_timestamp INT NOT NULL COMMENT 'Timestamp da verificação',
    execution_time FLOAT NOT NULL COMMENT 'Tempo de execução da verificação em segundos',
    checked_process_count INT NOT NULL DEFAULT 0 COMMENT 'Número de processos verificados',
    alert_count INT NOT NULL DEFAULT 0 COMMENT 'Número de alertas gerados',
    success TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Flag indicando se a verificação foi bem-sucedida',
    error_message TEXT NULL COMMENT 'Mensagem de erro, se houver',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_check_timestamp (check_timestamp)
) ENGINE=InnoDB COMMENT='Logs de verificações de performance';

-- Inserir valores padrão para limiares
INSERT INTO performance_thresholds (context, metric, threshold_value, warning_multiplier, error_multiplier)
VALUES 
    -- Limiares para processamento assíncrono
    ('async_process', 'execution_time', 3600, 1.5, 3),           -- 1 hora (3600s)
    ('async_process', 'memory_usage', 104857600, 1.2, 2),        -- 100 MB
    ('async_process', 'min_progress_rate', 20, 1.5, 3),          -- 20% de diferença mínima
    
    -- Limiares para processamento de pedidos
    ('checkout_process', 'execution_time', 10, 1.5, 3),          -- 10 segundos
    ('checkout_process', 'database_queries', 50, 1.5, 2.5),       -- 50 consultas
    
    -- Limiares para geração de relatórios
    ('report_generation', 'execution_time', 60, 2, 5),           -- 60 segundos
    ('report_generation', 'memory_usage', 52428800, 1.5, 2.5),   -- 50 MB
    ('report_generation', 'database_queries', 100, 1.5, 3);      -- 100 consultas
