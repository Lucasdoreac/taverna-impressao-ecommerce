-- Migração para estruturas de tabelas de monitoramento e notificações
-- Data: 2025-04-04
-- Versão: 1.0.0
-- Autor: Claude

-- Desativar verificações de chaves estrangeiras temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Tabela `performance_logs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `performance_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` VARCHAR(50) NOT NULL,
  `request_uri` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL,
  `execution_time` FLOAT NOT NULL COMMENT 'Tempo de execução em segundos',
  `memory_start` INT UNSIGNED NOT NULL COMMENT 'Memória inicial em bytes',
  `memory_end` INT UNSIGNED NOT NULL COMMENT 'Memória final em bytes',
  `memory_peak` INT UNSIGNED NOT NULL COMMENT 'Pico de memória em bytes',
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_performance_logs_timestamp` (`timestamp`),
  INDEX `idx_performance_logs_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `error_logs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `error_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL COMMENT 'Tipo de erro (404, 500, exception, etc)',
  `message` TEXT NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `context` JSON NULL COMMENT 'Contexto adicional do erro',
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_error_logs_timestamp` (`timestamp`),
  INDEX `idx_error_logs_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `resource_metrics`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `resource_metrics` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `memory_peak` INT UNSIGNED NOT NULL COMMENT 'Pico de memória em bytes',
  `cpu_usage` FLOAT NOT NULL COMMENT 'Uso de CPU em porcentagem',
  `disk_usage` FLOAT NULL COMMENT 'Uso de disco em porcentagem',
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_resource_metrics_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `database_metrics`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `database_metrics` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `query_time` FLOAT NOT NULL COMMENT 'Tempo médio de consulta em segundos',
  `query_count` INT UNSIGNED NOT NULL COMMENT 'Número de consultas',
  `slow_queries` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de consultas lentas',
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_database_metrics_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `security_events`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `security_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL COMMENT 'Tipo de evento (login_failure, csrf_failure, etc)',
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 para anônimo',
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_security_events_timestamp` (`timestamp`),
  INDEX `idx_security_events_type` (`type`),
  INDEX `idx_security_events_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `notifications`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info',
  `context` JSON NULL COMMENT 'Dados adicionais de contexto',
  `is_system` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Se é uma notificação de sistema',
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ID do usuário criador (0 para sistema)',
  `status` ENUM('pending', 'in_progress', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `target_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número total de destinatários',
  `delivered_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de entregas bem-sucedidas',
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de falhas na entrega',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_notifications_created_at` (`created_at`),
  INDEX `idx_notifications_type` (`type`),
  INDEX `idx_notifications_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `notification_targets`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_targets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL COMMENT 'ID do usuário destinatário (NULL para grupos)',
  `role` VARCHAR(50) NULL COMMENT 'Papel de usuário (NULL para usuários específicos)',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_notification_targets_notification_id` (`notification_id`),
  INDEX `idx_notification_targets_user_id` (`user_id`),
  INDEX `idx_notification_targets_role` (`role`),
  CONSTRAINT `fk_notification_targets_notification_id`
    FOREIGN KEY (`notification_id`)
    REFERENCES `notifications` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `notification_deliveries`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_deliveries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `channel` ENUM('database', 'push', 'email') NOT NULL,
  `status` ENUM('delivered', 'failed') NOT NULL,
  `details` JSON NULL COMMENT 'Detalhes da entrega ou falha',
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_notification_deliveries_notification_id` (`notification_id`),
  INDEX `idx_notification_deliveries_user_id` (`user_id`),
  INDEX `idx_notification_deliveries_status` (`status`),
  CONSTRAINT `fk_notification_deliveries_notification_id`
    FOREIGN KEY (`notification_id`)
    REFERENCES `notifications` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `user_notifications`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `notification_id` INT UNSIGNED NOT NULL,
  `status` ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
  `created_at` DATETIME NOT NULL,
  `read_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_user_notifications_user_notification` (`user_id`, `notification_id`),
  INDEX `idx_user_notifications_user_id` (`user_id`),
  INDEX `idx_user_notifications_notification_id` (`notification_id`),
  INDEX `idx_user_notifications_status` (`status`),
  CONSTRAINT `fk_user_notifications_notification_id`
    FOREIGN KEY (`notification_id`)
    REFERENCES `notifications` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `push_subscriptions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `endpoint` VARCHAR(500) NOT NULL,
  `subscription_data` JSON NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  `last_used` DATETIME NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_push_subscriptions_endpoint` (`endpoint`),
  INDEX `idx_push_subscriptions_user_id` (`user_id`),
  INDEX `idx_push_subscriptions_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `push_delivery_log`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `push_delivery_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `subscription_id` INT UNSIGNED NOT NULL,
  `notification_id` INT UNSIGNED NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_push_delivery_log_user_id` (`user_id`),
  INDEX `idx_push_delivery_log_subscription_id` (`subscription_id`),
  INDEX `idx_push_delivery_log_notification_id` (`notification_id`),
  INDEX `idx_push_delivery_log_success` (`success`),
  INDEX `idx_push_delivery_log_timestamp` (`timestamp`),
  CONSTRAINT `fk_push_delivery_log_subscription_id`
    FOREIGN KEY (`subscription_id`)
    REFERENCES `push_subscriptions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reativar verificações de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;
