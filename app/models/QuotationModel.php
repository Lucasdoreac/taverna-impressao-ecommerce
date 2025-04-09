<?php
/**
 * QuotationModel
 * 
 * Responsável pelo cálculo de cotações automatizadas baseadas em complexidade para 
 * modelos 3D. Esta classe implementa algoritmos para analisar metadados de modelos,
 * calcular coeficientes de complexidade e gerar cotações precisas.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Models
 * @version    1.0.0
 */

class QuotationModel {
    private $db;
    
    /**
     * @var array Configurações padrão de preços
     */
    private $defaultPriceSettings = [
        'base_material_cost_per_g' => 0.35, // Custo base do material por grama (R$)
        'complexity_multiplier' => 1.0,      // Multiplicador de complexidade
        'base_hour_cost' => 30.0,           // Custo base por hora de impressão (R$)
        'setup_cost' => 15.0,               // Custo de preparação (R$)
        'finishing_cost_per_cm2' => 0.1,    // Custo de acabamento por cm² (R$)
        'minimum_price' => 35.0,            // Preço mínimo (R$)
        'profit_margin' => 0.30             // Margem de lucro (30%)
    ];
    
    /**
     * @var array Configurações específicas por material
     */
    private $materialSettings = [
        'pla' => [
            'name' => 'PLA',
            'density' => 1.24,             // Densidade em g/cm³
            'cost_per_g' => 0.35,          // Custo por grama (R$)
            'print_speed_multiplier' => 1.0 // Multiplicador de velocidade de impressão
        ],
        'abs' => [
            'name' => 'ABS',
            'density' => 1.04,
            'cost_per_g' => 0.38,
            'print_speed_multiplier' => 0.9
        ],
        'petg' => [
            'name' => 'PETG',
            'density' => 1.27,
            'cost_per_g' => 0.42,
            'print_speed_multiplier' => 0.95
        ],
        'tpu' => [
            'name' => 'TPU (Flexível)',
            'density' => 1.21,
            'cost_per_g' => 0.60,
            'print_speed_multiplier' => 0.7
        ],
        'resin' => [
            'name' => 'Resina',
            'density' => 1.10,
            'cost_per_g' => 0.75,
            'print_speed_multiplier' => 1.2
        ]
    ];
    
    /**
     * @var array Configurações de complexidade
     */
    private $complexitySettings = [
        'triangle_threshold_low' => 5000,    // Limite inferior de triângulos para complexidade baixa
        'triangle_threshold_medium' => 50000, // Limite inferior de triângulos para complexidade média
        'triangle_threshold_high' => 200000,  // Limite inferior de triângulos para complexidade alta
        
        'complexity_multiplier_low' => 1.0,   // Multiplicador para complexidade baixa
        'complexity_multiplier_medium' => 1.3, // Multiplicador para complexidade média
        'complexity_multiplier_high' => 1.6,   // Multiplicador para complexidade alta
        'complexity_multiplier_very_high' => 2.0 // Multiplicador para complexidade muito alta
    ];
    
    /**
     * @var array Configurações de impressora
     */
    private $printerSettings = [
        'fdm_standard' => [
            'name' => 'FDM Padrão',
            'volume_x' => 220,              // Volume de impressão em mm
            'volume_y' => 220,
            'volume_z' => 250,
            'base_print_speed' => 60,       // Velocidade base em mm/s
            'cost_per_hour' => 30.0         // Custo por hora (R$)
        ],
        'fdm_large' => [
            'name' => 'FDM Grande',
            'volume_x' => 300,
            'volume_y' => 300,
            'volume_z' => 400,
            'base_print_speed' => 50,
            'cost_per_hour' => 45.0
        ],
        'sla_standard' => [
            'name' => 'SLA Resina',
            'volume_x' => 192,
            'volume_y' => 120,
            'volume_z' => 200,
            'base_print_speed' => 30,       // Em camadas/hora
            'cost_per_hour' => 50.0
        ]
    ];
    
    /**
     * Construtor da classe
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }
    
    /**
     * Carrega configurações salvas no banco de dados
     */
    private function loadSettings() {
        try {
            // Buscar configurações gerais de preço
            $sql = "SELECT * FROM quotation_settings WHERE setting_type = 'general'";
            $generalSettings = $this->db->fetchAll($sql);
            
            if ($generalSettings) {
                foreach ($generalSettings as $setting) {
                    $key = $setting['setting_key'];
                    $value = (float)$setting['setting_value'];
                    
                    if (array_key_exists($key, $this->defaultPriceSettings)) {
                        $this->defaultPriceSettings[$key] = $value;
                    }
                }
            }
            
            // Buscar configurações de material
            $sql = "SELECT * FROM quotation_settings WHERE setting_type = 'material'";
            $materialSettings = $this->db->fetchAll($sql);
            
            if ($materialSettings) {
                foreach ($materialSettings as $setting) {
                    $materialId = $setting['setting_subtype'];
                    $key = $setting['setting_key'];
                    $value = (float)$setting['setting_value'];
                    
                    if (array_key_exists($materialId, $this->materialSettings) && 
                        array_key_exists($key, $this->materialSettings[$materialId])) {
                        $this->materialSettings[$materialId][$key] = $value;
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log('Erro ao carregar configurações de cotação: ' . $e->getMessage());
            // Continua com as configurações padrão
        }
    }
    
    /**
     * Gera uma cotação para um modelo específico
     * 
     * @param int $modelId ID do modelo 3D
     * @param string $material Material a ser usado (pla, abs, etc.)
     * @param string $printerType Tipo de impressora a ser usada
     * @param array $options Opções adicionais (preenchimento, suportes, acabamento)
     * @return array Cotação com detalhes de preço e estimativas
     */
    public function generateQuotation($modelId, $material = 'pla', $printerType = 'fdm_standard', $options = []) {
        // Opções padrão
        $defaultOptions = [
            'infill_percentage' => 20,      // Preenchimento em %
            'layer_height' => 0.2,          // Altura da camada em mm
            'supports_needed' => false,     // Necessidade de suportes
            'finishing_level' => 'standard', // Nível de acabamento (none, standard, premium)
            'quantity' => 1                  // Quantidade a ser impressa
        ];
        
        // Mesclar opções padrão com as fornecidas
        $options = array_merge($defaultOptions, $options);
        
        // Validar material e impressora
        if (!array_key_exists($material, $this->materialSettings)) {
            $material = 'pla'; // Fallback para PLA se material não existir
        }
        
        if (!array_key_exists($printerType, $this->printerSettings)) {
            $printerType = 'fdm_standard'; // Fallback para impressora padrão
        }
        
        // Obter dados do modelo
        $modelData = $this->getModelData($modelId);
        if (!$modelData) {
            return [
                'success' => false,
                'message' => 'Modelo não encontrado'
            ];
        }
        
        // Analisar complexidade do modelo
        $complexity = $this->analyzeModelComplexity($modelData);
        
        // Calcular volume e peso estimados
        $volumeEstimate = $this->calculateVolumeEstimate($modelData, $options['infill_percentage']);
        $weightEstimate = $this->calculateWeightEstimate($volumeEstimate, $material);
        
        // Verificar se o modelo é imprimível (tamanho)
        $printability = $this->checkPrintability($modelData, $printerType);
        if (!$printability['printable']) {
            return [
                'success' => false,
                'message' => $printability['message'],
                'model_data' => $modelData,
                'complexity' => $complexity
            ];
        }
        
        // Estimar tempo de impressão
        $printTimeEstimate = $this->estimatePrintTime(
            $volumeEstimate, 
            $options['layer_height'], 
            $complexity, 
            $material, 
            $printerType,
            $options['supports_needed']
        );
        
        // Calcular custos
        $materialCost = $this->calculateMaterialCost($weightEstimate, $material);
        $timeCost = $this->calculateTimeCost($printTimeEstimate, $printerType);
        $setupCost = $this->defaultPriceSettings['setup_cost'];
        
        // Custo de acabamento
        $finishingCost = $this->calculateFinishingCost($modelData, $options['finishing_level']);
        
        // Custo adicional para suportes, se necessário
        $supportsCost = $options['supports_needed'] ? 
            ($materialCost * 0.15) : 0; // Estimativa de 15% a mais de material para suportes
        
        // Cálculo do subtotal
        $subtotal = $materialCost + $timeCost + $setupCost + $finishingCost + $supportsCost;
        
        // Aplicar preço mínimo se necessário
        $subtotal = max($subtotal, $this->defaultPriceSettings['minimum_price']);
        
        // Aplicar margem de lucro
        $totalCost = $subtotal / (1 - $this->defaultPriceSettings['profit_margin']);
        
        // Multiplicar pelo número de unidades
        $totalCost *= $options['quantity'];
        $printTimeEstimate *= $options['quantity'];
        
        // Arredondar para 2 casas decimais
        $totalCost = round($totalCost, 2);
        
        // Retornar cotação detalhada
        return [
            'success' => true,
            'price' => $totalCost,
            'currency' => 'BRL',
            'model_id' => $modelId,
            'model_data' => $modelData,
            'material' => $this->materialSettings[$material],
            'printer' => $this->printerSettings[$printerType],
            'complexity' => $complexity,
            'volume_cm3' => round($volumeEstimate, 2),
            'weight_g' => round($weightEstimate, 2),
            'print_time_minutes' => round($printTimeEstimate),
            'print_time_formatted' => $this->formatPrintTime($printTimeEstimate),
            'costs' => [
                'material' => round($materialCost, 2),
                'time' => round($timeCost, 2),
                'setup' => round($setupCost, 2),
                'finishing' => round($finishingCost, 2),
                'supports' => round($supportsCost, 2),
                'subtotal' => round($subtotal, 2)
            ],
            'options' => $options,
            'printability' => $printability
        ];
    }
    
    /**
     * Obtém dados do modelo 3D a partir do banco de dados
     * 
     * @param int $modelId ID do modelo
     * @return array|null Dados do modelo ou null se não encontrado
     */
    private function getModelData($modelId) {
        try {
            $sql = "SELECT * FROM customer_models WHERE id = :id";
            $model = $this->db->fetchSingle($sql, [':id' => $modelId]);
            
            if (!$model) {
                return null;
            }
            
            // Extrair metadados do modelo
            $metadata = json_decode($model['validation_data'], true);
            
            // Adicionar metadados extraídos ao modelo
            $model['metadata'] = $metadata;
            
            return $model;
        } catch (Exception $e) {
            error_log('Erro ao obter dados do modelo para cotação: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Analisa a complexidade do modelo com base em metadados
     * 
     * @param array $modelData Dados do modelo
     * @return array Informações de complexidade
     */
    private function analyzeModelComplexity($modelData) {
        // Inicializar com complexidade média (caso faltem dados para análise)
        $complexityLevel = 'medium';
        $complexityMultiplier = $this->complexitySettings['complexity_multiplier_medium'];
        $triangleCount = 0;
        $vertexCount = 0;
        
        // Extrair contagem de triângulos/vértices do modelo se disponível
        if (isset($modelData['metadata']) && is_array($modelData['metadata'])) {
            $metadata = $modelData['metadata'];
            
            // Obter contagem de triângulos de diferentes formatos
            if (isset($metadata['data'])) {
                if (isset($metadata['data']['triangles'])) {
                    $triangleCount = $metadata['data']['triangles'];
                } else if (isset($metadata['data']['faces'])) {
                    $triangleCount = $metadata['data']['faces'];
                }
                
                if (isset($metadata['data']['vertices'])) {
                    $vertexCount = $metadata['data']['vertices'];
                }
            }
        }
        
        // Determinar nível de complexidade com base na contagem de triângulos
        if ($triangleCount > 0) {
            if ($triangleCount < $this->complexitySettings['triangle_threshold_low']) {
                $complexityLevel = 'low';
                $complexityMultiplier = $this->complexitySettings['complexity_multiplier_low'];
            } else if ($triangleCount < $this->complexitySettings['triangle_threshold_medium']) {
                $complexityLevel = 'medium';
                $complexityMultiplier = $this->complexitySettings['complexity_multiplier_medium'];
            } else if ($triangleCount < $this->complexitySettings['triangle_threshold_high']) {
                $complexityLevel = 'high';
                $complexityMultiplier = $this->complexitySettings['complexity_multiplier_high'];
            } else {
                $complexityLevel = 'very_high';
                $complexityMultiplier = $this->complexitySettings['complexity_multiplier_very_high'];
            }
        }
        
        // Analisar complexidade adicional (proporções, geometria, etc.)
        $additionalComplexity = $this->analyzeAdditionalComplexity($modelData);
        
        // Ajustar multiplicador com base em complexidade adicional
        $complexityMultiplier += $additionalComplexity['modifier'];
        
        return [
            'level' => $complexityLevel,
            'multiplier' => $complexityMultiplier,
            'triangle_count' => $triangleCount,
            'vertex_count' => $vertexCount,
            'additional_factors' => $additionalComplexity['factors']
        ];
    }
    
    /**
     * Analisa fatores adicionais de complexidade
     * 
     * @param array $modelData Dados do modelo
     * @return array Fatores adicionais de complexidade
     */
    private function analyzeAdditionalComplexity($modelData) {
        $factors = [];
        $modifier = 0.0;
        
        // Verificar se temos dados de dimensões
        if (isset($modelData['metadata']) && is_array($modelData['metadata'])) {
            $metadata = $modelData['metadata'];
            
            // Verificar proporções (modelos muito altos ou muito finos são mais complexos)
            if (isset($metadata['data'])) {
                $data = $metadata['data'];
                
                if (isset($data['width']) && isset($data['height']) && isset($data['depth'])) {
                    $width = $data['width'];
                    $height = $data['height'];
                    $depth = $data['depth'];
                    
                    // Calcular proporção altura/base
                    $baseSize = max($width, $depth);
                    if ($baseSize > 0) {
                        $heightRatio = $height / $baseSize;
                        
                        if ($heightRatio > 3) {
                            $factors[] = 'high_height_ratio';
                            $modifier += 0.1;
                        }
                    }
                    
                    // Verificar peças finas que podem ser frágeis
                    $minDimension = min($width, $height, $depth);
                    $maxDimension = max($width, $height, $depth);
                    
                    if ($minDimension < 2 && $maxDimension > 20) {
                        $factors[] = 'thin_features';
                        $modifier += 0.15;
                    }
                }
            }
        }
        
        // Verificar extensão do arquivo (alguns formatos são mais complexos)
        if (isset($modelData['file_type'])) {
            $fileType = strtolower($modelData['file_type']);
            
            if ($fileType === '3mf') {
                $factors[] = 'complex_file_format';
                $modifier += 0.05;
            }
        }
        
        return [
            'factors' => $factors,
            'modifier' => $modifier
        ];
    }
    
    /**
     * Calcula uma estimativa de volume em cm³ do modelo
     * 
     * @param array $modelData Dados do modelo
     * @param int $infillPercentage Porcentagem de preenchimento
     * @return float Volume estimado em cm³
     */
    private function calculateVolumeEstimate($modelData, $infillPercentage = 20) {
        $volume = 0;
        
        // Extrair dimensões do modelo se disponíveis
        if (isset($modelData['metadata']) && is_array($modelData['metadata'])) {
            $metadata = $modelData['metadata'];
            
            if (isset($metadata['data'])) {
                $data = $metadata['data'];
                
                if (isset($data['width']) && isset($data['height']) && isset($data['depth'])) {
                    // Converter de mm para cm
                    $width = $data['width'] / 10;
                    $height = $data['height'] / 10;
                    $depth = $data['depth'] / 10;
                    
                    // Calcular volume bruto (cm³)
                    $rawVolume = $width * $height * $depth;
                    
                    // Estimar volume real com base na complexidade
                    // A maioria dos modelos 3D não preenche totalmente o seu envelope
                    $complexity = $this->analyzeModelComplexity($modelData);
                    $fillFactor = 0.3; // Fator de preenchimento padrão (30% do envelope)
                    
                    // Ajustar com base na complexidade
                    switch ($complexity['level']) {
                        case 'low':
                            $fillFactor = 0.25;
                            break;
                        case 'medium':
                            $fillFactor = 0.3;
                            break;
                        case 'high':
                            $fillFactor = 0.4;
                            break;
                        case 'very_high':
                            $fillFactor = 0.5;
                            break;
                    }
                    
                    // Calcular volume da casca (shell)
                    $volume = $rawVolume * $fillFactor;
                    
                    // Ajustar com base na porcentagem de preenchimento interno
                    // O preenchimento interno afeta o volume final de material usado
                    $infillFactor = $infillPercentage / 100;
                    $shellVolume = $volume * 0.3; // Aproximadamente 30% do volume é a casca externa
                    $infillVolume = ($volume - $shellVolume) * $infillFactor;
                    
                    // Volume total (casca + preenchimento)
                    $volume = $shellVolume + $infillVolume;
                }
            }
        }
        
        // Se não conseguimos calcular pelo método principal, tentar estimar pelo arquivo
        if ($volume <= 0 && isset($modelData['file_size'])) {
            // Método alternativo baseado no tamanho do arquivo
            // Este é um método muito aproximado e deve ser usado apenas como fallback
            $fileSize = intval($modelData['file_size']);
            
            // Estimativa grosseira: cada 1KB de arquivo STL representa aproximadamente 0.1cm³
            // Ajustado pelo tipo de arquivo
            $volumeEstimateFactor = 0.0001;
            
            if (isset($modelData['file_type'])) {
                switch (strtolower($modelData['file_type'])) {
                    case 'stl':
                        $volumeEstimateFactor = 0.0001;
                        break;
                    case 'obj':
                        $volumeEstimateFactor = 0.00008;
                        break;
                    case '3mf':
                        $volumeEstimateFactor = 0.00015;
                        break;
                }
            }
            
            $volume = $fileSize * $volumeEstimateFactor * ($infillPercentage / 20);
        }
        
        // Garantir um valor mínimo razoável
        return max($volume, 1.0);
    }
    
    /**
     * Calcula uma estimativa de peso em gramas do modelo
     * 
     * @param float $volumeCm3 Volume em cm³
     * @param string $material Tipo de material
     * @return float Peso estimado em gramas
     */
    private function calculateWeightEstimate($volumeCm3, $material = 'pla') {
        // Obter densidade do material
        $density = $this->materialSettings[$material]['density'];
        
        // Calcular peso (Volume * Densidade)
        $weight = $volumeCm3 * $density;
        
        return $weight;
    }
    
    /**
     * Verifica se o modelo é imprimível com base em suas dimensões e na impressora
     * 
     * @param array $modelData Dados do modelo
     * @param string $printerType Tipo de impressora
     * @return array Resultado da verificação
     */
    private function checkPrintability($modelData, $printerType = 'fdm_standard') {
        $result = [
            'printable' => true,
            'message' => 'Modelo adequado para impressão',
            'warnings' => []
        ];
        
        // Obter dimensões da impressora
        $printer = $this->printerSettings[$printerType];
        $printerX = $printer['volume_x'];
        $printerY = $printer['volume_y'];
        $printerZ = $printer['volume_z'];
        
        // Extrair dimensões do modelo se disponíveis
        if (isset($modelData['metadata']) && is_array($modelData['metadata'])) {
            $metadata = $modelData['metadata'];
            
            if (isset($metadata['data'])) {
                $data = $metadata['data'];
                
                if (isset($data['width']) && isset($data['height']) && isset($data['depth'])) {
                    $modelWidth = $data['width'];
                    $modelHeight = $data['height'];
                    $modelDepth = $data['depth'];
                    
                    // Verificar se o modelo cabe na impressora
                    if ($modelWidth > $printerX || $modelDepth > $printerY || $modelHeight > $printerZ) {
                        // Verificar se o modelo pode ser reorientado para caber
                        $dimensions = [$modelWidth, $modelHeight, $modelDepth];
                        sort($dimensions);
                        
                        $printerDimensions = [$printerX, $printerY, $printerZ];
                        sort($printerDimensions);
                        
                        if ($dimensions[0] <= $printerDimensions[0] && 
                            $dimensions[1] <= $printerDimensions[1] && 
                            $dimensions[2] <= $printerDimensions[2]) {
                            
                            $result['printable'] = true;
                            $result['warnings'][] = 'O modelo precisará ser reorientado para caber na impressora.';
                        } else {
                            $result['printable'] = false;
                            $result['message'] = 'O modelo é grande demais para a impressora selecionada.';
                        }
                    }
                    
                    // Verificar dimensões mínimas
                    if (min($modelWidth, $modelHeight, $modelDepth) < 1.0) {
                        $result['warnings'][] = 'O modelo tem elementos muito finos que podem não ser impressos corretamente.';
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Estima o tempo de impressão em minutos
     * 
     * @param float $volumeCm3 Volume em cm³
     * @param float $layerHeight Altura da camada em mm
     * @param array $complexity Informações de complexidade
     * @param string $material Tipo de material
     * @param string $printerType Tipo de impressora
     * @param bool $supportsNeeded Se há necessidade de suportes
     * @return float Tempo estimado em minutos
     */
    private function estimatePrintTime($volumeCm3, $layerHeight, $complexity, $material, $printerType, $supportsNeeded) {
        // Obter configurações da impressora
        $printer = $this->printerSettings[$printerType];
        $baseSpeed = $printer['base_print_speed'];
        
        // Obter multiplicador de velocidade do material
        $materialSpeedMultiplier = $this->materialSettings[$material]['print_speed_multiplier'];
        
        // Fator de tempo base (minutos por cm³)
        // Este é um valor empírico que relaciona volume com tempo de impressão
        $baseTimeFactor = 1.2;
        
        // Ajuste para altura da camada (camadas mais finas levam mais tempo)
        $layerHeightFactor = 0.2 / $layerHeight;
        
        // Ajuste para complexidade
        $complexityFactor = $complexity['multiplier'];
        
        // Calcular tempo base
        $baseTime = $volumeCm3 * $baseTimeFactor;
        
        // Aplicar fatores de ajuste
        $adjustedTime = $baseTime * $layerHeightFactor * $complexityFactor;
        
        // Ajustar pelo material e velocidade da impressora
        $adjustedTime = $adjustedTime / $materialSpeedMultiplier;
        $adjustedTime = $adjustedTime * (60 / $baseSpeed);
        
        // Adicionar tempo extra para suportes, se necessário
        if ($supportsNeeded) {
            $adjustedTime *= 1.25; // 25% a mais de tempo para suportes
        }
        
        // Adicionar tempo fixo para aquecimento e inicialização (10 minutos)
        $finalTime = $adjustedTime + 10;
        
        return $finalTime;
    }
    
    /**
     * Calcula o custo do material
     * 
     * @param float $weightGrams Peso estimado em gramas
     * @param string $material Tipo de material
     * @return float Custo do material
     */
    private function calculateMaterialCost($weightGrams, $material) {
        // Obter custo por grama do material
        $costPerGram = $this->materialSettings[$material]['cost_per_g'];
        
        // Calcular custo do material
        $materialCost = $weightGrams * $costPerGram;
        
        return $materialCost;
    }
    
    /**
     * Calcula o custo baseado no tempo de impressão
     * 
     * @param float $printTimeMinutes Tempo estimado em minutos
     * @param string $printerType Tipo de impressora
     * @return float Custo baseado no tempo
     */
    private function calculateTimeCost($printTimeMinutes, $printerType) {
        // Obter custo por hora da impressora
        $costPerHour = $this->printerSettings[$printerType]['cost_per_hour'];
        
        // Converter minutos para horas
        $printTimeHours = $printTimeMinutes / 60;
        
        // Calcular custo do tempo
        $timeCost = $printTimeHours * $costPerHour;
        
        return $timeCost;
    }
    
    /**
     * Calcula o custo de acabamento
     * 
     * @param array $modelData Dados do modelo
     * @param string $finishingLevel Nível de acabamento (none, standard, premium)
     * @return float Custo de acabamento
     */
    private function calculateFinishingCost($modelData, $finishingLevel = 'standard') {
        if ($finishingLevel === 'none') {
            return 0;
        }
        
        // Estimar área de superfície (cm²)
        $surfaceArea = $this->estimateSurfaceArea($modelData);
        
        // Custo base por cm²
        $costPerCm2 = $this->defaultPriceSettings['finishing_cost_per_cm2'];
        
        // Ajustar pelo nível de acabamento
        switch ($finishingLevel) {
            case 'standard':
                $costMultiplier = 1.0;
                break;
            case 'premium':
                $costMultiplier = 2.0;
                break;
            default:
                $costMultiplier = 0;
        }
        
        // Calcular custo de acabamento
        $finishingCost = $surfaceArea * $costPerCm2 * $costMultiplier;
        
        return $finishingCost;
    }
    
    /**
     * Estima a área de superfície do modelo
     * 
     * @param array $modelData Dados do modelo
     * @return float Área de superfície estimada em cm²
     */
    private function estimateSurfaceArea($modelData) {
        // Método de estimativa:
        // 1. Se contagem de triângulos estiver disponível, usamos ela
        // 2. Se temos dimensões, usamos um cálculo aproximado
        
        $surfaceArea = 0;
        
        // Método 1: Baseado na contagem de triângulos
        if (isset($modelData['metadata']) && is_array($modelData['metadata'])) {
            $metadata = $modelData['metadata'];
            
            if (isset($metadata['data'])) {
                $data = $metadata['data'];
                
                // Usar contagem de triângulos se disponível
                if (isset($data['triangles']) && $data['triangles'] > 0) {
                    // Estimativa: cada triângulo representa aproximadamente 0.1cm² (valor empírico)
                    $triangleCount = $data['triangles'];
                    $surfaceArea = $triangleCount * 0.1;
                    
                    // Ajustar para um intervalo razoável
                    return max(min($surfaceArea, 2000), 10);
                }
            }
        }
        
        // Método 2: Baseado nas dimensões
        if (isset($modelData['metadata']) && is_array($modelData['metadata'])) {
            $metadata = $modelData['metadata'];
            
            if (isset($metadata['data'])) {
                $data = $metadata['data'];
                
                if (isset($data['width']) && isset($data['height']) && isset($data['depth'])) {
                    // Converter de mm para cm
                    $width = $data['width'] / 10;
                    $height = $data['height'] / 10;
                    $depth = $data['depth'] / 10;
                    
                    // Estimativa utilizando a fórmula de área de superfície de um retângulo
                    // A = 2(wh + hd + wd)
                    $boxSurfaceArea = 2 * (($width * $height) + ($height * $depth) + ($width * $depth));
                    
                    // Ajustar baseado na complexidade (objetos complexos têm mais área que um retângulo)
                    $complexity = $this->analyzeModelComplexity($modelData);
                    $complexityFactor = 1.0;
                    
                    switch ($complexity['level']) {
                        case 'low':
                            $complexityFactor = 1.0;
                            break;
                        case 'medium':
                            $complexityFactor = 1.3;
                            break;
                        case 'high':
                            $complexityFactor = 1.6;
                            break;
                        case 'very_high':
                            $complexityFactor = 2.0;
                            break;
                    }
                    
                    $surfaceArea = $boxSurfaceArea * $complexityFactor;
                }
            }
        }
        
        // Método 3: Fallback, baseado no volume
        if ($surfaceArea <= 0) {
            // Estimativa grosseira baseada no volume
            $volume = $this->calculateVolumeEstimate($modelData);
            $surfaceArea = pow($volume, 2/3) * 6; // Aproximação do cubo
        }
        
        // Limitar a um intervalo razoável
        return max(min($surfaceArea, 2000), 10);
    }
    
    /**
     * Formata o tempo de impressão para exibição
     * 
     * @param float $minutes Tempo em minutos
     * @return string Tempo formatado (ex: "2h 30min")
     */
    private function formatPrintTime($minutes) {
        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);
        
        if ($hours > 0) {
            return $hours . 'h ' . $mins . 'min';
        } else {
            return $mins . 'min';
        }
    }
    
    /**
     * Salva uma cotação gerada
     * 
     * @param int $modelId ID do modelo
     * @param int $userId ID do usuário
     * @param array $quotationData Dados da cotação
     * @return int|bool ID da cotação ou false em caso de erro
     */
    public function saveQuotation($modelId, $userId, $quotationData) {
        $sql = "INSERT INTO model_quotations (model_id, user_id, price, material, printer_type, 
                options, complexity_data, volume, weight, print_time, created_at) 
                VALUES (:model_id, :user_id, :price, :material, :printer_type, 
                :options, :complexity_data, :volume, :weight, :print_time, NOW())";
        
        $params = [
            ':model_id' => $modelId,
            ':user_id' => $userId,
            ':price' => $quotationData['price'],
            ':material' => $quotationData['material']['name'],
            ':printer_type' => $quotationData['printer']['name'],
            ':options' => json_encode($quotationData['options']),
            ':complexity_data' => json_encode($quotationData['complexity']),
            ':volume' => $quotationData['volume_cm3'],
            ':weight' => $quotationData['weight_g'],
            ':print_time' => $quotationData['print_time_minutes']
        ];
        
        try {
            $this->db->execute($sql, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Erro ao salvar cotação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém cotações salvas para um modelo
     * 
     * @param int $modelId ID do modelo
     * @return array Lista de cotações
     */
    public function getQuotationsForModel($modelId) {
        $sql = "SELECT * FROM model_quotations WHERE model_id = :model_id ORDER BY created_at DESC";
        
        try {
            $quotations = $this->db->fetchAll($sql, [':model_id' => $modelId]);
            
            // Deserializar JSON para cada cotação
            foreach ($quotations as &$quotation) {
                if (isset($quotation['options']) && !empty($quotation['options'])) {
                    $quotation['options'] = json_decode($quotation['options'], true);
                }
                
                if (isset($quotation['complexity_data']) && !empty($quotation['complexity_data'])) {
                    $quotation['complexity_data'] = json_decode($quotation['complexity_data'], true);
                }
                
                // Adicionar tempo formatado
                $quotation['print_time_formatted'] = $this->formatPrintTime($quotation['print_time']);
            }
            
            return $quotations;
        } catch (Exception $e) {
            error_log('Erro ao obter cotações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Atualiza as configurações de preço
     * 
     * @param array $settings Novas configurações
     * @return bool True se atualizado com sucesso, false caso contrário
     */
    public function updatePriceSettings($settings) {
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            // Atualizar configurações gerais
            if (isset($settings['general']) && is_array($settings['general'])) {
                foreach ($settings['general'] as $key => $value) {
                    $sql = "INSERT INTO quotation_settings (setting_type, setting_key, setting_value) 
                            VALUES ('general', :key, :value)
                            ON DUPLICATE KEY UPDATE setting_value = :value";
                    
                    $this->db->execute($sql, [':key' => $key, ':value' => $value]);
                }
            }
            
            // Atualizar configurações de material
            if (isset($settings['materials']) && is_array($settings['materials'])) {
                foreach ($settings['materials'] as $materialId => $materialSettings) {
                    foreach ($materialSettings as $key => $value) {
                        $sql = "INSERT INTO quotation_settings (setting_type, setting_subtype, setting_key, setting_value) 
                                VALUES ('material', :material_id, :key, :value)
                                ON DUPLICATE KEY UPDATE setting_value = :value";
                        
                        $this->db->execute($sql, [
                            ':material_id' => $materialId,
                            ':key' => $key,
                            ':value' => $value
                        ]);
                    }
                }
            }
            
            // Commit da transação
            $this->db->commit();
            
            // Recarregar configurações
            $this->loadSettings();
            
            return true;
        } catch (Exception $e) {
            // Rollback em caso de erro
            $this->db->rollback();
            error_log('Erro ao atualizar configurações de preço: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todas as configurações de preço
     * 
     * @return array Configurações de preço
     */
    public function getAllSettings() {
        return [
            'general' => $this->defaultPriceSettings,
            'materials' => $this->materialSettings,
            'complexity' => $this->complexitySettings,
            'printers' => $this->printerSettings
        ];
    }
    
    /**
     * Obtém configurações de um material específico
     * 
     * @param string $materialId ID do material
     * @return array|null Configurações do material ou null se não encontrado
     */
    public function getMaterialSettings($materialId) {
        if (array_key_exists($materialId, $this->materialSettings)) {
            return $this->materialSettings[$materialId];
        }
        
        return null;
    }
    
    /**
     * Obtém configurações de uma impressora específica
     * 
     * @param string $printerId ID da impressora
     * @return array|null Configurações da impressora ou null se não encontrada
     */
    public function getPrinterSettings($printerId) {
        if (array_key_exists($printerId, $this->printerSettings)) {
            return $this->printerSettings[$printerId];
        }
        
        return null;
    }
}
