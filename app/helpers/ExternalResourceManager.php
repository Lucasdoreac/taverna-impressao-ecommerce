<?php
/**
 * ExternalResourceManager - Helper para gerenciar recursos externos
 * 
 * Esta classe complementa o ResourceOptimizerHelper fornecendo ferramentas para
 * monitorar, substituir e otimizar o carregamento de recursos externos, reduzindo
 * a dependência de serviços de terceiros quando possível.
 */
class ExternalResourceManager {
    // Lista de domínios externos comuns
    private static $externalDomains = [
        'cdnjs.cloudflare.com',
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'cdn.jsdelivr.net',
        'code.jquery.com',
        'ajax.googleapis.com',
        'maxcdn.bootstrapcdn.com',
        'unpkg.com',
        'maps.googleapis.com'
    ];
    
    // Mapeamento de bibliotecas populares para versões locais
    private static $libraryMap = [
        'jquery' => [
            'cdn' => [
                'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js',
                'https://code.jquery.com/jquery-3.6.0.min.js',
                'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'
            ],
            'local' => 'vendor/jquery/jquery-3.6.0.min.js',
            'version' => '3.6.0'
        ],
        'font-awesome' => [
            'cdn' => [
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                'https://use.fontawesome.com/releases/v6.4.0/css/all.css'
            ],
            'local' => 'vendor/fontawesome/css/all.min.css',
            'version' => '6.4.0'
        ],
        'three.js' => [
            'cdn' => [
                'https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/three.js/132/three.min.js'
            ],
            'local' => 'vendor/threejs/three.min.js',
            'version' => '0.132.2'
        ],
        'bootstrap' => [
            'cdn' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
                'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css'
            ],
            'local' => [
                'js' => 'vendor/bootstrap/js/bootstrap.bundle.min.js',
                'css' => 'vendor/bootstrap/css/bootstrap.min.css'
            ],
            'version' => '5.2.3'
        ]
    ];
    
    /**
     * Verifica se uma URL é um recurso externo
     * 
     * @param string $url URL a verificar
     * @return bool True se for recurso externo
     */
    public static function isExternalResource($url) {
        if (empty($url)) {
            return false;
        }
        
        // Remover protocolo para comparação
        $urlParts = parse_url($url);
        if (!isset($urlParts['host'])) {
            return false;
        }
        
        $host = $urlParts['host'];
        
        // Verificar se está na lista de domínios externos
        foreach (self::$externalDomains as $domain) {
            if (strpos($host, $domain) !== false) {
                return true;
            }
        }
        
        // Verificar se o domínio é diferente do domínio atual
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        return ($host !== $currentHost && !empty($currentHost));
    }
    
    /**
     * Identifica a biblioteca a partir de uma URL de CDN
     * 
     * @param string $url URL do CDN
     * @return array|null Informações da biblioteca ou null se não identificada
     */
    public static function identifyLibrary($url) {
        foreach (self::$libraryMap as $lib => $info) {
            if (is_array($info['cdn'])) {
                foreach ($info['cdn'] as $cdnUrl) {
                    if (strpos($url, $cdnUrl) === 0 || $url === $cdnUrl) {
                        return [
                            'name' => $lib,
                            'version' => $info['version'],
                            'local' => $info['local']
                        ];
                    }
                }
            } else if ($url === $info['cdn'] || strpos($url, $info['cdn']) === 0) {
                return [
                    'name' => $lib,
                    'version' => $info['version'],
                    'local' => $info['local']
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Obtém o caminho local para um recurso externo
     * 
     * @param string $url URL do recurso externo
     * @return string|