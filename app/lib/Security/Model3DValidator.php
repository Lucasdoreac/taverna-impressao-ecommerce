<?php
/**
 * Model3DValidator - Classe especializada para validação de arquivos de modelos 3D
 * 
 * Esta classe fornece métodos para validar arquivos de modelos 3D (.stl, .obj, .3mf),
 * incluindo verificações estruturais, assinaturas de arquivo e análise de segurança.
 * 
 * @package     App\Lib\Security
 * @version     1.1.0
 * @author      Taverna da Impressão
 */
class Model3DValidator {
    /**
     * Tipos MIME permitidos para modelos 3D
     * 
     * @var array
     */
    private static $allowedMimeTypes = [
        'application/sla',
        'model/stl',
        'application/vnd.ms-pki.stl',
        'text/plain', // STL ASCII
        'application/object',
        'model/obj',
        'application/vnd.ms-package.3dmanufacturing-3dmodel+xml',
        'application/3mf',
        'application/zip' // Arquivos .3mf são essencialmente arquivos ZIP
    ];
    
    /**
     * Extensões permitidas para modelos 3D
     * 
     * @var array
     */
    private static $allowedExtensions = ['stl', 'obj', '3mf'];
    
    /**
     * Tamanho máximo padrão para arquivos (50MB)
     * 
     * @var int
     */
    private static $defaultMaxSize = 52428800; // 50MB
    
    /**
     * Assinaturas de bytes para arquivos 3D
     * 
     * @var array
     */
    private static $fileSignatures = [
        'stl_binary' => [
            'offset' => 0,
            'signature' => null, // STL binário não tem assinatura padrão fixa
            'mask' => null,
            'size' => 84, // Cabeçalho (80 bytes) + tamanho do contador de triângulos (4 bytes)
            'validation' => 'validateStlSignature'
        ],
        'stl_ascii' => [
            'offset' => 0,
            'signature' => 'solid',
            'mask' => null,
            'size' => 5
        ],
        'obj' => [
            'offset' => 0,
            'signature' => null, // OBJ pode começar com comentários
            'mask' => null,
            'validation' => 'validateObjSignature'
        ],
        '3mf' => [
            'offset' => 0,
            'signature' => 'PK', // Assinatura ZIP
            'mask' => null,
            'size' => 2
        ]
    ];
    
    /**
     * Valida um arquivo de modelo 3D
     * 
     * @param array $file Informações do arquivo ($_FILES['campo'])
     * @param array $options Opções adicionais de validação
     * @return array Resultado da validação ['valid' => bool, 'message' => string, 'data' => array]
     */
    public static function validate($file, array $options = []) {
        $result = [
            'valid' => false,
            'message' => '',
            'data' => []
        ];
        
        // Verificar se há arquivo enviado
        if (!isset($file) || !is_array($file) || !isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $result['message'] = 'Nenhum arquivo enviado ou falha no upload';
            return $result;
        }
        
        // Verificar erros de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result['message'] = self::getUploadErrorMessage($file['error']);
            return $result;
        }
        
        // Obter opções de validação
        $maxSize = isset($options['maxSize']) ? $options['maxSize'] : self::$defaultMaxSize;
        $allowedExtensions = isset($options['allowedExtensions']) ? $options['allowedExtensions'] : self::$allowedExtensions;
        $allowedMimeTypes = isset($options['allowedMimeTypes']) ? $options['allowedMimeTypes'] : self::$allowedMimeTypes;
        $strictValidation = isset($options['strictValidation']) ? (bool)$options['strictValidation'] : true;
        
        // Validação de tamanho
        if ($file['size'] > $maxSize) {
            $result['message'] = 'O arquivo excede o tamanho máximo permitido (' . self::formatSize($maxSize) . ')';
            return $result;
        }
        
        // Validação de extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, array_map('strtolower', $allowedExtensions))) {
            $result['message'] = 'Extensão de arquivo não permitida. Extensões permitidas: ' . implode(', ', $allowedExtensions);
            return $result;
        }
        
        // Verificação de assinatura de arquivo (Magic Bytes)
        $signatureCheck = self::checkFileSignature($file['tmp_name'], $extension);
        if (!$signatureCheck['valid']) {
            $result['message'] = 'Tipo de arquivo inválido: ' . $signatureCheck['message'];
            return $result;
        }
        
        // Validação de tipo MIME - abordagem mais restritiva
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $fileMimeType = $fileInfo->file($file['tmp_name']);
        
        // Lista específica de tipos MIME válidos por extensão
        $validMimeTypes = [];
        switch ($extension) {
            case 'stl':
                $validMimeTypes = [
                    'application/sla',
                    'model/stl', 
                    'application/vnd.ms-pki.stl',
                    'text/plain' // Para STL ASCII
                ];
                // Somente aceitar octet-stream se a validação estrutural for bem-sucedida
                if ($strictValidation) {
                    $structureCheck = self::validateStlFile($file['tmp_name']);
                    if ($structureCheck && $fileMimeType === 'application/octet-stream') {
                        $validMimeTypes[] = 'application/octet-stream';
                    }
                }
                break;
                
            case 'obj':
                $validMimeTypes = [
                    'application/object',
                    'model/obj',
                    'text/plain'
                ];
                break;
                
            case '3mf':
                $validMimeTypes = [
                    'application/vnd.ms-package.3dmanufacturing-3dmodel+xml',
                    'application/3mf',
                    'application/zip'
                ];
                break;
        }
        
        $mimeTypeValid = in_array($fileMimeType, $validMimeTypes);
        
        if (!$mimeTypeValid) {
            // Verificações adicionais para casos especiais
            $isValid = false;
            
            // STL pode ser ASCII (text/plain) ou binário (application/octet-stream)
            if ($extension === 'stl') {
                $isValid = self::validateStlFile($file['tmp_name']);
            }
            // OBJ é geralmente text/plain
            elseif ($extension === 'obj' && $fileMimeType === 'text/plain') {
                $isValid = self::validateObjFile($file['tmp_name']);
            }
            // 3MF pode ser detectado como application/zip
            elseif ($extension === '3mf' && $fileMimeType === 'application/zip') {
                $isValid = self::validate3mfFile($file['tmp_name']);
            }
            
            if (!$isValid) {
                $result['message'] = 'Tipo MIME inválido: ' . $fileMimeType . '. Tipos permitidos: ' . implode(', ', $validMimeTypes);
                return $result;
            }
        }
        
        // Validação específica por tipo de arquivo
        $validationResult = null;
        switch ($extension) {
            case 'stl':
                $validationResult = self::validateStlFile($file['tmp_name'], true);
                break;
            case 'obj':
                $validationResult = self::validateObjFile($file['tmp_name'], true);
                break;
            case '3mf':
                $validationResult = self::validate3mfFile($file['tmp_name'], true);
                break;
        }
        
        // Se a validação específica falhar
        if (is_array($validationResult) && isset($validationResult['valid']) && !$validationResult['valid']) {
            return $validationResult;
        }
        
        // Verificações de segurança adicionais
        $securityResult = self::performSecurityChecks($file['tmp_name'], $extension);
        if (!$securityResult['passed']) {
            $result['message'] = 'Falha na verificação de segurança: ' . $securityResult['message'];
            return $result;
        }
        
        // Extrair metadados do modelo 3D
        $metadata = self::extractMetadata($file['tmp_name'], $extension);
        
        // Validação bem-sucedida
        $result['valid'] = true;
        $result['message'] = 'Arquivo válido';
        $result['data'] = [
            'metadata' => $metadata,
            'extension' => $extension,
            'mime_type' => $fileMimeType,
            'size' => $file['size'],
            'security_checks' => $securityResult['checks'],
            'file_signature' => $signatureCheck
        ];
        
        return $result;
    }
    
    /**
     * Verifica a assinatura de bytes de um arquivo
     * 
     * @param string $filePath Caminho do arquivo temporário
     * @param string $extension Extensão do arquivo
     * @return array Resultado da verificação ['valid' => bool, 'message' => string]
     */
    private static function checkFileSignature($filePath, $extension) {
        $result = ['valid' => false, 'message' => ''];
        
        try {
            $handle = fopen($filePath, 'rb');
            
            if (!$handle) {
                $result['message'] = 'Não foi possível abrir o arquivo para verificação de assinatura';
                return $result;
            }
            
            $signatures = [];
            
            // Determinar quais assinaturas verificar
            switch ($extension) {
                case 'stl':
                    $signatures[] = 'stl_ascii';
                    $signatures[] = 'stl_binary';
                    break;
                    
                case 'obj':
                    $signatures[] = 'obj';
                    break;
                    
                case '3mf':
                    $signatures[] = '3mf';
                    break;
            }
            
            // Verificar cada assinatura
            foreach ($signatures as $signatureType) {
                $signatureData = self::$fileSignatures[$signatureType];
                
                // Posicionar no offset correto
                fseek($handle, $signatureData['offset'], SEEK_SET);
                
                // Caso especial: validação por função específica
                if (isset($signatureData['validation'])) {
                    $validationMethod = $signatureData['validation'];
                    $validationResult = self::$validationMethod($handle, $filePath);
                    
                    if ($validationResult) {
                        $result['valid'] = true;
                        $result['message'] = 'Assinatura de arquivo válida: ' . $signatureType;
                        $result['type'] = $signatureType;
                        break;
                    }
                    
                    // Reposicionar o ponteiro para o início
                    rewind($handle);
                    continue;
                }
                
                // Caso padrão: verificação de assinatura fixa
                if (isset($signatureData['signature']) && $signatureData['signature'] !== null) {
                    $size = isset($signatureData['size']) ? $signatureData['size'] : strlen($signatureData['signature']);
                    $bytesToRead = $size;
                    
                    $fileSignature = fread($handle, $bytesToRead);
                    
                    if (strlen($fileSignature) < $bytesToRead) {
                        rewind($handle);
                        continue; // Arquivo muito pequeno
                    }
                    
                    $expectedSignature = $signatureData['signature'];
                    $mask = isset($signatureData['mask']) ? $signatureData['mask'] : null;
                    
                    // Aplicar máscara se existir
                    if ($mask !== null) {
                        for ($i = 0; $i < $size; $i++) {
                            $fileSignature[$i] = $fileSignature[$i] & $mask[$i];
                        }
                    }
                    
                    // Verificar se começa com a assinatura esperada
                    if (substr($fileSignature, 0, strlen($expectedSignature)) === $expectedSignature) {
                        $result['valid'] = true;
                        $result['message'] = 'Assinatura de arquivo válida: ' . $signatureType;
                        $result['type'] = $signatureType;
                        break;
                    }
                    
                    // Reposicionar o ponteiro para o início
                    rewind($handle);
                }
            }
            
            // Se nenhuma assinatura correspondeu
            if (!$result['valid']) {
                $result['message'] = 'Assinatura de arquivo inválida para ' . $extension;
            }
            
            fclose($handle);
            return $result;
            
        } catch (Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            
            $result['message'] = 'Erro ao verificar assinatura de arquivo: ' . $e->getMessage();
            return $result;
        }
    }
    
    /**
     * Valida a assinatura de um arquivo STL
     * 
     * @param resource $handle Manipulador de arquivo
     * @param string $filePath Caminho do arquivo (fallback)
     * @return bool True se for um STL válido
     */
    private static function validateStlSignature($handle, $filePath) {
        try {
            // Reposicionar o ponteiro para o início
            rewind($handle);
            
            // Ler os primeiros bytes para determinar se é ASCII ou binário
            $header = fread($handle, 5);
            
            // STL ASCII deve começar com "solid"
            if (preg_match('/^solid/i', $header)) {
                return true;
            }
            
            // Para STL binário, verificamos se o cabeçalho tem 80 bytes e é seguido por 4 bytes com o contador de triângulos
            rewind($handle);
            $header = fread($handle, 80); // Cabeçalho de 80 bytes
            $triangleCountBin = fread($handle, 4);
            
            if (strlen($triangleCountBin) !== 4) {
                return false;
            }
            
            // Extrair contagem de triângulos
            $triangleCount = unpack('V', $triangleCountBin)[1];
            
            // Verificar se a contagem de triângulos é razoável e se o tamanho do arquivo corresponde
            if ($triangleCount <= 0 || $triangleCount > 5000000) { // Limite arbitrário razoável
                return false;
            }
            
            // Verificar se o tamanho do arquivo é consistente com a contagem de triângulos
            $expectedSize = 84 + ($triangleCount * 50);
            $actualSize = filesize($filePath);
            
            // Permitir uma pequena diferença para metadados adicionais
            $tolerance = $expectedSize * 0.01;
            if (abs($actualSize - $expectedSize) > $tolerance) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Valida a assinatura de um arquivo OBJ
     * 
     * @param resource $handle Manipulador de arquivo
     * @param string $filePath Caminho do arquivo (fallback)
     * @return bool True se for um OBJ válido
     */
    private static function validateObjSignature($handle, $filePath) {
        try {
            // Reposicionar o ponteiro para o início
            rewind($handle);
            
            // Um arquivo OBJ válido deve conter linhas que começam com v (vértice), vt (textura), vn (normal), ou f (face)
            $validLines = 0;
            $totalLinesChecked = 0;
            
            // Verificar as primeiras 100 linhas
            while (($line = fgets($handle)) !== false && $totalLinesChecked < 100) {
                $totalLinesChecked++;
                $line = trim($line);
                
                // Pular linhas vazias e comentários
                if (empty($line) || $line[0] === '#') {
                    continue;
                }
                
                // Dividir a linha em partes
                $parts = preg_split('/\s+/', $line);
                $prefix = isset($parts[0]) ? strtolower($parts[0]) : '';
                
                // Verificar se é um tipo válido para OBJ
                if (in_array($prefix, ['v', 'vt', 'vn', 'f', 'o', 'g', 'mtllib', 'usemtl'])) {
                    $validLines++;
                    
                    // Se encontrarmos vértices ou faces suficientes, consideramos válido
                    if ($validLines >= 5) {
                        return true;
                    }
                }
            }
            
            // Se não encontramos linhas válidas suficientes, mas o arquivo é pequeno
            return ($validLines > 0 && $totalLinesChecked < 20);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Valida um arquivo STL
     * 
     * @param string $filePath Caminho do arquivo temporário
     * @param bool $extractInfo Se deve extrair informações detalhadas
     * @return bool|array True se válido, array com detalhes se $extractInfo for true
     */
    private static function validateStlFile($filePath, $extractInfo = false) {
        try {
            $handle = fopen($filePath, 'rb');
            if (!$handle) {
                return $extractInfo ? ['valid' => false, 'message' => 'Não foi possível abrir o arquivo para análise'] : false;
            }
            
            // Ler os primeiros bytes para determinar se é ASCII ou binário
            $header = fread($handle, 5);
            rewind($handle);
            
            $isBinary = !preg_match('/^solid/i', $header);
            
            if ($isBinary) {
                // Validação de STL binário
                $header = fread($handle, 80); // Cabeçalho de 80 bytes
                $triangleCountBin = fread($handle, 4);
                
                if (strlen($triangleCountBin) !== 4) {
                    fclose($handle);
                    return $extractInfo ? ['valid' => false, 'message' => 'Formato STL binário inválido'] : false;
                }
                
                // Extrair contagem de triângulos
                $triangleCount = unpack('V', $triangleCountBin)[1];
                
                // Verificar se a contagem é razoável
                if ($triangleCount <= 0 || $triangleCount > 5000000) { // Limite arbitrário razoável
                    fclose($handle);
                    return $extractInfo ? ['valid' => false, 'message' => 'Contagem de triângulos inválida: ' . $triangleCount] : false;
                }
                
                // Verificar coerência do arquivo
                $expectedSize = 84 + ($triangleCount * 50);
                $actualSize = filesize($filePath);
                
                // Permitir uma pequena diferença para metadados adicionais
                $tolerance = $expectedSize * 0.01;
                if (abs($actualSize - $expectedSize) > $tolerance) {
                    fclose($handle);
                    return $extractInfo ? ['valid' => false, 'message' => 'Estrutura de arquivo STL binário inválida'] : false;
                }
                
                // Verificação estrutural adicional - ler alguns triângulos aleatórios
                $maxTrianglesToCheck = min(100, $triangleCount);
                $stepSize = max(1, floor($triangleCount / $maxTrianglesToCheck));
                $invalidTriangles = 0;
                
                for ($i = 0; $i < $triangleCount; $i += $stepSize) {
                    // Posicionar no triângulo
                    $triangleOffset = 84 + ($i * 50);
                    
                    if (fseek($handle, $triangleOffset, SEEK_SET) === -1) {
                        break; // Não foi possível posicionar, arquivo pode estar corrompido
                    }
                    
                    // Ler normal (3 floats = 12 bytes)
                    $normalData = fread($handle, 12);
                    if (strlen($normalData) !== 12) {
                        $invalidTriangles++;
                        continue;
                    }
                    
                    // Ler 3 vértices (cada um com 3 floats = 12 bytes cada)
                    $vertex1 = fread($handle, 12);
                    $vertex2 = fread($handle, 12);
                    $vertex3 = fread($handle, 12);
                    
                    if (strlen($vertex1) !== 12 || strlen($vertex2) !== 12 || strlen($vertex3) !== 12) {
                        $invalidTriangles++;
                        continue;
                    }
                    
                    // Verificar se os valores dos vértices são números válidos
                    $v1 = unpack('f3', $vertex1);
                    $v2 = unpack('f3', $vertex2);
                    $v3 = unpack('f3', $vertex3);
                    
                    foreach ([$v1, $v2, $v3] as $v) {
                        foreach ($v as $coord) {
                            if (!is_finite($coord) || is_nan($coord)) {
                                $invalidTriangles++;
                                break 2; // Sair de ambos os loops
                            }
                        }
                    }
                }
                
                // Tolerância para triângulos inválidos (alguns arquivos STL não são perfeitos)
                $maxInvalidRatio = 0.02; // 2% de tolerância
                if ($invalidTriangles / $maxTrianglesToCheck > $maxInvalidRatio) {
                    fclose($handle);
                    return $extractInfo ? ['valid' => false, 'message' => 'Estrutura de vértices inválida'] : false;
                }
                
                if ($extractInfo) {
                    $metadata = [
                        'format' => 'STL Binary',
                        'triangles' => $triangleCount,
                        'header' => trim($header)
                    ];
                    
                    // Calcular dimensões aproximadas lendo alguns triângulos
                    $dimensions = self::calculateStlDimensions($handle, $triangleCount);
                    $metadata = array_merge($metadata, $dimensions);
                    
                    fclose($handle);
                    return ['valid' => true, 'message' => 'Arquivo STL binário válido', 'data' => $metadata];
                }
            } else {
                // Validação de STL ASCII
                $content = fread($handle, min(filesize($filePath), 10 * 1024 * 1024)); // Ler até 10MB para verificação
                
                // Verificar se contém "endsolid" que deve estar presente em arquivos STL ASCII válidos
                if (!preg_match('/endsolid/i', $content)) {
                    fclose($handle);
                    return $extractInfo ? ['valid' => false, 'message' => 'Formato STL ASCII inválido'] : false;
                }
                
                // Verificar estrutura básica (deve ter vértices e facetas)
                if (!preg_match('/vertex/i', $content) || !preg_match('/facet normal/i', $content)) {
                    fclose($handle);
                    return $extractInfo ? ['valid' => false, 'message' => 'Estrutura STL ASCII inválida'] : false;
                }
                
                if ($extractInfo) {
                    // Contar triângulos (cada triângulo tem uma linha "facet normal")
                    $triangleCount = substr_count(strtolower($content), 'facet normal');
                    
                    $metadata = [
                        'format' => 'STL ASCII',
                        'triangles' => $triangleCount
                    ];
                    
                    // Extrair nome do modelo
                    if (preg_match('/^solid\s+(.+?)$/m', $content, $matches)) {
                        $metadata['model_name'] = trim($matches[1]);
                    }
                    
                    fclose($handle);
                    return ['valid' => true, 'message' => 'Arquivo STL ASCII válido', 'data' => $metadata];
                }
            }
            
            fclose($handle);
            return true;
        } catch (Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return $extractInfo ? ['valid' => false, 'message' => 'Erro ao validar arquivo STL: ' . $e->getMessage()] : false;
        }
    }
    
    /**
     * Calcula as dimensões aproximadas de um modelo STL
     * 
     * @param resource $handle Manipulador de arquivo
     * @param int $triangleCount Número de triângulos
     * @return array Dimensões aproximadas (min_x, max_x, min_y, max_y, min_z, max_z)
     */
    private static function calculateStlDimensions($handle, $triangleCount) {
        // Limitar o número de triângulos a serem analisados para melhor performance
        $samplesToCheck = min($triangleCount, 1000);
        $step = max(1, floor($triangleCount / $samplesToCheck));
        
        $minX = $minY = $minZ = PHP_FLOAT_MAX;
        $maxX = $maxY = $maxZ = -PHP_FLOAT_MAX;
        
        // Salvar a posição atual
        $currentPos = ftell($handle);
        
        for ($i = 0; $i < $triangleCount; $i++) {
            if ($i % $step !== 0) {
                // Pular este triângulo
                fseek($handle, 50, SEEK_CUR);
                continue;
            }
            
            // Pular o vetor normal (12 bytes)
            fseek($handle, 12, SEEK_CUR);
            
            // Ler os 3 vértices (cada um com 12 bytes - 3 floats de 4 bytes)
            for ($j = 0; $j < 3; $j++) {
                $vertexData = fread($handle, 12);
                if (strlen($vertexData) === 12) {
                    $vertex = unpack('f3', $vertexData);
                    
                    $x = $vertex[1];
                    $y = $vertex[2];
                    $z = $vertex[3];
                    
                    // Verificar se os valores são válidos (não NaN ou infinito)
                    if (is_finite($x) && is_finite($y) && is_finite($z) && 
                        !is_nan($x) && !is_nan($y) && !is_nan($z)) {
                        $minX = min($minX, $x);
                        $maxX = max($maxX, $x);
                        $minY = min($minY, $y);
                        $maxY = max($maxY, $y);
                        $minZ = min($minZ, $z);
                        $maxZ = max($maxZ, $z);
                    }
                }
            }
            
            // Pular o atributo de 2 bytes
            fseek($handle, 2, SEEK_CUR);
        }
        
        // Restaurar a posição original
        fseek($handle, $currentPos, SEEK_SET);
        
        return [
            'min_x' => $minX !== PHP_FLOAT_MAX ? $minX : 0,
            'max_x' => $maxX !== -PHP_FLOAT_MAX ? $maxX : 0,
            'min_y' => $minY !== PHP_FLOAT_MAX ? $minY : 0,
            'max_y' => $maxY !== -PHP_FLOAT_MAX ? $maxY : 0,
            'min_z' => $minZ !== PHP_FLOAT_MAX ? $minZ : 0,
            'max_z' => $maxZ !== -PHP_FLOAT_MAX ? $maxZ : 0,
            'width' => $maxX - $minX,
            'height' => $maxY - $minY,
            'depth' => $maxZ - $minZ
        ];
    }
    
    /**
     * Valida um arquivo OBJ
     * 
     * @param string $filePath Caminho do arquivo temporário
     * @param bool $extractInfo Se deve extrair informações detalhadas
     * @return bool|array True se válido, array com detalhes se $extractInfo for true
     */
    private static function validateObjFile($filePath, $extractInfo = false) {
        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return $extractInfo ? ['valid' => false, 'message' => 'Não foi possível abrir o arquivo para análise'] : false;
            }
            
            $vertexCount = 0;
            $faceCount = 0;
            $minX = $minY = $minZ = PHP_FLOAT_MAX;
            $maxX = $maxY = $maxZ = -PHP_FLOAT_MAX;
            $modelName = '';
            
            // Ler o arquivo linha por linha
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                
                // Pular linhas vazias e comentários
                if (empty($line) || $line[0] === '#') {
                    continue;
                }
                
                // Dividir a linha em partes
                $parts = preg_split('/\s+/', $line);
                $prefix = strtolower($parts[0]);
                
                switch ($prefix) {
                    case 'v':
                        // Vértice
                        $vertexCount++;
                        
                        if (count($parts) >= 4) {
                            $x = floatval($parts[1]);
                            $y = floatval($parts[2]);
                            $z = floatval($parts[3]);
                            
                            // Verificar se os valores são válidos (não NaN ou infinito)
                            if (is_finite($x) && is_finite($y) && is_finite($z) && 
                                !is_nan($x) && !is_nan($y) && !is_nan($z)) {
                                $minX = min($minX, $x);
                                $maxX = max($maxX, $x);
                                $minY = min($minY, $y);
                                $maxY = max($maxY, $y);
                                $minZ = min($minZ, $z);
                                $maxZ = max($maxZ, $z);
                            }
                        }
                        break;
                        
                    case 'f':
                        // Face - verificar se todas as referências a vértices são válidas
                        $faceCount++;
                        
                        if ($vertexCount === 0) {
                            // Face definida antes de qualquer vértice é inválido
                            fclose($handle);
                            return $extractInfo ? ['valid' => false, 'message' => 'Faces definidas antes de vértices'] : false;
                        }
                        
                        // Verificar referências a vértices
                        for ($i = 1; $i < count($parts); $i++) {
                            $vertexRef = explode('/', $parts[$i])[0];
                            $vertexId = intval($vertexRef);
                            
                            // Vértices são indexados a partir de 1
                            if ($vertexId <= 0 || $vertexId > $vertexCount) {
                                // Referência a vértice inválido (permitimos alguma tolerância para casos especiais)
                                if ($vertexId > $vertexCount * 2) {
                                    fclose($handle);
                                    return $extractInfo ? ['valid' => false, 'message' => 'Referência a vértice inválido'] : false;
                                }
                            }
                        }
                        break;
                        
                    case 'o':
                        // Nome do objeto
                        if (count($parts) > 1) {
                            $modelName = $parts[1];
                        }
                        break;
                }
            }
            
            fclose($handle);
            
            // Um arquivo OBJ válido deve ter vértices e faces
            if ($vertexCount === 0) {
                return $extractInfo ? ['valid' => false, 'message' => 'O arquivo OBJ não contém vértices'] : false;
            }
            
            if ($faceCount === 0) {
                return $extractInfo ? ['valid' => false, 'message' => 'O arquivo OBJ não contém faces'] : false;
            }
            
            if ($extractInfo) {
                $metadata = [
                    'format' => 'OBJ',
                    'vertices' => $vertexCount,
                    'faces' => $faceCount
                ];
                
                if (!empty($modelName)) {
                    $metadata['model_name'] = $modelName;
                }
                
                if ($minX !== PHP_FLOAT_MAX) {
                    $metadata = array_merge($metadata, [
                        'min_x' => $minX,
                        'max_x' => $maxX,
                        'min_y' => $minY,
                        'max_y' => $maxY,
                        'min_z' => $minZ,
                        'max_z' => $maxZ,
                        'width' => $maxX - $minX,
                        'height' => $maxY - $minY,
                        'depth' => $maxZ - $minZ
                    ]);
                }
                
                return ['valid' => true, 'message' => 'Arquivo OBJ válido', 'data' => $metadata];
            }
            
            return true;
            
        } catch (Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return $extractInfo ? ['valid' => false, 'message' => 'Erro ao validar arquivo OBJ: ' . $e->getMessage()] : false;
        }
    }
    
    /**
     * Valida um arquivo 3MF
     * 
     * @param string $filePath Caminho do arquivo temporário
     * @param bool $extractInfo Se deve extrair informações detalhadas
     * @return bool|array True se válido, array com detalhes se $extractInfo for true
     */
    private static function validate3mfFile($filePath, $extractInfo = false) {
        try {
            // Verificar se o arquivo é um ZIP válido
            $zip = new ZipArchive();
            $result = $zip->open($filePath);
            
            if ($result !== true) {
                return $extractInfo ? ['valid' => false, 'message' => 'O arquivo 3MF não é um arquivo ZIP válido'] : false;
            }
            
            // Verificar se contém os arquivos necessários para um 3MF válido
            $hasModel = false;
            $hasRels = false;
            $modelPath = '';
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                
                if (strpos($name, '3D/3dmodel.model') !== false || strpos($name, '3dmodel.model') !== false) {
                    $hasModel = true;
                    $modelPath = $name;
                } else if (strpos($name, '_rels/.rels') !== false) {
                    $hasRels = true;
                }
            }
            
            if (!$hasModel) {
                $zip->close();
                return $extractInfo ? ['valid' => false, 'message' => 'O arquivo 3MF não contém um modelo 3D válido'] : false;
            }
            
            // Verificação adicional - o arquivo deve ser XML válido
            if (!empty($modelPath)) {
                $modelContent = $zip->getFromName($modelPath);
                
                // Tentar carregar o XML
                $previousValue = libxml_use_internal_errors(true);
                $doc = simplexml_load_string($modelContent);
                $loadErrors = libxml_get_errors();
                libxml_clear_errors();
                libxml_use_internal_errors($previousValue);
                
                if ($doc === false || !empty($loadErrors)) {
                    $zip->close();
                    return $extractInfo ? ['valid' => false, 'message' => 'O arquivo 3MF contém XML inválido'] : false;
                }
                
                // Verificar namespace correto para 3MF
                $namespaces = $doc->getNamespaces(true);
                $has3mfNamespace = false;
                
                foreach ($namespaces as $prefix => $ns) {
                    if (strpos($ns, '3mf.org') !== false) {
                        $has3mfNamespace = true;
                        break;
                    }
                }
                
                if (!$has3mfNamespace) {
                    $zip->close();
                    return $extractInfo ? ['valid' => false, 'message' => 'O arquivo 3MF não contém o namespace 3MF correto'] : false;
                }
            }
            
            if ($extractInfo) {
                $metadata = [
                    'format' => '3MF',
                    'has_relationships' => $hasRels
                ];
                
                // Extrair informações do modelo 3D
                if (!empty($modelPath)) {
                    $modelContent = $zip->getFromName($modelPath);
                    
                    // Contar elementos básicos do modelo
                    $vertexCount = substr_count($modelContent, '<vertex');
                    $triangleCount = substr_count($modelContent, '<triangle');
                    
                    $metadata = array_merge($metadata, [
                        'vertices' => $vertexCount,
                        'triangles' => $triangleCount
                    ]);
                    
                    // Extrair metadados adicionais
                    if (preg_match('/<metadata.*?name="Title".*?>(.*?)<\/metadata>/s', $modelContent, $matches)) {
                        $metadata['title'] = $matches[1];
                    }
                    
                    if (preg_match('/<metadata.*?name="Description".*?>(.*?)<\/metadata>/s', $modelContent, $matches)) {
                        $metadata['description'] = $matches[1];
                    }
                    
                    if (preg_match('/<metadata.*?name="Designer".*?>(.*?)<\/metadata>/s', $modelContent, $matches)) {
                        $metadata['designer'] = $matches[1];
                    }
                }
                
                $zip->close();
                return ['valid' => true, 'message' => 'Arquivo 3MF válido', 'data' => $metadata];
            }
            
            $zip->close();
            return true;
            
        } catch (Exception $e) {
            if (isset($zip) && $zip instanceof ZipArchive) {
                $zip->close();
            }
            return $extractInfo ? ['valid' => false, 'message' => 'Erro ao validar arquivo 3MF: ' . $e->getMessage()] : false;
        }
    }
    
    /**
     * Executa verificações de segurança adicionais no arquivo
     * 
     * @param string $filePath Caminho do arquivo temporário
     * @param string $extension Extensão do arquivo
     * @return array Resultado das verificações de segurança
     */
    private static function performSecurityChecks($filePath, $extension) {
        $checks = [
            'code_injection' => [
                'passed' => true,
                'message' => 'Nenhum código malicioso detectado'
            ],
            'file_corruption' => [
                'passed' => true,
                'message' => 'Arquivo não está corrompido'
            ],
            'structural_integrity' => [
                'passed' => true,
                'message' => 'Estrutura do arquivo íntegra'
            ],
            'malicious_content' => [
                'passed' => true,
                'message' => 'Nenhum conteúdo malicioso detectado'
            ]
        ];
        
        // Verificar se o arquivo está vazio
        if (filesize($filePath) === 0) {
            $checks['file_corruption']['passed'] = false;
            $checks['file_corruption']['message'] = 'Arquivo vazio';
        }
        
        // Verificar assinaturas de código malicioso apenas para formatos que podem conter texto
        if (in_array($extension, ['obj', '3mf'])) {
            $handle = fopen($filePath, 'rb');
            if ($handle) {
                $content = fread($handle, min(filesize($filePath), 1024 * 1024)); // Ler até 1MB
                fclose($handle);
                
                // Padrões de segurança para verificar
                $maliciousPatterns = [
                    // Scripts e códigos maliciosos
                    '/<script\b[^>]*>(.*?)<\/script>/is',
                    '/eval\s*\(/is',
                    '/exec\s*\(/is',
                    '/system\s*\(/is',
                    '/chmod\s*\(/is',
                    '/include\s*\(/is',
                    '/include_once\s*\(/is',
                    '/require\s*\(/is',
                    '/require_once\s*\(/is',
                    '/passthru\s*\(/is',
                    '/shell_exec\s*\(/is',
                    '/proc_open\s*\(/is',
                    '/popen\s*\(/is',
                    '/curl_exec\s*\(/is',
                    '/curl_multi_exec\s*\(/is',
                    '/parse_ini_file\s*\(/is',
                    '/show_source\s*\(/is',
                    '/highlight_file\s*\(/is',
                    '/fopen\s*\(/is',
                    '/fclose\s*\(/is',
                    '/readfile\s*\(/is',
                    '/file_get_contents\s*\(/is',
                    '/file_put_contents\s*\(/is',
                    '/unlink\s*\(/is',
                    '/mysql_/is',
                    '/mysqli_/is',
                    '/SQLite3/is',
                    '/PDO/is',
                    '/phpinfo\s*\(/is',
                    
                    // Comandos shell
                    '/\b(wget|curl|gcc|nc|netcat|ping|telnet|net|ipconfig|ifconfig|nslookup|tracert|traceroute)\s/is',
                    
                    // URLs suspeitas
                    '/https?:\/\/[^\s"\'}<\n]*[^a-zA-Z0-9,\._\-\/&\?\%\=:~\s"\'}<\n]/is',
                    
                    // Caracteres nulos (podem ser usados para injeção)
                    '/\x00/s',
                    
                    // Iframes ou objetos suspeitos
                    '/<iframe\b[^>]*>/is',
                    '/<object\b[^>]*>/is',
                    '/<embed\b[^>]*>/is',
                    
                    // Meta redirects ou refresh
                    '/<meta\s+[^>]*http-equiv\s*=\s*["\']?refresh["\']?[^>]*>/is',
                    
                    // XML External Entities (XXE)
                    '/<!ENTITY\s+[^>]*SYSTEM/is',
                    
                    // Comentários suspeitos
                    '/\/\*.*?\*\//s'
                ];
                
                foreach ($maliciousPatterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $checks['code_injection']['passed'] = false;
                        $checks['code_injection']['message'] = 'Detectado conteúdo potencialmente malicioso';
                        break;
                    }
                }
            }
        }
        
        // Verificar estrutura binária de arquivos STL binários
        if ($extension === 'stl') {
            $handle = fopen($filePath, 'rb');
            if ($handle) {
                $header = fread($handle, 5);
                rewind($handle);
                
                $isBinary = !preg_match('/^solid/i', $header);
                
                if ($isBinary) {
                    // Verificar estrutura STL binária
                    $header = fread($handle, 80);
                    $triangleCountBin = fread($handle, 4);
                    
                    if (strlen($triangleCountBin) !== 4) {
                        $checks['structural_integrity']['passed'] = false;
                        $checks['structural_integrity']['message'] = 'Estrutura de cabeçalho STL binário inválida';
                    } else {
                        $triangleCount = unpack('V', $triangleCountBin)[1];
                        
                        // Verificar se a contagem é razoável
                        if ($triangleCount <= 0 || $triangleCount > 5000000) {
                            $checks['structural_integrity']['passed'] = false;
                            $checks['structural_integrity']['message'] = 'Contagem de triângulos inválida: ' . $triangleCount;
                        }
                        
                        // Verificar coerência do arquivo
                        $expectedSize = 84 + ($triangleCount * 50);
                        $actualSize = filesize($filePath);
                        
                        // Permitir uma pequena diferença para metadados adicionais
                        $tolerance = $expectedSize * 0.01;
                        if (abs($actualSize - $expectedSize) > $tolerance) {
                            $checks['structural_integrity']['passed'] = false;
                            $checks['structural_integrity']['message'] = 'Tamanho de arquivo inconsistente com a contagem de triângulos';
                        }
                    }
                } else {
                    // Verificar estrutura STL ASCII
                    $content = fread($handle, min(filesize($filePath), 10 * 1024 * 1024)); // Ler até 10MB
                    
                    if (!preg_match('/endsolid/i', $content)) {
                        $checks['structural_integrity']['passed'] = false;
                        $checks['structural_integrity']['message'] = 'Estrutura STL ASCII inválida: falta tag endsolid';
                    }
                    
                    if (!preg_match('/facet\s+normal/i', $content) || !preg_match('/vertex/i', $content)) {
                        $checks['structural_integrity']['passed'] = false;
                        $checks['structural_integrity']['message'] = 'Estrutura STL ASCII inválida: falta facet normal ou vertex';
                    }
                }
                
                fclose($handle);
            }
        }
        
        // Verificar estrutura de arquivos 3MF (deve ser um ZIP válido com arquivos específicos)
        if ($extension === '3mf') {
            $zip = new ZipArchive();
            $result = $zip->open($filePath);
            
            if ($result !== true) {
                $checks['structural_integrity']['passed'] = false;
                $checks['structural_integrity']['message'] = 'Estrutura ZIP inválida para arquivo 3MF';
            } else {
                $hasModelFile = false;
                
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    
                    if (strpos($name, '3D/3dmodel.model') !== false || strpos($name, '3dmodel.model') !== false) {
                        $hasModelFile = true;
                        break;
                    }
                }
                
                if (!$hasModelFile) {
                    $checks['structural_integrity']['passed'] = false;
                    $checks['structural_integrity']['message'] = 'Estrutura 3MF inválida: falta arquivo 3dmodel.model';
                }
                
                $zip->close();
            }
        }
        
        // Verificar tamanho realista do arquivo
        $fileSize = filesize($filePath);
        $minValidSize = 100; // 100 bytes mínimo para qualquer modelo 3D válido
        $maxValidSize = 500 * 1024 * 1024; // 500MB máximo (um valor realista para a maioria dos casos)
        
        if ($fileSize < $minValidSize) {
            $checks['file_corruption']['passed'] = false;
            $checks['file_corruption']['message'] = 'Arquivo muito pequeno para ser um modelo 3D válido';
        }
        
        if ($fileSize > $maxValidSize) {
            $checks['malicious_content']['passed'] = false;
            $checks['malicious_content']['message'] = 'Arquivo excede o tamanho máximo razoável para um modelo 3D';
        }
        
        // Resultado final
        $passed = true;
        $message = 'Todas as verificações de segurança passaram';
        
        foreach ($checks as $check) {
            if (!$check['passed']) {
                $passed = false;
                $message = $check['message'];
                break;
            }
        }
        
        return [
            'passed' => $passed,
            'message' => $message,
            'checks' => $checks
        ];
    }
    
    /**
     * Extrai metadados do modelo 3D
     * 
     * @param string $filePath Caminho do arquivo temporário
     * @param string $extension Extensão do arquivo
     * @return array Metadados extraídos
     */
    private static function extractMetadata($filePath, $extension) {
        $metadata = [
            'file_size' => filesize($filePath),
            'file_extension' => $extension,
            'file_size_formatted' => self::formatSize(filesize($filePath))
        ];
        
        // Extrair metadados específicos por tipo de arquivo
        switch ($extension) {
            case 'stl':
                $stlMetadata = self::validateStlFile($filePath, true);
                if (is_array($stlMetadata) && isset($stlMetadata['data'])) {
                    $metadata = array_merge($metadata, $stlMetadata['data']);
                }
                break;
                
            case 'obj':
                $objMetadata = self::validateObjFile($filePath, true);
                if (is_array($objMetadata) && isset($objMetadata['data'])) {
                    $metadata = array_merge($metadata, $objMetadata['data']);
                }
                break;
                
            case '3mf':
                $mfMetadata = self::validate3mfFile($filePath, true);
                if (is_array($mfMetadata) && isset($mfMetadata['data'])) {
                    $metadata = array_merge($metadata, $mfMetadata['data']);
                }
                break;
        }
        
        return $metadata;
    }
    
    /**
     * Formata tamanho do arquivo para exibição
     * 
     * @param int $bytes Tamanho em bytes
     * @param int $precision Precisão de casas decimais
     * @return string Tamanho formatado
     */
    public static function formatSize($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Retorna mensagem de erro para códigos de erro de upload
     * 
     * @param int $errorCode Código de erro do upload
     * @return string Mensagem de erro
     */
    private static function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'O arquivo excede o tamanho máximo permitido pelo PHP (upload_max_filesize)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'O arquivo excede o tamanho máximo permitido pelo formulário (MAX_FILE_SIZE)';
            case UPLOAD_ERR_PARTIAL:
                return 'O arquivo foi enviado parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo foi enviado';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta uma pasta temporária no servidor';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao gravar arquivo no disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Uma extensão PHP interrompeu o upload do arquivo';
            default:
                return 'Erro desconhecido no upload';
        }
    }
}