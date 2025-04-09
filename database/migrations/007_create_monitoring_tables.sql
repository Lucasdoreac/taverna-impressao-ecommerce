-- Migração 007: Criação de tabelas para monitoramento e notificações
-- Autor: Taverna da Impressão
-- Data: 2025-04-05

-- Certifique-se de que qualquer trabalho anterior seja confirmado
COMMIT;

-- Iniciar transação
START TRANSACTION;

-- -----------------------------------------------------
-- Tabela para métricas de desempenho
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `performance_metrics` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uri` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL,
  `response_time` FLOAT NOT NULL COMMENT 'Tempo de resposta em milissegundos',
  `memory_start` INT UNSIGNED NOT NULL COMMENT 'Uso de memória no início em bytes',
  `memory_end` INT UNSIGNED NOT NULL COMMENT 'Uso de memória no fim em bytes',
  `memory_peak` INT UNSIGNED NOT NULL COMMENT 'Pico de uso de memória em bytes',
  `query_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de consultas SQL',
  `query_time` FLOAT NOT NULL DEFAULT 0 COMMENT 'Tempo total de consultas SQL em ms',
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_perf_timestamp` (`timestamp`),
  INDEX `idx_perf_uri` (`uri`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela para alertas de desempenho
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `performance_alerts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `metric` VARCHAR(50) NOT NULL COMMENT 'Métrica que gerou o alerta',
  `value` VARCHAR(100) NOT NULL COMMENT 'Valor registrado',
  `threshold` VARCHAR(100) NOT NULL COMMENT 'Limite configurado',
  `uri` VARCHAR(255) NULL DEFAULT NULL,
  `method` VARCHAR(10) NULL DEFAULT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_perf_alert_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela para registro de erros
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `error_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL COMMENT 'Tipo de erro',
  `message` TEXT NOT NULL COMMENT 'Mensagem de erro',
  `file` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Arquivo onde ocorreu o erro',
  `line` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Linha onde ocorreu o erro',
  `uri` VARCHAR(255) NULL DEFAULT NULL,
  `method` VARCHAR(10) NULL DEFAULT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_error_timestamp` (`timestamp`),
  INDEX `idx_error_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela para alertas da fila de impressão
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `print_queue_alerts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL COMMENT 'Tipo de alerta',
  `level` ENUM('ALERTA', 'CRÍTICO') NOT NULL DEFAULT 'ALERTA',
  `message` TEXT NOT NULL COMMENT 'Mensagem do alerta',
  `acknowledged` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Se o alerta foi reconhecido',
  `acknowledged_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'ID do usuário que reconheceu',
  `acknowledged_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_queue_alert_created` (`created_at`),
  INDEX `idx_queue_alert_type` (`type`),
  INDEX `idx_queue_alert_level` (`level`),
  INDEX `idx_queue_alert_ack` (`acknowledged`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela para notificações de usuários
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL COMMENT 'Tipo de notificação',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Link opcional',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_created` (`created_at`),
  INDEX `idx_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela para subscrições de notificações push
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `endpoint` VARCHAR(512) NOT NULL,
  `p256dh_key` VARCHAR(255) NOT NULL,
  `auth_key` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `failure_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_failure_at` TIMESTAMP NULL DEFAULT NULL,
  `unsubscribed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_endpoint_user` (`endpoint`(191), `user_id`),
  INDEX `idx_push_user` (`user_id`),
  INDEX `idx_push_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Confirmar alterações
COMMIT;
