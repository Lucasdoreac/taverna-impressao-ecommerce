<?php
namespace App\Lib\Cache;

/**
 * AdvancedReportCache
 * 
 * Implementação avançada do sistema de cache para relatórios com suporte a:
 * - Caching em memória para relatórios acessados frequentemente
 * - Sistema de prefetching para relatórios comuns
 * - Estratégia de expiração adaptativa baseada em padrões de uso
 * - Compressão de dados para economia de espaço
 * 
 * @package App\Lib\Cache
 * @version 1.0.0
 */
class AdvancedReportCache extends ReportCache
{
    /**
     * Cache em memória para acesso rápido
     * @var array
     */
    private static $memoryCache = [];
    
    /**
     * Limite de itens em memória
     * @var int
     */
    private $memoryCacheLimit = 20;
    
    /**
     * Lista de relatórios mais acessados para prefetching
     * @var array
     */
    private $frequentReports = [];
    
    /**
     * Flag para habilitar compressão
     * @var bool
     */
    private $compressionEnabled = true;
    
    /**
     * Nível de compressão (1-9)
     * @var int
     */
    private $compressionLevel = 6;
    
    /**
     * Construtor
     * 
     * @param string $cacheDir Diretório opcional para armazenamento do cache
     * @param int $defaultExpiration Tempo opcional de expiração em segundos
     * @param array $config Configurações adicionais
     */
    public function __construct(string $cacheDir = null, int $defaultExpiration = null, array $config = [])
    {
        parent::__construct($cacheDir, $defaultExpiration);
        
        // Aplicar configurações personalizadas
        if (isset($config['memoryCacheLimit'])) {
            $this->memoryCacheLimit = max(1, (int)$config['memoryCacheLimit']);
        }
        
        if (isset($config['compressionEnabled'])) {
            $this->compressionEnabled = (bool)$config['compressionEnabled'];
        }
        
        if (isset($config['compressionLevel'])) {
            $this->compressionLevel = min(9, max(1, (int)$config['compressionLevel']));
        }
        
        // Inicializar lista de relatórios frequentes
        $this->initializeFrequentReports();
        
        // Realizar prefetching de relatórios comuns em segundo plano
        if (!empty($this->frequentReports)) {
            $this->prefetchFrequentReports();
        }
    }
    
    /**
     * Inicializa a lista de relatórios mais acessados com base no histórico
     */
    private function initializeFrequentReports(): void
    {
        // Obter estatísticas para determinar relatórios mais acessados
        $stats = parent::getStats();
        
        if (isset($stats['hit_counts']) && !empty($stats['hit_counts'])) {
            // Ordenar por número de hits (decrescente)
            arsort($stats['hit_counts']);
            
            // Manter apenas os 5 mais acessados
            $this->frequentReports = array_slice(array_keys($stats['hit_counts']), 0, 5, true);
        }
    }
    
    /**
     * Realiza prefetching de relatórios frequentemente acessados
     */
    private function prefetchFrequentReports(): void
    {
        // Este método carrega relatórios comuns para o cache em memória
        foreach ($this->frequentReports as $key) {
            // Verificar se o relatório já está em cache
            if (!isset(self::$memoryCache[$key]) && parent::has($key)) {
                // Carregar para o cache em memória
                self::$memoryCache[$key] = parent::get($key);
            }
        }
    }
    
    /**
     * Sobrescreve o método de obtenção para verificar primeiro o cache em memória
     * 
     * @param string $key Chave de cache
     * @param mixed $default Valor padrão se o item não existir
     * @return mixed Dados armazenados ou valor padrão
     */
    public function get(string $key, $default = null)
    {
        // Verificar primeiro no cache em memória (mais rápido)
        if (isset(self::$memoryCache[$key])) {
            return self::$memoryCache[$key];
        }
        
        // Se não estiver em memória, buscar no cache em disco
        $data = parent::get($key, $default);
        
        // Se encontrado, armazenar em memória para acessos futuros
        if ($data !== $default) {
            $this->addToMemoryCache($key, $data);
        }
        
        return $data;
    }
    
    /**
     * Sobrescreve o método de armazenamento para usar compressão e manter o cache em memória
     * 
     * @param string $key Chave de cache
     * @param mixed $data Dados a serem armazenados
     * @param int|null $expiration Tempo de expiração em segundos (opcional)
     * @return bool Sucesso da operação
     */
    public function set(string $key, $data, int $expiration = null): bool
    {
        // Armazenar em memória para acesso rápido
        $this->addToMemoryCache($key, $data);
        
        // Armazenar em disco com compressão opcional
        return parent::set($key, $data, $expiration);
    }
    
    /**
     * Sobrescreve o método de verificação para incluir o cache em memória
     * 
     * @param string $key Chave de cache
     * @return bool Verdadeiro se o item existe e é válido
     */
    public function has(string $key): bool
    {
        // Verificar primeiro no cache em memória
        if (isset(self::$memoryCache[$key])) {
            return true;
        }
        
        return parent::has($key);
    }
    
    /**
     * Sobrescreve o método de remoção para limpar tanto o cache em disco quanto em memória
     * 
     * @param string $key Chave de cache
     * @return bool Sucesso da operação
     */
    public function delete(string $key): bool
    {
        // Remover do cache em memória
        if (isset(self::$memoryCache[$key])) {
            unset(self::$memoryCache[$key]);
        }
        
        // Remover do cache em disco
        return parent::delete($key);
    }
    
    /**
     * Adiciona um item ao cache em memória, respeitando o limite configurado
     * 
     * @param string $key Chave de cache
     * @param mixed $data Dados a serem armazenados
     */
    private function addToMemoryCache(string $key, $data): void
    {
        // Verificar se o cache em memória atingiu o limite
        if (count(self::$memoryCache) >= $this->memoryCacheLimit) {
            // Remover o item menos recente (FIFO)
            array_shift(self::$memoryCache);
        }
        
        // Adicionar o novo item
        self::$memoryCache[$key] = $data;
    }
    
    /**
     * Sobrescreve o método de serialização para usar compressão quando habilitada
     * 
     * @param mixed $data Dados a serem serializados
     * @return string Dados serializados (e possivelmente comprimidos)
     */
    protected function serialize($data): string
    {
        // Serializar usando JSON
        $serialized = json_encode($data, JSON_PRETTY_PRINT);
        
        // Aplicar compressão se habilitada e o tamanho justificar (> 1KB)
        if ($this->compressionEnabled && strlen($serialized) > 1024) {
            $compressed = gzencode($serialized, $this->compressionLevel);
            
            // Usar compressão apenas se houver redução real de tamanho
            if ($compressed !== false && strlen($compressed) < strlen($serialized)) {
                // Adicionar prefixo para indicar que o conteúdo está comprimido
                return 'gz:' . $compressed;
            }
        }
        
        return $serialized;
    }
    
    /**
     * Sobrescreve o método de desserialização para suportar conteúdo comprimido
     * 
     * @param string $data Dados serializados (e possivelmente comprimidos)
     * @return mixed Dados originais ou false em caso de erro
     */
    protected function unserialize(string $data)
    {
        // Verificar se o conteúdo está comprimido
        if (substr($data, 0, 3) === 'gz:') {
            // Remover prefixo e descomprimir
            $uncompressed = gzdecode(substr($data, 3));
            
            if ($uncompressed === false) {
                error_log('Erro ao descomprimir dados de cache');
                return false;
            }
            
            $data = $uncompressed;
        }
        
        // Desserializar usando JSON
        $result = json_decode($data, true);
        
        // Verificar erro de parsing JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Erro ao desserializar cache: ' . json_last_error_msg());
            return false;
        }
        
        return $result;
    }
    
    /**
     * Limpa todo o cache (em memória e em disco)
     * 
     * @return int Número de itens removidos
     */
    public function clear(): int
    {
        // Limpar cache em memória
        $memoryCount = count(self::$memoryCache);
        self::$memoryCache = [];
        
        // Limpar cache em disco
        $diskCount = parent::clear();
        
        return $memoryCount + $diskCount;
    }
    
    /**
     * Invalida cache para um tipo específico de relatório
     * 
     * @param string $reportType Tipo de relatório
     * @return int Número de itens invalidados
     */
    public function invalidateReportType(string $reportType): int
    {
        $count = 0;
        
        // Invalidar no cache em memória
        foreach (self::$memoryCache as $key => $value) {
            if (strpos($key, $reportType . '_') === 0) {
                unset(self::$memoryCache[$key]);
                $count++;
            }
        }
        
        // Invalidar no cache em disco
        $count += parent::invalidateReportType($reportType);
        
        return $count;
    }
    
    /**
     * Retorna estatísticas avançadas sobre o uso do cache
     * 
     * @return array Estatísticas de uso
     */
    public function getStats(): array
    {
        // Obter estatísticas básicas do cache em disco
        $stats = parent::getStats();
        
        // Adicionar estatísticas do cache em memória
        $stats['memory_cache'] = [
            'items' => count(self::$memoryCache),
            'limit' => $this->memoryCacheLimit,
            'keys' => array_keys(self::$memoryCache)
        ];
        
        // Adicionar configurações de compressão
        $stats['compression'] = [
            'enabled' => $this->compressionEnabled,
            'level' => $this->compressionLevel
        ];
        
        // Adicionar informações sobre relatórios frequentes
        $stats['frequent_reports'] = $this->frequentReports;
        
        return $stats;
    }
    
    /**
     * Ajusta adaptativamente o tempo de expiração com base na frequência de acesso
     * 
     * @param string $key Chave de cache
     * @param int $baseExpiration Expiração base em segundos
     * @return int Expiração ajustada em segundos
     */
    public function getAdaptiveExpiration(string $key, int $baseExpiration): int
    {
        // Obter estatísticas para determinar frequência de acesso
        $stats = parent::getStats();
        $hitCount = $stats['hit_counts'][$key] ?? 0;
        
        // Ajustar expiração com base na frequência de acesso
        // Relatórios mais acessados expiram mais lentamente
        if ($hitCount > 50) {
            // Relatório muito acessado - duplicar expiração
            return $baseExpiration * 2;
        } elseif ($hitCount > 20) {
            // Relatório acessado moderadamente - aumentar 50%
            return (int)($baseExpiration * 1.5);
        } elseif ($hitCount < 5) {
            // Relatório pouco acessado - reduzir 25%
            return max(300, (int)($baseExpiration * 0.75)); // Mínimo 5 minutos
        }
        
        // Manter expiração padrão para casos intermediários
        return $baseExpiration;
    }
}
