-- Criação das tabelas do Sistema de Fila de Impressão
-- Autor: Claude
-- Data: 2025-04-04

-- Tabela para armazenar os itens na fila de impressão
CREATE TABLE IF NOT EXISTS `print_queue` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `model_id` INT UNSIGNED NOT NULL COMMENT 'ID do modelo 3D em customer_models',
    `user_id` INT UNSIGNED NOT NULL COMMENT 'ID do usuário que solicitou a impressão',
    `status` ENUM('pending', 'assigned', 'printing', 'completed', 'cancelled', 'failed') NOT NULL DEFAULT 'pending',
    `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Prioridade de 1 a 10, com 10 sendo a mais alta',
    `notes` TEXT COMMENT 'Notas do cliente ou administrador sobre a impressão',
    `print_settings` JSON COMMENT 'Configurações específicas de impressão (escala, resolução, suportes, etc.)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`model_id`) REFERENCES `customer_models`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_model_id` (`model_id`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar informações sobre as impressoras disponíveis
CREATE TABLE IF NOT EXISTS `printers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `status` ENUM('available', 'busy', 'maintenance', 'offline') NOT NULL DEFAULT 'available',
    `capabilities` JSON COMMENT 'Capacidades da impressora (materiais suportados, tamanho máximo, etc.)',
    `current_job_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID do trabalho atual (null quando não estiver imprimindo)',
    `last_maintenance` DATETIME DEFAULT NULL COMMENT 'Data/hora da última manutenção',
    `notes` TEXT COMMENT 'Notas administrativas sobre a impressora',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar informações sobre os trabalhos de impressão
CREATE TABLE IF NOT EXISTS `print_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `queue_id` INT UNSIGNED NOT NULL COMMENT 'ID do item na fila associado',
    `printer_id` INT UNSIGNED NOT NULL COMMENT 'ID da impressora atribuída',
    `start_time` DATETIME DEFAULT NULL COMMENT 'Data/hora de início da impressão',
    `estimated_end_time` DATETIME DEFAULT NULL COMMENT 'Data/hora estimada para conclusão',
    `actual_end_time` DATETIME DEFAULT NULL COMMENT 'Data/hora real da conclusão',
    `status` ENUM('pending', 'preparing', 'printing', 'post-processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `progress` FLOAT DEFAULT 0 COMMENT 'Porcentagem de conclusão (0-100)',
    `notes` TEXT COMMENT 'Notas operacionais sobre o trabalho',
    `material_used` FLOAT DEFAULT NULL COMMENT 'Quantidade de material utilizado (em gramas)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`queue_id`) REFERENCES `print_queue`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`printer_id`) REFERENCES `printers`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`),
    INDEX `idx_printer_id` (`printer_id`),
    INDEX `idx_queue_id` (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar referência na tabela printers após a criação da tabela print_jobs
ALTER TABLE `printers` ADD FOREIGN KEY (`current_job_id`) REFERENCES `print_jobs`(`id`) ON DELETE SET NULL;

-- Tabela para armazenar o histórico de eventos da fila
CREATE TABLE IF NOT EXISTS `print_queue_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `queue_id` INT UNSIGNED NOT NULL COMMENT 'ID do item na fila',
    `event_type` VARCHAR(50) NOT NULL COMMENT 'Tipo de evento (status_change, printer_assignment, priority_change, etc.)',
    `description` TEXT NOT NULL COMMENT 'Descrição detalhada do evento',
    `previous_value` JSON DEFAULT NULL COMMENT 'Valor anterior (para rastreamento de alterações)',
    `new_value` JSON DEFAULT NULL COMMENT 'Novo valor (para rastreamento de alterações)',
    `created_by` INT UNSIGNED DEFAULT NULL COMMENT 'ID do usuário que gerou o evento',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`queue_id`) REFERENCES `print_queue`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_queue_id` (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar notificações relacionadas à fila de impressão
CREATE TABLE IF NOT EXISTS `print_notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'ID do usuário destinatário',
    `queue_id` INT UNSIGNED NOT NULL COMMENT 'ID do item na fila',
    `job_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID do trabalho de impressão (opcional)',
    `title` VARCHAR(255) NOT NULL COMMENT 'Título da notificação',
    `message` TEXT NOT NULL COMMENT 'Conteúdo da notificação',
    `type` ENUM('info', 'warning', 'success', 'error') NOT NULL DEFAULT 'info',
    `status` ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `read_at` DATETIME DEFAULT NULL COMMENT 'Data/hora em que a notificação foi lida',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`queue_id`) REFERENCES `print_queue`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`job_id`) REFERENCES `print_jobs`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar configurações da fila de impressão
CREATE TABLE IF NOT EXISTS `print_queue_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `description` TEXT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão
INSERT INTO `print_queue_settings` (`setting_key`, `setting_value`, `description`) VALUES
('default_priority', '5', 'Prioridade padrão para novos itens na fila (1-10)'),
('notify_on_status_change', 'true', 'Enviar notificações quando o status de um item mudar'),
('notify_on_completion', 'true', 'Enviar notificações quando uma impressão for concluída'),
('notify_on_failure', 'true', 'Enviar notificações quando uma impressão falhar'),
('estimated_time_buffer', '10', 'Porcentagem de tempo adicional para estimativas (%)'),
('max_queue_items_per_user', '10', 'Número máximo de itens na fila por usuário');

-- Triggers para automação

-- Trigger para atualizar o status da impressora quando um trabalho começa
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `after_print_job_update_printer`
AFTER UPDATE ON `print_jobs`
FOR EACH ROW
BEGIN
    IF NEW.status = 'printing' AND OLD.status != 'printing' THEN
        UPDATE printers SET status = 'busy', current_job_id = NEW.id WHERE id = NEW.printer_id;
    ELSEIF (NEW.status = 'completed' OR NEW.status = 'failed') AND OLD.status = 'printing' THEN
        UPDATE printers SET status = 'available', current_job_id = NULL WHERE id = NEW.printer_id;
    END IF;
END//
DELIMITER ;

-- Trigger para atualizar o status do item na fila quando um trabalho muda de status
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `after_print_job_update_queue`
AFTER UPDATE ON `print_jobs`
FOR EACH ROW
BEGIN
    DECLARE queue_status VARCHAR(20);
    
    CASE NEW.status
        WHEN 'pending' THEN SET queue_status = 'assigned';
        WHEN 'preparing' THEN SET queue_status = 'assigned';
        WHEN 'printing' THEN SET queue_status = 'printing';
        WHEN 'post-processing' THEN SET queue_status = 'printing';
        WHEN 'completed' THEN SET queue_status = 'completed';
        WHEN 'failed' THEN SET queue_status = 'failed';
        ELSE SET queue_status = NULL;
    END CASE;
    
    IF queue_status IS NOT NULL THEN
        UPDATE print_queue SET status = queue_status WHERE id = NEW.queue_id;
    END IF;
END//
DELIMITER ;

-- Índices adicionais para performance em consultas frequentes
ALTER TABLE `print_queue` ADD INDEX `idx_created_at` (`created_at`);
ALTER TABLE `print_jobs` ADD INDEX `idx_start_time` (`start_time`);
ALTER TABLE `print_jobs` ADD INDEX `idx_end_time` (`actual_end_time`);
ALTER TABLE `print_notifications` ADD INDEX `idx_created_at` (`created_at`);
