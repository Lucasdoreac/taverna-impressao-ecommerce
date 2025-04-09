-- Criação da tabela customer_models
CREATE TABLE IF NOT EXISTS `customer_models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL COMMENT 'Nome seguro do arquivo no servidor',
  `original_name` varchar(255) NOT NULL COMMENT 'Nome original do arquivo enviado',
  `file_size` int(11) NOT NULL COMMENT 'Tamanho do arquivo em bytes',
  `file_type` varchar(10) NOT NULL COMMENT 'Extensão/tipo do arquivo (stl, obj, 3mf)',
  `notes` text DEFAULT NULL COMMENT 'Observações do usuário sobre o modelo',
  `admin_notes` text DEFAULT NULL COMMENT 'Notas administrativas (ex: motivo de rejeição)',
  `status` enum('pending_validation','approved','rejected') NOT NULL DEFAULT 'pending_validation',
  `validation_data` longtext DEFAULT NULL COMMENT 'Dados JSON da validação técnica',
  `print_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Número de vezes que o modelo foi impresso',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_model_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de auditoria para operações com modelos 3D
CREATE TABLE IF NOT EXISTS `model_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Usuário que realizou a ação',
  `action` varchar(50) NOT NULL COMMENT 'Tipo de ação (upload, approve, reject, delete)',
  `action_data` text DEFAULT NULL COMMENT 'Dados JSON relacionados à ação',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Endereço IP',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'User Agent',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_model_id` FOREIGN KEY (`model_id`) REFERENCES `customer_models` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audit_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para metadados técnicos de modelos 3D
CREATE TABLE IF NOT EXISTS `model_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL,
  `format` varchar(20) DEFAULT NULL COMMENT 'Formato específico (STL Binary, STL ASCII, etc)',
  `triangles` int(11) DEFAULT NULL COMMENT 'Número de triângulos',
  `vertices` int(11) DEFAULT NULL COMMENT 'Número de vértices',
  `width` float DEFAULT NULL COMMENT 'Largura aproximada (unidades do modelo)',
  `height` float DEFAULT NULL COMMENT 'Altura aproximada (unidades do modelo)',
  `depth` float DEFAULT NULL COMMENT 'Profundidade aproximada (unidades do modelo)',
  `volume` float DEFAULT NULL COMMENT 'Volume estimado (cm³)',
  `is_watertight` tinyint(1) DEFAULT NULL COMMENT 'Se o modelo é estanque',
  `has_issues` tinyint(1) DEFAULT NULL COMMENT 'Se o modelo tem problemas estruturais',
  `analysis_data` longtext DEFAULT NULL COMMENT 'Dados JSON da análise técnica detalhada',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_model_id` (`model_id`),
  CONSTRAINT `fk_metadata_model_id` FOREIGN KEY (`model_id`) REFERENCES `customer_models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para rastreamento de uploads (controle de taxa)
CREATE TABLE IF NOT EXISTS `upload_rate_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `upload_count` int(11) NOT NULL DEFAULT 1,
  `first_upload_time` datetime NOT NULL,
  `last_upload_time` datetime NOT NULL,
  `tracking_period` enum('hour','day','week','month') NOT NULL DEFAULT 'hour',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_period_idx` (`user_id`,`tracking_period`),
  CONSTRAINT `fk_upload_track_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger para atualizar o controle de taxa de upload
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `update_upload_rate_tracking_after_insert` 
AFTER INSERT ON `customer_models`
FOR EACH ROW
BEGIN
    -- Tracking por hora
    INSERT INTO `upload_rate_tracking` 
        (`user_id`, `upload_count`, `first_upload_time`, `last_upload_time`, `tracking_period`) 
    VALUES 
        (NEW.`user_id`, 1, NOW(), NOW(), 'hour')
    ON DUPLICATE KEY UPDATE 
        `upload_count` = `upload_count` + 1,
        `last_upload_time` = NOW();
        
    -- Tracking por dia
    INSERT INTO `upload_rate_tracking` 
        (`user_id`, `upload_count`, `first_upload_time`, `last_upload_time`, `tracking_period`) 
    VALUES 
        (NEW.`user_id`, 1, NOW(), NOW(), 'day')
    ON DUPLICATE KEY UPDATE 
        `upload_count` = `upload_count` + 1,
        `last_upload_time` = NOW();
END //
DELIMITER ;

-- Evento para limpar dados antigos de tracking de upload
DELIMITER //
CREATE EVENT IF NOT EXISTS `cleanup_upload_rate_tracking` 
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    -- Limpar tracking de hora com mais de 2 horas
    DELETE FROM `upload_rate_tracking` 
    WHERE `tracking_period` = 'hour' 
    AND `last_upload_time` < DATE_SUB(NOW(), INTERVAL 2 HOUR);
    
    -- Limpar tracking de dia com mais de 2 dias
    DELETE FROM `upload_rate_tracking` 
    WHERE `tracking_period` = 'day' 
    AND `last_upload_time` < DATE_SUB(NOW(), INTERVAL 2 DAY);
END //
DELIMITER ;
