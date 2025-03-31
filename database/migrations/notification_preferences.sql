-- Migração para adicionar tabelas de preferências de notificação
-- Data: 31/03/2025

-- -----------------------------------------------------
-- Tabela notification_types
-- Armazena os diferentes tipos de notificações disponíveis
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_types` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL COMMENT 'Código único para identificação interna',
  `name` VARCHAR(100) NOT NULL COMMENT 'Nome legível do tipo de notificação',
  `description` TEXT NULL COMMENT 'Descrição detalhada do tipo de notificação',
  `category` VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT 'Categoria da notificação (pedidos, impressão3d, sistema, etc)',
  `is_critical` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Se é um tipo crítico que não pode ser desativado',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `code_UNIQUE` (`code` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela notification_channels
-- Armazena os diferentes canais de entrega de notificações
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_channels` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL COMMENT 'Código único para identificação interna',
  `name` VARCHAR(100) NOT NULL COMMENT 'Nome legível do canal de notificação',
  `description` TEXT NULL COMMENT 'Descrição detalhada do canal de notificação',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se o canal está ativo no sistema',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `code_UNIQUE` (`code` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela user_notification_preferences
-- Armazena as preferências de notificação para cada usuário
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL COMMENT 'ID do usuário',
  `notification_type_id` INT NOT NULL COMMENT 'ID do tipo de notificação',
  `notification_channel_id` INT NOT NULL COMMENT 'ID do canal de notificação',
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se as notificações deste tipo e canal estão habilitadas',
  `frequency` VARCHAR(20) NOT NULL DEFAULT 'realtime' COMMENT 'Frequência de envio: realtime, daily, weekly',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_type_channel_UNIQUE` (`user_id` ASC, `notification_type_id` ASC, `notification_channel_id` ASC),
  INDEX `user_id_idx` (`user_id` ASC),
  INDEX `notification_type_id_idx` (`notification_type_id` ASC),
  INDEX `notification_channel_id_idx` (`notification_channel_id` ASC),
  CONSTRAINT `fk_user_notification_preferences_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_notification_preferences_type`
    FOREIGN KEY (`notification_type_id`)
    REFERENCES `notification_types` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_notification_preferences_channel`
    FOREIGN KEY (`notification_channel_id`)
    REFERENCES `notification_channels` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabela notification_delivery_logs
-- Armazena logs de entrega de notificações para auditoria e análise
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_delivery_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `notification_id` INT NOT NULL COMMENT 'ID da notificação enviada',
  `user_id` INT NOT NULL COMMENT 'ID do usuário',
  `notification_type_id` INT NOT NULL COMMENT 'ID do tipo de notificação',
  `notification_channel_id` INT NOT NULL COMMENT 'ID do canal de notificação',
  `status` VARCHAR(20) NOT NULL COMMENT 'Status da entrega: sent, failed, delivered, read',
  `sent_at` TIMESTAMP NULL COMMENT 'Data/hora do envio',
  `delivered_at` TIMESTAMP NULL COMMENT 'Data/hora da entrega confirmada',
  `read_at` TIMESTAMP NULL COMMENT 'Data/hora da leitura confirmada',
  `error_message` TEXT NULL COMMENT 'Mensagem de erro em caso de falha',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `notification_id_idx` (`notification_id` ASC),
  INDEX `user_id_idx` (`user_id` ASC),
  INDEX `sent_at_idx` (`sent_at` ASC),
  CONSTRAINT `fk_notification_delivery_logs_notification`
    FOREIGN KEY (`notification_id`)
    REFERENCES `notifications` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Dados Iniciais - Tipos de Notificação
-- -----------------------------------------------------
INSERT INTO `notification_types` (`code`, `name`, `description`, `category`, `is_critical`) VALUES
('order_status_change', 'Alteração de Status de Pedido', 'Notificações sobre mudanças no status do seu pedido', 'pedidos', 1),
('print_queue_update', 'Atualização na Fila de Impressão', 'Notificações sobre atualizações na fila de impressão 3D', 'impressao3d', 0),
('print_started', 'Impressão Iniciada', 'Notificações quando a impressão 3D do seu item começar', 'impressao3d', 1),
('print_completed', 'Impressão Concluída', 'Notificações quando a impressão 3D do seu item for concluída', 'impressao3d', 1),
('print_failed', 'Falha na Impressão', 'Notificações sobre falhas no processo de impressão 3D', 'impressao3d', 1),
('printer_assigned', 'Impressora Atribuída', 'Notificações quando uma impressora for atribuída ao seu trabalho', 'impressao3d', 0),
('new_promotion', 'Novas Promoções', 'Notificações sobre novas promoções e descontos', 'marketing', 0),
('shipping_update', 'Atualização de Envio', 'Notificações sobre o envio e entrega do seu pedido', 'pedidos', 1),
('payment_received', 'Pagamento Recebido', 'Notificações quando seu pagamento for processado com sucesso', 'pedidos', 1),
('payment_failed', 'Falha no Pagamento', 'Notificações sobre problemas com seu pagamento', 'pedidos', 1),
('product_restock', 'Reabastecimento de Produto', 'Notificações quando um produto da sua lista de desejos for reabastecido', 'produtos', 0),
('account_security', 'Segurança da Conta', 'Notificações relacionadas à segurança da sua conta', 'sistema', 1);

-- -----------------------------------------------------
-- Dados Iniciais - Canais de Notificação
-- -----------------------------------------------------
INSERT INTO `notification_channels` (`code`, `name`, `description`, `is_active`) VALUES
('website', 'Website', 'Notificações exibidas no site após login', 1),
('email', 'E-mail', 'Notificações enviadas para seu endereço de e-mail', 1),
('sms', 'SMS', 'Notificações enviadas via mensagem de texto para seu celular', 0),
('push', 'Notificação Push', 'Notificações enviadas diretamente para seu navegador ou aplicativo', 0);
