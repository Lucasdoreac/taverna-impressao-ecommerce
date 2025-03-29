<?php
/**
 * ModelValidator - Classe para validação avançada de modelos 3D (STL/OBJ)
 * 
 * Esta classe fornece métodos para verificar se um modelo 3D é adequado para impressão,
 * realizando verificações de integridade da malha, dimensões, geometria, etc.
 */
class ModelValidator {
    /**
     * Resultado da última validação
     * @var array
     */
    private $lastValidationResult = [];
    
    /**
     * Caminho para o diretório temporário para processamento de arquivos
     * @var string
     */
    private $tempDir;
    
    /**
     * Configurações de validação
     * @var array
     */
    private $config = [
        'max_vertices' => 1000000,      // Número máximo de vértices
        'max_polygons' => 500000,       // Número máximo de polígonos
        'min_dimensions' => [1, 1, 1],  // Dimensões mínimas em mm [x, y, z]
        'max_dimensions' => [250, 250, 250], // Dimensões máximas em mm [x, y, z]
        'check_watertight' => true,     // Verificar se o modelo é estanque
        'check_manifold' => true,       // Verificar se todas as arestas têm exatamente 2 faces
        'check_normals' => true,        // Verificar orientação normal das faces
        'auto_repair' => false          // Tentar reparar problemas automaticamente
    ];
    
    /**
     * Construtor
     * 
     * @param array $config Configurações personalizadas de validação (opcional)
     */
    public function __construct($config = []) {
        // Mesclar configurações personalizadas
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        
        // Definir diretório temporário
        $this->tempDir = sys_get_temp_dir() . '/model_validator_' . uniqid();
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Executa todas as validações em um arquivo de modelo 3D
     * 
     * @param string $filePath Caminho completo para o arquivo do modelo
     * @return bool True se o modelo passou em todas as validações, False caso contrário
     */
    public function validate($filePath) {
        // Reiniciar resultado da validação
        $this->lastValidationResult = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'info' => [],
            'stats' => [],
            'repair_suggestions' => []
        ];
        
        // Verificar se o arquivo existe
        if (!file_exists($filePath)) {
            $this->addError('file_not_found', 'O arquivo do modelo não foi encontrado');
            return false;
        }
        
        // Obter a extensão do arquivo
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Verificar se a extensão é suportada
        if (!in_array($fileExtension, ['stl', 'obj'])) {
            $this->addError('unsupported_format', 'Formato de arquivo não suportado. Apenas STL e OBJ são aceitos.');
            return false;
        }
        
        // Executar validações básicas
        $this->validateFileIntegrity($filePath, $fileExtension);
        
        // Executar validações específicas para cada formato
        if ($fileExtension === 'stl') {
            $this->validateSTL($filePath);
        } elseif ($fileExtension === 'obj') {
            $this->validateOBJ($filePath);
        }
        
        // Verificar dimensões
        $this->validateDimensions($filePath, $fileExtension);
        
        // Limpar arquivos temporários
        $this->cleanupTempFiles();
        
        // Retornar resultado
        return $this->lastValidationResult['is_valid'];
    }
    
    /**
     * Obtém o resultado detalhado da última validação
     * 
     * @return array Resultado da validação
     */
    public function getValidationResult() {
        return $this->lastValidationResult;
    }
    
    /**
     * Verifica a integridade básica do arquivo
     * 
     * @param string $filePath Caminho para o arquivo
     * @param string $fileExtension Extensão do arquivo
     */
    private function validateFileIntegrity($filePath, $fileExtension) {
        // Verificar se o arquivo pode ser aberto para leitura
        if (!is_readable($filePath)) {
            $this->addError('file_not_readable', 'O arquivo não pode ser lido');
            return;
        }
        
        // Verificar tamanho do arquivo
        $fileSize = filesize($filePath);
        if ($fileSize == 0) {
            $this->addError('empty_file', 'O arquivo está vazio');
            return;
        }
        
        // Adicionar estatísticas básicas
        $this->lastValidationResult['stats']['file_size'] = $fileSize;
        $this->lastValidationResult['stats']['file_type'] = $fileExtension;
        
        // Verificar se é um arquivo binário ou ASCII
        $handle = fopen($filePath, 'r');
        $chunk = fread($handle, 1024);
        fclose($handle);
        
        $isBinary = false;
        for ($i = 0; $i < strlen($chunk); $i++) {
            if (ord($chunk[$i]) < 7 || (ord($chunk[$i]) > 14 && ord($chunk[$i]) < 32)) {
                $isBinary = true;
                break;
            }
        }
        
        $this->lastValidationResult['stats']['format'] = $isBinary ? 'binary' : 'ascii';
        
        // Se for STL, verificar se o cabeçalho está correto
        if ($fileExtension === 'stl' && $isBinary) {
            $handle = fopen($filePath, 'rb');
            $header = fread($handle, 84); // Header de 80 bytes + 4 bytes para o número de triângulos
            fclose($handle);
            
            // Verificar se o tamanho do arquivo é consistente com o número de triângulos
            $numTriangles = unpack('V', substr($header, 80, 4))[1];
            $expectedSize = 84 + ($numTriangles * 50); // Header + (triângulos * 50 bytes por triângulo)
            
            $this->lastValidationResult['stats']['triangles'] = $numTriangles;
            
            // Verificar se o número de triângulos é razoável
            if ($numTriangles > $this->config['max_polygons']) {
                $this->addError('too_many_polygons', "O modelo tem $numTriangles triângulos, o que excede o limite de " . $this->config['max_polygons']);
            }
            
            // Verificar se o tamanho do arquivo é consistente
            if (abs($fileSize - $expectedSize) > 100) { // Permitir uma pequena margem de erro
                $this->addError('file_size_mismatch', "O tamanho do arquivo não corresponde ao número de triângulos declarado. Esperado: $expectedSize bytes, Atual: $fileSize bytes.");
            }
        }
    }
    
    /**
     * Valida um arquivo STL
     * 
     * @param string $filePath Caminho para o arquivo STL
     */
    private function validateSTL($filePath) {
        // Esta é uma implementação básica. Para uma validação completa,
        // idealmente usaríamos uma biblioteca externa ou binário.
        
        // Analisar estatísticas básicas
        $fileContents = file_get_contents($filePath);
        $isBinary = strpos($fileContents, 'solid') !== 0;
        
        if ($isBinary) {
            // STL Binário
            $header = substr($fileContents, 0, 80);
            $numTriangles = unpack('V', substr($fileContents, 80, 4))[1];
            
            // Verificar consistência do número de triângulos
            $expectedSize = 84 + ($numTriangles * 50);
            if (strlen($fileContents) != $expectedSize) {
                $this->addError('corrupted_stl', 'O arquivo STL binário parece estar corrompido');
            }
            
            // Verificar se há triângulos com área zero
            $zeroAreaTriangles = 0;
            for ($i = 0; $i < $numTriangles; $i++) {
                $offset = 84 + ($i * 50);
                
                // Ler normal
                $nx = unpack('f', substr($fileContents, $offset, 4))[1];
                $ny = unpack('f', substr($fileContents, $offset + 4, 4))[1];
                $nz = unpack('f', substr($fileContents, $offset + 8, 4))[1];
                
                // Ler vértices
                $v1x = unpack('f', substr($fileContents, $offset + 12, 4))[1];
                $v1y = unpack('f', substr($fileContents, $offset + 16, 4))[1];
                $v1z = unpack('f', substr($fileContents, $offset + 20, 4))[1];
                
                $v2x = unpack('f', substr($fileContents, $offset + 24, 4))[1];
                $v2y = unpack('f', substr($fileContents, $offset + 28, 4))[1];
                $v2z = unpack('f', substr($fileContents, $offset + 32, 4))[1];
                
                $v3x = unpack('f', substr($fileContents, $offset + 36, 4))[1];
                $v3y = unpack('f', substr($fileContents, $offset + 40, 4))[1];
                $v3z = unpack('f', substr($fileContents, $offset + 44, 4))[1];
                
                // Verificar se o triângulo tem área zero (vértices colineares)
                // Simplificação: apenas verificar se 2 vértices são iguais
                if (($v1x == $v2x && $v1y == $v2y && $v1z == $v2z) ||
                    ($v2x == $v3x && $v2y == $v3y && $v2z == $v3z) ||
                    ($v3x == $v1x && $v3y == $v1y && $v3z == $v1z)) {
                    $zeroAreaTriangles++;
                }
            }
            
            // Adicionar estatísticas
            $this->lastValidationResult['stats']['triangles'] = $numTriangles;
            $this->lastValidationResult['stats']['zero_area_triangles'] = $zeroAreaTriangles;
            
            // Adicionar avisos se necessário
            if ($zeroAreaTriangles > 0) {
                $percentage = round(($zeroAreaTriangles / $numTriangles) * 100, 2);
                if ($percentage > 5) {
                    $this->addWarning('too_many_zero_area_triangles', "O modelo contém $zeroAreaTriangles triângulos com área zero ($percentage%)");
                } else {
                    $this->addInfo('zero_area_triangles', "O modelo contém $zeroAreaTriangles triângulos com área zero ($percentage%)");
                }
            }
            
        } else {
            // STL ASCII
            $lines = explode("\n", $fileContents);
            
            // Verificar se o arquivo começa com "solid" e termina com "endsolid"
            if (!preg_match('/^\s*solid\s+/i', $lines[0]) || !preg_match('/\s*endsolid\s*/i', end($lines))) {
                $this->addError('invalid_stl_ascii', 'O arquivo STL ASCII não possui a estrutura correta (solid/endsolid)');
            }
            
            // Contar triângulos
            $numTriangles = 0;
            $numFacets = 0;
            
            foreach ($lines as $line) {
                if (preg_match('/^\s*facet\s+/i', $line)) {
                    $numFacets++;
                }
                if (preg_match('/^\s*endfacet\s*$/i', $line)) {
                    $numTriangles++;
                }
            }
            
            // Verificar consistência
            if ($numFacets != $numTriangles) {
                $this->addError('inconsistent_stl_ascii', "Número inconsistente de facets ($numFacets) e endfacets ($numTriangles)");
            }
            
            // Adicionar estatísticas
            $this->lastValidationResult['stats']['triangles'] = $numTriangles;
            
            // Verificar número de polígonos
            if ($numTriangles > $this->config['max_polygons']) {
                $this->addError('too_many_polygons', "O modelo tem $numTriangles triângulos, o que excede o limite de " . $this->config['max_polygons']);
            }
        }
    }
    
    /**
     * Valida um arquivo OBJ
     * 
     * @param string $filePath Caminho para o arquivo OBJ
     */
    private function validateOBJ($filePath) {
        // Realizar uma análise básica do arquivo OBJ
        $contents = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $vertices = 0;
        $normals = 0;
        $texCoords = 0;
        $faces = 0;
        $materials = 0;
        
        foreach ($contents as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] == '#') {
                continue; // Ignorar linhas vazias e comentários
            }
            
            $parts = preg_split('/\s+/', $line);
            $type = $parts[0];
            
            switch ($type) {
                case 'v':  // Vértice
                    $vertices++;
                    break;
                case 'vn': // Normal
                    $normals++;
                    break;
                case 'vt': // Coordenada de textura
                    $texCoords++;
                    break;
                case 'f':  // Face
                    $faces++;
                    
                    // Verificar formato da face
                    for ($i = 1; $i < count($parts); $i++) {
                        if (!preg_match('/^\d+(\/((\d+)?)(\/(\d+)?))?$/', $parts[$i])) {
                            $this->addWarning('invalid_face_format', "Formato de face inválido: $line");
                        }
                    }
                    
                    // Verificar polígonos não-triangulares (mais de 4 vértices)
                    if (count($parts) > 5) {
                        $this->addWarning('non_triangular_face', "Face com " . (count($parts) - 1) . " vértices detectada: $line");
                    }
                    break;
                case 'mtllib':
                    $materials++;
                    break;
            }
        }
        
        // Verificar consistência básica
        if ($vertices == 0) {
            $this->addError('no_vertices', 'O arquivo OBJ não contém vértices');
        }
        
        if ($faces == 0) {
            $this->addError('no_faces', 'O arquivo OBJ não contém faces');
        }
        
        // Verificar limites
        if ($vertices > $this->config['max_vertices']) {
            $this->addError('too_many_vertices', "O modelo tem $vertices vértices, o que excede o limite de " . $this->config['max_vertices']);
        }
        
        if ($faces > $this->config['max_polygons']) {
            $this->addError('too_many_faces', "O modelo tem $faces faces, o que excede o limite de " . $this->config['max_polygons']);
        }
        
        // Adicionar estatísticas
        $this->lastValidationResult['stats']['vertices'] = $vertices;
        $this->lastValidationResult['stats']['normals'] = $normals;
        $this->lastValidationResult['stats']['texture_coords'] = $texCoords;
        $this->lastValidationResult['stats']['faces'] = $faces;
        $this->lastValidationResult['stats']['has_materials'] = ($materials > 0);
        
        // Verificar se há normals ou texture coords
        if ($normals == 0) {
            $this->addInfo('no_normals', 'O modelo não possui vetores normais. Isso pode afetar a renderização.');
        }
        
        if ($texCoords > 0) {
            $this->addInfo('has_textures', 'O modelo inclui coordenadas de textura, mas estas não serão utilizadas na impressão 3D.');
        }
    }
    
    /**
     * Valida as dimensões do modelo
     * 
     * @param string $filePath Caminho para o arquivo
     * @param string $fileExtension Extensão do arquivo
     */
    private function validateDimensions($filePath, $fileExtension) {
        // Obter dimensões aproximadas
        $dimensions = $this->estimateModelDimensions($filePath, $fileExtension);
        
        if ($dimensions) {
            // Verificar dimensões mínimas
            if ($dimensions['min_x'] < $this->config['min_dimensions'][0] ||
                $dimensions['min_y'] < $this->config['min_dimensions'][1] ||
                $dimensions['min_z'] < $this->config['min_dimensions'][2]) {
                
                $this->addWarning('model_too_small', sprintf(
                    'O modelo pode ser muito pequeno para impressão. Dimensões mínimas: (%.2f, %.2f, %.2f)mm',
                    $dimensions['min_x'], $dimensions['min_y'], $dimensions['min_z']
                ));
                
                $this->addRecommendation('scale_up_model', 'Considere aumentar a escala do modelo para melhorar a impressão.');
            }
            
            // Verificar dimensões máximas
            if ($dimensions['max_x'] > $this->config['max_dimensions'][0] ||
                $dimensions['max_y'] > $this->config['max_dimensions'][1] ||
                $dimensions['max_z'] > $this->config['max_dimensions'][2]) {
                
                $this->addWarning('model_too_large', sprintf(
                    'O modelo excede as dimensões máximas de impressão. Dimensões atuais: (%.2f, %.2f, %.2f)mm',
                    $dimensions['max_x'] - $dimensions['min_x'],
                    $dimensions['max_y'] - $dimensions['min_y'],
                    $dimensions['max_z'] - $dimensions['min_z']
                ));
                
                $this->addRecommendation('scale_down_model', 'Reduza a escala do modelo para caber na impressora.');
            }
            
            // Adicionar estatísticas
            $this->lastValidationResult['stats']['dimensions'] = [
                'width' => $dimensions['max_x'] - $dimensions['min_x'],
                'height' => $dimensions['max_y'] - $dimensions['min_y'],
                'depth' => $dimensions['max_z'] - $dimensions['min_z']
            ];
            
            $this->lastValidationResult['stats']['bounding_box'] = [
                'min' => [$dimensions['min_x'], $dimensions['min_y'], $dimensions['min_z']],
                'max' => [$dimensions['max_x'], $dimensions['max_y'], $dimensions['max_z']]
            ];
        } else {
            $this->addWarning('dimensions_calculation_failed', 'Não foi possível calcular as dimensões do modelo');
        }
    }
    
    /**
     * Estima as dimensões do modelo 3D
     * 
     * @param string $filePath Caminho para o arquivo
     * @param string $fileExtension Extensão do arquivo
     * @return array|null Dimensões do modelo ou null em caso de erro
     */
    private function estimateModelDimensions($filePath, $fileExtension) {
        $dimensions = [
            'min_x' => PHP_FLOAT_MAX,
            'min_y' => PHP_FLOAT_MAX,
            'min_z' => PHP_FLOAT_MAX,
            'max_x' => -PHP_FLOAT_MAX,
            'max_y' => -PHP_FLOAT_MAX,
            'max_z' => -PHP_FLOAT_MAX
        ];
        
        if ($fileExtension === 'stl') {
            // Analisar arquivo STL
            $fileContents = file_get_contents($filePath);
            $isBinary = strpos($fileContents, 'solid') !== 0;
            
            if ($isBinary) {
                // STL Binário
                $numTriangles = unpack('V', substr($fileContents, 80, 4))[1];
                
                for ($i = 0; $i < $numTriangles; $i++) {
                    $offset = 84 + ($i * 50) + 12; // Pular o header de 80 bytes, número de triângulos (4 bytes) e normal (12 bytes)
                    
                    // Ler os 3 vértices (9 floats = 36 bytes)
                    for ($j = 0; $j < 3; $j++) {
                        $x = unpack('f', substr($fileContents, $offset + ($j * 12), 4))[1];
                        $y = unpack('f', substr($fileContents, $offset + ($j * 12) + 4, 4))[1];
                        $z = unpack('f', substr($fileContents, $offset + ($j * 12) + 8, 4))[1];
                        
                        $dimensions['min_x'] = min($dimensions['min_x'], $x);
                        $dimensions['min_y'] = min($dimensions['min_y'], $y);
                        $dimensions['min_z'] = min($dimensions['min_z'], $z);
                        $dimensions['max_x'] = max($dimensions['max_x'], $x);
                        $dimensions['max_y'] = max($dimensions['max_y'], $y);
                        $dimensions['max_z'] = max($dimensions['max_z'], $z);
                    }
                }
            } else {
                // STL ASCII
                $lines = explode("\n", $fileContents);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, 'vertex') === 0) {
                        $parts = preg_split('/\s+/', $line);
                        if (count($parts) >= 4) {
                            $x = (float)$parts[1];
                            $y = (float)$parts[2];
                            $z = (float)$parts[3];
                            
                            $dimensions['min_x'] = min($dimensions['min_x'], $x);
                            $dimensions['min_y'] = min($dimensions['min_y'], $y);
                            $dimensions['min_z'] = min($dimensions['min_z'], $z);
                            $dimensions['max_x'] = max($dimensions['max_x'], $x);
                            $dimensions['max_y'] = max($dimensions['max_y'], $y);
                            $dimensions['max_z'] = max($dimensions['max_z'], $z);
                        }
                    }
                }
            }
        } elseif ($fileExtension === 'obj') {
            // Analisar arquivo OBJ
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'v ') === 0) {
                    $parts = preg_split('/\s+/', $line);
                    if (count($parts) >= 4) {
                        $x = (float)$parts[1];
                        $y = (float)$parts[2];
                        $z = (float)$parts[3];
                        
                        $dimensions['min_x'] = min($dimensions['min_x'], $x);
                        $dimensions['min_y'] = min($dimensions['min_y'], $y);
                        $dimensions['min_z'] = min($dimensions['min_z'], $z);
                        $dimensions['max_x'] = max($dimensions['max_x'], $x);
                        $dimensions['max_y'] = max($dimensions['max_y'], $y);
                        $dimensions['max_z'] = max($dimensions['max_z'], $z);
                    }
                }
            }
        }
        
        // Verificar se obtivemos dimensões válidas
        if ($dimensions['min_x'] === PHP_FLOAT_MAX || $dimensions['max_x'] === -PHP_FLOAT_MAX) {
            return null;
        }
        
        return $dimensions;
    }
    
    /**
     * Adiciona um erro à lista de resultados
     * 
     * @param string $code Código do erro
     * @param string $message Mensagem de erro
     */
    private function addError($code, $message) {
        $this->lastValidationResult['errors'][$code] = $message;
        $this->lastValidationResult['is_valid'] = false;
    }
    
    /**
     * Adiciona um aviso à lista de resultados
     * 
     * @param string $code Código do aviso
     * @param string $message Mensagem de aviso
     */
    private function addWarning($code, $message) {
        $this->lastValidationResult['warnings'][$code] = $message;
    }
    
    /**
     * Adiciona uma informação à lista de resultados
     * 
     * @param string $code Código da informação
     * @param string $message Mensagem informativa
     */
    private function addInfo($code, $message) {
        $this->lastValidationResult['info'][$code] = $message;
    }
    
    /**
     * Adiciona uma recomendação à lista de resultados
     * 
     * @param string $code Código da recomendação
     * @param string $message Mensagem de recomendação
     */
    private function addRecommendation($code, $message) {
        $this->lastValidationResult['repair_suggestions'][$code] = $message;
    }
    
    /**
     * Limpa os arquivos temporários
     */
    private function cleanupTempFiles() {
        // Remover arquivos temporários se o diretório existir
        if (file_exists($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }
    
    /**
     * Destrutor
     */
    public function __destruct() {
        $this->cleanupTempFiles();
    }
}
