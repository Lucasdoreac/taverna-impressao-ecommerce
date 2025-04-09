<?php
/**
 * ModelComplexityAnalyzer
 * 
 * Classe responsável por analisar a complexidade de modelos 3D para 
 * cálculo de cotações automatizadas com base em métricas como volume,
 * densidade de polígonos, características especiais e outros fatores relevantes.
 * 
 * Esta classe segue os princípios de segurança da Taverna, incluindo validação
 * robusta de entradas e sanitização adequada.
 * 
 * @package App\Lib\Analysis
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */
class ModelComplexityAnalyzer {
    /**
     * Tipos de arquivo suportados para análise
     * 
     * @var array
     */
    private $supportedFormats = ['stl', 'obj', '3mf', 'gcode'];
    
    /**
     * Pontuação máxima de complexidade
     * 
     * @var int
     */
    private const MAX_COMPLEXITY_SCORE = 100;
    
    /**
     * Pesos para diferentes métricas na análise de complexidade
     * 
     * @var array
     */
    private $metricWeights = [
        'polygon_count' => 0.3,     // Peso para contagem de polígonos
        'volume' => 0.25,           // Peso para volume do modelo
        'surface_area' => 0.15,     // Peso para área de superfície
        'hollow_spaces' => 0.1,     // Peso para espaços vazios internos
        'overhangs' => 0.1,         // Peso para estruturas em balanço
        'thin_walls' => 0.1,        // Peso para paredes finas
    ];
    
    /**
     * Taxa básica para tempo de impressão (minutos por cm³)
     * 
     * @var float
     */
    private $basePrintTimeRate = 12.0;  // 12 minutos por cm³ como base
    
    /**
     * Taxa mínima de impressão (minutos)
     * 
     * @var int
     */
    private $minimumPrintTime = 30;     // Mínimo de 30 minutos
    
    /**
     * Custo básico por cm³ de material
     * 
     * @var float
     */
    private $baseMaterialCost = 0.5;    // R$ 0,50 por cm³ de material
    
    /**
     * Custo por hora de impressão
     * 
     * @var float
     */
    private $hourlyPrintCost = 15.0;    // R$ 15,00 por hora de impressão
    
    /**
     * Modelo 3D sendo analisado
     * 
     * @var array
     */
    private $model = null;
    
    /**
     * Resultados da análise
     * 
     * @var array
     */
    private $analysisResults = null;
    
    /**
     * Construtor
     * 
     * @param array $configuration Configuração opcional para sobrescrever padrões
     */
    public function __construct(array $configuration = []) {
        // Aplicar configurações personalizadas
        if (!empty($configuration)) {
            if (isset($configuration['metricWeights'])) {
                $this->metricWeights = array_merge($this->metricWeights, $configuration['metricWeights']);
                
                // Garantir que a soma dos pesos seja 1.0
                $totalWeight = array_sum($this->metricWeights);
                if (abs($totalWeight - 1.0) > 0.01) {
                    foreach ($this->metricWeights as $key => $weight) {
                        $this->metricWeights[$key] = $weight / $totalWeight;
                    }
                }
            }
            
            if (isset($configuration['basePrintTimeRate'])) {
                $this->basePrintTimeRate = (float)$configuration['basePrintTimeRate'];
            }
            
            if (isset($configuration['minimumPrintTime'])) {
                $this->minimumPrintTime = (int)$configuration['minimumPrintTime'];
            }
            
            if (isset($configuration['baseMaterialCost'])) {
                $this->baseMaterialCost = (float)$configuration['baseMaterialCost'];
            }
            
            if (isset($configuration['hourlyPrintCost'])) {
                $this->hourlyPrintCost = (float)$configuration['hourlyPrintCost'];
            }
            
            if (isset($configuration['supportedFormats'])) {
                $this->supportedFormats = $configuration['supportedFormats'];
            }
        }
    }
    
    /**
     * Carrega e valida um modelo 3D para análise
     * 
     * @param array $model Informações do modelo (tipo, caminho do arquivo, metadados)
     * @return boolean True se o modelo foi carregado com sucesso
     * @throws Exception Se o modelo não for válido ou não puder ser carregado
     */
    public function loadModel(array $model): bool {
        // Validar parâmetros obrigatórios
        if (!isset($model['file_path']) || !file_exists($model['file_path'])) {
            throw new Exception('Caminho do arquivo do modelo inválido ou não encontrado');
        }
        
        if (!isset($model['file_type'])) {
            // Tentar extrair o tipo do arquivo do caminho
            $extension = pathinfo($model['file_path'], PATHINFO_EXTENSION);
            if (!empty($extension)) {
                $model['file_type'] = strtolower($extension);
            } else {
                throw new Exception('Tipo de arquivo do modelo não especificado');
            }
        }
        
        // Verificar se o formato é suportado
        if (!in_array(strtolower($model['file_type']), $this->supportedFormats)) {
            throw new Exception('Formato de arquivo não suportado para análise de complexidade');
        }
        
        // Verificar se o arquivo é acessível
        if (!is_readable($model['file_path'])) {
            throw new Exception('Arquivo do modelo não pode ser lido');
        }
        
        // Verificar o tamanho do arquivo
        $fileSize = filesize($model['file_path']);
        if ($fileSize <= 0) {
            throw new Exception('Arquivo do modelo vazio ou inválido');
        }
        
        // Verificar tamanho máximo (100MB por segurança)
        if ($fileSize > 100 * 1024 * 1024) {
            throw new Exception('Arquivo do modelo excede o tamanho máximo permitido para análise');
        }
        
        // Armazenar o modelo
        $this->model = $model;
        
        // Inicializar o resultado da análise
        $this->analysisResults = null;
        
        return true;
    }
    
    /**
     * Analisa a complexidade do modelo 3D carregado
     * 
     * @param bool $forceReanalysis Se deve forçar uma nova análise mesmo com resultados existentes
     * @return array Resultados da análise de complexidade
     * @throws Exception Se o modelo não foi carregado ou se ocorrer um erro durante a análise
     */
    public function analyzeComplexity(bool $forceReanalysis = false): array {
        // Verificar se o modelo foi carregado
        if ($this->model === null) {
            throw new Exception('Nenhum modelo carregado para análise');
        }
        
        // Verificar se a análise já foi realizada e não é forçada uma reanálise
        if ($this->analysisResults !== null && !$forceReanalysis) {
            return $this->analysisResults;
        }
        
        // Extração de métricas com base no tipo de arquivo
        $metrics = $this->extractModelMetrics();
        
        // Cálculo da pontuação de complexidade
        $complexityScore = $this->calculateComplexityScore($metrics);
        
        // Estimativa de tempo de impressão
        $printTime = $this->estimatePrintTime($metrics, $complexityScore);
        
        // Cálculo de custos
        $materialCost = $this->calculateMaterialCost($metrics);
        $printingCost = $this->calculatePrintingCost($printTime);
        
        // Armazenar resultados
        $this->analysisResults = [
            'complexity_score' => $complexityScore,
            'metrics' => $metrics,
            'estimated_print_time_minutes' => $printTime,
            'material_cost' => $materialCost,
            'printing_cost' => $printingCost,
            'total_cost' => $materialCost + $printingCost,
            'analysis_timestamp' => time()
        ];
        
        return $this->analysisResults;
    }
    
    /**
     * Extrai métricas do modelo 3D com base no tipo de arquivo
     * 
     * @return array Métricas extraídas (contagem de polígonos, volume, área, etc.)
     * @throws Exception Se ocorrer um erro na extração de métricas
     */
    private function extractModelMetrics(): array {
        $metrics = [
            'polygon_count' => 0,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0,
            'overhangs' => 0,
            'thin_walls' => 0,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Tipo de arquivo em minúsculas para comparação
        $fileType = strtolower($this->model['file_type']);
        
        try {
            // Implementação específica para cada formato
            switch ($fileType) {
                case 'stl':
                    $metrics = $this->extractMetricsFromSTL($this->model['file_path']);
                    break;
                
                case 'obj':
                    $metrics = $this->extractMetricsFromOBJ($this->model['file_path']);
                    break;
                
                case '3mf':
                    $metrics = $this->extractMetricsFrom3MF($this->model['file_path']);
                    break;
                
                case 'gcode':
                    $metrics = $this->extractMetricsFromGCode($this->model['file_path']);
                    break;
                
                default:
                    throw new Exception("Formato não suportado para extração de métricas: {$fileType}");
            }
        } catch (Exception $e) {
            // Quando ocorrer um erro, use métricas estimadas dos metadados do modelo
            if (isset($this->model['metadata'])) {
                $metrics = $this->estimateMetricsFromMetadata($this->model['metadata']);
            } else {
                throw new Exception("Falha ao extrair métricas e nenhum metadado disponível: " . $e->getMessage());
            }
        }
        
        // Validar as métricas extraídas
        $this->validateMetrics($metrics);
        
        return $metrics;
    }
    
    /**
     * Extrai métricas de um arquivo STL
     * 
     * @param string $filePath Caminho do arquivo STL
     * @return array Métricas extraídas
     */
    private function extractMetricsFromSTL(string $filePath): array {
        // Verificar se é possível ler o arquivo
        if (!is_readable($filePath)) {
            throw new Exception("Arquivo STL não pode ser lido: {$filePath}");
        }
        
        // Determinar se é um arquivo STL binário ou ASCII
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new Exception("Falha ao abrir o arquivo STL: {$filePath}");
        }
        
        // Ler os primeiros bytes para determinar o tipo de STL
        $header = fread($handle, 80);
        $isBinary = true;
        
        // Se for ASCII, deve começar com "solid"
        if (stripos($header, 'solid') === 0) {
            // Ler mais alguns bytes para verificar se é realmente ASCII
            $nextLine = fgets($handle);
            // Arquivos binários podem falsamente começar com "solid"
            if (stripos($nextLine, 'facet') !== false || stripos($nextLine, 'endsolid') !== false) {
                $isBinary = false;
            }
        }
        
        // Reiniciar a leitura do arquivo
        rewind($handle);
        
        $metrics = [
            'polygon_count' => 0,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0,
            'overhangs' => 0,
            'thin_walls' => 0,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Extrair métricas com base no tipo de STL
        if ($isBinary) {
            $metrics = $this->extractMetricsFromBinarySTL($handle);
        } else {
            $metrics = $this->extractMetricsFromAsciiSTL($handle);
        }
        
        // Fechar o arquivo
        fclose($handle);
        
        return $metrics;
    }
    
    /**
     * Extrai métricas de um arquivo STL binário
     * 
     * @param resource $handle Handle do arquivo aberto
     * @return array Métricas extraídas
     */
    private function extractMetricsFromBinarySTL($handle): array {
        // Pular o cabeçalho de 80 bytes
        fseek($handle, 80);
        
        // Ler o número de triângulos (4 bytes)
        $data = fread($handle, 4);
        $triangleCount = unpack('V', $data)[1];
        
        // Inicializar métricas
        $metrics = [
            'polygon_count' => $triangleCount,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0,
            'overhangs' => 0,
            'thin_walls' => 0,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Inicializar limites para calcular dimensões
        $minX = $minY = $minZ = PHP_FLOAT_MAX;
        $maxX = $maxY = $maxZ = PHP_FLOAT_MIN;
        
        // Para cada triângulo (50 bytes cada)
        $totalArea = 0;
        $volume = 0;
        
        // Limitar processamento para arquivos muito grandes
        $maxTrianglesToProcess = min($triangleCount, 1000000); // Máximo 1 milhão de triângulos
        
        for ($i = 0; $i < $maxTrianglesToProcess; $i++) {
            // Pular o vetor normal (12 bytes)
            fseek($handle, 12, SEEK_CUR);
            
            // Ler as coordenadas dos vértices (3 vértices x 12 bytes cada = 36 bytes)
            $vertexData = fread($handle, 36);
            
            if (strlen($vertexData) !== 36) {
                break; // Fim do arquivo ou erro de leitura
            }
            
            // Descompactar coordenadas (formato float de 4 bytes para cada coordenada)
            $vertices = unpack('f12', $vertexData);
            
            // Extrair coordenadas dos vértices
            $v1 = [
                'x' => $vertices[1],
                'y' => $vertices[2],
                'z' => $vertices[3]
            ];
            
            $v2 = [
                'x' => $vertices[4],
                'y' => $vertices[5],
                'z' => $vertices[6]
            ];
            
            $v3 = [
                'x' => $vertices[7],
                'y' => $vertices[8],
                'z' => $vertices[9]
            ];
            
            // Atualizar limites para calcular dimensões
            $minX = min($minX, $v1['x'], $v2['x'], $v3['x']);
            $minY = min($minY, $v1['y'], $v2['y'], $v3['y']);
            $minZ = min($minZ, $v1['z'], $v2['z'], $v3['z']);
            
            $maxX = max($maxX, $v1['x'], $v2['x'], $v3['x']);
            $maxY = max($maxY, $v1['y'], $v2['y'], $v3['y']);
            $maxZ = max($maxZ, $v1['z'], $v2['z'], $v3['z']);
            
            // Calcular área do triângulo
            $area = $this->calculateTriangleArea($v1, $v2, $v3);
            $totalArea += $area;
            
            // Contribuição para o volume
            $volume += $this->calculateSignedVolumeOfTriangle($v1, $v2, $v3);
            
            // Pular o atributo (2 bytes)
            fseek($handle, 2, SEEK_CUR);
        }
        
        // Atualizar métricas
        $metrics['surface_area'] = $totalArea;
        $metrics['volume'] = abs($volume); // Volume deve ser positivo
        
        // Calcular dimensões
        $metrics['dimensions']['width'] = $maxX - $minX;
        $metrics['dimensions']['height'] = $maxY - $minY;
        $metrics['dimensions']['depth'] = $maxZ - $minZ;
        
        // Calcular volume da caixa delimitadora
        $metrics['bounding_box_volume'] = 
            $metrics['dimensions']['width'] * 
            $metrics['dimensions']['height'] * 
            $metrics['dimensions']['depth'];
        
        // Estimativa de espaços vazios (razão entre volume e bounding box)
        if ($metrics['bounding_box_volume'] > 0) {
            $metrics['hollow_spaces'] = 1 - ($metrics['volume'] / $metrics['bounding_box_volume']);
        }
        
        // Estimativa simples de balanços e paredes finas
        // Valores baseados em heurísticas
        $metrics['overhangs'] = min(1.0, $triangleCount / 50000);
        $metrics['thin_walls'] = min(1.0, max(0, 0.5 - ($metrics['volume'] / $metrics['surface_area'] / 10)));
        
        return $metrics;
    }
    
    /**
     * Extrai métricas de um arquivo STL ASCII
     * 
     * @param resource $handle Handle do arquivo aberto
     * @return array Métricas extraídas
     */
    private function extractMetricsFromAsciiSTL($handle): array {
        // Inicializar métricas
        $metrics = [
            'polygon_count' => 0,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0,
            'overhangs' => 0,
            'thin_walls' => 0,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Inicializar limites para calcular dimensões
        $minX = $minY = $minZ = PHP_FLOAT_MAX;
        $maxX = $maxY = $maxZ = PHP_FLOAT_MIN;
        
        // Para cada triângulo no arquivo
        $triangleCount = 0;
        $totalArea = 0;
        $volume = 0;
        $vertices = [];
        $currentVertex = 0;
        
        // Limite de segurança para evitar loops infinitos
        $maxLines = 10000000; // 10 milhões de linhas
        $lineCount = 0;
        
        while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
            $lineCount++;
            
            // Ignorar linhas em branco e comentários
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Verificar se é um vértice
            if (preg_match('/vertex\s+([-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?)\s+([-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?)\s+([-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?)/i', $line, $matches)) {
                $x = (float)$matches[1];
                $y = (float)$matches[3];
                $z = (float)$matches[5];
                
                // Armazenar vértice
                $vertices[$currentVertex] = [
                    'x' => $x,
                    'y' => $y,
                    'z' => $z
                ];
                
                // Atualizar limites para calcular dimensões
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $minZ = min($minZ, $z);
                
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
                $maxZ = max($maxZ, $z);
                
                $currentVertex++;
                
                // Se temos um triângulo completo (3 vértices), processar
                if ($currentVertex == 3) {
                    // Incrementar contador de triângulos
                    $triangleCount++;
                    
                    // Calcular área do triângulo
                    $area = $this->calculateTriangleArea($vertices[0], $vertices[1], $vertices[2]);
                    $totalArea += $area;
                    
                    // Contribuição para o volume
                    $volume += $this->calculateSignedVolumeOfTriangle($vertices[0], $vertices[1], $vertices[2]);
                    
                    // Reiniciar para o próximo triângulo
                    $currentVertex = 0;
                }
            }
            
            // Verificar se estamos no final do arquivo
            if (stripos($line, 'endsolid') !== false) {
                break;
            }
        }
        
        // Atualizar métricas
        $metrics['polygon_count'] = $triangleCount;
        $metrics['surface_area'] = $totalArea;
        $metrics['volume'] = abs($volume); // Volume deve ser positivo
        
        // Calcular dimensões
        $metrics['dimensions']['width'] = $maxX - $minX;
        $metrics['dimensions']['height'] = $maxY - $minY;
        $metrics['dimensions']['depth'] = $maxZ - $minZ;
        
        // Calcular volume da caixa delimitadora
        $metrics['bounding_box_volume'] = 
            $metrics['dimensions']['width'] * 
            $metrics['dimensions']['height'] * 
            $metrics['dimensions']['depth'];
        
        // Estimativa de espaços vazios (razão entre volume e bounding box)
        if ($metrics['bounding_box_volume'] > 0) {
            $metrics['hollow_spaces'] = 1 - ($metrics['volume'] / $metrics['bounding_box_volume']);
        }
        
        // Estimativa simples de balanços e paredes finas
        // Valores baseados em heurísticas
        $metrics['overhangs'] = min(1.0, $triangleCount / 50000);
        $metrics['thin_walls'] = min(1.0, max(0, 0.5 - ($metrics['volume'] / $metrics['surface_area'] / 10)));
        
        return $metrics;
    }
    
    /**
     * Extrai métricas de um arquivo OBJ
     * 
     * @param string $filePath Caminho do arquivo OBJ
     * @return array Métricas extraídas
     */
    private function extractMetricsFromOBJ(string $filePath): array {
        // Verificar se é possível ler o arquivo
        if (!is_readable($filePath)) {
            throw new Exception("Arquivo OBJ não pode ser lido: {$filePath}");
        }
        
        // Inicializar métricas
        $metrics = [
            'polygon_count' => 0,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0,
            'overhangs' => 0,
            'thin_walls' => 0,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Abrir o arquivo
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Falha ao abrir o arquivo OBJ: {$filePath}");
        }
        
        // Arrays para armazenar vértices e faces
        $vertices = [];
        $faces = [];
        
        // Inicializar limites para calcular dimensões
        $minX = $minY = $minZ = PHP_FLOAT_MAX;
        $maxX = $maxY = $maxZ = PHP_FLOAT_MIN;
        
        // Limite de segurança para evitar loops infinitos
        $maxLines = 10000000; // 10 milhões de linhas
        $lineCount = 0;
        
        // Ler o arquivo linha por linha
        while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
            $lineCount++;
            
            // Ignorar linhas em branco e comentários
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Extrair tipo de linha e dados
            $parts = preg_split('/\s+/', $line);
            $type = array_shift($parts);
            
            // Processar vértices
            if ($type === 'v') {
                // Verificar se temos pelo menos 3 coordenadas
                if (count($parts) >= 3) {
                    $x = (float)$parts[0];
                    $y = (float)$parts[1];
                    $z = (float)$parts[2];
                    
                    // Armazenar vértice
                    $vertices[] = [
                        'x' => $x,
                        'y' => $y,
                        'z' => $z
                    ];
                    
                    // Atualizar limites para calcular dimensões
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $minZ = min($minZ, $z);
                    
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                    $maxZ = max($maxZ, $z);
                }
            }
            // Processar faces
            elseif ($type === 'f') {
                // Formato das faces: f v1/vt1/vn1 v2/vt2/vn2 v3/vt3/vn3 ...
                $faceVertices = [];
                
                foreach ($parts as $part) {
                    // Extrair índice do vértice (primeiro número antes de '/')
                    $vertexIndex = (int)strtok($part, '/') - 1; // OBJ indexa a partir de 1
                    
                    // Verificar se o índice é válido
                    if ($vertexIndex >= 0 && $vertexIndex < count($vertices)) {
                        $faceVertices[] = $vertexIndex;
                    }
                }
                
                // Armazenar face apenas se tiver pelo menos 3 vértices válidos
                if (count($faceVertices) >= 3) {
                    $faces[] = $faceVertices;
                }
            }
        }
        
        // Fechar o arquivo
        fclose($handle);
        
        // Atualizar métricas
        $metrics['polygon_count'] = count($faces);
        
        // Calcular área de superfície e volume
        $totalArea = 0;
        $volume = 0;
        
        // Limitar processamento para arquivos muito grandes
        $maxFacesToProcess = min(count($faces), 1000000); // Máximo 1 milhão de faces
        
        for ($i = 0; $i < $maxFacesToProcess; $i++) {
            $face = $faces[$i];
            
            // Triangular a face (para faces com mais de 3 vértices)
            for ($j = 2; $j < count($face); $j++) {
                $v1 = $vertices[$face[0]];
                $v2 = $vertices[$face[$j-1]];
                $v3 = $vertices[$face[$j]];
                
                // Calcular área do triângulo
                $area = $this->calculateTriangleArea($v1, $v2, $v3);
                $totalArea += $area;
                
                // Contribuição para o volume
                $volume += $this->calculateSignedVolumeOfTriangle($v1, $v2, $v3);
            }
        }
        
        $metrics['surface_area'] = $totalArea;
        $metrics['volume'] = abs($volume); // Volume deve ser positivo
        
        // Calcular dimensões
        $metrics['dimensions']['width'] = $maxX - $minX;
        $metrics['dimensions']['height'] = $maxY - $minY;
        $metrics['dimensions']['depth'] = $maxZ - $minZ;
        
        // Calcular volume da caixa delimitadora
        $metrics['bounding_box_volume'] = 
            $metrics['dimensions']['width'] * 
            $metrics['dimensions']['height'] * 
            $metrics['dimensions']['depth'];
        
        // Estimativa de espaços vazios (razão entre volume e bounding box)
        if ($metrics['bounding_box_volume'] > 0) {
            $metrics['hollow_spaces'] = 1 - ($metrics['volume'] / $metrics['bounding_box_volume']);
        }
        
        // Estimativa simples de balanços e paredes finas
        // Valores baseados em heurísticas
        $metrics['overhangs'] = min(1.0, count($faces) / 50000);
        $metrics['thin_walls'] = min(1.0, max(0, 0.5 - ($metrics['volume'] / $metrics['surface_area'] / 10)));
        
        return $metrics;
    }
    
    /**
     * Extrai métricas de um arquivo 3MF
     * 
     * @param string $filePath Caminho do arquivo 3MF
     * @return array Métricas extraídas
     */
    private function extractMetricsFrom3MF(string $filePath): array {
        // Verificar se é possível ler o arquivo
        if (!is_readable($filePath)) {
            throw new Exception("Arquivo 3MF não pode ser lido: {$filePath}");
        }
        
        // Verificar se a extensão ZIP está disponível (3MF é um arquivo ZIP)
        if (!class_exists('ZipArchive')) {
            throw new Exception("Extensão ZipArchive não disponível para processar arquivo 3MF");
        }
        
        // Inicializar métricas
        $metrics = [
            'polygon_count' => 0,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0,
            'overhangs' => 0,
            'thin_walls' => 0,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Abrir o arquivo 3MF como um ZIP
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Falha ao abrir o arquivo 3MF como ZIP: {$filePath}");
        }
        
        // Encontrar e ler o arquivo 3D model dentro do 3MF
        $modelFile = '3D/3dmodel.model';
        $modelXml = $zip->getFromName($modelFile);
        
        if (!$modelXml) {
            $zip->close();
            throw new Exception("Arquivo de modelo não encontrado dentro do 3MF: {$modelFile}");
        }
        
        // Fechar o arquivo ZIP
        $zip->close();
        
        // Analisar o XML do modelo
        $xml = simplexml_load_string($modelXml);
        
        if (!$xml) {
            throw new Exception("Falha ao analisar o XML do modelo 3MF");
        }
        
        // Definir os namespaces corretos
        $namespaces = $xml->getNamespaces(true);
        
        // Registro de namespaces usados ​​no 3MF
        $ns = '';
        foreach ($namespaces as $prefix => $namespace) {
            if (empty($prefix)) {
                $ns = $namespace;
                break;
            }
        }
        
        // Registrar namespace padrão
        if (empty($ns)) {
            $ns = 'http://schemas.microsoft.com/3dmanufacturing/core/2015/02';
        }
        
        // Inicializar arrays para vértices e triângulos
        $vertices = [];
        $triangles = [];
        
        // Inicializar limites para calcular dimensões
        $minX = $minY = $minZ = PHP_FLOAT_MAX;
        $maxX = $maxY = $maxZ = PHP_FLOAT_MIN;
        
        // Processar cada objeto de malha (mesh) no modelo
        foreach ($xml->children($ns)->resources->children($ns) as $resource) {
            if ($resource->getName() === 'object' && isset($resource->mesh)) {
                $mesh = $resource->mesh;
                
                // Processar vértices
                if (isset($mesh->vertices)) {
                    foreach ($mesh->vertices->children($ns) as $vertex) {
                        if ($vertex->getName() === 'vertex') {
                            $x = (float)$vertex['x'];
                            $y = (float)$vertex['y'];
                            $z = (float)$vertex['z'];
                            
                            // Armazenar vértice
                            $vertices[] = [
                                'x' => $x,
                                'y' => $y,
                                'z' => $z
                            ];
                            
                            // Atualizar limites para calcular dimensões
                            $minX = min($minX, $x);
                            $minY = min($minY, $y);
                            $minZ = min($minZ, $z);
                            
                            $maxX = max($maxX, $x);
                            $maxY = max($maxY, $y);
                            $maxZ = max($maxZ, $z);
                        }
                    }
                }
                
                // Processar triângulos
                if (isset($mesh->triangles)) {
                    foreach ($mesh->triangles->children($ns) as $triangle) {
                        if ($triangle->getName() === 'triangle') {
                            $v1 = (int)$triangle['v1'];
                            $v2 = (int)$triangle['v2'];
                            $v3 = (int)$triangle['v3'];
                            
                            // Verificar se os índices são válidos
                            if ($v1 >= 0 && $v1 < count($vertices) && 
                                $v2 >= 0 && $v2 < count($vertices) && 
                                $v3 >= 0 && $v3 < count($vertices)) {
                                $triangles[] = [$v1, $v2, $v3];
                            }
                        }
                    }
                }
            }
        }
        
        // Atualizar métricas
        $metrics['polygon_count'] = count($triangles);
        
        // Calcular área de superfície e volume
        $totalArea = 0;
        $volume = 0;
        
        // Limitar processamento para arquivos muito grandes
        $maxTrianglesToProcess = min(count($triangles), 1000000); // Máximo 1 milhão de triângulos
        
        for ($i = 0; $i < $maxTrianglesToProcess; $i++) {
            $triangle = $triangles[$i];
            
            $v1 = $vertices[$triangle[0]];
            $v2 = $vertices[$triangle[1]];
            $v3 = $vertices[$triangle[2]];
            
            // Calcular área do triângulo
            $area = $this->calculateTriangleArea($v1, $v2, $v3);
            $totalArea += $area;
            
            // Contribuição para o volume
            $volume += $this->calculateSignedVolumeOfTriangle($v1, $v2, $v3);
        }
        
        $metrics['surface_area'] = $totalArea;
        $metrics['volume'] = abs($volume); // Volume deve ser positivo
        
        // Calcular dimensões
        $metrics['dimensions']['width'] = $maxX - $minX;
        $metrics['dimensions']['height'] = $maxY - $minY;
        $metrics['dimensions']['depth'] = $maxZ - $minZ;
        
        // Calcular volume da caixa delimitadora
        $metrics['bounding_box_volume'] = 
            $metrics['dimensions']['width'] * 
            $metrics['dimensions']['height'] * 
            $metrics['dimensions']['depth'];
        
        // Estimativa de espaços vazios (razão entre volume e bounding box)
        if ($metrics['bounding_box_volume'] > 0) {
            $metrics['hollow_spaces'] = 1 - ($metrics['volume'] / $metrics['bounding_box_volume']);
        }
        
        // Estimativa simples de balanços e paredes finas
        // Valores baseados em heurísticas
        $metrics['overhangs'] = min(1.0, count($triangles) / 50000);
        $metrics['thin_walls'] = min(1.0, max(0, 0.5 - ($metrics['volume'] / $metrics['surface_area'] / 10)));
        
        return $metrics;
    }
    
    /**
     * Extrai métricas de um arquivo GCode
     * 
     * @param string $filePath Caminho do arquivo GCode
     * @return array Métricas extraídas
     */
    private function extractMetricsFromGCode(string $filePath): array {
        // Verificar se é possível ler o arquivo
        if (!is_readable($filePath)) {
            throw new Exception("Arquivo GCode não pode ser lido: {$filePath}");
        }
        
        // Inicializar métricas
        $metrics = [
            'polygon_count' => 0,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0,
            'overhangs' => 0,
            'thin_walls' => 0,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0,
            'print_time' => 0,
            'filament_length' => 0,
            'layer_count' => 0,
            'layer_height' => 0
        ];
        
        // Abrir o arquivo
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Falha ao abrir o arquivo GCode: {$filePath}");
        }
        
        // Inicializar variáveis para extração de informações
        $minX = $minY = $minZ = PHP_FLOAT_MAX;
        $maxX = $maxY = $maxZ = PHP_FLOAT_MIN;
        $currentX = $currentY = $currentZ = 0;
        $layerCount = 0;
        $layerHeight = 0;
        $totalFilament = 0;
        $printTime = 0;
        $inComment = false;
        
        // Identificar padrões de comentários para diferentes slicers
        $pattern_cura = '/;TIME:(\d+)/i';            // Cura
        $pattern_slic3r = '/; estimated printing time.*?(\d+)h.*?(\d+)m.*?(\d+)s/i'; // Slic3r/PrusaSlicer
        $pattern_simplify = '/; Build time: (\d+) hours? (\d+) minutes?/i';         // Simplify3D
        $pattern_filament = '/; filament used \[mm\] = (\d+\.\d+)/i'; // Filamento usado (Slic3r)
        $pattern_layer = '/; layer (\d+),/i';        // Padrão de camada
        
        // Ler o arquivo linha por linha
        $lineCount = 0;
        $maxLines = 1000000; // Limite de 1 milhão de linhas para segurança
        
        while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
            $lineCount++;
            
            // Verificar comentários com metadados
            // Tempo de impressão (Cura)
            if (preg_match($pattern_cura, $line, $matches)) {
                $printTime = (int)$matches[1]; // Tempo em segundos
            }
            // Tempo de impressão (Slic3r)
            elseif (preg_match($pattern_slic3r, $line, $matches)) {
                $hours = (int)$matches[1];
                $minutes = (int)$matches[2];
                $seconds = (int)$matches[3];
                $printTime = $hours * 3600 + $minutes * 60 + $seconds;
            }
            // Tempo de impressão (Simplify3D)
            elseif (preg_match($pattern_simplify, $line, $matches)) {
                $hours = (int)$matches[1];
                $minutes = (int)$matches[2];
                $printTime = $hours * 3600 + $minutes * 60;
            }
            // Filamento usado
            elseif (preg_match($pattern_filament, $line, $matches)) {
                $totalFilament = (float)$matches[1];
            }
            // Número da camada
            elseif (preg_match($pattern_layer, $line, $matches)) {
                $currentLayer = (int)$matches[1];
                $layerCount = max($layerCount, $currentLayer + 1);
            }
            
            // Processar comandos G-code
            if (!$inComment && strpos($line, 'G') === 0) {
                // Extrair o tipo de comando G
                $parts = explode(' ', trim($line));
                $command = array_shift($parts);
                
                // Processar comando de movimento (G0/G1)
                if ($command === 'G0' || $command === 'G1') {
                    $newX = $currentX;
                    $newY = $currentY;
                    $newZ = $currentZ;
                    $hasE = false;
                    
                    // Extrair coordenadas
                    foreach ($parts as $part) {
                        $axis = $part[0];
                        $value = (float)substr($part, 1);
                        
                        switch ($axis) {
                            case 'X':
                                $newX = $value;
                                break;
                            case 'Y':
                                $newY = $value;
                                break;
                            case 'Z':
                                $newZ = $value;
                                if ($newZ > $currentZ) {
                                    // Possível nova camada
                                    if ($layerHeight === 0 && $newZ > 0) {
                                        $layerHeight = $newZ;
                                    }
                                }
                                break;
                            case 'E':
                                // Extrudo - ignoramos aqui pois já temos o total de filamentos
                                $hasE = true;
                                break;
                        }
                    }
                    
                    // Atualizar posição atual
                    $currentX = $newX;
                    $currentY = $newY;
                    $currentZ = $newZ;
                    
                    // Atualizar limites
                    $minX = min($minX, $currentX);
                    $minY = min($minY, $currentY);
                    $minZ = min($minZ, $currentZ);
                    
                    $maxX = max($maxX, $currentX);
                    $maxY = max($maxY, $currentY);
                    $maxZ = max($maxZ, $currentZ);
                }
            }
        }
        
        // Fechar o arquivo
        fclose($handle);
        
        // Estimativa de contagem de polígonos (1 polígono a cada 2mm de filamento, heurística)
        if ($totalFilament > 0) {
            $metrics['polygon_count'] = (int)($totalFilament / 2);
        } else {
            // Estimativa baseada no tamanho do arquivo (1 polígono a cada 10 bytes, heurística)
            $metrics['polygon_count'] = (int)(filesize($filePath) / 10);
        }
        
        // Atualizar métricas de dimensões
        $metrics['dimensions']['width'] = $maxX - $minX;
        $metrics['dimensions']['height'] = $maxY - $minY;
        $metrics['dimensions']['depth'] = $maxZ - $minZ;
        
        // Calcular volume da caixa delimitadora
        $metrics['bounding_box_volume'] = 
            $metrics['dimensions']['width'] * 
            $metrics['dimensions']['height'] * 
            $metrics['dimensions']['depth'];
        
        // Estimativa de volume (baseada no filamento e diâmetro típico)
        if ($totalFilament > 0) {
            // Assumindo filamento de 1.75mm e multiplicador de 0.95 para considerar sobre-extrudo
            $filamentCrossSectionArea = M_PI * pow(1.75 / 2, 2); // Área da seção transversal em mm²
            $metrics['volume'] = $totalFilament * $filamentCrossSectionArea * 0.95; // Volume em mm³
        } else {
            // Estimativa grosseira baseada no volume da caixa delimitadora e fator de preenchimento
            $metrics['volume'] = $metrics['bounding_box_volume'] * 0.25; // Assume 25% de preenchimento
        }
        
        // Estimativa de área de superfície
        if ($metrics['volume'] > 0) {
            // Relação volume/área típica
            $metrics['surface_area'] = pow($metrics['volume'], 2/3) * 6; // Aproximação simples
        }
        
        // Atualizar outras métricas
        $metrics['print_time'] = $printTime;
        $metrics['filament_length'] = $totalFilament;
        $metrics['layer_count'] = $layerCount > 0 ? $layerCount : (int)($maxZ / max(0.1, $layerHeight));
        $metrics['layer_height'] = $layerHeight;
        
        // Estimativa de espaços vazios (razão entre volume e bounding box)
        if ($metrics['bounding_box_volume'] > 0) {
            $metrics['hollow_spaces'] = 1 - ($metrics['volume'] / $metrics['bounding_box_volume']);
        }
        
        // Estimativa de balanços baseada na densidade de camadas e altura
        $metrics['overhangs'] = min(1.0, $metrics['layer_count'] / 500);
        
        // Estimativa de paredes finas baseada na relação volume/área
        if ($metrics['surface_area'] > 0) {
            $metrics['thin_walls'] = min(1.0, max(0, 0.5 - ($metrics['volume'] / $metrics['surface_area'] / 10)));
        }
        
        return $metrics;
    }
    
    /**
     * Estima métricas a partir dos metadados do modelo
     * 
     * @param array $metadata Metadados do modelo
     * @return array Métricas estimadas
     */
    private function estimateMetricsFromMetadata(array $metadata): array {
        // Inicializar métricas com valores padrão
        $metrics = [
            'polygon_count' => 0,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0,
            'overhangs' => 0,
            'thin_walls' => 0,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Extrair valores dos metadados, se disponíveis
        if (isset($metadata['polygon_count'])) {
            $metrics['polygon_count'] = (int)$metadata['polygon_count'];
        }
        
        if (isset($metadata['volume'])) {
            $metrics['volume'] = (float)$metadata['volume'];
        }
        
        if (isset($metadata['surface_area'])) {
            $metrics['surface_area'] = (float)$metadata['surface_area'];
        }
        
        if (isset($metadata['dimensions'])) {
            if (is_array($metadata['dimensions'])) {
                $metrics['dimensions'] = $metadata['dimensions'];
            }
        } else {
            // Tentar extrair dimensões individuais
            if (isset($metadata['width'])) {
                $metrics['dimensions']['width'] = (float)$metadata['width'];
            }
            
            if (isset($metadata['height'])) {
                $metrics['dimensions']['height'] = (float)$metadata['height'];
            }
            
            if (isset($metadata['depth'])) {
                $metrics['dimensions']['depth'] = (float)$metadata['depth'];
            }
        }
        
        // Calcular volume da caixa delimitadora a partir das dimensões
        $metrics['bounding_box_volume'] = 
            $metrics['dimensions']['width'] * 
            $metrics['dimensions']['height'] * 
            $metrics['dimensions']['depth'];
        
        // Estimativas de valores com base no que temos
        
        // Se temos volume e bounding box, estimar espaços vazios
        if ($metrics['volume'] > 0 && $metrics['bounding_box_volume'] > 0) {
            $metrics['hollow_spaces'] = 1 - ($metrics['volume'] / $metrics['bounding_box_volume']);
        } 
        // Caso contrário, usar valor padrão
        else {
            $metrics['hollow_spaces'] = 0.3; // Valor médio típico
        }
        
        // Estimativa de balanços e paredes finas
        $metrics['overhangs'] = isset($metadata['overhangs']) ? 
            (float)$metadata['overhangs'] : 
            min(1.0, max(0, $metrics['polygon_count'] / 50000));
        
        $metrics['thin_walls'] = isset($metadata['thin_walls']) ? 
            (float)$metadata['thin_walls'] : 
            min(1.0, max(0, 0.5 - ($metrics['volume'] / max(1, $metrics['surface_area']) / 10)));
        
        return $metrics;
    }
    
    /**
     * Calcula a área de um triângulo 3D
     * 
     * @param array $v1 Primeiro vértice [x,y,z]
     * @param array $v2 Segundo vértice [x,y,z]
     * @param array $v3 Terceiro vértice [x,y,z]
     * @return float Área do triângulo
     */
    private function calculateTriangleArea(array $v1, array $v2, array $v3): float {
        // Calcular vetores dos lados do triângulo
        $a = [
            $v2['x'] - $v1['x'],
            $v2['y'] - $v1['y'],
            $v2['z'] - $v1['z']
        ];
        
        $b = [
            $v3['x'] - $v1['x'],
            $v3['y'] - $v1['y'],
            $v3['z'] - $v1['z']
        ];
        
        // Calcular produto vetorial
        $crossProduct = [
            $a[1] * $b[2] - $a[2] * $b[1],
            $a[2] * $b[0] - $a[0] * $b[2],
            $a[0] * $b[1] - $a[1] * $b[0]
        ];
        
        // Calcular comprimento do produto vetorial (magnitude)
        $magnitude = sqrt(
            $crossProduct[0] * $crossProduct[0] + 
            $crossProduct[1] * $crossProduct[1] + 
            $crossProduct[2] * $crossProduct[2]
        );
        
        // Área = metade da magnitude do produto vetorial
        return $magnitude / 2;
    }
    
    /**
     * Calcula o volume assinado de um tetraedro formado por um triângulo e a origem
     * Usado para calcular o volume total de uma malha fechada
     * 
     * @param array $v1 Primeiro vértice [x,y,z]
     * @param array $v2 Segundo vértice [x,y,z]
     * @param array $v3 Terceiro vértice [x,y,z]
     * @return float Volume assinado do tetraedro
     */
    private function calculateSignedVolumeOfTriangle(array $v1, array $v2, array $v3): float {
        // Calcular volume assinado usando o produto misto
        return (
            $v1['x'] * ($v2['y'] * $v3['z'] - $v3['y'] * $v2['z']) +
            $v1['y'] * ($v2['z'] * $v3['x'] - $v3['z'] * $v2['x']) +
            $v1['z'] * ($v2['x'] * $v3['y'] - $v3['x'] * $v2['y'])
        ) / 6.0;
    }
    
    /**
     * Valida e normaliza métricas extraídas
     * 
     * @param array $metrics Métricas a serem validadas
     * @return void
     */
    private function validateMetrics(array &$metrics): void {
        // Garantir que todas as métricas tenham valores válidos e positivos
        
        // Polygon count deve ser um inteiro positivo
        $metrics['polygon_count'] = max(0, (int)($metrics['polygon_count']));
        
        // Volume deve ser um número positivo
        $metrics['volume'] = max(0, (float)($metrics['volume']));
        
        // Área de superfície deve ser um número positivo
        $metrics['surface_area'] = max(0, (float)($metrics['surface_area']));
        
        // Espaços vazios deve estar entre 0 e 1
        $metrics['hollow_spaces'] = max(0, min(1, (float)($metrics['hollow_spaces'])));
        
        // Balanços deve estar entre 0 e 1
        $metrics['overhangs'] = max(0, min(1, (float)($metrics['overhangs'])));
        
        // Paredes finas deve estar entre 0 e 1
        $metrics['thin_walls'] = max(0, min(1, (float)($metrics['thin_walls'])));
        
        // Dimensões devem ser positivas
        $metrics['dimensions']['width'] = max(0, (float)($metrics['dimensions']['width']));
        $metrics['dimensions']['height'] = max(0, (float)($metrics['dimensions']['height']));
        $metrics['dimensions']['depth'] = max(0, (float)($metrics['dimensions']['depth']));
        
        // Volume da caixa delimitadora
        $metrics['bounding_box_volume'] = max(0, (float)($metrics['bounding_box_volume']));
        
        // Se o volume da caixa delimitadora for zero, calcular a partir das dimensões
        if ($metrics['bounding_box_volume'] <= 0 && 
            $metrics['dimensions']['width'] > 0 && 
            $metrics['dimensions']['height'] > 0 && 
            $metrics['dimensions']['depth'] > 0) {
            
            $metrics['bounding_box_volume'] = 
                $metrics['dimensions']['width'] * 
                $metrics['dimensions']['height'] * 
                $metrics['dimensions']['depth'];
        }
    }
    
    /**
     * Calcula a pontuação de complexidade do modelo com base nas métricas
     * 
     * @param array $metrics Métricas do modelo
     * @return float Pontuação de complexidade (0-100)
     */
    private function calculateComplexityScore(array $metrics): float {
        // Inicializar pontuação
        $score = 0;
        
        // Normalizar contagem de polígonos (0-1)
        // Considerando que 500k polígonos é bastante complexo
        $normalizedPolygonCount = min(1, $metrics['polygon_count'] / 500000);
        
        // Normalizar volume (0-1)
        // Volume típico de impressão 3D vai até 125000 mm³ (5x5x5 cm)
        $normalizedVolume = min(1, $metrics['volume'] / 125000);
        
        // Normalizar área de superfície (0-1)
        // Área típica de impressão 3D vai até 15000 mm² (150 cm²)
        $normalizedSurfaceArea = min(1, $metrics['surface_area'] / 15000);
        
        // Os outros fatores já estão normalizados entre 0-1
        
        // Calcular pontuação ponderada
        $score += $normalizedPolygonCount * $this->metricWeights['polygon_count'];
        $score += $normalizedVolume * $this->metricWeights['volume'];
        $score += $normalizedSurfaceArea * $this->metricWeights['surface_area'];
        $score += $metrics['hollow_spaces'] * $this->metricWeights['hollow_spaces'];
        $score += $metrics['overhangs'] * $this->metricWeights['overhangs'];
        $score += $metrics['thin_walls'] * $this->metricWeights['thin_walls'];
        
        // Converter para escala 0-100
        $score = $score * self::MAX_COMPLEXITY_SCORE;
        
        // Garantir limites
        return max(0, min(self::MAX_COMPLEXITY_SCORE, $score));
    }
    
    /**
     * Estima o tempo de impressão com base nas métricas e pontuação de complexidade
     * 
     * @param array $metrics Métricas do modelo
     * @param float $complexityScore Pontuação de complexidade (0-100)
     * @return int Tempo estimado de impressão em minutos
     */
    private function estimatePrintTime(array $metrics, float $complexityScore): int {
        // Tempo base proporcional ao volume
        $baseTime = $metrics['volume'] * $this->basePrintTimeRate / 60; // Converter para minutos
        
        // Fator de complexidade (1.0 - 2.5)
        // Quanto maior a complexidade, mais tempo por unidade de volume
        $complexityFactor = 1.0 + ($complexityScore / self::MAX_COMPLEXITY_SCORE) * 1.5;
        
        // Calcular tempo ajustado
        $adjustedTime = $baseTime * $complexityFactor;
        
        // Fator de espaços vazios (mais espaços vazios = menos tempo)
        $hollowFactor = 1.0 - ($metrics['hollow_spaces'] * 0.3);
        
        // Fator de balanços (mais balanços = mais tempo para suportes)
        $overhangFactor = 1.0 + ($metrics['overhangs'] * 0.5);
        
        // Fator de paredes finas (mais paredes finas = mais tempo para detalhes)
        $thinWallFactor = 1.0 + ($metrics['thin_walls'] * 0.4);
        
        // Aplicar fatores
        $finalTime = $adjustedTime * $hollowFactor * $overhangFactor * $thinWallFactor;
        
        // Garantir tempo mínimo
        return max($this->minimumPrintTime, (int)round($finalTime));
    }
    
    /**
     * Calcula o custo de material para imprimir o modelo
     * 
     * @param array $metrics Métricas do modelo
     * @return float Custo do material em reais
     */
    private function calculateMaterialCost(array $metrics): float {
        // Custo baseado no volume
        $cost = $metrics['volume'] * $this->baseMaterialCost / 1000; // Converter para cm³
        
        // Garantir valor mínimo
        return max(0.50, $cost); // Mínimo de R$ 0,50
    }
    
    /**
     * Calcula o custo de operação da impressora para o tempo estimado
     * 
     * @param int $printTimeMinutes Tempo estimado de impressão em minutos
     * @return float Custo de operação em reais
     */
    private function calculatePrintingCost(int $printTimeMinutes): float {
        // Converter para horas
        $printTimeHours = $printTimeMinutes / 60;
        
        // Calcular custo baseado no tempo
        $cost = $printTimeHours * $this->hourlyPrintCost;
        
        // Garantir valor mínimo
        return max(1.0, $cost); // Mínimo de R$ 1,00
    }
    
    /**
     * Obtém o resultado da análise mais recente
     * 
     * @return array|null Resultados da análise ou null se não houver
     */
    public function getAnalysisResults(): ?array {
        return $this->analysisResults;
    }
    
    /**
     * Obtém uma métrica específica dos resultados da análise
     * 
     * @param string $metricName Nome da métrica
     * @return mixed Valor da métrica ou null se não existir
     */
    public function getMetric(string $metricName) {
        if ($this->analysisResults === null) {
            return null;
        }
        
        if (isset($this->analysisResults[$metricName])) {
            return $this->analysisResults[$metricName];
        }
        
        if (isset($this->analysisResults['metrics'][$metricName])) {
            return $this->analysisResults['metrics'][$metricName];
        }
        
        return null;
    }
}