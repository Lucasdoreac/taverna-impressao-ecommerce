<?php
namespace App\Lib\Cache;

/**
 * ReportCache
 * 
 * Sistema de cache para relatórios com controle de expiração e invalidação seletiva.
 * Implementa padrão de armazenamento em arquivo com serialização segura
 * e mecanismos de prevenção contra race conditions.
 * 
 * @package App\Lib\Cache
 * @version 1.0.0
 */
class ReportCache
{
    /**
     * @var string Diretório de cache
     */
    private $cacheDir;
    
    /**
     * @var int Tempo de expiração padrão em segundos (1 hora)
     */
    private $defaultExpiration = 3600;
    
    /**
     * @var string Prefixo para chaves de cache
     */
    private $keyPrefix = 'report_';
    
    /**
     * @var bool Flag para habilitar/desabilitar o cache
     */
    private $enabled = true;
    
    /**
     * Construtor
     * 
     * @param string $cacheDir Diretório opcional para armazenamento do cache
     * @param int $defaultExpiration Tempo opcional de expiração em segundos
     */
    public function __construct(string $cacheDir = null, int $defaultExpiration = null)
    {
        // Definir diretório de cache
        $this->cacheDir = $cacheDir ?? dirname(__DIR__, 3) . '/cache/reports';
        
        // Garantir que o diretório existe
        if (!is_dir($this->cacheDir)) {
            $this->createCacheDirectory();
        }
        
        // Definir tempo de expiração personalizado se fornecido
        if ($defaultExpiration !== null) {
            $this->defaultExpiration = $defaultExpiration;
        }
        
        // Verificar se o cache está habilitado nas configurações
        $this->checkCacheEnabled();
    }
    
    /**
     * Cria o diretório de cache com permissões adequadas
     * 
     * @return bool Sucesso da operação
     * @throws \RuntimeException Se não for possível criar o diretório
     */
    private function createCacheDirectory(): bool
    {
        if (!mkdir($this->cacheDir, 0755, true)) {
            throw new \RuntimeException("Não foi possível criar o diretório de cache: {$this->cacheDir}");
        }
        
        // Arquivo .htaccess para proteção adicional
        file_put_contents("{$this->cacheDir}/.htaccess", "Deny from all");
        
        return true;
    }
    
    /**
     * Verifica se o cache está habilitado nas configurações
     */
    private function checkCacheEnabled(): void
    {
        // Se existir a configuração para desabilitar cache
        if (defined('DISABLE_REPORT_CACHE') && DISABLE_REPORT_CACHE === true) {
            $this->enabled = false;
        }
    }
    
    /**
     * Gera o caminho do arquivo de cache baseado na chave
     * 
     * @param string $key Chave de cache
     * @return string Caminho completo do arquivo
     */
    private function getCacheFilePath(string $key): string
    {
        // Normalizar chave para uso em nome de arquivo
        $normalizedKey = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $key);
        
        // Adicionar prefixo e usar hash para evitar colisões e arquivos muito longos
        $filename = $this->keyPrefix . md5($normalizedKey) . '.cache';
        
        return $this->cacheDir . '/' . $filename;
    }
    
    /**
     * Gera uma chave de cache baseada nos parâmetros
     * 
     * @param string $reportType Tipo de relatório
     * @param array $parameters Parâmetros do relatório
     * @return string Chave de cache
     */
    public function generateKey(string $reportType, array $parameters = []): string
    {
        // Ordenar parâmetros para garantir que a mesma combinação gere a mesma chave
        ksort($parameters);
        
        // Converter parâmetros para string
        $paramString = json_encode($parameters);
        
        return $reportType . '_' . md5($paramString);
    }
    
    /**
     * Verifica se um item existe no cache e não está expirado
     * 
     * @param string $key Chave de cache
     * @return bool Verdadeiro se o item existe e é válido
     */
    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheFile = $this->getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        // Verificar se o arquivo está corrompido ou vazio
        if (filesize($cacheFile) < 10) {
            $this->delete($key);
            return false;
        }
        
        // Ler metadados do cache
        $contents = file_get_contents($cacheFile);
        $cacheData = $this->unserialize($contents);
        
        if ($cacheData === false || !isset($cacheData['expires_at'])) {
            $this->delete($key);
            return false;
        }
        
        // Verificar expiração
        return $cacheData['expires_at'] > time();
    }
    
    /**
     * Obtém um item do cache
     * 
     * @param string $key Chave de cache
     * @param mixed $default Valor padrão se o item não existir
     * @return mixed Dados armazenados ou valor padrão
     */
    public function get(string $key, $default = null)
    {
        if (!$this->enabled || !$this->has($key)) {
            return $default;
        }
        
        $cacheFile = $this->getCacheFilePath($key);
        $contents = file_get_contents($cacheFile);
        $cacheData = $this->unserialize($contents);
        
        if ($cacheData === false) {
            return $default;
        }
        
        // Registrar hitcount do cache para análise
        $this->logCacheHit($key);
        
        return $cacheData['data'];
    }
    
    /**
     * Armazena um item no cache
     * 
     * @param string $key Chave de cache
     * @param mixed $data Dados a serem armazenados
     * @param int|null $expiration Tempo de expiração em segundos (opcional)
     * @return bool Sucesso da operação
     */
    public function set(string $key, $data, int $expiration = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheFile = $this->getCacheFilePath($key);
        
        // Calcular timestamp de expiração
        $expiresAt = time() + ($expiration ?? $this->defaultExpiration);
        
        // Preparar dados do cache
        $cacheData = [
            'key' => $key,
            'data' => $data,
            'created_at' => time(),
            'expires_at' => $expiresAt,
            'hits' => 0
        ];
        
        // Serializar dados
        $contents = $this->serialize($cacheData);
        
        // Escrita atômica para evitar race conditions
        $tempFile = $cacheFile . '.tmp.' . uniqid();
        if (file_put_contents($tempFile, $contents) === false) {
            return false;
        }
        
        // Renomear é atômico na maioria dos sistemas de arquivos
        if (!rename($tempFile, $cacheFile)) {
            unlink($tempFile);
            return false;
        }
        
        return true;
    }
    
    /**
     * Remove um item do cache
     * 
     * @param string $key Chave de cache
     * @return bool Sucesso da operação
     */
    public function delete(string $key): bool
    {
        $cacheFile = $this->getCacheFilePath($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Invalida todos os caches de um tipo específico de relatório
     * 
     * @param string $reportType Tipo de relatório
     * @return int Número de itens invalidados
     */
    public function invalidateReportType(string $reportType): int
    {
        $count = 0;
        $files = glob($this->cacheDir . '/' . $this->keyPrefix . '*.cache');
        
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $cacheData = $this->unserialize($contents);
            
            if ($cacheData !== false && isset($cacheData['key']) && 
                strpos($cacheData['key'], $reportType . '_') === 0) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Limpa todo o cache de relatórios
     * 
     * @return int Número de itens removidos
     */
    public function clear(): int
    {
        $count = 0;
        $files = glob($this->cacheDir . '/' . $this->keyPrefix . '*.cache');
        
        foreach ($files as $file) {
            unlink($file);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Remove itens de cache expirados
     * 
     * @return int Número de itens removidos
     */
    public function clearExpired(): int
    {
        $count = 0;
        $now = time();
        $files = glob($this->cacheDir . '/' . $this->keyPrefix . '*.cache');
        
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $cacheData = $this->unserialize($contents);
            
            if ($cacheData !== false && isset($cacheData['expires_at']) && $cacheData['expires_at'] < $now) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Serializa dados para armazenamento seguro
     * 
     * @param mixed $data Dados a serem serializados
     * @return string Dados serializados
     */
    private function serialize($data): string
    {
        // Usar JSON para serialização mais segura, alternativa a serialize()
        return json_encode($data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Desserializa dados do cache com validação
     * 
     * @param string $data Dados serializados
     * @return mixed Dados originais ou false em caso de erro
     */
    private function unserialize(string $data)
    {
        // Usar JSON para desserialização mais segura
        $result = json_decode($data, true);
        
        // Verificar erro de parsing JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Erro ao desserializar cache: ' . json_last_error_msg());
            return false;
        }
        
        return $result;
    }
    
    /**
     * Registra hit de cache para análise de performance
     * 
     * @param string $key Chave de cache
     */
    private function logCacheHit(string $key): void
    {
        $cacheFile = $this->getCacheFilePath($key);
        
        // Ler dados atuais
        $contents = file_get_contents($cacheFile);
        $cacheData = $this->unserialize($contents);
        
        if ($cacheData !== false) {
            // Incrementar contador de hits
            $cacheData['hits'] = ($cacheData['hits'] ?? 0) + 1;
            
            // Salvar de volta
            $contents = $this->serialize($cacheData);
            file_put_contents($cacheFile, $contents);
        }
    }
    
    /**
     * Retorna estatísticas sobre o uso do cache
     * 
     * @return array Estatísticas de uso
     */
    public function getStats(): array
    {
        $stats = [
            'total_items' => 0,
            'expired_items' => 0,
            'valid_items' => 0,
            'size_bytes' => 0,
            'hit_counts' => [],
            'by_report_type' => []
        ];
        
        $now = time();
        $files = glob($this->cacheDir . '/' . $this->keyPrefix . '*.cache');
        
        foreach ($files as $file) {
            $stats['total_items']++;
            $stats['size_bytes'] += filesize($file);
            
            $contents = file_get_contents($file);
            $cacheData = $this->unserialize($contents);
            
            if ($cacheData !== false) {
                // Verificar expiração
                if (isset($cacheData['expires_at']) && $cacheData['expires_at'] < $now) {
                    $stats['expired_items']++;
                } else {
                    $stats['valid_items']++;
                    
                    // Extrair tipo de relatório da chave
                    if (isset($cacheData['key'])) {
                        $keyParts = explode('_', $cacheData['key']);
                        $reportType = $keyParts[0];
                        
                        if (!isset($stats['by_report_type'][$reportType])) {
                            $stats['by_report_type'][$reportType] = 0;
                        }
                        
                        $stats['by_report_type'][$reportType]++;
                    }
                    
                    // Armazenar contadores de hit
                    if (isset($cacheData['key']) && isset($cacheData['hits'])) {
                        $stats['hit_counts'][$cacheData['key']] = $cacheData['hits'];
                    }
                }
            }
        }
        
        return $stats;
    }
}
