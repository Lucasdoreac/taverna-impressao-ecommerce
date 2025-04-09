<?php
/**
 * QuotationCache
 * 
 * Implementa um sistema de cache adaptativo para o Sistema de Cotação Automatizada,
 * otimizando o desempenho para consultas repetidas e reduzindo o overhead de análise
 * de modelos 3D complexos.
 * 
 * Este componente segue os guardrails de segurança da Taverna da Impressão 3D,
 * implementando validação rigorosa de entradas, sanitização de chaves de cache,
 * e controle de expiração para prevenir ataques de cache poisoning.
 * 
 * @package App\Lib\Analysis
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */
class QuotationCache {
    /**
     * Instância singleton
     * 
     * @var QuotationCache
     */
    private static $instance = null;
    
    /**
     * Diretório de armazenamento do cache
     * 
     * @var string
     */
    private $cacheDir;
    
    /**
     * Tempo de vida padrão do cache em segundos (1 hora)
     * 
     * @var int
     */
    private $defaultTtl = 3600;
    
    /**
     * Prefixo para chaves de cache
     * 
     * @var string
     */
    private $keyPrefix = 'quotation_';
    
    /**
     * Cache em memória para acesso rápido
     * 
     * @var array
     */
    private $memoryCache = [];
    
    /**
     * Tamanho máximo do cache em memória
     * 
     * @var int
     */
    private $memoryCacheSize = 50;
    
    /**
     * Estatísticas de uso do cache
     * 
     * @var array
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'memory_hits' => 0,
        'disk_hits' => 0,
        'writes' => 0,
        'errors' => 0
    ];
    
    /**
     * Mapa de frequência de acesso para chaves
     * 
     * @var array
     */
    private $accessFrequency = [];
    
    /**
     * Construtor privado (padrão Singleton)
     */
    private function __construct() {
        // Definir diretório de cache
        $this->cacheDir = dirname(dirname(dirname(__DIR__))) . '/cache/quotation';
        
        // Garantir que o diretório de cache existe
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new Exception("Não foi possível criar o diretório de cache: {$this->cacheDir}");
            }
        }
        
        // Verificar permissões do diretório
        if (!is_writable($this->cacheDir)) {
            throw new Exception("Diretório de cache não tem permissão de escrita: {$this->cacheDir}");
        }
    }
    
    /**
     * Obtém a instância singleton
     * 
     * @return QuotationCache Instância da classe
     */
    public static function getInstance(): QuotationCache {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Gera uma chave de cache segura a partir de parâmetros de cotação
     * 
     * @param array $params Parâmetros de cotação (modelo, material, etc.)
     * @return string Chave de cache segura
     */
    public function generateKey(array $params): string {
        // Validar parâmetros
        $this->validateParams($params);
        
        // Extrair atributos relevantes para a chave
        $keyParams = $this->extractKeyParams($params);
        
        // Serializar e criar hash SHA-256
        $serialized = json_encode($keyParams);
        $hash = hash('sha256', $serialized);
        
        // Adicionar prefixo para evitar colisões
        return $this->keyPrefix . $hash;
    }
    
    /**
     * Valida parâmetros de cotação para prevenção de ataques
     * 
     * @param array $params Parâmetros a validar
     * @throws Exception Se parâmetros forem inválidos
     */
    private function validateParams(array $params): void {
        // Verificar parâmetros obrigatórios
        if (empty($params['model_id']) && empty($params['file_path'])) {
            throw new Exception("Parâmetro model_id ou file_path é obrigatório");
        }
        
        // Validar model_id (deve ser um inteiro positivo ou um hash)
        if (!empty($params['model_id'])) {
            if (is_string($params['model_id'])) {
                if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $params['model_id'])) {
                    throw new Exception("Formato de model_id inválido");
                }
            } elseif (is_int($params['model_id'])) {
                if ($params['model_id'] <= 0) {
                    throw new Exception("model_id deve ser um inteiro positivo");
                }
            } else {
                throw new Exception("Tipo de model_id inválido");
            }
        }
        
        // Validar file_path (evitar path traversal)
        if (!empty($params['file_path'])) {
            // Normalizar caminho
            $realPath = realpath($params['file_path']);
            
            // Verificar se o arquivo existe
            if (!$realPath || !file_exists($realPath)) {
                throw new Exception("Arquivo não encontrado: {$params['file_path']}");
            }
            
            // Verificar extensão de arquivo (prevenção contra upload de arquivos maliciosos)
            $allowedExtensions = ['stl', 'obj', '3mf', 'gcode'];
            $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                throw new Exception("Tipo de arquivo não suportado: {$extension}");
            }
            
            // Verificação de diretório (prevenção contra path traversal)
            $allowedDirs = [
                dirname(dirname(dirname(__DIR__))) . '/uploads',
                dirname(dirname(dirname(__DIR__))) . '/models',
                dirname(dirname(dirname(__DIR__))) . '/customer_models',
                dirname(dirname(dirname(__DIR__))) . '/tests/test_data'
            ];
            
            $isAllowed = false;
            foreach ($allowedDirs as $dir) {
                if (strpos($realPath, realpath($dir)) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
            
            if (!$isAllowed) {
                throw new Exception("Acesso negado ao arquivo em diretório não autorizado");
            }
        }
        
        // Validar material (se fornecido)
        if (isset($params['material'])) {
            if (!is_string($params['material']) || strlen($params['material']) > 50) {
                throw new Exception("Formato de material inválido");
            }
        }
        
        // Validar qualidade (se fornecida)
        if (isset($params['quality'])) {
            if (!is_string($params['quality']) && !is_numeric($params['quality'])) {
                throw new Exception("Formato de qualidade inválido");
            }
        }
        
        // Validar configurações personalizadas (se fornecidas)
        if (isset($params['custom_settings']) && !is_array($params['custom_settings'])) {
            throw new Exception("Configurações personalizadas devem ser um array");
        }
    }
    
    /**
     * Extrai parâmetros relevantes para geração da chave de cache
     * 
     * @param array $params Parâmetros completos
     * @return array Parâmetros relevantes para a chave
     */
    private function extractKeyParams(array $params): array {
        $keyParams = [];
        
        // Identificação do modelo (ID ou hash de arquivo)
        if (!empty($params['model_id'])) {
            $keyParams['model_id'] = $params['model_id'];
        } elseif (!empty($params['file_path'])) {
            // Usar MD5 do conteúdo do arquivo como identificador estável
            $keyParams['file_hash'] = md5_file($params['file_path']);
            // Adicionar tamanho do arquivo para maior precisão
            $keyParams['file_size'] = filesize($params['file_path']);
        }
        
        // Parâmetros que afetam a cotação
        if (isset($params['material'])) {
            $keyParams['material'] = $params['material'];
        }
        
        if (isset($params['quality'])) {
            $keyParams['quality'] = $params['quality'];
        }
        
        if (isset($params['scale'])) {
            $keyParams['scale'] = (float)$params['scale'];
        }
        
        // Adicionar configurações personalizadas relevantes
        if (isset($params['custom_settings']) && is_array($params['custom_settings'])) {
            // Incluir apenas configurações que afetam o preço
            $relevantSettings = ['infill', 'supports', 'layer_height', 'finish'];
            
            foreach ($relevantSettings as $setting) {
                if (isset($params['custom_settings'][$setting])) {
                    $keyParams['settings'][$setting] = $params['custom_settings'][$setting];
                }
            }
        }
        
        // Adicionar versão do algoritmo de cotação para invalidar cache após atualizações
        $keyParams['version'] = '1.0.0';
        
        return $keyParams;
    }
    
    /**
     * Obtém cotação do cache
     * 
     * @param string $key Chave de cache
     * @return array|null Dados da cotação ou null se não encontrado/expirado
     */
    public function get(string $key): ?array {
        // Sanitizar chave
        $key = $this->sanitizeKey($key);
        
        // Verificar cache em memória primeiro (mais rápido)
        if (isset($this->memoryCache[$key])) {
            $cacheItem = $this->memoryCache[$key];
            
            // Verificar expiração
            if (time() < $cacheItem['expires_at']) {
                // Atualizar estatísticas
                $this->stats['hits']++;
                $this->stats['memory_hits']++;
                $this->updateAccessFrequency($key);
                
                return $cacheItem['data'];
            }
            
            // Expirado, remover da memória
            unset($this->memoryCache[$key]);
        }
        
        // Verificar cache em disco
        $filePath = $this->getCacheFilePath($key);
        
        if (file_exists($filePath)) {
            try {
                $content = file_get_contents($filePath);
                $cacheItem = json_decode($content, true);
                
                // Verificar se o conteúdo é válido
                if (!is_array($cacheItem) || !isset($cacheItem['expires_at']) || !isset($cacheItem['data'])) {
                    $this->stats['errors']++;
                    return null;
                }
                
                // Verificar expiração
                if (time() < $cacheItem['expires_at']) {
                    // Atualizar estatísticas
                    $this->stats['hits']++;
                    $this->stats['disk_hits']++;
                    $this->updateAccessFrequency($key);
                    
                    // Adicionar ao cache em memória para acesso mais rápido no futuro
                    $this->addToMemoryCache($key, $cacheItem);
                    
                    return $cacheItem['data'];
                }
                
                // Expirado, remover arquivo
                @unlink($filePath);
            } catch (Exception $e) {
                $this->stats['errors']++;
                return null;
            }
        }
        
        // Item não encontrado ou expirado
        $this->stats['misses']++;
        return null;
    }
    
    /**
     * Armazena cotação no cache
     * 
     * @param string $key Chave de cache
     * @param array $data Dados da cotação
     * @param int|null $ttl Tempo de vida em segundos (null para usar padrão)
     * @return bool Sucesso da operação
     */
    public function set(string $key, array $data, ?int $ttl = null): bool {
        // Sanitizar chave
        $key = $this->sanitizeKey($key);
        
        // Determinar TTL
        $ttl = $ttl ?? $this->defaultTtl;
        
        // Criar item de cache
        $cacheItem = [
            'created_at' => time(),
            'expires_at' => time() + $ttl,
            'data' => $data
        ];
        
        // Salvar no cache em memória
        $this->addToMemoryCache($key, $cacheItem);
        
        // Salvar no cache em disco
        $filePath = $this->getCacheFilePath($key);
        
        try {
            // Escrever em arquivo temporário primeiro para garantir atomicidade
            $tempFile = $filePath . '.tmp';
            $content = json_encode($cacheItem, JSON_PRETTY_PRINT);
            
            if (file_put_contents($tempFile, $content, LOCK_EX) === false) {
                $this->stats['errors']++;
                return false;
            }
            
            // Mover arquivo temporário para o arquivo final (operação atômica)
            if (!rename($tempFile, $filePath)) {
                @unlink($tempFile);
                $this->stats['errors']++;
                return false;
            }
            
            // Atualizar estatísticas
            $this->stats['writes']++;
            $this->updateAccessFrequency($key);
            
            return true;
        } catch (Exception $e) {
            $this->stats['errors']++;
            return false;
        }
    }
    
    /**
     * Sanitiza a chave de cache para segurança
     * 
     * @param string $key Chave a ser sanitizada
     * @return string Chave sanitizada
     */
    private function sanitizeKey(string $key): string {
        // Remover caracteres não permitidos
        $key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
        
        // Limitar tamanho
        return substr($key, 0, 100);
    }
    
    /**
     * Obtém o caminho do arquivo de cache para uma chave
     * 
     * @param string $key Chave de cache
     * @return string Caminho completo do arquivo
     */
    private function getCacheFilePath(string $key): string {
        // Usar os primeiros 2 caracteres para sharding do diretório
        $shardDir = substr($key, 0, 2);
        $cacheSubDir = $this->cacheDir . '/' . $shardDir;
        
        // Garantir que o subdiretório existe
        if (!is_dir($cacheSubDir) && !mkdir($cacheSubDir, 0755, true)) {
            throw new Exception("Não foi possível criar o subdiretório de cache: {$cacheSubDir}");
        }
        
        return $cacheSubDir . '/' . $key . '.cache';
    }
    
    /**
     * Adiciona um item ao cache em memória, gerenciando o tamanho máximo
     * 
     * @param string $key Chave de cache
     * @param array $cacheItem Item de cache
     */
    private function addToMemoryCache(string $key, array $cacheItem): void {
        // Adicionar ao cache em memória
        $this->memoryCache[$key] = $cacheItem;
        
        // Verificar tamanho do cache em memória
        if (count($this->memoryCache) > $this->memoryCacheSize) {
            // Remover o item menos acessado
            $leastAccessedKey = $this->getLeastAccessedKey();
            if ($leastAccessedKey !== null) {
                unset($this->memoryCache[$leastAccessedKey]);
                unset($this->accessFrequency[$leastAccessedKey]);
            }
        }
    }
    
    /**
     * Atualiza a frequência de acesso para uma chave
     * 
     * @param string $key Chave de cache
     */
    private function updateAccessFrequency(string $key): void {
        if (!isset($this->accessFrequency[$key])) {
            $this->accessFrequency[$key] = 0;
        }
        
        $this->accessFrequency[$key]++;
    }
    
    /**
     * Obtém a chave menos acessada do cache em memória
     * 
     * @return string|null Chave menos acessada ou null se vazio
     */
    private function getLeastAccessedKey(): ?string {
        if (empty($this->accessFrequency)) {
            return null;
        }
        
        // Encontrar a chave com menor contagem de acesso
        $leastAccessedKey = null;
        $minAccess = PHP_INT_MAX;
        
        foreach ($this->accessFrequency as $key => $count) {
            if ($count < $minAccess && isset($this->memoryCache[$key])) {
                $minAccess = $count;
                $leastAccessedKey = $key;
            }
        }
        
        return $leastAccessedKey;
    }
    
    /**
     * Invalida um item específico do cache
     * 
     * @param string $key Chave de cache
     * @return bool Sucesso da operação
     */
    public function invalidate(string $key): bool {
        // Sanitizar chave
        $key = $this->sanitizeKey($key);
        
        // Remover do cache em memória
        unset($this->memoryCache[$key]);
        unset($this->accessFrequency[$key]);
        
        // Remover do cache em disco
        $filePath = $this->getCacheFilePath($key);
        
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Limpa todo o cache (memória e disco)
     * 
     * @return bool Sucesso da operação
     */
    public function clear(): bool {
        // Limpar cache em memória
        $this->memoryCache = [];
        $this->accessFrequency = [];
        
        // Limpar cache em disco
        $success = true;
        
        try {
            $this->clearDirectory($this->cacheDir);
        } catch (Exception $e) {
            $this->stats['errors']++;
            $success = false;
        }
        
        // Reiniciar estatísticas
        $this->resetStats();
        
        return $success;
    }
    
    /**
     * Limpa recursivamente um diretório
     * 
     * @param string $dir Diretório a ser limpo
     */
    private function clearDirectory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                // Limpar subdiretório recursivamente
                $this->clearDirectory($path);
                
                // Remover diretório vazio
                @rmdir($path);
            } else {
                // Remover arquivo
                @unlink($path);
            }
        }
    }
    
    /**
     * Remove itens expirados do cache
     * 
     * @return int Número de itens removidos
     */
    public function purgeExpired(): int {
        $removedCount = 0;
        
        // Remover itens expirados do cache em memória
        $now = time();
        foreach ($this->memoryCache as $key => $item) {
            if ($now >= $item['expires_at']) {
                unset($this->memoryCache[$key]);
                unset($this->accessFrequency[$key]);
                $removedCount++;
            }
        }
        
        // Remover itens expirados do cache em disco
        try {
            $removedCount += $this->purgeExpiredFromDisk();
        } catch (Exception $e) {
            $this->stats['errors']++;
        }
        
        return $removedCount;
    }
    
    /**
     * Remove itens expirados do cache em disco
     * 
     * @return int Número de itens removidos
     */
    private function purgeExpiredFromDisk(): int {
        $removedCount = 0;
        $now = time();
        
        // Percorrer todos os subdiretórios
        $dirs = scandir($this->cacheDir);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $subDir = $this->cacheDir . '/' . $dir;
            
            if (!is_dir($subDir)) {
                continue;
            }
            
            // Percorrer todos os arquivos no subdiretório
            $files = scandir($subDir);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || !preg_match('/\.cache$/', $file)) {
                    continue;
                }
                
                $filePath = $subDir . '/' . $file;
                
                if (!is_file($filePath)) {
                    continue;
                }
                
                try {
                    $content = file_get_contents($filePath);
                    $cacheItem = json_decode($content, true);
                    
                    // Verificar se o conteúdo é válido
                    if (!is_array($cacheItem) || !isset($cacheItem['expires_at'])) {
                        // Conteúdo inválido, remover
                        @unlink($filePath);
                        $removedCount++;
                        continue;
                    }
                    
                    // Verificar expiração
                    if ($now >= $cacheItem['expires_at']) {
                        @unlink($filePath);
                        $removedCount++;
                    }
                } catch (Exception $e) {
                    // Erro ao ler arquivo, remover
                    @unlink($filePath);
                    $removedCount++;
                }
            }
        }
        
        return $removedCount;
    }
    
    /**
     * Redefine as estatísticas de uso do cache
     */
    public function resetStats(): void {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'memory_hits' => 0,
            'disk_hits' => 0,
            'writes' => 0,
            'errors' => 0
        ];
    }
    
    /**
     * Obtém estatísticas de uso do cache
     * 
     * @return array Estatísticas do cache
     */
    public function getStats(): array {
        // Calcular estatísticas adicionais
        $totalRequests = $this->stats['hits'] + $this->stats['misses'];
        $hitRatio = $totalRequests > 0 ? ($this->stats['hits'] / $totalRequests) * 100 : 0;
        
        return array_merge($this->stats, [
            'total_requests' => $totalRequests,
            'hit_ratio' => round($hitRatio, 2),
            'memory_cache_size' => count($this->memoryCache),
            'memory_cache_limit' => $this->memoryCacheSize
        ]);
    }
    
    /**
     * Configura o tamanho máximo do cache em memória
     * 
     * @param int $size Novo tamanho máximo
     * @return self Instância atual para encadeamento
     */
    public function setMemoryCacheSize(int $size): self {
        if ($size < 10) {
            throw new Exception("Tamanho do cache em memória deve ser pelo menos 10");
        }
        
        $this->memoryCacheSize = $size;
        
        // Se o cache atual for maior que o novo tamanho, remover itens
        while (count($this->memoryCache) > $this->memoryCacheSize) {
            $leastAccessedKey = $this->getLeastAccessedKey();
            if ($leastAccessedKey === null) {
                break;
            }
            
            unset($this->memoryCache[$leastAccessedKey]);
            unset($this->accessFrequency[$leastAccessedKey]);
        }
        
        return $this;
    }
    
    /**
     * Configura o tempo de vida padrão do cache
     * 
     * @param int $ttl Tempo de vida em segundos
     * @return self Instância atual para encadeamento
     */
    public function setDefaultTtl(int $ttl): self {
        if ($ttl < 60) {
            throw new Exception("Tempo de vida padrão deve ser pelo menos 60 segundos");
        }
        
        $this->defaultTtl = $ttl;
        
        return $this;
    }
    
    /**
     * Calcula dinamicamente o TTL ideal com base na frequência de acesso
     * e na complexidade do modelo
     * 
     * @param string $key Chave de cache
     * @param float $complexityScore Pontuação de complexidade (0-100)
     * @return int TTL calculado em segundos
     */
    public function calculateAdaptiveTtl(string $key, float $complexityScore): int {
        // TTL base (2 horas)
        $baseTtl = 2 * 3600;
        
        // Fator de complexidade (modelos mais complexos têm TTL maior)
        // Escala: 1.0 (simples) a 3.0 (muito complexo)
        $complexityFactor = 1.0 + ($complexityScore / 100 * 2.0);
        
        // Fator de frequência de acesso (itens mais acessados têm TTL maior)
        $accessCount = $this->accessFrequency[$key] ?? 0;
        // Escala: 1.0 (nunca acessado) a 2.0 (muito acessado)
        $accessFactor = 1.0 + min(1.0, $accessCount / 10);
        
        // Calcular TTL final
        $calculatedTtl = (int)($baseTtl * $complexityFactor * $accessFactor);
        
        // Limitar TTL entre 1 hora e 24 horas
        return max(3600, min($calculatedTtl, 24 * 3600));
    }
    
    /**
     * Ajusta o TTL de um item de cache existente
     * 
     * @param string $key Chave de cache
     * @param int $newTtl Novo TTL em segundos
     * @return bool Sucesso da operação
     */
    public function extendTtl(string $key, int $newTtl): bool {
        // Sanitizar chave
        $key = $this->sanitizeKey($key);
        
        // Verificar se o item existe no cache em memória
        if (isset($this->memoryCache[$key])) {
            $this->memoryCache[$key]['expires_at'] = time() + $newTtl;
        }
        
        // Verificar se o item existe no cache em disco
        $filePath = $this->getCacheFilePath($key);
        
        if (file_exists($filePath)) {
            try {
                $content = file_get_contents($filePath);
                $cacheItem = json_decode($content, true);
                
                // Verificar se o conteúdo é válido
                if (!is_array($cacheItem) || !isset($cacheItem['expires_at']) || !isset($cacheItem['data'])) {
                    $this->stats['errors']++;
                    return false;
                }
                
                // Atualizar tempo de expiração
                $cacheItem['expires_at'] = time() + $newTtl;
                
                // Salvar alterações
                $content = json_encode($cacheItem, JSON_PRETTY_PRINT);
                
                // Escrever em arquivo temporário primeiro para garantir atomicidade
                $tempFile = $filePath . '.tmp';
                
                if (file_put_contents($tempFile, $content, LOCK_EX) === false) {
                    $this->stats['errors']++;
                    return false;
                }
                
                // Mover arquivo temporário para o arquivo final (operação atômica)
                if (!rename($tempFile, $filePath)) {
                    @unlink($tempFile);
                    $this->stats['errors']++;
                    return false;
                }
                
                return true;
            } catch (Exception $e) {
                $this->stats['errors']++;
                return false;
            }
        }
        
        return false;
    }
}