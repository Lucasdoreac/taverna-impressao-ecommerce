-- Tabela principal de cotações
CREATE TABLE IF NOT EXISTS quotations (
    id VARCHAR(36) PRIMARY KEY,
    customer_id INT NOT NULL,
    model_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    quotation_data JSON NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    complexity_score DECIMAL(5,2) NOT NULL,
    estimated_print_time INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    approved_by INT NULL,
    approval_date TIMESTAMP NULL,
    notes TEXT NULL,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (model_id) REFERENCES customer_models(id),
    INDEX (status),
    INDEX (customer_id),
    INDEX (created_at),
    INDEX (expires_at)
);

-- Tabela de histórico de cotações para auditoria
CREATE TABLE IF NOT EXISTS quotation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id VARCHAR(36) NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NULL,
    previous_data JSON NULL,
    new_data JSON NULL,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id),
    INDEX (quotation_id),
    INDEX (action),
    INDEX (created_at)
);

-- Tabela de parâmetros de cotação
CREATE TABLE IF NOT EXISTS quotation_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parameters JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Inserir dados iniciais na tabela de parâmetros
INSERT INTO quotation_parameters (parameters) VALUES (
    '{
        "materials": {
            "pla": {
                "name": "PLA",
                "price_per_kg": 120.00,
                "density": 1.24,
                "printer_cost_per_hour": 10.00
            },
            "petg": {
                "name": "PETG",
                "price_per_kg": 150.00,
                "density": 1.27,
                "printer_cost_per_hour": 12.00
            },
            "abs": {
                "name": "ABS",
                "price_per_kg": 140.00,
                "density": 1.04,
                "printer_cost_per_hour": 15.00
            },
            "tpu": {
                "name": "TPU",
                "price_per_kg": 220.00,
                "density": 1.21,
                "printer_cost_per_hour": 20.00
            },
            "resin": {
                "name": "Resina",
                "price_per_kg": 350.00,
                "density": 1.12,
                "printer_cost_per_hour": 35.00
            }
        },
        "quality_settings": {
            "draft": {
                "name": "Rascunho",
                "layer_height": 0.3,
                "time_factor": 0.7,
                "price_factor": 0.8,
                "default_infill": 15
            },
            "standard": {
                "name": "Padrão",
                "layer_height": 0.2,
                "time_factor": 1.0,
                "price_factor": 1.0,
                "default_infill": 20
            },
            "high": {
                "name": "Alta Qualidade",
                "layer_height": 0.1,
                "time_factor": 1.8,
                "price_factor": 1.3,
                "default_infill": 25
            },
            "ultra": {
                "name": "Ultra Qualidade",
                "layer_height": 0.05,
                "time_factor": 3.0,
                "price_factor": 1.7,
                "default_infill": 30
            }
        },
        "complexity_factors": {
            "simple": 1.0,
            "moderate": 1.25,
            "complex": 1.5,
            "very_complex": 2.0
        },
        "base_margin_rate": 0.35,
        "minimum_price": 20.00,
        "risk_factor_per_point": 0.02,
        "urgent_factor": 1.5,
        "delivery_options": {
            "standard": {
                "name": "Padrão",
                "min_days": 2,
                "max_days": 10,
                "additional_cost": 0.00
            },
            "express": {
                "name": "Express",
                "min_days": 1,
                "max_days": 3,
                "additional_cost": 30.00
            }
        }
    }'
);