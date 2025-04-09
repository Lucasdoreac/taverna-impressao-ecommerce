-- Migrations para o Sistema de Cotação Automatizada
-- Autor: Taverna da Impressão
-- Data: 07/04/2025

-- Tabela de materiais disponíveis para impressão 3D
CREATE TABLE IF NOT EXISTS `materials` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `base_price_per_gram` DECIMAL(10,2) NOT NULL,
  `density` DECIMAL(10,2) NOT NULL COMMENT 'Densidade em g/cm³ para cálculo de peso',
  `color` VARCHAR(7) NULL COMMENT 'Código de cor (hex)',
  `min_thickness` DECIMAL(10,2) NULL COMMENT 'Espessura mínima em mm',
  `max_size` INT NULL COMMENT 'Tamanho máximo em mm',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `code_UNIQUE` (`code` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de níveis de complexidade (para cálculo de preço)
CREATE TABLE IF NOT EXISTS `complexity_levels` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `min_value` DECIMAL(10,2) NOT NULL,
  `max_value` DECIMAL(10,2) NOT NULL,
  `multiplier` DECIMAL(10,2) NOT NULL COMMENT 'Multiplicador de preço',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações de cotação
CREATE TABLE IF NOT EXISTS `quotation_config` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `parameter_name` VARCHAR(100) NOT NULL,
  `parameter_value` TEXT NOT NULL,
  `parameter_type` ENUM('string', 'numeric', 'boolean', 'json') NOT NULL DEFAULT 'string',
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `parameter_name_UNIQUE` (`parameter_name` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de histórico de cotações
CREATE TABLE IF NOT EXISTS `quotation_history` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `model_id` INT NULL,
  `material_id` INT NULL,
  `complexity_score` DECIMAL(10,2) NOT NULL,
  `complexity_level_id` INT NULL,
  `estimated_weight` DECIMAL(10,2) NULL COMMENT 'Peso estimado em gramas',
  `estimated_print_time` INT NULL COMMENT 'Tempo estimado em minutos',
  `base_price` DECIMAL(10,2) NOT NULL,
  `complexity_price` DECIMAL(10,2) NOT NULL,
  `additional_fees` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL,
  `parameters` JSON NULL COMMENT 'Parâmetros específicos usados na cotação',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_quotation_history_user_idx` (`user_id` ASC),
  INDEX `fk_quotation_history_model_idx` (`model_id` ASC),
  INDEX `fk_quotation_history_material_idx` (`material_id` ASC),
  INDEX `fk_quotation_history_complexity_idx` (`complexity_level_id` ASC),
  CONSTRAINT `fk_quotation_history_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_quotation_history_model`
    FOREIGN KEY (`model_id`)
    REFERENCES `customer_models` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_quotation_history_material`
    FOREIGN KEY (`material_id`)
    REFERENCES `materials` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_quotation_history_complexity`
    FOREIGN KEY (`complexity_level_id`)
    REFERENCES `complexity_levels` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir dados iniciais para níveis de complexidade
INSERT INTO `complexity_levels` (`name`, `description`, `min_value`, `max_value`, `multiplier`) VALUES
('Muito Baixa', 'Modelos extremamente simples com poucas faces e geometria básica', 0.00, 1.99, 1.0),
('Baixa', 'Modelos simples com geometria regular', 2.00, 3.99, 1.2),
('Média', 'Modelos com complexidade mediana', 4.00, 5.99, 1.5),
('Alta', 'Modelos complexos com muitos detalhes', 6.00, 7.99, 1.8),
('Muito Alta', 'Modelos extremamente complexos com estruturas intrincadas', 8.00, 10.00, 2.2);

-- Inserir dados iniciais para materiais
INSERT INTO `materials` (`name`, `code`, `description`, `base_price_per_gram`, `density`, `color`, `min_thickness`, `max_size`, `is_active`) VALUES
('PLA Básico', 'PLA_BASIC', 'Filamento PLA básico para impressões comuns', 0.15, 1.24, '#FFFFFF', 0.8, 200, 1),
('PLA Premium', 'PLA_PREMIUM', 'Filamento PLA de alta qualidade para impressões detalhadas', 0.22, 1.24, '#FFFFFF', 0.4, 200, 1),
('ABS', 'ABS_STD', 'Filamento ABS para peças resistentes', 0.18, 1.04, '#1A1A1A', 1.0, 180, 1),
('PETG', 'PETG_STD', 'Filamento PETG para peças funcionais e resistentes', 0.20, 1.27, '#00A3E0', 0.8, 220, 1),
('TPU Flexível', 'TPU_FLEX', 'Filamento flexível para peças com elasticidade', 0.35, 1.21, '#F28C28', 1.5, 150, 1),
('Resina Standard', 'RESIN_STD', 'Resina padrão para impressão SLA com alta resolução', 0.40, 1.10, '#E6E6E6', 0.3, 180, 1);

-- Inserir configurações iniciais
INSERT INTO `quotation_config` (`parameter_name`, `parameter_value`, `parameter_type`, `description`) VALUES
('base_service_fee', '10.00', 'numeric', 'Taxa base de serviço aplicada a todas as cotações'),
('complexity_algorithm_version', '1.0', 'string', 'Versão atual do algoritmo de cálculo de complexidade'),
('support_material_multiplier', '0.2', 'numeric', 'Multiplicador para material de suporte (20% do material principal)'),
('time_cost_per_hour', '8.00', 'numeric', 'Custo por hora de impressão'),
('min_wall_thickness', '0.8', 'numeric', 'Espessura mínima de parede em mm'),
('default_infill_percentage', '20', 'numeric', 'Percentual de preenchimento padrão'),
('max_model_dimension', '200', 'numeric', 'Dimensão máxima para modelos em mm'),
('complexity_factors', '{"triangles_weight":0.4,"vertices_weight":0.2,"volume_weight":0.2,"aspect_ratio_weight":0.1,"overhang_weight":0.1}', 'json', 'Pesos para fatores de complexidade');
