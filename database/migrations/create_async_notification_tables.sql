-- Migração para criar tabelas relacionadas a notificações de processos assíncronos
-- Data: 2025-04-08
-- Versão: 1.0.0
-- Autor: Taverna da Impressão 3D

-- Desativar verificações de chaves estrangeiras temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Tabela `notification_types` (Se não existir)
-- Registra os tipos de notificação disponíveis
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL COMMENT 'Código único para identificação interna',
  `name` VARCHAR(100) NOT NULL COMMENT 'Nome legível do tipo de notificação',
  `description` TEXT NULL COMMENT 'Descrição detalhada do tipo de notificação',
  `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT 'Categoria da notificação',
  `is_critical` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Se é um tipo crítico que não pode ser desativado',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `code_UNIQUE` (`code` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `async_notification_logs`
-- Registra logs de notificações relacionadas a processos assíncronos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `async_notification_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_id` INT UNSIGNED NOT NULL COMMENT 'ID da notificação enviada',
  `user_id` INT UNSIGNED NOT NULL COMMENT 'ID do usuário destinatário',
  `process_token` VARCHAR(32) NOT NULL COMMENT 'Token do processo assíncrono',
  `process_status` VARCHAR(50) NOT NULL COMMENT 'Status do processo no momento da notificação',
  `delivery_channels` JSON NOT NULL COMMENT 'Canais utilizados para entrega',
  `timestamp` DATETIME NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se a notificação foi entregue com sucesso',
  `notes` TEXT NULL COMMENT 'Observações adicionais',
  PRIMARY KEY (`id`),
  INDEX `idx_async_logs_notification_id` (`notification_id`),
  INDEX `idx_async_logs_user_id` (`user_id`),
  INDEX `idx_async_logs_process_token` (`process_token`),
  INDEX `idx_async_logs_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `async_process_deliveries`
-- Rastreia entregas de notificações por canal para processos assíncronos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `async_process_deliveries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_id` INT UNSIGNED NOT NULL COMMENT 'ID da notificação',
  `process_token` VARCHAR(32) NOT NULL COMMENT 'Token do processo',
  `channel` ENUM('database', 'push', 'email') NOT NULL,
  `status` ENUM('queued', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'queued',
  `queued_at` DATETIME NOT NULL,
  `sent_at` DATETIME NULL,
  `delivered_at` DATETIME NULL,
  `error_message` TEXT NULL COMMENT 'Mensagem de erro em caso de falha',
  `retry_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de tentativas de envio',
  PRIMARY KEY (`id`),
  INDEX `idx_process_deliveries_notification_id` (`notification_id`),
  INDEX `idx_process_deliveries_process_token` (`process_token`),
  INDEX `idx_process_deliveries_status` (`status`),
  INDEX `idx_process_deliveries_channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela `async_process_notification_preferences`
-- Preferências de notificação específicas para processos assíncronos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `async_process_notification_preferences` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `notification_type` VARCHAR(50) NOT NULL COMMENT 'Código do tipo de notificação',
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se notificações deste tipo estão habilitadas',
  `email_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se notificações por e-mail estão habilitadas',
  `push_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se notificações push estão habilitadas',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_type_UNIQUE` (`user_id`, `notification_type`),
  INDEX `idx_async_prefs_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Adicionar tipos de notificação específicos para processos assíncronos
-- -----------------------------------------------------
INSERT INTO `notification_types` (`code`, `name`, `description`, `category`, `is_critical`) VALUES
('process_status', 'Mudança de Status do Processo', 'Notificações sobre mudanças no status de processos assíncronos', 'async_process', 0),
('process_progress', 'Progresso do Processo', 'Notificações sobre o progresso de processos assíncronos', 'async_process', 0),
('process_completed', 'Processo Concluído', 'Notificações quando um processo assíncrono é concluído com sucesso', 'async_process', 1),
('process_failed', 'Falha no Processo', 'Notificações quando um processo assíncrono falha', 'async_process', 1),
('process_results', 'Resultados Disponíveis', 'Notificações quando os resultados de um processo estão disponíveis', 'async_process', 1),
('process_expiration', 'Expiração de Processo', 'Avisos sobre processos que estão prestes a expirar', 'async_process', 1),
('admin_process_failure', 'Falha de Processo (Admin)', 'Alertas administrativos sobre falhas críticas em processos', 'admin', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `is_critical` = VALUES(`is_critical`);

-- -----------------------------------------------------
-- Adicionar preferências padrão para todos os usuários existentes
-- -----------------------------------------------------
INSERT INTO `async_process_notification_preferences` (user_id, notification_type, is_enabled, email_enabled, push_enabled)
SELECT id, 'process_status', 1, 1, 1 FROM users
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO `async_process_notification_preferences` (user_id, notification_type, is_enabled, email_enabled, push_enabled)
SELECT id, 'process_progress', 1, 0, 1 FROM users
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO `async_process_notification_preferences` (user_id, notification_type, is_enabled, email_enabled, push_enabled)
SELECT id, 'process_completed', 1, 1, 1 FROM users
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO `async_process_notification_preferences` (user_id, notification_type, is_enabled, email_enabled, push_enabled)
SELECT id, 'process_failed', 1, 1, 1 FROM users
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO `async_process_notification_preferences` (user_id, notification_type, is_enabled, email_enabled, push_enabled)
SELECT id, 'process_results', 1, 1, 1 FROM users
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO `async_process_notification_preferences` (user_id, notification_type, is_enabled, email_enabled, push_enabled)
SELECT id, 'process_expiration', 1, 1, 1 FROM users
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Reativar verificações de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;
