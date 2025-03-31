<?php
/**
 * ModelCacheHelper - Helper para gerenciamento de cache de modelos 3D
 * 
 * Este helper gerencia o cache de modelos 3D (STL/OBJ) no servidor e fornece
 * suporte para cache no lado do cliente. Trabalha em conjunto com ResourceOptimizerHelper
 * para otimizar a entrega e armazenamento de modelos, reduzindo tráfego de rede e
 * melhorando a experiência do usuário em visitas subsequentes.
 */
class ModelCacheHelper {
    // Configurações
    private static $initialized = false;
    private static $cachePath = '/cache/models/';
    private static $cacheEnabled = true;
    private static $maxAge = 2592000; // 30 dias em segundos
    private static $version = '1.0.0';
    private static $supportedFormats = ['stl', 'obj', 'mtl'];
    private static $maxCacheSize = 104857600; // 100MB em bytes
    private static $minFreeDiskSpace = 52428800; // 50MB em bytes
    
    /**
     * Inicializa o helper
     */
    public static function init() {
        if (self::$initialized) {
            return true;
        }
        
        // Criar diretório de cache se não existir
        $fullCachePath = ROOT_PATH . '/public' . self::$cachePath;
        if (!file_exists($fullCachePath)) {
            if (!mkdir($fullCachePath, 0755, true)) {
                error_log('ModelCacheHelper: Não foi possível criar diretório de cache: ' . $fullCachePath);
                self::$cacheEnabled = false;
                return false;
            }
        }
        
        // Verificar se o diretório tem permissão de escrita
        if (!is_writable($fullCachePath)) {
            error_log('ModelCacheHelper: Diretório de cache sem permissão de escrita: ' . $fullCachePath);
            self::$cacheEnabled = false;
            return false;
        }
        
        self::$initialized = true;
        return true;
    }
    
    /**
     * Define se o cache está habilitado
     * 
     * @param bool $enabled Status de ativação do cache
     */
    public static function setCacheEnabled($enabled) {
        self::$cacheEnabled = $enabled;
    }
    
    /**
     * Verifica se o cache está habilitado
     * 
     * @return bool Status atual do cache
     */
    public static function isCacheEnabled() {
        return self::$cacheEnabled && self::$initialized;
    }
    
    /**
     * Define a versão do cache (para invalidação)
     * 
     * @param string $version Nova versão
     */
    public static function setVersion($version) {
        self::$version = $version;
    }
    
    /**
     * Retorna a versão atual do cache
     * 
     * @return string Versão atual
     */
    public static function getVersion() {
        return self::$version;
    }
    
    /**
     * Gera um identificador único para o modelo
     * 
     * @param string $filePath Caminho para o arquivo do modelo
     * @return string Identificador único baseado no caminho e conteúdo do arquivo
     */
    public static function generateModelId($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Usar caminho relativo se o arquivo estiver dentro do ROOT_PATH
        $relativePath = str_replace(ROOT_PATH, '', $filePath);
        
        // Combinar caminho relativo, data de modificação e hash do conteúdo para identificador único
        $modTime = filemtime($filePath);
        $contentHash = md5_file($filePath);
        
        return md5($relativePath . '_' . $modTime . '_' . $contentHash . '_' . self::$version);
    }
    
    /**
     * Verifica se um modelo está em cache
     * 
     * @param string $modelId Identificador único do modelo
     * @return bool Verdadeiro se o modelo estiver em cache
     */
    public static function isModelCached($modelId) {
        if (!self::isCacheEnabled() || empty($modelId)) {
            return false;
        }
        
        $cachedFilePath = ROOT_PATH . '/public' . self::$cachePath . $modelId;
        return file_exists($cachedFilePath);
    }
    
    /**
     * Obtém o caminho do arquivo em cache
     * 
     * @param string $modelId Identificador único do modelo
     * @return string Caminho para o arquivo em cache ou falso se não estiver em cache
     */
    public static function getCachedModelPath($modelId) {
        if (!self::isModelCached($modelId)) {
            return false;
        }
        
        return self::$cachePath . $modelId;
    }
    
    /**
     * Adiciona um modelo ao cache
     * 
     * @param string $filePath Caminho para o arquivo do modelo
     * @param string $modelId Identificador único do modelo (opcional, será gerado se não fornecido)
     * @return string|bool ID do modelo em cache ou falso em caso de erro
     */
    public static function cacheModel($filePath, $modelId = null) {
        if (!self::isCacheEnabled() || !file_exists($filePath)) {
            return false;
        }
        
        // Verificar espaço em disco
        if (!self::checkDiskSpace()) {
            self::cleanCache();
            
            // Verificar novamente após limpeza
            if (!self::checkDiskSpace()) {
                error_log('ModelCacheHelper: Espaço em disco insuficiente mesmo após limpeza de cache');
                return false;
            }
        }
        
        // Verificar formato suportado
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, self::$supportedFormats)) {
            error_log('ModelCacheHelper: Formato não suportado: ' . $extension);
            return false;
        }
        
        // Gerar ID se não fornecido
        if (empty($modelId)) {
            $modelId = self::generateModelId($filePath);
            if (!$modelId) {
                return false;
            }
        }
        
        // Caminho para o arquivo em cache
        $cacheFilePath = ROOT_PATH . '/public' . self::$cachePath . $modelId;
        
        // Não sobrescrever se já existir
        if (file_exists($cacheFilePath)) {
            return $modelId;
        }
        
        // Copiar arquivo para o cache
        if (!copy($filePath, $cacheFilePath)) {
            error_log('ModelCacheHelper: Erro ao copiar arquivo para cache: ' . $filePath);
            return false;
        }
        
        // Registrar no arquivo de metadados
        self::updateMetadata($modelId, [
            'originalPath' => $filePath,
            'extension' => $extension,
            'size' => filesize($filePath),
            'timestamp' => time(),
            'lastAccessed' => time()
        ]);
        
        return $modelId;
    }
    
    /**
     * Obtém metadados de um modelo em cache
     * 
     * @param string $modelId Identificador único do modelo
     * @return array Metadados do modelo ou array vazio se não encontrado
     */
    public static function getModelMetadata($modelId) {
        if (!self::isCacheEnabled() || empty($modelId)) {
            return [];
        }
        
        $metadataFile = ROOT_PATH . '/public' . self::$cachePath . 'metadata.json';
        if (!file_exists($metadataFile)) {
            return [];
        }
        
        $metadata = json_decode(file_get_contents($metadataFile), true);
        if (!$metadata || !isset($metadata[$modelId])) {
            return [];
        }
        
        return $metadata[$modelId];
    }
    
    /**
     * Atualiza metadados de um modelo em cache
     * 
     * @param string $modelId Identificador único do modelo
     * @param array $data Dados de metadados a serem atualizados
     * @return bool Sucesso da operação
     */
    private static function updateMetadata($modelId, $data) {
        if (!self::isCacheEnabled() || empty($modelId)) {
            return false;
        }
        
        $metadataFile = ROOT_PATH . '/public' . self::$cachePath . 'metadata.json';
        
        $metadata = [];
        if (file_exists($metadataFile)) {
            $content = file_get_contents($metadataFile);
            if (!empty($content)) {
                $metadata = json_decode($content, true) ?: [];
            }
        }
        
        // Atualizar metadados do modelo
        $metadata[$modelId] = isset($metadata[$modelId]) ? 
                              array_merge($metadata[$modelId], $data) : 
                              $data;
        
        return file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Registra acesso a um modelo em cache
     * 
     * @param string $modelId Identificador único do modelo
     * @return bool Sucesso da operação
     */
    public static function recordAccess($modelId) {
        return self::updateMetadata($modelId, ['lastAccessed' => time()]);
    }
    
    /**
     * Remove um modelo do cache
     * 
     * @param string $modelId Identificador único do modelo
     * @return bool Sucesso da operação
     */
    public static function removeFromCache($modelId) {
        if (!self::isCacheEnabled() || empty($modelId)) {
            return false;
        }
        
        $cachedFilePath = ROOT_PATH . '/public' . self::$cachePath . $modelId;
        if (!file_exists($cachedFilePath)) {
            return false;
        }
        
        // Remover arquivo
        if (!unlink($cachedFilePath)) {
            error_log('ModelCacheHelper: Erro ao remover arquivo do cache: ' . $cachedFilePath);
            return false;
        }
        
        // Atualizar metadados
        $metadataFile = ROOT_PATH . '/public' . self::$cachePath . 'metadata.json';
        if (file_exists($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true);
            if ($metadata && isset($metadata[$modelId])) {
                unset($metadata[$modelId]);
                file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
            }
        }
        
        return true;
    }
    
    /**
     * Verifica se há espaço em disco suficiente
     * 
     * @return bool Verdadeiro se houver espaço suficiente
     */
    private static function checkDiskSpace() {
        $cacheDir = ROOT_PATH . '/public' . self::$cachePath;
        $freeSpace = disk_free_space($cacheDir);
        
        return ($freeSpace !== false && $freeSpace > self::$minFreeDiskSpace);
    }
    
    /**
     * Limpa o cache removendo arquivos antigos ou menos acessados
     * 
     * @param int $targetSize Tamanho alvo para o cache após limpeza (em bytes)
     * @return bool Sucesso da operação
     */
    public static function cleanCache($targetSize = null) {
        if (!self::isCacheEnabled()) {
            return false;
        }
        
        $targetSize = $targetSize ?: (self::$maxCacheSize * 0.7); // 70% do tamanho máximo se não especificado
        $cacheDir = ROOT_PATH . '/public' . self::$cachePath;
        $metadataFile = $cacheDir . 'metadata.json';
        
        if (!file_exists($metadataFile)) {
            return true; // Nada para limpar
        }
        
        $metadata = json_decode(file_get_contents($metadataFile), true);
        if (!$metadata) {
            return true; // Nada para limpar
        }
        
        // Calcular tamanho atual do cache
        $currentSize = 0;
        foreach ($metadata as $modelId => $data) {
            $filePath = $cacheDir . $modelId;
            if (file_exists($filePath)) {
                $currentSize += filesize($filePath);
            }
        }
        
        // Se já estiver abaixo do tamanho alvo, não precisa limpar
        if ($currentSize <= $targetSize) {
            return true;
        }
        
        // Ordenar por último acesso (mais antigos primeiro)
        uasort($metadata, function($a, $b) {
            return ($a['lastAccessed'] ?? 0) - ($b['lastAccessed'] ?? 0);
        });
        
        // Remover modelos antigos até atingir o tamanho alvo
        $removed = 0;
        foreach ($metadata as $modelId => $data) {
            if ($currentSize <= $targetSize) {
                break;
            }
            
            $filePath = $cacheDir . $modelId;
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                if (unlink($filePath)) {
                    $currentSize -= $fileSize;
                    unset($metadata[$modelId]);
                    $removed++;
                }
            } else {
                // Arquivo não existe, remover dos metadados
                unset($metadata[$modelId]);
            }
        }
        
        // Atualizar arquivo de metadados
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
        
        error_log("ModelCacheHelper: Cache limpo. Removidos {$removed} arquivos.");
        return true;
    }
    
    /**
     * Configura headers HTTP para controle de cache
     * 
     * @param int $maxAge Tempo máximo de cache em segundos
     */
    public static function setCacheHeaders($maxAge = null) {
        $maxAge = $maxAge ?: self::$maxAge;
        
        header('Cache-Control: public, max-age=' . $maxAge);
        header('ETag: "' . self::$version . '"');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }
    
    /**
     * Verifica se o cliente tem uma versão em cache válida
     * 
     * @return bool Verdadeiro se o cliente tem uma versão válida
     */
    public static function clientHasValidCache() {
        // Verificar ETag
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if (!empty($ifNoneMatch) && $ifNoneMatch === '"' . self::$version . '"') {
            return true;
        }
        
        // Verificar If-Modified-Since
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
        if (!empty($ifModifiedSince)) {
            $modifiedSince = strtotime($ifModifiedSince);
            $lastModified = filemtime(ROOT_PATH . '/public' . self::$cachePath . 'metadata.json');
            
            if ($lastModified <= $modifiedSince) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Envia resposta 304 Not Modified se o cliente tiver uma versão válida em cache
     * 
     * @return bool Verdadeiro se uma resposta 304 foi enviada
     */
    public static function sendNotModifiedIfValid() {
        if (self::clientHasValidCache()) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
        
        return false;
    }
    
    /**
     * Limpa todo o cache
     * 
     * @return bool Sucesso da operação
     */
    public static function clearCache() {
        if (!self::isCacheEnabled()) {
            return false;
        }
        
        $cacheDir = ROOT_PATH . '/public' . self::$cachePath;
        $metadataFile = $cacheDir . 'metadata.json';
        
        if (file_exists($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true);
            if ($metadata) {
                foreach ($metadata as $modelId => $data) {
                    $filePath = $cacheDir . $modelId;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
            
            // Limpar metadados
            file_put_contents($metadataFile, '{}');
        }
        
        return true;
    }
    
    /**
     * Gera os parâmetros de URL para cache no cliente
     * 
     * @param string $modelId Identificador único do modelo
     * @return array Parâmetros para a URL
     */
    public static function getClientCacheParameters($modelId) {
        return [
            'v' => self::$version,
            'id' => $modelId,
            't' => time()
        ];
    }
    
    /**
     * Obtém informações gerais sobre o cache
     * 
     * @return array Informações sobre o cache
     */
    public static function getCacheInfo() {
        if (!self::isCacheEnabled()) {
            return [
                'enabled' => false,
                'reason' => 'Cache desabilitado'
            ];
        }
        
        $cacheDir = ROOT_PATH . '/public' . self::$cachePath;
        $metadataFile = $cacheDir . 'metadata.json';
        
        $metadata = [];
        if (file_exists($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true) ?: [];
        }
        
        $totalSize = 0;
        $modelCount = count($metadata);
        $formats = [];
        
        foreach ($metadata as $modelId => $data) {
            $totalSize += $data['size'] ?? 0;
            $extension = $data['extension'] ?? 'unknown';
            $formats[$extension] = ($formats[$extension] ?? 0) + 1;
        }
        
        return [
            'enabled' => true,
            'version' => self::$version,
            'models' => $modelCount,
            'size' => $totalSize,
            'formats' => $formats,
            'maxSize' => self::$maxCacheSize,
            'usagePercent' => $totalSize / self::$maxCacheSize * 100,
            'directory' => self::$cachePath
        ];
    }
}
