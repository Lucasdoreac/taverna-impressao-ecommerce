-- Migration: add_print_status_files
-- Descrição: Adiciona tabela para armazenamento seguro de arquivos relacionados ao status de impressão

-- Criar tabela print_status_files para armazenar arquivos relacionados aos status de impressão
CREATE TABLE IF NOT EXISTS `print_status_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `print_status_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `access_token` varchar(64) DEFAULT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `download_count` int(11) NOT NULL DEFAULT '0',
  `last_download` datetime DEFAULT NULL,
  `file_hash` varchar(128) DEFAULT NULL,
  `content_type` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_print_status_files_print_status_id` (`print_status_id`),
  KEY `idx_print_status_files_uploaded_by` (`uploaded_by`),
  KEY `idx_print_status_files_file_type` (`file_type`),
  KEY `idx_print_status_files_access_token` (`access_token`),
  CONSTRAINT `fk_print_status_files_print_status_id` FOREIGN KEY (`print_status_id`) REFERENCES `print_status` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_print_status_files_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar campo file_count na tabela print_status para facilitar a contagem de arquivos
ALTER TABLE `print_status` ADD COLUMN IF NOT EXISTS `file_count` int(11) NOT NULL DEFAULT '0';

-- Criar trigger para atualizar o contador de arquivos automaticamente
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `update_print_status_file_count_after_insert`
AFTER INSERT ON `print_status_files`
FOR EACH ROW
BEGIN
    UPDATE `print_status` SET `file_count` = (
        SELECT COUNT(*) FROM `print_status_files` WHERE `print_status_id` = NEW.print_status_id
    ) WHERE `id` = NEW.print_status_id;
END //

CREATE TRIGGER IF NOT EXISTS `update_print_status_file_count_after_delete`
AFTER DELETE ON `print_status_files`
FOR EACH ROW
BEGIN
    UPDATE `print_status` SET `file_count` = (
        SELECT COUNT(*) FROM `print_status_files` WHERE `print_status_id` = OLD.print_status_id
    ) WHERE `id` = OLD.print_status_id;
END //
DELIMITER ;

-- Tabela para rastrear histórico de acesso aos arquivos
CREATE TABLE IF NOT EXISTS `print_status_file_access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `access_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `access_type` enum('download','view') NOT NULL,
  `access_status` enum('success','denied') NOT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_file_access_log_file_id` (`file_id`),
  KEY `idx_file_access_log_user_id` (`user_id`),
  KEY `idx_file_access_log_access_time` (`access_time`),
  CONSTRAINT `fk_file_access_log_file_id` FOREIGN KEY (`file_id`) REFERENCES `print_status_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_access_log_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna para permissões de acesso granular
ALTER TABLE `print_status_files` ADD COLUMN IF NOT EXISTS `access_permissions` text AFTER `is_public`;

-- Adicionar coluna para status de verificação do arquivo
ALTER TABLE `print_status_files` ADD COLUMN IF NOT EXISTS `security_scan_status` enum('pending', 'in_progress', 'clean', 'suspicious', 'malicious') DEFAULT 'pending' AFTER `file_hash`;
ALTER TABLE `print_status_files` ADD COLUMN IF NOT EXISTS `security_scan_date` datetime DEFAULT NULL AFTER `security_scan_status`;
ALTER TABLE `print_status_files` ADD COLUMN IF NOT EXISTS `security_scan_notes` text AFTER `security_scan_date`;

-- Criar diretório para arquivos se ele não existir via PHP
-- Nota: Isto deve ser executado manualmente ou por um script PHP separado
-- <?php
-- $uploadDir = __DIR__ . '/../public/uploads/print_status_files';
-- if (!is_dir($uploadDir)) {
--     mkdir($uploadDir, 0755, true);
-- }
-- ?>

-- Adicionar comentário com instruções para criar o diretório
-- IMPORTANT: Execute o seguinte comando PHP para criar o diretório de uploads:
-- php -r "mkdir(__DIR__ . '/public/uploads/print_status_files', 0755, true);"