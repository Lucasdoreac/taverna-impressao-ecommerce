-- Tabelas para o Sistema de Monitoramento em Tempo Real do Status da Impressão
-- Desenvolvido para Taverna da Impressão 3D
-- Data: 2025-03-31

-- Tabela de Status de Impressão
CREATE TABLE IF NOT EXISTS `print_status` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `print_queue_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pending', 'preparing', 'printing', 'paused', 'completed', 'failed', 'canceled') NOT NULL DEFAULT 'pending',
  `progress_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `started_at` DATETIME DEFAULT NULL,
  `estimated_completion` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_print_time_seconds` INT UNSIGNED DEFAULT NULL,
  `elapsed_print_time_seconds` INT UNSIGNED DEFAULT 0,
  `printer_id` VARCHAR(50) DEFAULT NULL,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  INDEX `idx_print_status_order` (`order_id`),
  INDEX `idx_print_status_product` (`product_id`),
  INDEX `idx_print_status_queue` (`print_queue_id`),
  INDEX `idx_print_status_status` (`status`),
  INDEX `idx_print_status_printer` (`printer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Atualizações de Status
CREATE TABLE IF NOT EXISTS `print_status_updates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `print_status_id` INT UNSIGNED NOT NULL,
  `previous_status` ENUM('pending', 'preparing', 'printing', 'paused', 'completed', 'failed', 'canceled') DEFAULT NULL,
  `new_status` ENUM('pending', 'preparing', 'printing', 'paused', 'completed', 'failed', 'canceled') NOT NULL,
  `previous_progress` DECIMAL(5,2) DEFAULT NULL,
  `new_progress` DECIMAL(5,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(100) DEFAULT NULL,
  `message` TEXT,
  PRIMARY KEY (`id`),
  INDEX `idx_status_updates_print_status` (`print_status_id`),
  INDEX `idx_status_updates_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configurações de Impressora
CREATE TABLE IF NOT EXISTS `printer_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `printer_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `status` ENUM('online', 'offline', 'maintenance', 'error') NOT NULL DEFAULT 'offline',
  `max_bed_temp` INT UNSIGNED DEFAULT NULL,
  `max_hotend_temp` INT UNSIGNED DEFAULT NULL,
  `available_filaments` JSON DEFAULT NULL,
  `current_filament` VARCHAR(50) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_maintenance` DATE DEFAULT NULL,
  `api_key` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_printer_printer_id` (`printer_id`),
  INDEX `idx_printer_status` (`status`),
  INDEX `idx_printer_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Mensagens de Status
CREATE TABLE IF NOT EXISTS `status_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `print_status_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'warning', 'error', 'success') NOT NULL DEFAULT 'info',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_visible_to_customer` TINYINT(1) NOT NULL DEFAULT 0,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_messages_print_status` (`print_status_id`),
  INDEX `idx_messages_type` (`type`),
  INDEX `idx_messages_visible` (`is_visible_to_customer`),
  INDEX `idx_messages_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Métricas de Impressão
CREATE TABLE IF NOT EXISTS `print_metrics` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `print_status_id` INT UNSIGNED NOT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `hotend_temp` DECIMAL(5,2) DEFAULT NULL,
  `bed_temp` DECIMAL(5,2) DEFAULT NULL,
  `speed_percentage` TINYINT UNSIGNED DEFAULT NULL,
  `fan_speed_percentage` TINYINT UNSIGNED DEFAULT NULL,
  `layer_height` DECIMAL(5,2) DEFAULT NULL,
  `current_layer` INT UNSIGNED DEFAULT NULL,
  `total_layers` INT UNSIGNED DEFAULT NULL,
  `filament_used_mm` DECIMAL(10,2) DEFAULT NULL,
  `print_time_remaining_seconds` INT UNSIGNED DEFAULT NULL,
  `additional_data` JSON DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_metrics_print_status` (`print_status_id`),
  INDEX `idx_metrics_recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configurações de Notificação
CREATE TABLE IF NOT EXISTS `print_notification_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `notify_on_start` TINYINT(1) NOT NULL DEFAULT 1,
  `notify_on_complete` TINYINT(1) NOT NULL DEFAULT 1,
  `notify_on_failure` TINYINT(1) NOT NULL DEFAULT 1,
  `notify_on_pause` TINYINT(1) NOT NULL DEFAULT 0,
  `notify_on_resume` TINYINT(1) NOT NULL DEFAULT 0,
  `notify_on_progress` TINYINT(1) NOT NULL DEFAULT 0,
  `progress_notification_interval` INT UNSIGNED DEFAULT 25,
  `email_notifications` TINYINT(1) NOT NULL DEFAULT 1,
  `sms_notifications` TINYINT(1) NOT NULL DEFAULT 0,
  `web_notifications` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_notification_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar chaves estrangeiras

-- Adicionar referências da tabela print_status
ALTER TABLE `print_status`
  ADD CONSTRAINT `fk_print_status_order`
  FOREIGN KEY (`order_id`)
  REFERENCES `orders` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `print_status`
  ADD CONSTRAINT `fk_print_status_product`
  FOREIGN KEY (`product_id`)
  REFERENCES `products` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `print_status`
  ADD CONSTRAINT `fk_print_status_queue`
  FOREIGN KEY (`print_queue_id`)
  REFERENCES `print_queue` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

-- Adicionar referências da tabela print_status_updates
ALTER TABLE `print_status_updates`
  ADD CONSTRAINT `fk_status_updates_print_status`
  FOREIGN KEY (`print_status_id`)
  REFERENCES `print_status` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

-- Adicionar referências da tabela status_messages
ALTER TABLE `status_messages`
  ADD CONSTRAINT `fk_messages_print_status`
  FOREIGN KEY (`print_status_id`)
  REFERENCES `print_status` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

-- Adicionar referências da tabela print_metrics
ALTER TABLE `print_metrics`
  ADD CONSTRAINT `fk_metrics_print_status`
  FOREIGN KEY (`print_status_id`)
  REFERENCES `print_status` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

-- Adicionar referências da tabela print_notification_settings
ALTER TABLE `print_notification_settings`
  ADD CONSTRAINT `fk_notification_user`
  FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

-- Inserir alguns exemplos de configurações de impressora
INSERT INTO `printer_settings` 
  (`printer_id`, `name`, `type`, `status`, `max_bed_temp`, `max_hotend_temp`, `available_filaments`, `current_filament`, `is_active`)
VALUES 
  ('PRINTER001', 'Ender 3 Pro', 'FDM', 'online', 100, 240, '["PLA", "ABS", "PETG"]', 'PLA', 1),
  ('PRINTER002', 'Prusa i3 MK3S+', 'FDM', 'online', 120, 280, '["PLA", "ABS", "PETG", "TPU"]', 'PETG', 1),
  ('PRINTER003', 'Anycubic Photon Mono', 'SLA', 'maintenance', NULL, NULL, '["Standard Resin", "Tough Resin", "Flexible Resin"]', 'Standard Resin', 1);
