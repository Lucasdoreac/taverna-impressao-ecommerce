<?php
/**
 * ModelAnalysisOptimizer
 * 
 * Otimiza o processo de análise de modelos 3D implementando:
 * 1. Processamento em lotes (batch processing)
 * 2. Early-stopping para modelos complexos
 * 3. Otimização de estruturas de dados
 * 4. Cache de resultados parciais
 * 
 * Este componente trabalha em conjunto com ModelComplexityAnalyzer para
 * melhorar significativamente o desempenho da análise de modelos grandes.
 * 
 * @package App\Lib\Analysis\Optimizer
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */
class ModelAnalysisOptimizer {
    /**
     * ModelComplexityAnalyzer a ser otimizado
     * 
     * @var ModelComplexityAnalyzer
     */
    private $analyzer;
    
    /**
     * Tamanho do lote para processamento
     * 
     * @var int
     */
    private $batchSize = 10000;
    
    /**
     * Limite de triângulos para early-stopping
     * 
     * @var int
     */
    private $earlyStoppingThreshold = 100000;
    
    /**
     * Nível de precisão para early-stopping (0.0-1.0)
     * 
     * @var float
     */
    private $precisionLevel = 0.95;
    
    /**
     * Armazenamento de resultados parciais
     * 
     * @var array
     */
    private $partialResults = [];
    
    /**
     * Informações de performance
     * 
     * @var array
     */
    private $performanceMetrics = [
        'start_time' => 0,
        'end_time' => 0,
        'memory_start' => 0,
        'memory_peak' => 0,
        'processed_triangles' => 0,
        'total_triangles' => 0,
        'batches_processed' => 0,
        'early_stopped' => false
    ];
    
    /**
     * Construtor
     * 
     * @param ModelComplexityAnalyzer $analyzer Instância do analisador a ser otimizado
     * @param array $config Configurações opcionais
     */
    public function __construct(ModelComplexityAnalyzer $analyzer, array $config = []) {
        $this->analyzer = $analyzer;
        
        // Aplicar configurações personalizadas
        if (!empty($config)) {
            if (isset($config['batchSize']) && is_int($config['batchSize']) && $config['batchSize'] > 0) {
                $this->batchSize = $config['batchSize'];
            }
            
            if (isset($config['earlyStoppingThreshold']) && is_int($config['earlyStoppingThreshold']) && $config['earlyStoppingThreshold'] > 0) {
                $this->earlyStoppingThreshold = $config['earlyStoppingThreshold'];
            }
            
            if (isset($config['precisionLevel']) && is_float($config['precisionLevel']) && $config['precisionLevel'] > 0 && $config['precisionLevel'] <= 1.0) {
                $this->precisionLevel = $config['precisionLevel'];
            }
        }
    }
    
    /**
     * Analisa um modelo 3D de forma otimizada
     * 
     * @param array $model Informações do modelo
     * @param bool $trackPerformance Se deve registrar métricas de performance
     * @return array Resultados da análise de complexidade
     * @throws Exception Se ocorrer um erro durante a análise
     */
    public function analyzeModelOptimized(array $model, bool $trackPerformance = false): array {
        // Registrar métricas de início, se solicitado
        if ($trackPerformance) {
            $this->startPerformanceTracking();
        }
        
        try {
            // Validar modelo e determinar abordagem de otimização
            $modelInfo = $this->validateAndPreprocessModel($model);
            
            // Decidir estratégia com base no tamanho do modelo
            if ($modelInfo['estimated_triangles'] <= $this->batchSize) {
                // Modelo pequeno, usar análise direta
                $result = $this->analyzer->loadModel($model);
                
                if ($result) {
                    $analysisResult = $this->analyzer->analyzeComplexity();
                } else {
                    throw new Exception('Falha ao carregar modelo para análise');
                }
            } else {
                // Modelo grande, usar análise otimizada
                $analysisResult = $this->performOptimizedAnalysis($model, $modelInfo);
            }
            
            // Registrar métricas de fim, se solicitado
            if ($trackPerformance) {
                $this->finalizePerformanceTracking();
            }
            
            return $analysisResult;
        } catch (Exception $e) {
            // Em caso de erro, tentar abordagem de fallback se disponível
            if (isset($model['metadata']) && is_array($model['metadata'])) {
                // Usar metadados para estimativa
                $estimatedResult = $this->generateEstimatedResults($model['metadata']);
                
                // Adicionar flag indicando que é uma estimativa
                $estimatedResult['is_estimated'] = true;
                $estimatedResult['estimation_reason'] = 'Erro na análise detalhada: ' . $e->getMessage();
                
                return $estimatedResult;
            }
            
            // Se não for possível estimar, propagar exceção
            throw $e;
        } finally {
            // Limpar resultados parciais para liberar memória
            $this->partialResults = [];
        }
    }
    
    /**
     * Inicia rastreamento de métricas de performance
     */
    private function startPerformanceTracking(): void {
        $this->performanceMetrics = [
            'start_time' => microtime(true),
            'end_time' => 0,
            'memory_start' => memory_get_usage(true),
            'memory_peak' => 0,
            'processed_triangles' => 0,
            'total_triangles' => 0,
            'batches_processed' => 0,
            'early_stopped' => false
        ];
    }
    
    /**
     * Finaliza rastreamento de métricas de performance
     */
    private function finalizePerformanceTracking(): void {
        $this->performanceMetrics['end_time'] = microtime(true);
        $this->performanceMetrics['memory_peak'] = memory_get_peak_usage(true);
        
        // Calcular métricas adicionais
        $this->performanceMetrics['duration_seconds'] = 
            $this->performanceMetrics['end_time'] - $this->performanceMetrics['start_time'];
        
        $this->performanceMetrics['memory_used_mb'] = 
            ($this->performanceMetrics['memory_peak'] - $this->performanceMetrics['memory_start']) / (1024 * 1024);
        
        if ($this->performanceMetrics['total_triangles'] > 0) {
            $this->performanceMetrics['completion_percentage'] = 
                ($this->performanceMetrics['processed_triangles'] / $this->performanceMetrics['total_triangles']) * 100;
        } else {
            $this->performanceMetrics['completion_percentage'] = 100;
        }
    }
    
    /**
     * Valida e pré-processa o modelo para determinar a estratégia de otimização
     * 
     * @param array $model Informações do modelo
     * @return array Informações para otimização
     * @throws Exception Se o modelo for inválido
     */
    private function validateAndPreprocessModel(array $model): array {
        $modelInfo = [
            'estimated_triangles' => 0,
            'file_type' => '',
            'file_size' => 0,
            'supports_batch_processing' => false
        ];
        
        // Verificar parâmetros obrigatórios
        if (!isset($model['file_path']) || !file_exists($model['file_path'])) {
            throw new Exception('Caminho do arquivo do modelo inválido ou não encontrado');
        }
        
        // Determinar tipo de arquivo
        if (!isset($model['file_type'])) {
            // Extrair do caminho
            $extension = pathinfo($model['file_path'], PATHINFO_EXTENSION);
            $modelInfo['file_type'] = strtolower($extension);
        } else {
            $modelInfo['file_type'] = strtolower($model['file_type']);
        }
        
        // Verificar se o tipo é suportado
        $supportedFormats = ['stl', 'obj', '3mf', 'gcode'];
        if (!in_array($modelInfo['file_type'], $supportedFormats)) {
            throw new Exception('Formato de arquivo não suportado para análise de complexidade');
        }
        
        // Verificar tamanho do arquivo
        $modelInfo['file_size'] = filesize($model['file_path']);
        if ($modelInfo['file_size'] <= 0) {
            throw new Exception('Arquivo do modelo vazio ou inválido');
        }
        
        // Verificar tamanho máximo (100MB por segurança)
        if ($modelInfo['file_size'] > 100 * 1024 * 1024) {
            throw new Exception('Arquivo do modelo excede o tamanho máximo permitido para análise');
        }
        
        // Estimar número de triângulos com base no tipo e tamanho do arquivo
        $modelInfo['estimated_triangles'] = $this->estimateTriangleCount($modelInfo['file_type'], $modelInfo['file_size']);
        
        // Determinar se o formato suporta processamento em lotes
        $modelInfo['supports_batch_processing'] = $this->doesFormatSupportBatchProcessing($modelInfo['file_type']);
        
        return $modelInfo;
    }
    
    /**
     * Estima o número de triângulos com base no tipo e tamanho do arquivo
     * 
     * @param string $fileType Tipo do arquivo
     * @param int $fileSize Tamanho do arquivo em bytes
     * @return int Número estimado de triângulos
     */
    private function estimateTriangleCount(string $fileType, int $fileSize): int {
        switch ($fileType) {
            case 'stl':
                // STL binário: 50 bytes por triângulo (84 bytes de cabeçalho)
                if ($fileSize > 84) {
                    return (int)(($fileSize - 84) / 50);
                }
                return 0;
                
            case 'obj':
                // OBJ: aproximadamente 100 bytes por triângulo (estimativa)
                return (int)($fileSize / 100);
                
            case '3mf':
                // 3MF: aproximadamente 200 bytes por triângulo (estimativa para arquivo compactado)
                return (int)($fileSize / 200);
                
            case 'gcode':
                // GCode: estimativa mais complexa, usar valor conservador
                return (int)($fileSize / 500);
                
            default:
                // Valor padrão conservador
                return (int)($fileSize / 200);
        }
    }
    
    /**
     * Verifica se o formato de arquivo suporta processamento em lotes
     * 
     * @param string $fileType Tipo do arquivo
     * @return bool True se suportar processamento em lotes
     */
    private function doesFormatSupportBatchProcessing(string $fileType): bool {
        // Formatos que suportam leitura sequencial eficiente
        $batchSupportedFormats = ['stl', 'obj'];
        
        return in_array($fileType, $batchSupportedFormats);
    }
    
    /**
     * Executa análise otimizada para modelos grandes
     * 
     * @param array $model Informações do modelo
     * @param array $modelInfo Informações pré-processadas do modelo
     * @return array Resultados da análise
     * @throws Exception Se ocorrer um erro durante a análise
     */
    private function performOptimizedAnalysis(array $model, array $modelInfo): array {
        // Definir métricas de performance para modelo grande
        $this->performanceMetrics['total_triangles'] = $modelInfo['estimated_triangles'];
        
        // Determinar a abordagem com base no suporte a processamento em lotes
        if ($modelInfo['supports_batch_processing']) {
            // Usar processamento em lotes para formatos suportados
            $result = $this->processByBatches($model, $modelInfo);
        } else {
            // Para formatos que não suportam lotes facilmente, usar amostragem
            $result = $this->processBySampling($model, $modelInfo);
        }
        
        return $result;
    }
    
    /**
     * Processa o modelo em lotes para reduzir uso de memória
     * 
     * @param array $model Informações do modelo
     * @param array $modelInfo Informações pré-processadas do modelo
     * @return array Resultados da análise
     * @throws Exception Se ocorrer um erro durante o processamento
     */
    private function processByBatches(array $model, array $modelInfo): array {
        // Inicializar métricas acumuladas
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
        
        // Inicializar limites para dimensões
        $minX = $minY = $minZ = PHP_FLOAT_MAX;
        $maxX = $maxY = $maxZ = PHP_FLOAT_MIN;
        
        // Abrir arquivo para leitura
        $handle = $this->openModelFile($model['file_path'], $modelInfo['file_type']);
        
        // Processar em lotes baseado no tipo de arquivo
        switch ($modelInfo['file_type']) {
            case 'stl':
                $result = $this->processBatchesFromSTL($handle, $metrics, $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
                break;
                
            case 'obj':
                $result = $this->processBatchesFromOBJ($handle, $metrics, $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
                break;
                
            default:
                // Não deveria chegar aqui devido à verificação em doesFormatSupportBatchProcessing
                throw new Exception("Formato não suporta processamento em lotes: {$modelInfo['file_type']}");
        }
        
        // Fechar o arquivo
        fclose($handle);
        
        // Processar dimensões e outros cálculos finais
        $result['dimensions']['width'] = $maxX - $minX;
        $result['dimensions']['height'] = $maxY - $minY;
        $result['dimensions']['depth'] = $maxZ - $minZ;
        
        // Calcular volume da caixa delimitadora
        $result['bounding_box_volume'] = 
            $result['dimensions']['width'] * 
            $result['dimensions']['height'] * 
            $result['dimensions']['depth'];
        
        // Ajustar valores para espaços vazios com base no volume e bounding box
        if ($result['bounding_box_volume'] > 0) {
            $result['hollow_spaces'] = 1 - ($result['volume'] / $result['bounding_box_volume']);
        }
        
        // Normalizar valores
        $this->normalizeMetrics($result);
        
        // Calcular complexidade e estimativas de tempo e custo
        $analysisResult = $this->finalizeAnalysisResults($result);
        
        return $analysisResult;
    }
    
    /**
     * Abre o arquivo do modelo para leitura
     * 
     * @param string $filePath Caminho do arquivo
     * @param string $fileType Tipo do arquivo
     * @return resource Handle do arquivo
     * @throws Exception Se não for possível abrir o arquivo
     */
    private function openModelFile(string $filePath, string $fileType): $resource {
        // Modo de abertura com base no tipo
        $mode = ($fileType == 'stl') ? 'rb' : 'r';
        
        $handle = fopen($filePath, $mode);
        if (!$handle) {
            throw new Exception("Falha ao abrir o arquivo: {$filePath}");
        }
        
        return $handle;
    }
    
    /**
     * Processa arquivo STL em lotes
     * 
     * @param resource $handle Handle do arquivo
     * @param array $metrics Métricas acumuladas
     * @param float $minX Limite X mínimo
     * @param float $minY Limite Y mínimo
     * @param float $minZ Limite Z mínimo
     * @param float $maxX Limite X máximo
     * @param float $maxY Limite Y máximo
     * @param float $maxZ Limite Z máximo
     * @return array Métricas processadas
     */
    private function processBatchesFromSTL($handle, array $metrics, float &$minX, float &$minY, float &$minZ, float &$maxX, float &$maxY, float &$maxZ): array {
        // Determinar se é binário ou ASCII
        $isBinary = $this->isSTLBinary($handle);
        
        // Reiniciar posição do arquivo
        rewind($handle);
        
        // Processar com base no tipo
        if ($isBinary) {
            return $this->processBatchesFromBinarySTL($handle, $metrics, $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        } else {
            return $this->processBatchesFromAsciiSTL($handle, $metrics, $minX, $minY, $minZ, $maxX, $maxY, $maxZ);
        }
    }
    
    /**
     * Verifica se um arquivo STL é binário
     * 
     * @param resource $handle Handle do arquivo
     * @return bool True se for binário
     */
    private function isSTLBinary($handle): bool {
        // Ler cabeçalho
        $header = fread($handle, 80);
        
        // Se for ASCII, deve começar com "solid"
        if (stripos($header, 'solid') === 0) {
            // Ler mais alguns bytes para verificar se é realmente ASCII
            $nextLine = fgets($handle);
            // Arquivos binários podem falsamente começar com "solid"
            if (stripos($nextLine, 'facet') !== false || stripos($nextLine, 'endsolid') !== false) {
                return false; // É ASCII
            }
        }
        
        return true; // É binário
    }
    
    /**
     * Processa arquivo STL binário em lotes
     * 
     * @param resource $handle Handle do arquivo
     * @param array $metrics Métricas acumuladas
     * @param float $minX Limite X mínimo
     * @param float $minY Limite Y mínimo
     * @param float $minZ Limite Z mínimo
     * @param float $maxX Limite X máximo
     * @param float $maxY Limite Y máximo
     * @param float $maxZ Limite Z máximo
     * @return array Métricas processadas
     */
    private function processBatchesFromBinarySTL($handle, array $metrics, float &$minX, float &$minY, float &$minZ, float &$maxX, float &$maxY, float &$maxZ): array {
        // Pular cabeçalho de 80 bytes
        fseek($handle, 80);
        
        // Ler número de triângulos (4 bytes)
        $data = fread($handle, 4);
        $triangleCount = unpack('V', $data)[1];
        
        // Atualizar métricas
        $metrics['polygon_count'] = $triangleCount;
        
        // Inicializar métricas acumuladas
        $totalArea = 0;
        $volume = 0;
        $processedTriangles = 0;
        $batchCount = 0;
        
        // Calcular número de lotes
        $totalBatches = ceil($triangleCount / $this->batchSize);
        
        // Processar em lotes
        while ($processedTriangles < $triangleCount) {
            // Determinar tamanho do lote atual
            $batchSize = min($this->batchSize, $triangleCount - $processedTriangles);
            
            // Atualizar contagem de lotes
            $batchCount++;
            $this->performanceMetrics['batches_processed'] = $batchCount;
            
            // Processar lote
            for ($i = 0; $i < $batchSize; $i++) {
                // Pular vetor normal (12 bytes)
                fseek($handle, 12, SEEK_CUR);
                
                // Ler coordenadas dos vértices (36 bytes)
                $vertexData = fread($handle, 36);
                
                if (strlen($vertexData) !== 36) {
                    break; // Fim do arquivo ou erro de leitura
                }
                
                // Descompactar coordenadas
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
                
                // Atualizar limites
                $minX = min($minX, $v1['x'], $v2['x'], $v3['x']);
                $minY = min($minY, $v1['y'], $v2['y'], $v3['y']);
                $minZ = min($minZ, $v1['z'], $v2['z'], $v3['z']);
                
                $maxX = max($maxX, $v1['x'], $v2['x'], $v3['x']);
                $maxY = max($maxY, $v1['y'], $v2['y'], $v3['y']);
                $maxZ = max($maxZ, $v1['z'], $v2['z'], $v3['z']);
                
                // Calcular área e volume
                $area = $this->calculateTriangleArea($v1, $v2, $v3);
                $totalArea += $area;
                
                $volume += $this->calculateSignedVolumeOfTriangle($v1, $v2, $v3);
                
                // Pular atributo (2 bytes)
                fseek($handle, 2, SEEK_CUR);
                
                // Incrementar contador
                $processedTriangles++;
            }
            
            // Atualizar métricas de performance
            $this->performanceMetrics['processed_triangles'] = $processedTriangles;
            
            // Verificar early-stopping após cada lote completo
            if ($this->shouldStopEarly($processedTriangles, $triangleCount, $batchCount, $totalBatches)) {
                // Marcar que parou mais cedo
                $this->performanceMetrics['early_stopped'] = true;
                break;
            }
        }
        
        // Atualizar métricas finais
        $metrics['surface_area'] = $totalArea;
        $metrics['volume'] = abs($volume); // Volume deve ser positivo
        
        // Estimar valores para métricas menos críticas
        $metrics['overhangs'] = min(1.0, $triangleCount / 50000);
        $metrics['thin_walls'] = min(1.0, max(0, 0.5 - ($metrics['volume'] / $totalArea / 10)));
        
        return $metrics;
    }
    
    /**
     * Processa arquivo STL ASCII em lotes
     * 
     * @param resource $handle Handle do arquivo
     * @param array $metrics Métricas acumuladas
     * @param float $minX Limite X mínimo
     * @param float $minY Limite Y mínimo
     * @param float $minZ Limite Z mínimo
     * @param float $maxX Limite X máximo
     * @param float $maxY Limite Y máximo
     * @param float $maxZ Limite Z máximo
     * @return array Métricas processadas
     */
    private function processBatchesFromAsciiSTL($handle, array $metrics, float &$minX, float &$minY, float &$minZ, float &$maxX, float &$maxY, float &$maxZ): array {
        // Inicializar métricas acumuladas
        $totalArea = 0;
        $volume = 0;
        $triangleCount = 0;
        $processedTriangles = 0;
        $batchCount = 0;
        $vertices = [];
        $currentVertex = 0;
        
        // Estimar triângulos totais (menos preciso para ASCII)
        $estimatedTriangles = (int)(filesize(stream_get_meta_data($handle)['uri']) / 500);
        
        // Limite de segurança para evitar loops infinitos
        $maxLines = 10000000; // 10 milhões de linhas
        $lineCount = 0;
        
        // Processar em lotes (por linha em vez de por triângulo)
        $batchLines = 0;
        $batchSize = $this->batchSize * 7; // Aproximadamente 7 linhas por triângulo
        
        // Processar arquivo linha por linha
        while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
            $lineCount++;
            $batchLines++;
            
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
                
                // Atualizar limites
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
                    $processedTriangles++;
                    
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
            
            // Verificar se completamos um lote de linhas
            if ($batchLines >= $batchSize) {
                $batchCount++;
                $batchLines = 0;
                
                // Atualizar métricas de performance
                $this->performanceMetrics['processed_triangles'] = $processedTriangles;
                $this->performanceMetrics['batches_processed'] = $batchCount;
                
                // Verificar early-stopping
                if ($this->shouldStopEarly($processedTriangles, $estimatedTriangles, $batchCount, $estimatedTriangles / $this->batchSize)) {
                    $this->performanceMetrics['early_stopped'] = true;
                    break;
                }
            }
        }
        
        // Atualizar métricas finais
        $metrics['polygon_count'] = $triangleCount;
        $metrics['surface_area'] = $totalArea;
        $metrics['volume'] = abs($volume); // Volume deve ser positivo
        
        // Estimar valores para métricas menos críticas
        $metrics['overhangs'] = min(1.0, $triangleCount / 50000);
        $metrics['thin_walls'] = min(1.0, max(0, 0.5 - ($metrics['volume'] / $totalArea / 10)));
        
        return $metrics;
    }
    
    /**
     * Processa arquivo OBJ em lotes
     * 
     * @param resource $handle Handle do arquivo
     * @param array $metrics Métricas acumuladas
     * @param float $minX Limite X mínimo
     * @param float $minY Limite Y mínimo
     * @param float $minZ Limite Z mínimo
     * @param float $maxX Limite X máximo
     * @param float $maxY Limite Y máximo
     * @param float $maxZ Limite Z máximo
     * @return array Métricas processadas
     */
    private function processBatchesFromOBJ($handle, array $metrics, float &$minX, float &$minY, float &$minZ, float &$maxX, float &$maxY, float &$maxZ): array {
        // Inicializar estruturas de dados
        $vertices = [];
        $faces = [];
        $triangleCount = 0;
        $processedVertices = 0;
        $processedFaces = 0;
        $batchCount = 0;
        
        // Estimar triângulos totais
        $estimatedTriangles = (int)(filesize(stream_get_meta_data($handle)['uri']) / 100);
        
        // Limite de segurança para evitar loops infinitos
        $maxLines = 10000000; // 10 milhões de linhas
        $lineCount = 0;
        
        // Processar em lotes (por linha)
        $batchLines = 0;
        $batchSize = $this->batchSize * 5; // Aproximadamente 5 linhas por face
        
        // Métricas acumuladas
        $totalArea = 0;
        $volume = 0;
        
        // Primeira passagem: Ler todos os vértices (necessário para referências das faces)
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
            
            // Processar apenas vértices nessa passagem
            if ($type === 'v') {
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
                    
                    $processedVertices++;
                    
                    // Atualizar limites
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $minZ = min($minZ, $z);
                    
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                    $maxZ = max($maxZ, $z);
                }
            }
            
            // Se não for vértice e já chegamos às faces, sair do loop
            if ($type === 'f') {
                break;
            }
        }
        
        // Reiniciar posição para processar as faces
        rewind($handle);
        $lineCount = 0;
        
        // Segunda passagem: Processar faces em lotes
        while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
            $lineCount++;
            $batchLines++;
            
            // Ignorar linhas em branco e comentários
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Extrair tipo de linha e dados
            $parts = preg_split('/\s+/', $line);
            $type = array_shift($parts);
            
            // Processar apenas faces nessa passagem
            if ($type === 'f') {
                $faceVertices = [];
                
                foreach ($parts as $part) {
                    // Extrair índice do vértice (primeiro número antes de '/')
                    $vertexIndex = (int)strtok($part, '/') - 1; // OBJ indexa a partir de 1
                    
                    // Verificar se o índice é válido
                    if ($vertexIndex >= 0 && $vertexIndex < count($vertices)) {
                        $faceVertices[] = $vertexIndex;
                    }
                }
                
                // Processar face se tiver pelo menos 3 vértices válidos
                if (count($faceVertices) >= 3) {
                    // Triangular a face (para faces com mais de 3 vértices)
                    for ($j = 2; $j < count($faceVertices); $j++) {
                        $v1 = $vertices[$faceVertices[0]];
                        $v2 = $vertices[$faceVertices[$j-1]];
                        $v3 = $vertices[$faceVertices[$j]];
                        
                        // Calcular área do triângulo
                        $area = $this->calculateTriangleArea($v1, $v2, $v3);
                        $totalArea += $area;
                        
                        // Contribuição para o volume
                        $volume += $this->calculateSignedVolumeOfTriangle($v1, $v2, $v3);
                        
                        // Incrementar contador de triângulos
                        $triangleCount++;
                    }
                    
                    $processedFaces++;
                }
            }
            
            // Verificar se completamos um lote de linhas
            if ($batchLines >= $batchSize) {
                $batchCount++;
                $batchLines = 0;
                
                // Atualizar métricas de performance
                $this->performanceMetrics['processed_triangles'] = $triangleCount;
                $this->performanceMetrics['batches_processed'] = $batchCount;
                
                // Verificar early-stopping
                if ($this->shouldStopEarly($triangleCount, $estimatedTriangles, $batchCount, $estimatedTriangles / $this->batchSize)) {
                    $this->performanceMetrics['early_stopped'] = true;
                    break;
                }
            }
        }
        
        // Atualizar métricas finais
        $metrics['polygon_count'] = $triangleCount;
        $metrics['surface_area'] = $totalArea;
        $metrics['volume'] = abs($volume); // Volume deve ser positivo
        
        // Estimar valores para métricas menos críticas
        $metrics['overhangs'] = min(1.0, $triangleCount / 50000);
        $metrics['thin_walls'] = min(1.0, max(0, 0.5 - ($metrics['volume'] / $totalArea / 10)));
        
        return $metrics;
    }
    
    /**
     * Determina se deve parar a análise mais cedo com base nos critérios configurados
     * 
     * @param int $processedItems Itens processados
     * @param int $totalItems Total de itens
     * @param int $currentBatch Lote atual
     * @param int $totalBatches Total de lotes
     * @return bool True se deve parar a análise
     */
    private function shouldStopEarly(int $processedItems, int $totalItems, int $currentBatch, int $totalBatches): bool {
        // Se não atingiu o limite de early-stopping, continuar
        if ($processedItems < $this->earlyStoppingThreshold) {
            return false;
        }
        
        // Verificar se processamos triângulos suficientes para uma aproximação precisa
        $percentProcessed = ($processedItems / $totalItems) * 100;
        
        // Se já processamos uma porcentagem significativa, podemos parar
        if ($percentProcessed >= ($this->precisionLevel * 100)) {
            return true;
        }
        
        // Se já processamos muitos lotes, considerar parar
        // Fator de proporção: mais preciso para modelos grandes
        $batchProportion = $currentBatch / $totalBatches;
        if ($batchProportion >= $this->precisionLevel && $processedItems > 200000) {
            return true;
        }
        
        // Verificar se estamos vendo estabilidade nas métricas
        if ($currentBatch > 5 && $this->areMetricsStabilizing()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica se as métricas estão estabilizando entre os lotes
     * 
     * @return bool True se as métricas estão estáveis o suficiente
     */
    private function areMetricsStabilizing(): bool {
        // Se não tivermos resultados parciais suficientes, não podemos avaliar estabilidade
        if (count($this->partialResults) < 3) {
            return false;
        }
        
        // Obter os três últimos resultados
        $lastResults = array_slice($this->partialResults, -3);
        
        // Verificar estabilidade da área de superfície
        $surfaceAreas = array_column($lastResults, 'surface_area');
        $maxArea = max($surfaceAreas);
        $minArea = min($surfaceAreas);
        
        if ($maxArea > 0) {
            $areaDifference = ($maxArea - $minArea) / $maxArea;
            // Se a diferença for menor que 2%, consideramos estável
            if ($areaDifference < 0.02) {
                return true;
            }
        }
        
        // Verificar estabilidade do volume
        $volumes = array_column($lastResults, 'volume');
        $maxVolume = max($volumes);
        $minVolume = min($volumes);
        
        if ($maxVolume > 0) {
            $volumeDifference = ($maxVolume - $minVolume) / $maxVolume;
            // Se a diferença for menor que 2%, consideramos estável
            if ($volumeDifference < 0.02) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Processa o modelo por amostragem para formatos que não suportam lotes
     * 
     * @param array $model Informações do modelo
     * @param array $modelInfo Informações pré-processadas do modelo
     * @return array Resultados da análise
     * @throws Exception Se ocorrer um erro durante o processamento
     */
    private function processBySampling(array $model, array $modelInfo): array {
        // Criar arquivos de amostra temporários para análise
        $samples = $this->createSamplesFromModel($model, $modelInfo);
        
        // Analisar cada amostra
        $sampleResults = [];
        foreach ($samples as $index => $samplePath) {
            try {
                // Criar modelo de amostra
                $sampleModel = $model;
                $sampleModel['file_path'] = $samplePath;
                
                // Analisar amostra usando o analisador original
                $this->analyzer->loadModel($sampleModel);
                $result = $this->analyzer->analyzeComplexity();
                
                // Armazenar resultado
                $sampleResults[] = $result;
                
                // Registrar progresso
                $this->performanceMetrics['processed_triangles'] += $result['metrics']['polygon_count'];
                $this->performanceMetrics['batches_processed'] = $index + 1;
            } catch (Exception $e) {
                // Ignorar erros em amostras individuais
                continue;
            } finally {
                // Limpar arquivo temporário
                @unlink($samplePath);
            }
        }
        
        // Combinar resultados das amostras
        $combinedResult = $this->combineSampleResults($sampleResults, $modelInfo);
        
        return $combinedResult;
    }
    
    /**
     * Cria arquivos de amostra do modelo original
     * 
     * @param array $model Informações do modelo
     * @param array $modelInfo Informações pré-processadas do modelo
     * @return array Caminhos dos arquivos de amostra
     * @throws Exception Se não for possível criar amostras
     */
    private function createSamplesFromModel(array $model, array $modelInfo): array {
        // Diretório temporário para as amostras
        $tempDir = sys_get_temp_dir();
        
        // Determinar número de amostras baseado no tamanho estimado
        $numSamples = min(5, max(3, ceil($modelInfo['estimated_triangles'] / 100000)));
        
        // Tipo específico de amostragem baseado no formato
        switch ($modelInfo['file_type']) {
            case '3mf':
                return $this->createSamplesFrom3MF($model['file_path'], $tempDir, $numSamples);
                
            case 'gcode':
                return $this->createSamplesFromGCode($model['file_path'], $tempDir, $numSamples);
                
            default:
                throw new Exception("Formato não suporta amostragem: {$modelInfo['file_type']}");
        }
    }
    
    /**
     * Cria amostras de um arquivo 3MF
     * 
     * @param string $filePath Caminho do arquivo 3MF
     * @param string $tempDir Diretório temporário
     * @param int $numSamples Número de amostras
     * @return array Caminhos dos arquivos de amostra
     */
    private function createSamplesFrom3MF(string $filePath, string $tempDir, int $numSamples): array {
        $samplePaths = [];
        
        // Verificar se a extensão ZIP está disponível (3MF é um arquivo ZIP)
        if (!class_exists('ZipArchive')) {
            throw new Exception("Extensão ZipArchive não disponível para processar arquivo 3MF");
        }
        
        // Abrir o arquivo 3MF como um ZIP
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Falha ao abrir o arquivo 3MF como ZIP: {$filePath}");
        }
        
        // Encontrar o arquivo 3D model dentro do 3MF
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
        
        // Encontrar objetos de malha e dividir entre as amostras
        $meshes = [];
        foreach ($xml->children($ns)->resources->children($ns) as $resource) {
            if ($resource->getName() === 'object' && isset($resource->mesh)) {
                $meshes[] = $resource;
            }
        }
        
        // Se houver menos malhas que o número de amostras desejado, ajustar
        $numSamples = min($numSamples, count($meshes));
        if ($numSamples <= 0) {
            return [];
        }
        
        // Dividir malhas entre as amostras
        $meshesPerSample = ceil(count($meshes) / $numSamples);
        
        // Criar arquivos de amostra
        for ($i = 0; $i < $numSamples; $i++) {
            // Índices de início e fim para este lote
            $startIndex = $i * $meshesPerSample;
            $endIndex = min(($i + 1) * $meshesPerSample, count($meshes));
            
            // Pular se não houver malhas para este lote
            if ($startIndex >= count($meshes)) {
                continue;
            }
            
            // Criar novo documento XML para a amostra
            $sampleXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><model xmlns="' . $ns . '"></model>');
            
            // Adicionar elementos necessários
            $sampleXml->addChild('resources');
            
            // Adicionar malhas deste lote
            for ($j = $startIndex; $j < $endIndex; $j++) {
                // Copiar nó da malha para o novo documento
                $this->simpleXmlCopyNode($meshes[$j], $sampleXml->resources);
            }
            
            // Adicionar seção build
            $sampleXml->addChild('build');
            
            // Gravar XML em arquivo temporário
            $samplePath = $tempDir . '/sample_' . $i . '_' . uniqid() . '.xml';
            $sampleXml->asXML($samplePath);
            
            $samplePaths[] = $samplePath;
        }
        
        return $samplePaths;
    }
    
    /**
     * Copia um nó de SimpleXML para outro
     * 
     * @param SimpleXMLElement $source Nó fonte
     * @param SimpleXMLElement $destination Nó destino
     */
    private function simpleXmlCopyNode($source, $destination) {
        // Criar novo nó no destino
        $new = $destination->addChild($source->getName(), (string)$source);
        
        // Copiar atributos
        foreach ($source->attributes() as $name => $value) {
            $new->addAttribute($name, (string)$value);
        }
        
        // Copiar filhos recursivamente
        foreach ($source->children() as $child) {
            $this->simpleXmlCopyNode($child, $new);
        }
    }
    
    /**
     * Cria amostras de um arquivo GCode
     * 
     * @param string $filePath Caminho do arquivo GCode
     * @param string $tempDir Diretório temporário
     * @param int $numSamples Número de amostras
     * @return array Caminhos dos arquivos de amostra
     */
    private function createSamplesFromGCode(string $filePath, string $tempDir, int $numSamples): array {
        $samplePaths = [];
        
        // Ler o arquivo para memória (GCode é texto)
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new Exception("Falha ao ler o arquivo GCode: {$filePath}");
        }
        
        // Encontrar camadas (geralmente delimitadas por comentários ou comandos específicos)
        $layers = $this->splitGCodeIntoLayers($contents);
        
        // Se houver menos camadas que o número de amostras desejado, ajustar
        $numSamples = min($numSamples, count($layers));
        if ($numSamples <= 0) {
            return [];
        }
        
        // Dividir camadas entre as amostras
        $layersPerSample = ceil(count($layers) / $numSamples);
        
        // Criar arquivos de amostra
        for ($i = 0; $i < $numSamples; $i++) {
            // Índices de início e fim para este lote
            $startIndex = $i * $layersPerSample;
            $endIndex = min(($i + 1) * $layersPerSample, count($layers));
            
            // Pular se não houver camadas para este lote
            if ($startIndex >= count($layers)) {
                continue;
            }
            
            // Combinar camadas para esta amostra
            $sampleContent = '';
            for ($j = $startIndex; $j < $endIndex; $j++) {
                $sampleContent .= $layers[$j];
            }
            
            // Gravar em arquivo temporário
            $samplePath = $tempDir . '/sample_' . $i . '_' . uniqid() . '.gcode';
            if (file_put_contents($samplePath, $sampleContent) === false) {
                throw new Exception("Falha ao escrever arquivo de amostra: {$samplePath}");
            }
            
            $samplePaths[] = $samplePath;
        }
        
        return $samplePaths;
    }
    
    /**
     * Divide um arquivo GCode em camadas
     * 
     * @param string $contents Conteúdo do arquivo GCode
     * @return array Conteúdo dividido por camadas
     */
    private function splitGCodeIntoLayers(string $contents): array {
        $layers = [];
        $currentLayer = '';
        $inLayer = false;
        
        // Padrões para identificar início de camada
        $layerPatterns = [
            '/^;LAYER:(\d+)/m',         // Cura
            '/^; layer (\d+),/m',       // Slic3r/PrusaSlicer
            '/^;BEFORE_LAYER_CHANGE/m', // Simplify3D
            '/^G0 Z(\d+\.\d+)/m'        // Movimento em Z (genérico)
        ];
        
        // Dividir em linhas
        $lines = explode("\n", $contents);
        
        foreach ($lines as $line) {
            // Verificar se é início de camada
            $isLayerStart = false;
            foreach ($layerPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $isLayerStart = true;
                    break;
                }
            }
            
            // Se encontramos início de nova camada
            if ($isLayerStart) {
                // Salvar camada atual, se existir
                if ($inLayer && !empty($currentLayer)) {
                    $layers[] = $currentLayer;
                }
                
                // Iniciar nova camada
                $currentLayer = $line . "\n";
                $inLayer = true;
            } 
            // Caso contrário, adicionar à camada atual
            else if ($inLayer) {
                $currentLayer .= $line . "\n";
            }
            // Se ainda não encontramos camada, adicionar ao cabeçalho
            else {
                if (empty($layers)) {
                    $layers[] = '';
                }
                $layers[0] .= $line . "\n";
            }
        }
        
        // Adicionar última camada, se existir
        if ($inLayer && !empty($currentLayer)) {
            $layers[] = $currentLayer;
        }
        
        return $layers;
    }
    
    /**
     * Combina resultados de múltiplas amostras
     * 
     * @param array $sampleResults Resultados das amostras
     * @param array $modelInfo Informações do modelo original
     * @return array Resultado combinado
     */
    private function combineSampleResults(array $sampleResults, array $modelInfo): array {
        // Se não houver resultados, retornar resultado padrão estimado
        if (empty($sampleResults)) {
            return $this->generateEstimatedResults(['file_size' => $modelInfo['file_size']]);
        }
        
        // Inicializar métricas combinadas
        $combinedMetrics = [
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
        
        // Inicializar limites
        $minX = $minY = $minZ = PHP_FLOAT_MAX;
        $maxX = $maxY = $maxZ = PHP_FLOAT_MIN;
        
        // Somar valores de todas as amostras
        foreach ($sampleResults as $result) {
            $metrics = $result['metrics'];
            
            // Acumular contagens
            $combinedMetrics['polygon_count'] += $metrics['polygon_count'];
            $combinedMetrics['volume'] += $metrics['volume'];
            $combinedMetrics['surface_area'] += $metrics['surface_area'];
            
            // Média ponderada para outras métricas
            $combinedMetrics['hollow_spaces'] += $metrics['hollow_spaces'] * $metrics['polygon_count'];
            $combinedMetrics['overhangs'] += $metrics['overhangs'] * $metrics['polygon_count'];
            $combinedMetrics['thin_walls'] += $metrics['thin_walls'] * $metrics['polygon_count'];
            
            // Atualizar limites para dimensões
            $minX = min($minX, $metrics['dimensions']['width'] * -0.5);
            $maxX = max($maxX, $metrics['dimensions']['width'] * 0.5);
            $minY = min($minY, $metrics['dimensions']['height'] * -0.5);
            $maxY = max($maxY, $metrics['dimensions']['height'] * 0.5);
            $minZ = min($minZ, $metrics['dimensions']['depth'] * -0.5);
            $maxZ = max($maxZ, $metrics['dimensions']['depth'] * 0.5);
        }
        
        // Normalizar métricas que usam média ponderada
        if ($combinedMetrics['polygon_count'] > 0) {
            $combinedMetrics['hollow_spaces'] /= $combinedMetrics['polygon_count'];
            $combinedMetrics['overhangs'] /= $combinedMetrics['polygon_count'];
            $combinedMetrics['thin_walls'] /= $combinedMetrics['polygon_count'];
        }
        
        // Calcular dimensões combinadas
        $combinedMetrics['dimensions']['width'] = $maxX - $minX;
        $combinedMetrics['dimensions']['height'] = $maxY - $minY;
        $combinedMetrics['dimensions']['depth'] = $maxZ - $minZ;
        
        // Calcular volume da caixa delimitadora
        $combinedMetrics['bounding_box_volume'] = 
            $combinedMetrics['dimensions']['width'] * 
            $combinedMetrics['dimensions']['height'] * 
            $combinedMetrics['dimensions']['depth'];
        
        // Escalar resultados com base na proporção do modelo original
        if ($modelInfo['estimated_triangles'] > 0 && $combinedMetrics['polygon_count'] > 0) {
            $scaleFactor = $modelInfo['estimated_triangles'] / $combinedMetrics['polygon_count'];
            
            // Aplicar escala para métricas relevantes
            $combinedMetrics['polygon_count'] = (int)($combinedMetrics['polygon_count'] * $scaleFactor);
            $combinedMetrics['volume'] *= $scaleFactor;
            $combinedMetrics['surface_area'] *= $scaleFactor;
            
            // Não escalar as métricas normalizadas (hollow_spaces, overhangs, thin_walls)
        }
        
        // Normalizar métricas finais
        $this->normalizeMetrics($combinedMetrics);
        
        // Calcular complexidade e estimativas de tempo e custo
        $analysisResult = $this->finalizeAnalysisResults($combinedMetrics);
        
        // Adicionar flag indicando amostragem
        $analysisResult['is_sampled'] = true;
        $analysisResult['sampling_info'] = [
            'samples_count' => count($sampleResults),
            'total_polygon_count' => array_sum(array_column(array_column($sampleResults, 'metrics'), 'polygon_count')),
            'estimated_total_polygons' => $modelInfo['estimated_triangles']
        ];
        
        return $analysisResult;
    }
    
    /**
     * Normaliza os valores de métricas para garantir consistência
     * 
     * @param array $metrics Métricas a serem normalizadas
     */
    private function normalizeMetrics(array &$metrics): void {
        // Garantir que todos os valores sejam válidos e positivos
        
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
     * Finaliza os resultados da análise calculando métricas adicionais
     * 
     * @param array $metrics Métricas básicas
     * @return array Resultados completos da análise
     */
    private function finalizeAnalysisResults(array $metrics): array {
        // Calcular pontuação de complexidade
        $complexityScore = $this->calculateComplexityScore($metrics);
        
        // Estimar tempo de impressão
        $printTime = $this->estimatePrintTime($metrics, $complexityScore);
        
        // Calcular custos
        $materialCost = $this->calculateMaterialCost($metrics);
        $printingCost = $this->calculatePrintingCost($printTime);
        
        // Construir resultado final
        $result = [
            'complexity_score' => $complexityScore,
            'metrics' => $metrics,
            'estimated_print_time_minutes' => $printTime,
            'material_cost' => $materialCost,
            'printing_cost' => $printingCost,
            'total_cost' => $materialCost + $printingCost,
            'analysis_timestamp' => time()
        ];
        
        // Adicionar métricas de performance, se disponíveis
        if ($this->performanceMetrics['start_time'] > 0) {
            $result['performance_metrics'] = $this->performanceMetrics;
        }
        
        return $result;
    }
    
    /**
     * Gera resultados estimados com base em metadados limitados
     * 
     * @param array $metadata Metadados disponíveis
     * @return array Resultados estimados
     */
    private function generateEstimatedResults(array $metadata): array {
        // Inicializar métricas estimadas
        $metrics = [
            'polygon_count' => 0,
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0.3, // Valor médio típico
            'overhangs' => 0.5,    // Valor médio
            'thin_walls' => 0.5,   // Valor médio
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Estimar contagem de polígonos com base no tamanho do arquivo
        if (isset($metadata['file_size'])) {
            // Estimativa grosseira: 1 polígono por 50 bytes em média
            $metrics['polygon_count'] = (int)($metadata['file_size'] / 50);
        }
        
        // Estimar dimensões com base na contagem de polígonos
        // Assumindo um objeto esférico com densidade média
        $estimatedRadius = pow($metrics['polygon_count'] / 1000, 1/3) * 10;
        $metrics['dimensions']['width'] = $estimatedRadius * 2;
        $metrics['dimensions']['height'] = $estimatedRadius * 2;
        $metrics['dimensions']['depth'] = $estimatedRadius * 2;
        
        // Estimar volume e área de superfície
        $metrics['bounding_box_volume'] = 
            $metrics['dimensions']['width'] * 
            $metrics['dimensions']['height'] * 
            $metrics['dimensions']['depth'];
        
        // Estimar volume do objeto (considerando espaços vazios)
        $metrics['volume'] = $metrics['bounding_box_volume'] * 0.7;
        
        // Estimar área de superfície (aproximação esférica)
        $metrics['surface_area'] = 4 * M_PI * pow($estimatedRadius, 2);
        
        // Calcular complexidade e outros resultados
        return $this->finalizeAnalysisResults($metrics);
    }
    
    /**
     * Calcula a pontuação de complexidade do modelo com base nas métricas
     * 
     * @param array $metrics Métricas do modelo
     * @return float Pontuação de complexidade (0-100)
     */
    private function calculateComplexityScore(array $metrics): float {
        // Pesos para diferentes métricas
        $weights = [
            'polygon_count' => 0.3,
            'volume' => 0.25,
            'surface_area' => 0.15,
            'hollow_spaces' => 0.1,
            'overhangs' => 0.1,
            'thin_walls' => 0.1
        ];
        
        // Normalizar valores
        
        // Contagem de polígonos (0-1)
        // Considerando que 500k polígonos é bastante complexo
        $normalizedPolygonCount = min(1, $metrics['polygon_count'] / 500000);
        
        // Volume (0-1)
        // Volume típico de impressão 3D vai até 125000 mm³ (5x5x5 cm)
        $normalizedVolume = min(1, $metrics['volume'] / 125000);
        
        // Área de superfície (0-1)
        // Área típica de impressão 3D vai até 15000 mm² (150 cm²)
        $normalizedSurfaceArea = min(1, $metrics['surface_area'] / 15000);
        
        // Calcular pontuação ponderada
        $score = 0;
        $score += $normalizedPolygonCount * $weights['polygon_count'];
        $score += $normalizedVolume * $weights['volume'];
        $score += $normalizedSurfaceArea * $weights['surface_area'];
        $score += $metrics['hollow_spaces'] * $weights['hollow_spaces'];
        $score += $metrics['overhangs'] * $weights['overhangs'];
        $score += $metrics['thin_walls'] * $weights['thin_walls'];
        
        // Converter para escala 0-100
        $score = $score * 100;
        
        // Garantir limites
        return max(0, min(100, $score));
    }
    
    /**
     * Estima o tempo de impressão com base nas métricas e pontuação de complexidade
     * 
     * @param array $metrics Métricas do modelo
     * @param float $complexityScore Pontuação de complexidade (0-100)
     * @return int Tempo estimado de impressão em minutos
     */
    private function estimatePrintTime(array $metrics, float $complexityScore): int {
        // Constantes de tempo
        $basePrintTimeRate = 12.0;  // 12 minutos por cm³ como base
        $minimumPrintTime = 30;     // Mínimo de 30 minutos
        
        // Tempo base proporcional ao volume
        $baseTime = $metrics['volume'] * $basePrintTimeRate / 60; // Converter para minutos
        
        // Fator de complexidade (1.0 - 2.5)
        // Quanto maior a complexidade, mais tempo por unidade de volume
        $complexityFactor = 1.0 + ($complexityScore / 100) * 1.5;
        
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
        return max($minimumPrintTime, (int)round($finalTime));
    }
    
    /**
     * Calcula o custo de material para imprimir o modelo
     * 
     * @param array $metrics Métricas do modelo
     * @return float Custo do material em reais
     */
    private function calculateMaterialCost(array $metrics): float {
        // Constantes de custo
        $baseMaterialCost = 0.5;    // R$ 0,50 por cm³ de material
        
        // Custo baseado no volume
        $cost = $metrics['volume'] * $baseMaterialCost / 1000; // Converter para cm³
        
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
        // Constantes de custo
        $hourlyPrintCost = 15.0;    // R$ 15,00 por hora de impressão
        
        // Converter para horas
        $printTimeHours = $printTimeMinutes / 60;
        
        // Calcular custo baseado no tempo
        $cost = $printTimeHours * $hourlyPrintCost;
        
        // Garantir valor mínimo
        return max(1.0, $cost); // Mínimo de R$ 1,00
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
     * Obtém as métricas de performance da análise
     * 
     * @return array Métricas de performance
     */
    public function getPerformanceMetrics(): array {
        return $this->performanceMetrics;
    }
    
    /**
     * Define o tamanho do lote para processamento
     * 
     * @param int $size Tamanho do lote
     * @return self Instância atual para encadeamento
     */
    public function setBatchSize(int $size): self {
        if ($size <= 0) {
            throw new Exception("Tamanho do lote deve ser positivo");
        }
        
        $this->batchSize = $size;
        return $this;
    }
    
    /**
     * Define o limite de triângulos para early-stopping
     * 
     * @param int $threshold Limite de triângulos
     * @return self Instância atual para encadeamento
     */
    public function setEarlyStoppingThreshold(int $threshold): self {
        if ($threshold <= 0) {
            throw new Exception("Limite de early-stopping deve ser positivo");
        }
        
        $this->earlyStoppingThreshold = $threshold;
        return $this;
    }
    
    /**
     * Define o nível de precisão para early-stopping
     * 
     * @param float $level Nível de precisão (0.0-1.0)
     * @return self Instância atual para encadeamento
     */
    public function setPrecisionLevel(float $level): self {
        if ($level <= 0 || $level > 1.0) {
            throw new Exception("Nível de precisão deve estar entre 0.0 e 1.0");
        }
        
        $this->precisionLevel = $level;
        return $this;
    }
}