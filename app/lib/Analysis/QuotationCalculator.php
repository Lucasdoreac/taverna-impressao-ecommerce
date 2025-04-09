<?php
/**
 * QuotationCalculator
 * 
 * Classe responsável por calcular cotações automatizadas para impressão 3D
 * com base na complexidade do modelo, material escolhido, configurações de impressão
 * e outros fatores relevantes para determinar custos e tempos de entrega.
 * 
 * Esta classe segue os princípios de segurança da Taverna, incluindo validação
 * robusta de entradas e sanitização adequada.
 * 
 * @package App\Lib\Analysis
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */
class QuotationCalculator {
    /**
     * Instância do analisador de complexidade
     * 
     * @var ModelComplexityAnalyzer
     */
    private $complexityAnalyzer;
    
    /**
     * Configurações de materiais disponíveis
     * 
     * @var array
     */
    private $materials;
    
    /**
     * Configurações de qualidade de impressão
     * 
     * @var array
     */
    private $qualitySettings;
    
    /**
     * Fatores de custo adicional
     * 
     * @var array
     */
    private $additionalCostFactors;
    
    /**
     * Opções de prazos de entrega
     * 
     * @var array
     */
    private $deliveryOptions;
    
    /**
     * Taxa básica de lucro
     * 
     * @var float
     */
    private $baseMarginRate;
    
    /**
     * Parâmetros da última cotação
     * 
     * @var array
     */
    private $lastQuotationParams;
    
    /**
     * Resultado da última cotação
     * 
     * @var array
     */
    private $lastQuotationResult;
    
    /**
     * Construtor
     * 
     * @param ModelComplexityAnalyzer|null $complexityAnalyzer Instância do analisador de complexidade
     * @param array $configuration Configuração personalizada para o calculador
     */
    public function __construct(?ModelComplexityAnalyzer $complexityAnalyzer = null, array $configuration = []) {
        // Configurar o analisador de complexidade
        $this->complexityAnalyzer = $complexityAnalyzer ?? new ModelComplexityAnalyzer();
        
        // Configurar materiais disponíveis
        $this->materials = $configuration['materials'] ?? $this->getDefaultMaterials();
        
        // Configurar configurações de qualidade
        $this->qualitySettings = $configuration['qualitySettings'] ?? $this->getDefaultQualitySettings();
        
        // Configurar fatores de custo adicional
        $this->additionalCostFactors = $configuration['additionalCostFactors'] ?? $this->getDefaultAdditionalCostFactors();
        
        // Configurar opções de entrega
        $this->deliveryOptions = $configuration['deliveryOptions'] ?? $this->getDefaultDeliveryOptions();
        
        // Configurar taxa de lucro
        $this->baseMarginRate = $configuration['baseMarginRate'] ?? 0.35; // 35% de margem padrão
        
        // Inicializar variáveis de cotação
        $this->lastQuotationParams = null;
        $this->lastQuotationResult = null;
    }
    
    /**
     * Calcula uma cotação com base nos parâmetros fornecidos
     * 
     * @param array $params Parâmetros da cotação (modelo, material, qualidade, etc)
     * @return array Cotação calculada
     * @throws Exception Se ocorrer um erro durante o cálculo
     */
    public function calculateQuotation(array $params): array {
        // Validar parâmetros
        $this->validateQuotationParams($params);
        
        // Armazenar parâmetros
        $this->lastQuotationParams = $params;
        
        // Inicializar cotação
        $quotation = [
            'timestamp' => time(),
            'quotation_id' => $this->generateQuotationId(),
            'model_info' => [],
            'material_info' => [],
            'quality_info' => [],
            'print_info' => [],
            'costs' => [],
            'delivery_options' => [],
            'final_price' => 0,
            'complexity_score' => 0,
            'estimated_print_time' => 0,
            'valid_until' => time() + (7 * 24 * 60 * 60) // Validade de 7 dias
        ];
        
        try {
            // Analisar o modelo
            $analysisResults = null;
            
            // Verificar se resultados foram fornecidos direto nos parâmetros
            if (isset($params['analysis_results']) && is_array($params['analysis_results'])) {
                $analysisResults = $params['analysis_results'];
            } 
            // Caso contrário, fazer a análise
            else {
                // Carregar o modelo
                $this->complexityAnalyzer->loadModel([
                    'file_path' => $params['model']['file_path'],
                    'file_type' => $params['model']['file_type'],
                    'metadata' => $params['model']['metadata'] ?? []
                ]);
                
                // Analisar complexidade
                $analysisResults = $this->complexityAnalyzer->analyzeComplexity();
            }
            
            // Obter informações do material selecionado
            $materialInfo = $this->getMaterialInfo($params['material_id']);
            
            // Obter configurações de qualidade
            $qualityInfo = $this->getQualityInfo($params['quality_id']);
            
            // Calcular custos base
            $baseMaterialCost = $this->calculateMaterialCost($analysisResults, $materialInfo);
            $basePrintingCost = $this->calculatePrintingCost($analysisResults, $materialInfo, $qualityInfo);
            
            // Calcular custos adicionais
            $additionalCosts = $this->calculateAdditionalCosts($params, $analysisResults);
            
            // Calcular tempo de impressão ajustado
            $printTime = $this->calculateAdjustedPrintTime($analysisResults, $qualityInfo);
            
            // Calcular opções de entrega
            $deliveryOptions = $this->calculateDeliveryOptions($printTime);
            
            // Calcular preço final com margem
            $totalCost = $baseMaterialCost + $basePrintingCost + $additionalCosts['total'];
            $finalPrice = $this->applyMargin($totalCost);
            
            // Preencher cotação
            $quotation['model_info'] = [
                'name' => $params['model']['name'] ?? 'Modelo Sem Nome',
                'file_type' => $params['model']['file_type'],
                'dimensions' => $analysisResults['metrics']['dimensions'],
                'volume' => $analysisResults['metrics']['volume'],
                'surface_area' => $analysisResults['metrics']['surface_area'],
                'polygon_count' => $analysisResults['metrics']['polygon_count']
            ];
            
            $quotation['material_info'] = $materialInfo;
            $quotation['quality_info'] = $qualityInfo;
            
            $quotation['print_info'] = [
                'estimated_print_time_hours' => round($printTime / 60, 1),
                'estimated_weight' => round($analysisResults['metrics']['volume'] * $materialInfo['density'] / 1000, 2),
                'infill_percentage' => $params['infill_percentage'] ?? $qualityInfo['default_infill'],
                'layer_height' => $qualityInfo['layer_height'],
                'supports_required' => $analysisResults['metrics']['overhangs'] > 0.3
            ];
            
            $quotation['costs'] = [
                'material' => round($baseMaterialCost, 2),
                'printing' => round($basePrintingCost, 2),
                'additional' => $additionalCosts['breakdown'],
                'total_cost' => round($totalCost, 2)
            ];
            
            $quotation['delivery_options'] = $deliveryOptions;
            $quotation['final_price'] = round($finalPrice, 2);
            $quotation['complexity_score'] = round($analysisResults['complexity_score'], 1);
            $quotation['estimated_print_time'] = $printTime;
            
            // Armazenar resultado
            $this->lastQuotationResult = $quotation;
            
            return $quotation;
        } catch (Exception $e) {
            // Registrar erro e propagá-lo
            error_log("Erro ao calcular cotação: " . $e->getMessage());
            throw new Exception("Falha ao calcular cotação: " . $e->getMessage());
        }
    }
    
    /**
     * Valida os parâmetros da cotação
     * 
     * @param array $params Parâmetros a serem validados
     * @return void
     * @throws Exception Se os parâmetros forem inválidos
     */
    private function validateQuotationParams(array $params): void {
        // Validar modelo
        if (!isset($params['model']) || !is_array($params['model'])) {
            throw new Exception('Parâmetro do modelo inválido ou ausente');
        }
        
        // Se fornecido um caminho de arquivo, verificar se existe
        if (isset($params['model']['file_path']) && !file_exists($params['model']['file_path'])) {
            throw new Exception('Arquivo do modelo não encontrado');
        }
        
        // Se não houver caminho de arquivo, verificar se resultados de análise foram fornecidos
        if (!isset($params['model']['file_path']) && !isset($params['analysis_results'])) {
            throw new Exception('É necessário fornecer um arquivo de modelo ou resultados de análise');
        }
        
        // Validar tipo de arquivo se fornecido
        if (isset($params['model']['file_type'])) {
            $validTypes = ['stl', 'obj', '3mf', 'gcode'];
            if (!in_array(strtolower($params['model']['file_type']), $validTypes)) {
                throw new Exception('Tipo de arquivo do modelo não suportado');
            }
        } else if (isset($params['model']['file_path'])) {
            // Tentar extrair do nome do arquivo
            $extension = pathinfo($params['model']['file_path'], PATHINFO_EXTENSION);
            if (empty($extension)) {
                throw new Exception('Tipo de arquivo do modelo não especificado');
            }
            $params['model']['file_type'] = strtolower($extension);
        }
        
        // Validar ID do material
        if (!isset($params['material_id']) || !is_string($params['material_id'])) {
            throw new Exception('ID do material inválido ou ausente');
        }
        
        // Verificar se o material existe
        if (!$this->materialExists($params['material_id'])) {
            throw new Exception('Material especificado não encontrado');
        }
        
        // Validar ID da qualidade
        if (!isset($params['quality_id']) || !is_string($params['quality_id'])) {
            throw new Exception('ID da qualidade inválido ou ausente');
        }
        
        // Verificar se a qualidade existe
        if (!$this->qualityExists($params['quality_id'])) {
            throw new Exception('Configuração de qualidade não encontrada');
        }
        
        // Validar percentage de preenchimento (infill) se fornecido
        if (isset($params['infill_percentage'])) {
            $infill = (int)$params['infill_percentage'];
            if ($infill < 0 || $infill > 100) {
                throw new Exception('Porcentagem de preenchimento deve estar entre 0 e 100');
            }
        }
        
        // Validar opções adicionais
        if (isset($params['additional_options']) && is_array($params['additional_options'])) {
            foreach ($params['additional_options'] as $option) {
                if (!isset($option['id']) || !isset($option['value'])) {
                    throw new Exception('Formato de opção adicional inválido');
                }
                
                if (!$this->additionalOptionExists($option['id'])) {
                    throw new Exception('Opção adicional não reconhecida: ' . $option['id']);
                }
            }
        }
    }
    
    /**
     * Verifica se um material existe na lista de materiais disponíveis
     * 
     * @param string $materialId ID do material
     * @return bool True se o material existe
     */
    private function materialExists(string $materialId): bool {
        foreach ($this->materials as $material) {
            if ($material['id'] === $materialId) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Verifica se uma configuração de qualidade existe
     * 
     * @param string $qualityId ID da configuração de qualidade
     * @return bool True se a configuração existe
     */
    private function qualityExists(string $qualityId): bool {
        foreach ($this->qualitySettings as $quality) {
            if ($quality['id'] === $qualityId) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Verifica se uma opção adicional existe
     * 
     * @param string $optionId ID da opção adicional
     * @return bool True se a opção existe
     */
    private function additionalOptionExists(string $optionId): bool {
        foreach ($this->additionalCostFactors as $section) {
            if (isset($section['options'])) {
                foreach ($section['options'] as $option) {
                    if ($option['id'] === $optionId) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Gera um ID único para a cotação
     * 
     * @return string ID da cotação
     */
    private function generateQuotationId(): string {
        // Formato: Q-ANO-MES-DIA-RANDOMHEX
        $date = date('Y-m-d');
        $random = bin2hex(random_bytes(4));
        return "Q-{$date}-{$random}";
    }
    
    /**
     * Obtém informações detalhadas de um material
     * 
     * @param string $materialId ID do material
     * @return array Informações do material
     * @throws Exception Se o material não for encontrado
     */
    private function getMaterialInfo(string $materialId): array {
        foreach ($this->materials as $material) {
            if ($material['id'] === $materialId) {
                return $material;
            }
        }
        throw new Exception("Material não encontrado: {$materialId}");
    }
    
    /**
     * Obtém informações detalhadas de uma configuração de qualidade
     * 
     * @param string $qualityId ID da configuração de qualidade
     * @return array Informações da configuração de qualidade
     * @throws Exception Se a configuração não for encontrada
     */
    private function getQualityInfo(string $qualityId): array {
        foreach ($this->qualitySettings as $quality) {
            if ($quality['id'] === $qualityId) {
                return $quality;
            }
        }
        throw new Exception("Configuração de qualidade não encontrada: {$qualityId}");
    }
    
    /**
     * Calcula o custo de material para a impressão
     * 
     * @param array $analysisResults Resultados da análise do modelo
     * @param array $materialInfo Informações do material
     * @return float Custo do material em reais
     */
    private function calculateMaterialCost(array $analysisResults, array $materialInfo): float {
        // Volume em mm³
        $volume = $analysisResults['metrics']['volume'];
        
        // Densidade em g/cm³
        $density = $materialInfo['density'];
        
        // Preço por kg
        $pricePerKg = $materialInfo['price_per_kg'];
        
        // Calcular peso em gramas
        $weightGrams = $volume * $density / 1000; // cm³ = mm³ / 1000
        
        // Calcular custo
        $cost = ($weightGrams / 1000) * $pricePerKg;
        
        // Aplicar fator de desperdício (material para suportes, falhas, etc.)
        $wasteFactor = 1.15; // 15% a mais para desperdício
        
        // Calcular custo final com desperdício
        $finalCost = $cost * $wasteFactor;
        
        // Custo mínimo de material (R$ 1,00)
        return max(1.0, $finalCost);
    }
    
    /**
     * Calcula o custo de impressão
     * 
     * @param array $analysisResults Resultados da análise do modelo
     * @param array $materialInfo Informações do material
     * @param array $qualityInfo Informações da configuração de qualidade
     * @return float Custo de impressão em reais
     */
    private function calculatePrintingCost(array $analysisResults, array $materialInfo, array $qualityInfo): float {
        // Tempo estimado de impressão em minutos
        $printTimeMinutes = $analysisResults['estimated_print_time_minutes'];
        
        // Ajustar tempo com base na configuração de qualidade
        $adjustedTime = $printTimeMinutes * $qualityInfo['time_factor'];
        
        // Custo por hora de impressão
        $costPerHour = $materialInfo['printer_cost_per_hour'];
        
        // Calcular custo
        $cost = ($adjustedTime / 60) * $costPerHour;
        
        // Adicionar custo fixo de setup da impressora
        $setupCost = 5.0; // R$ 5,00 de setup
        
        // Calcular custo final
        $finalCost = $cost + $setupCost;
        
        // Custo mínimo de impressão (R$ 5,00)
        return max(5.0, $finalCost);
    }
    
    /**
     * Calcula custos adicionais com base nas opções selecionadas
     * 
     * @param array $params Parâmetros da cotação
     * @param array $analysisResults Resultados da análise do modelo
     * @return array Custos adicionais calculados
     */
    private function calculateAdditionalCosts(array $params, array $analysisResults): array {
        $totalAdditionalCost = 0;
        $costBreakdown = [];
        
        // Processar opções adicionais selecionadas
        if (isset($params['additional_options']) && is_array($params['additional_options'])) {
            foreach ($params['additional_options'] as $option) {
                $optionInfo = $this->getAdditionalOptionInfo($option['id']);
                
                if ($optionInfo) {
                    // Calcular custo baseado no tipo de cálculo
                    $optionCost = 0;
                    
                    switch ($optionInfo['cost_type']) {
                        case 'fixed':
                            $optionCost = $optionInfo['cost_value'];
                            break;
                            
                        case 'percentage':
                            // Percentual do custo base (material + impressão)
                            $baseCost = $analysisResults['material_cost'] + $analysisResults['printing_cost'];
                            $optionCost = $baseCost * ($optionInfo['cost_value'] / 100);
                            break;
                            
                        case 'per_volume':
                            // Custo por cm³
                            $volumeCm3 = $analysisResults['metrics']['volume'] / 1000;
                            $optionCost = $volumeCm3 * $optionInfo['cost_value'];
                            break;
                            
                        case 'per_time':
                            // Custo por hora
                            $printTimeHours = $analysisResults['estimated_print_time_minutes'] / 60;
                            $optionCost = $printTimeHours * $optionInfo['cost_value'];
                            break;
                            
                        case 'variable':
                            // Custo variável baseado no valor da opção
                            $optionValue = (float)$option['value'];
                            $optionCost = $optionValue * $optionInfo['cost_value'];
                            break;
                    }
                    
                    // Adicionar ao total
                    $totalAdditionalCost += $optionCost;
                    
                    // Adicionar ao detalhamento
                    $costBreakdown[$option['id']] = [
                        'name' => $optionInfo['name'],
                        'value' => $option['value'],
                        'cost' => round($optionCost, 2)
                    ];
                }
            }
        }
        
        // Processar custos automáticos baseados em características do modelo
        
        // Suportes adicionais para balanços
        if ($analysisResults['metrics']['overhangs'] > 0.3) {
            $supportCost = $analysisResults['printing_cost'] * 0.15; // 15% a mais
            $totalAdditionalCost += $supportCost;
            $costBreakdown['auto_supports'] = [
                'name' => 'Suportes Adicionais',
                'value' => 'Auto',
                'cost' => round($supportCost, 2)
            ];
        }
        
        // Processamento adicional para modelos muito complexos
        if ($analysisResults['complexity_score'] > 70) {
            $complexityCost = 10.0; // Taxa fixa para complexidade alta
            $totalAdditionalCost += $complexityCost;
            $costBreakdown['complexity_processing'] = [
                'name' => 'Processamento de Complexidade',
                'value' => 'Auto',
                'cost' => round($complexityCost, 2)
            ];
        }
        
        return [
            'total' => $totalAdditionalCost,
            'breakdown' => $costBreakdown
        ];
    }
    
    /**
     * Calcula o tempo de impressão ajustado com base nas configurações de qualidade
     * 
     * @param array $analysisResults Resultados da análise do modelo
     * @param array $qualityInfo Informações da configuração de qualidade
     * @return int Tempo de impressão estimado em minutos
     */
    private function calculateAdjustedPrintTime(array $analysisResults, array $qualityInfo): int {
        // Tempo base em minutos
        $baseTime = $analysisResults['estimated_print_time_minutes'];
        
        // Aplicar fator de tempo da configuração de qualidade
        $adjustedTime = $baseTime * $qualityInfo['time_factor'];
        
        // Tempo mínimo de impressão (15 minutos)
        return max(15, (int)round($adjustedTime));
    }
    
    /**
     * Calcula opções de entrega com base no tempo de impressão
     * 
     * @param int $printTimeMinutes Tempo de impressão estimado em minutos
     * @return array Opções de entrega com prazos e custos
     */
    private function calculateDeliveryOptions(int $printTimeMinutes): array {
        $result = [];
        
        foreach ($this->deliveryOptions as $option) {
            // Converter minutos para horas e depois para dias
            $printTimeHours = $printTimeMinutes / 60;
            $productionDays = ceil($printTimeHours / 8); // Assume 8 horas de trabalho por dia
            
            // Incluir tempo de processamento
            $processingDays = max(1, (int)$option['min_processing_days']);
            
            // Tempo total até a coleta
            $totalDays = $productionDays + $processingDays;
            
            // Se opção expressa, garantir o prazo máximo
            if ($option['type'] === 'express' && $totalDays > $option['max_days']) {
                // Aplicar custo adicional de prioridade
                $option['additional_cost'] += ($totalDays - $option['max_days']) * 10; // R$ 10 por dia de aceleração
                $totalDays = $option['max_days'];
            }
            
            // Calcular data de entrega
            $deliveryDate = date('Y-m-d', strtotime("+{$totalDays} weekdays"));
            
            // Adicionar opção ao resultado
            $result[] = [
                'id' => $option['id'],
                'name' => $option['name'],
                'type' => $option['type'],
                'days' => $totalDays,
                'delivery_date' => $deliveryDate,
                'additional_cost' => $option['additional_cost'],
                'description' => $option['description']
            ];
        }
        
        return $result;
    }
    
    /**
     * Aplica a margem de lucro ao custo total
     * 
     * @param float $totalCost Custo total da impressão
     * @return float Preço final com margem
     */
    private function applyMargin(float $totalCost): float {
        // Aplicar margem de lucro
        $finalPrice = $totalCost / (1 - $this->baseMarginRate);
        
        // Arredondar para cima ao próximo múltiplo de 0.5
        return ceil($finalPrice * 2) / 2;
    }
    
    /**
     * Obtém informações de uma opção adicional
     * 
     * @param string $optionId ID da opção
     * @return array|null Informações da opção ou null se não encontrada
     */
    private function getAdditionalOptionInfo(string $optionId): ?array {
        foreach ($this->additionalCostFactors as $section) {
            if (isset($section['options'])) {
                foreach ($section['options'] as $option) {
                    if ($option['id'] === $optionId) {
                        return $option;
                    }
                }
            }
        }
        return null;
    }
    
    /**
     * Retorna os materiais disponíveis para impressão
     * 
     * @return array Lista de materiais disponíveis
     */
    public function getAvailableMaterials(): array {
        return $this->materials;
    }
    
    /**
     * Retorna as configurações de qualidade disponíveis
     * 
     * @return array Lista de configurações de qualidade
     */
    public function getAvailableQualitySettings(): array {
        return $this->qualitySettings;
    }
    
    /**
     * Retorna os fatores de custo adicional disponíveis
     * 
     * @return array Lista de fatores de custo adicional
     */
    public function getAdditionalCostFactors(): array {
        return $this->additionalCostFactors;
    }
    
    /**
     * Retorna as opções de entrega disponíveis
     * 
     * @return array Lista de opções de entrega
     */
    public function getDeliveryOptions(): array {
        return $this->deliveryOptions;
    }
    
    /**
     * Retorna materiais padrão para impressão
     * 
     * @return array Lista de materiais padrão
     */
    private function getDefaultMaterials(): array {
        return [
            [
                'id' => 'pla',
                'name' => 'PLA',
                'full_name' => 'Ácido Poliláctico',
                'description' => 'Material biodegradável derivado de fontes renováveis, ideal para modelos decorativos e protótipos básicos.',
                'price_per_kg' => 120.00, // R$ 120,00 por kg
                'density' => 1.24, // g/cm³
                'printer_cost_per_hour' => 10.00, // R$ 10,00 por hora
                'color_options' => ['white', 'black', 'red', 'blue', 'green', 'yellow', 'gray', 'transparent'],
                'mechanical_properties' => [
                    'tensile_strength' => '50-60 MPa',
                    'flexural_strength' => '80-90 MPa',
                    'temperature_resistance' => '55-60°C',
                    'impact_resistance' => 'Baixa'
                ],
                'suitable_for' => [
                    'Protótipos não funcionais',
                    'Peças decorativas',
                    'Modelos de visualização',
                    'Uso interno (não resistente a UV)'
                ],
                'not_suitable_for' => [
                    'Peças mecânicas sob carga',
                    'Uso externo prolongado',
                    'Ambientes de alta temperatura',
                    'Contato com alimentos (sem certificação)'
                ]
            ],
            [
                'id' => 'petg',
                'name' => 'PETG',
                'full_name' => 'Polietileno Tereftalato com Glicol',
                'description' => 'Material durável e resistente a impactos, ideal para peças funcionais com boa transparência e resistência química.',
                'price_per_kg' => 150.00, // R$ 150,00 por kg
                'density' => 1.27, // g/cm³
                'printer_cost_per_hour' => 12.00, // R$ 12,00 por hora
                'color_options' => ['white', 'black', 'red', 'blue', 'green', 'transparent', 'translucent'],
                'mechanical_properties' => [
                    'tensile_strength' => '50-55 MPa',
                    'flexural_strength' => '75-80 MPa',
                    'temperature_resistance' => '70-75°C',
                    'impact_resistance' => 'Média-Alta'
                ],
                'suitable_for' => [
                    'Peças funcionais',
                    'Uso semi-externo',
                    'Componentes mecânicos leves',
                    'Peças com requisitos de durabilidade'
                ],
                'not_suitable_for' => [
                    'Peças sujeitas a altas temperaturas',
                    'Aplicações onde rigidez é crítica',
                    'Contato com alimentos (sem certificação)'
                ]
            ],
            [
                'id' => 'abs',
                'name' => 'ABS',
                'full_name' => 'Acrilonitrila Butadieno Estireno',
                'description' => 'Material resistente e rígido, adequado para peças funcionais que requerem resistência mecânica e térmica.',
                'price_per_kg' => 140.00, // R$ 140,00 por kg
                'density' => 1.04, // g/cm³
                'printer_cost_per_hour' => 15.00, // R$ 15,00 por hora
                'color_options' => ['white', 'black', 'red', 'blue', 'gray', 'yellow'],
                'mechanical_properties' => [
                    'tensile_strength' => '40-45 MPa',
                    'flexural_strength' => '60-75 MPa',
                    'temperature_resistance' => '90-100°C',
                    'impact_resistance' => 'Alta'
                ],
                'suitable_for' => [
                    'Peças funcionais',
                    'Componentes mecânicos',
                    'Protótipos de uso final',
                    'Aplicações automotivas',
                    'Peças sujeitas a impactos'
                ],
                'not_suitable_for' => [
                    'Uso externo prolongado (desgaste por UV)',
                    'Peças com detalhes muito finos',
                    'Contato com alimentos'
                ]
            ],
            [
                'id' => 'tpu',
                'name' => 'TPU',
                'full_name' => 'Poliuretano Termoplástico',
                'description' => 'Material flexível e elástico, ideal para peças que requerem amortecimento ou flexibilidade.',
                'price_per_kg' => 220.00, // R$ 220,00 por kg
                'density' => 1.21, // g/cm³
                'printer_cost_per_hour' => 20.00, // R$ 20,00 por hora
                'color_options' => ['white', 'black', 'red', 'blue', 'transparent', 'natural'],
                'mechanical_properties' => [
                    'tensile_strength' => '20-40 MPa',
                    'elongation_at_break' => '450-550%',
                    'shore_hardness' => '90-95A',
                    'impact_resistance' => 'Muito Alta'
                ],
                'suitable_for' => [
                    'Peças flexíveis',
                    'Protetores e capas',
                    'Amortecedores e juntas',
                    'Pneus para protótipos',
                    'Alças e empunhaduras'
                ],
                'not_suitable_for' => [
                    'Peças rígidas ou estruturais',
                    'Alta precisão dimensional',
                    'Peças com overhangs significativos',
                    'Contato com alimentos (sem certificação)'
                ]
            ],
            [
                'id' => 'nylon',
                'name' => 'Nylon',
                'full_name' => 'Poliamida (PA12)',
                'description' => 'Material com excelente resistência mecânica e flexibilidade, ideal para peças engenheiradas.',
                'price_per_kg' => 280.00, // R$ 280,00 por kg
                'density' => 1.02, // g/cm³
                'printer_cost_per_hour' => 25.00, // R$ 25,00 por hora
                'color_options' => ['white', 'black', 'natural'],
                'mechanical_properties' => [
                    'tensile_strength' => '40-50 MPa',
                    'flexural_strength' => '70-80 MPa',
                    'temperature_resistance' => '80-90°C',
                    'impact_resistance' => 'Muito Alta'
                ],
                'suitable_for' => [
                    'Peças mecânicas funcionais',
                    'Engrenagens e rolamentos',
                    'Fixadores resistentes',
                    'Componentes de uso final',
                    'Peças de alta durabilidade'
                ],
                'not_suitable_for' => [
                    'Produção de baixo custo',
                    'Peças que precisam ser coladas',
                    'Ambientes com alta umidade (sem tratamento)',
                    'Contato com alimentos (sem certificação)'
                ]
            ],
            [
                'id' => 'resin',
                'name' => 'Resina',
                'full_name' => 'Resina Fotopolimérica',
                'description' => 'Material de alta precisão para impressão SLA/DLP, ideal para modelos detalhados e peças com acabamento liso.',
                'price_per_kg' => 350.00, // R$ 350,00 por kg
                'density' => 1.12, // g/cm³
                'printer_cost_per_hour' => 35.00, // R$ 35,00 por hora
                'color_options' => ['clear', 'white', 'black', 'gray', 'amber'],
                'mechanical_properties' => [
                    'tensile_strength' => '50-65 MPa',
                    'flexural_strength' => '75-90 MPa',
                    'temperature_resistance' => '45-80°C (varia por tipo)',
                    'impact_resistance' => 'Baixa-Média'
                ],
                'suitable_for' => [
                    'Protótipos de alta precisão',
                    'Peças com detalhes finos',
                    'Modelos para fundição',
                    'Moldes para joalheria',
                    'Modelos odontológicos'
                ],
                'not_suitable_for' => [
                    'Peças grandes',
                    'Peças para uso externo prolongado',
                    'Componentes sujeitos a altos impactos',
                    'Peças com propriedades mecânicas críticas',
                    'Contato com alimentos (sem certificação específica)'
                ]
            ]
        ];
    }
    
    /**
     * Retorna configurações de qualidade padrão
     * 
     * @return array Lista de configurações de qualidade padrão
     */
    private function getDefaultQualitySettings(): array {
        return [
            [
                'id' => 'draft',
                'name' => 'Rascunho',
                'description' => 'Camadas mais grossas para impressão rápida. Adequado para protótipos iniciais onde o acabamento não é prioritário.',
                'layer_height' => 0.3, // mm
                'time_factor' => 0.7, // Mais rápido que o padrão
                'resolution_factor' => 0.6, // Resolução menor que o padrão
                'price_factor' => 0.8, // 20% mais barato
                'default_infill' => 15, // 15% de preenchimento padrão
                'default_perimeters' => 2, // 2 perímetros
                'suitable_for' => [
                    'Protótipos conceituais',
                    'Peças grandes não-detalhadas',
                    'Testes de forma e ajuste'
                ],
                'not_suitable_for' => [
                    'Peças com detalhes finos',
                    'Modelos de apresentação',
                    'Peças com features pequenas',
                    'Componentes de precisão'
                ]
            ],
            [
                'id' => 'standard',
                'name' => 'Padrão',
                'description' => 'Equilíbrio entre velocidade e qualidade. Adequado para a maioria das aplicações gerais.',
                'layer_height' => 0.2, // mm
                'time_factor' => 1.0, // Tempo padrão
                'resolution_factor' => 1.0, // Resolução padrão
                'price_factor' => 1.0, // Preço padrão
                'default_infill' => 20, // 20% de preenchimento padrão
                'default_perimeters' => 3, // 3 perímetros
                'suitable_for' => [
                    'Maioria das peças funcionais',
                    'Protótipos de uso geral',
                    'Peças decorativas',
                    'Modelos de demonstração'
                ],
                'not_suitable_for' => [
                    'Peças com detalhes muito finos',
                    'Modelos de alta precisão',
                    'Peças que exigem o melhor acabamento'
                ]
            ],
            [
                'id' => 'high',
                'name' => 'Alta Qualidade',
                'description' => 'Camadas mais finas para melhor acabamento superficial. Ideal para modelos de apresentação e peças detalhadas.',
                'layer_height' => 0.1, // mm
                'time_factor' => 1.8, // 80% mais lento que o padrão
                'resolution_factor' => 1.5, // 50% mais resolução
                'price_factor' => 1.3, // 30% mais caro
                'default_infill' => 25, // 25% de preenchimento padrão
                'default_perimeters' => 4, // 4 perímetros
                'suitable_for' => [
                    'Modelos de apresentação',
                    'Peças com detalhes finos',
                    'Protótipos de alta fidelidade',
                    'Modelos arquitetônicos',
                    'Peças com curvas suaves'
                ],
                'not_suitable_for' => [
                    'Peças grandes simples',
                    'Protótipos rápidos',
                    'Projetos com restrição de orçamento'
                ]
            ],
            [
                'id' => 'ultra',
                'name' => 'Qualidade Ultra',
                'description' => 'Máxima qualidade e resolução. Para peças que exigem o melhor acabamento possível e alta precisão.',
                'layer_height' => 0.06, // mm
                'time_factor' => 3.0, // 3x mais lento que o padrão
                'resolution_factor' => 2.0, // 2x mais resolução
                'price_factor' => 1.7, // 70% mais caro
                'default_infill' => 30, // 30% de preenchimento padrão
                'default_perimeters' => 5, // 5 perímetros
                'suitable_for' => [
                    'Modelos de exposição',
                    'Peças com microdetalhes',
                    'Protótipos finais para aprovação',
                    'Peças de precisão',
                    'Modelos para documentação fotográfica'
                ],
                'not_suitable_for' => [
                    'Produção em lote',
                    'Peças grandes',
                    'Protótipos de conceito',
                    'Projetos com prazo apertado',
                    'Orçamentos limitados'
                ]
            ],
            [
                'id' => 'engineering',
                'name' => 'Engenharia',
                'description' => 'Otimizado para resistência mecânica. Alta densidade de preenchimento e perímetros para peças funcionais.',
                'layer_height' => 0.15, // mm
                'time_factor' => 1.5, // 50% mais lento que o padrão
                'resolution_factor' => 1.2, // 20% mais resolução
                'price_factor' => 1.4, // 40% mais caro
                'default_infill' => 50, // 50% de preenchimento padrão
                'default_perimeters' => 4, // 4 perímetros
                'suitable_for' => [
                    'Peças funcionais',
                    'Componentes mecânicos',
                    'Protótipos para testes de carga',
                    'Peças para uso final',
                    'Fixadores e montagens'
                ],
                'not_suitable_for' => [
                    'Modelos puramente estéticos',
                    'Peças de baixa carga',
                    'Projetos com restrição de material'
                ]
            ]
        ];
    }
    
    /**
     * Retorna fatores de custo adicional padrão
     * 
     * @return array Lista de fatores de custo adicional padrão
     */
    private function getDefaultAdditionalCostFactors(): array {
        return [
            [
                'section_id' => 'finish',
                'section_name' => 'Acabamento',
                'options' => [
                    [
                        'id' => 'sanding',
                        'name' => 'Lixamento',
                        'description' => 'Acabamento com lixas para reduzir marcas de camadas e melhorar textura superficial.',
                        'cost_type' => 'percentage',
                        'cost_value' => 15, // 15% do custo base
                        'default_value' => false
                    ],
                    [
                        'id' => 'painting',
                        'name' => 'Pintura Básica',
                        'description' => 'Aplicação de tinta em cor única com acabamento fosco ou brilhante.',
                        'cost_type' => 'percentage',
                        'cost_value' => 30, // 30% do custo base
                        'default_value' => false
                    ],
                    [
                        'id' => 'advanced_painting',
                        'name' => 'Pintura Avançada',
                        'description' => 'Pintura multicor com detalhes, sombreamento e acabamento profissional.',
                        'cost_type' => 'percentage',
                        'cost_value' => 75, // 75% do custo base
                        'default_value' => false
                    ],
                    [
                        'id' => 'polishing',
                        'name' => 'Polimento',
                        'description' => 'Tratamento de superfície para acabamento brilhante e suave.',
                        'cost_type' => 'percentage',
                        'cost_value' => 20, // 20% do custo base
                        'default_value' => false
                    ]
                ]
            ],
            [
                'section_id' => 'assembly',
                'section_name' => 'Montagem',
                'options' => [
                    [
                        'id' => 'basic_assembly',
                        'name' => 'Montagem Básica',
                        'description' => 'Montagem de componentes múltiplos em uma única peça.',
                        'cost_type' => 'fixed',
                        'cost_value' => 20, // R$ 20,00 fixo
                        'default_value' => false
                    ],
                    [
                        'id' => 'hardware_installation',
                        'name' => 'Instalação de Hardware',
                        'description' => 'Instalação de parafusos, porcas, insertos e outros componentes de hardware.',
                        'cost_type' => 'variable',
                        'cost_value' => 2, // R$ 2,00 por item
                        'default_value' => 0
                    ],
                    [
                        'id' => 'complex_assembly',
                        'name' => 'Montagem Complexa',
                        'description' => 'Montagem de mecanismos ou sistemas com múltiplas peças e ajustes precisos.',
                        'cost_type' => 'per_time',
                        'cost_value' => 40, // R$ 40,00 por hora estimada
                        'default_value' => false
                    ]
                ]
            ],
            [
                'section_id' => 'treatment',
                'section_name' => 'Tratamentos',
                'options' => [
                    [
                        'id' => 'acetone_smoothing',
                        'name' => 'Alisamento com Acetona',
                        'description' => 'Tratamento químico para alisar superfícies de ABS, resultando em acabamento brilhante e sem camadas visíveis.',
                        'cost_type' => 'percentage',
                        'cost_value' => 25, // 25% do custo base
                        'default_value' => false
                    ],
                    [
                        'id' => 'heat_treatment',
                        'name' => 'Tratamento Térmico',
                        'description' => 'Aquecimento controlado para melhorar a resistência mecânica e reduzir stress entre camadas.',
                        'cost_type' => 'fixed',
                        'cost_value' => 35, // R$ 35,00 fixo
                        'default_value' => false
                    ],
                    [
                        'id' => 'waterproofing',
                        'name' => 'Impermeabilização',
                        'description' => 'Aplicação de selante para tornar a peça resistente à água.',
                        'cost_type' => 'percentage',
                        'cost_value' => 20, // 20% do custo base
                        'default_value' => false
                    ],
                    [
                        'id' => 'uv_protection',
                        'name' => 'Proteção UV',
                        'description' => 'Aplicação de revestimento protetor contra raios UV para uso externo.',
                        'cost_type' => 'percentage',
                        'cost_value' => 15, // 15% do custo base
                        'default_value' => false
                    ]
                ]
            ],
            [
                'section_id' => 'extras',
                'section_name' => 'Extras',
                'options' => [
                    [
                        'id' => 'priority_queue',
                        'name' => 'Fila Prioritária',
                        'description' => 'Prioridade na fila de impressão, reduzindo o tempo de espera.',
                        'cost_type' => 'percentage',
                        'cost_value' => 40, // 40% do custo base
                        'default_value' => false
                    ],
                    [
                        'id' => 'technical_drawing',
                        'name' => 'Desenho Técnico',
                        'description' => 'Criação de desenho técnico com cotas e especificações a partir do modelo 3D.',
                        'cost_type' => 'fixed',
                        'cost_value' => 50, // R$ 50,00 fixo
                        'default_value' => false
                    ],
                    [
                        'id' => 'packaging_premium',
                        'name' => 'Embalagem Premium',
                        'description' => 'Embalagem personalizada com proteção extra para transporte seguro.',
                        'cost_type' => 'fixed',
                        'cost_value' => 25, // R$ 25,00 fixo
                        'default_value' => false
                    ],
                    [
                        'id' => 'certificate',
                        'name' => 'Certificado de Conformidade',
                        'description' => 'Documento certificando as especificações do material e propriedades da peça.',
                        'cost_type' => 'fixed',
                        'cost_value' => 30, // R$ 30,00 fixo
                        'default_value' => false
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Retorna opções de entrega padrão
     * 
     * @return array Lista de opções de entrega padrão
     */
    private function getDefaultDeliveryOptions(): array {
        return [
            [
                'id' => 'standard',
                'name' => 'Padrão',
                'type' => 'standard',
                'description' => 'Retirada na loja quando o pedido estiver pronto.',
                'min_processing_days' => 2,
                'max_days' => 10,
                'additional_cost' => 0
            ],
            [
                'id' => 'express',
                'name' => 'Express',
                'type' => 'express',
                'description' => 'Retirada prioritária com prazo reduzido.',
                'min_processing_days' => 1,
                'max_days' => 3,
                'additional_cost' => 30
            ],
            [
                'id' => 'delivery_local',
                'name' => 'Entrega Local',
                'type' => 'delivery',
                'description' => 'Entrega na cidade (raio de 10km).',
                'min_processing_days' => 2,
                'max_days' => 5,
                'additional_cost' => 20
            ],
            [
                'id' => 'delivery_national',
                'name' => 'Entrega Nacional',
                'type' => 'delivery',
                'description' => 'Envio por transportadora para todo o Brasil.',
                'min_processing_days' => 2,
                'max_days' => 10,
                'additional_cost' => 45
            ]
        ];
    }
    
    /**
     * Obtém o resultado da última cotação
     * 
     * @return array|null Resultado da última cotação ou null se não houver
     */
    public function getLastQuotationResult(): ?array {
        return $this->lastQuotationResult;
    }
    
    /**
     * Obtém os parâmetros da última cotação
     * 
     * @return array|null Parâmetros da última cotação ou null se não houver
     */
    public function getLastQuotationParams(): ?array {
        return $this->lastQuotationParams;
    }
}