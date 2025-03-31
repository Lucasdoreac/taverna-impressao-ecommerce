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
     * @return string|null Caminho local ou null se não disponível
     */
    public static function getLocalPath($url) {
        // Verificar se é um recurso externo
        if (!self::isExternalResource($url)) {
            return null;
        }
        
        // Identificar a biblioteca
        $library = self::identifyLibrary($url);
        if ($library) {
            // Verificar se é CSS ou JS para casos como Bootstrap
            if (is_array($library['local'])) {
                $ext = pathinfo($url, PATHINFO_EXTENSION);
                if ($ext === 'css' && isset($library['local']['css'])) {
                    return $library['local']['css'];
                } else if (($ext === 'js' || empty($ext)) && isset($library['local']['js'])) {
                    return $library['local']['js'];
                }
            } else {
                return $library['local'];
            }
        }
        
        // Verificação genérica baseada em padrões de URL
        if (strpos($url, 'fonts.googleapis.com') !== false) {
            // Fontes do Google - seria necessário baixar e hospedar localmente
            // Isso é mais complexo e pode requerer uma abordagem específica
            return null;
        }
        
        return null;
    }
    
    /**
     * Verifica se uma versão local está disponível
     * 
     * @param string $url URL do recurso externo
     * @return bool True se versão local existir
     */
    public static function hasLocalVersion($url) {
        $localPath = self::getLocalPath($url);
        if ($localPath) {
            $fullPath = ROOT_PATH . '/public/' . $localPath;
            return file_exists($fullPath);
        }
        
        return false;
    }
    
    /**
     * Gera a URL completa para o recurso local
     * 
     * @param string $url URL original
     * @return string URL para o recurso (local ou original)
     */
    public static function getResourceUrl($url) {
        if (self::hasLocalVersion($url)) {
            return BASE_URL . self::getLocalPath($url);
        }
        
        return $url;
    }
    
    /**
     * Verifica em uma string HTML a presença de recursos externos
     * e oferece recomendações para otimização
     * 
     * @param string $html Conteúdo HTML
     * @return array Lista de recomendações
     */
    public static function analyzeHtml($html) {
        $recommendations = [];
        $externalCount = 0;
        $localizable = 0;
        
        // Encontrar todos os scripts externos - EXPRESSÃO REGULAR CORRIGIDA
        preg_match_all('/<script[^>]*src=(["\'])(.*?)\\1[^>]*>/i', $html, $scriptMatches);
        if (!empty($scriptMatches[2])) {
            foreach ($scriptMatches[2] as $src) {
                if (self::isExternalResource($src)) {
                    $externalCount++;
                    
                    if (self::hasLocalVersion($src)) {
                        $localizable++;
                        $recommendations[] = "Script externo pode ser substituído por versão local: {$src}";
                    } else {
                        $recommendations[] = "Script externo sem versão local disponível: {$src}";
                    }
                }
            }
        }
        
        // Encontrar todos os links de CSS externos - EXPRESSÃO REGULAR CORRIGIDA
        preg_match_all('/<link[^>]*href=(["\'])(.*?)\\1[^>]*>/i', $html, $linkMatches);
        if (!empty($linkMatches[2])) {
            foreach ($linkMatches[2] as $href) {
                if (strpos($href, '.css') !== false && self::isExternalResource($href)) {
                    $externalCount++;
                    
                    if (self::hasLocalVersion($href)) {
                        $localizable++;
                        $recommendations[] = "CSS externo pode ser substituído por versão local: {$href}";
                    } else {
                        $recommendations[] = "CSS externo sem versão local disponível: {$href}";
                    }
                }
            }
        }
        
        // Encontrar todos os iframes externos - EXPRESSÃO REGULAR CORRIGIDA
        preg_match_all('/<iframe[^>]*src=(["\'])(.*?)\\1[^>]*>/i', $html, $iframeMatches);
        if (!empty($iframeMatches[2])) {
            foreach ($iframeMatches[2] as $src) {
                if (self::isExternalResource($src)) {
                    $externalCount++;
                    $recommendations[] = "iframe carrega conteúdo externo: {$src}";
                }
            }
        }
        
        // Encontrar imagens externas - EXPRESSÃO REGULAR CORRIGIDA
        preg_match_all('/<img[^>]*src=(["\'])(.*?)\\1[^>]*>/i', $html, $imgMatches);
        if (!empty($imgMatches[2])) {
            foreach ($imgMatches[2] as $src) {
                if (self::isExternalResource($src)) {
                    $externalCount++;
                    $recommendations[] = "Imagem carregada de fonte externa: {$src}";
                }
            }
        }
        
        // Adicionar estatísticas
        array_unshift($recommendations, "Total de recursos externos encontrados: {$externalCount}");
        array_unshift($recommendations, "Recursos que podem ser localizados: {$localizable}");
        
        return $recommendations;
    }
    
    /**
     * Otimiza o carregamento de um recurso externo
     * 
     * @param string $url URL do recurso
     * @param string $type Tipo (css, js)
     * @param array $options Opções adicionais
     * @return string HTML otimizado para o recurso
     */
    public static function optimizeExternalResource($url, $type = '', $options = []) {
        // Determinar o tipo pelo final da URL se não especificado
        if (empty($type)) {
            $ext = pathinfo($url, PATHINFO_EXTENSION);
            if ($ext === 'css') {
                $type = 'css';
            } elseif ($ext === 'js') {
                $type = 'js';
            }
        }
        
        // Obter a URL ideal (local ou externa)
        $resourceUrl = self::getResourceUrl($url);
        $isExternal = ($resourceUrl === $url);
        
        // Determinar loading (async, defer, module)
        $async = $options['async'] ?? false;
        $defer = $options['defer'] ?? true;
        $module = $options['module'] ?? false;
        $preload = $options['preload'] ?? false;
        
        // Construir tag HTML otimizada
        $html = '';
        
        // Adicionar preload se necessário
        if ($preload) {
            $as = ($type === 'css') ? 'style' : 'script';
            $crossorigin = $isExternal ? ' crossorigin' : '';
            $html .= "<link rel=\"preload\" href=\"{$resourceUrl}\" as=\"{$as}\"{$crossorigin}>\n";
        }
        
        // Gerar tag conforme o tipo
        if ($type === 'css') {
            // Otimização para CSS
            $html .= "<link rel=\"stylesheet\" href=\"{$resourceUrl}\" media=\"print\" onload=\"this.media='all'\">\n";
            $html .= "<noscript><link rel=\"stylesheet\" href=\"{$resourceUrl}\"></noscript>\n";
        } else if ($type === 'js') {
            // Otimização para JavaScript
            $asyncAttr = $async ? ' async' : '';
            $deferAttr = $defer ? ' defer' : '';
            $moduleAttr = $module ? ' type="module"' : '';
            
            $html .= "<script src=\"{$resourceUrl}\"{$asyncAttr}{$deferAttr}{$moduleAttr}></script>\n";
        } else {
            // Caso genérico
            $html .= "<link href=\"{$resourceUrl}\" rel=\"stylesheet\">\n";
        }
        
        return $html;
    }
    
    /**
     * Baixa e armazena localmente um recurso externo
     * 
     * @param string $url URL do recurso externo
     * @param string $localPath Caminho local para salvar
     * @return bool Sucesso da operação
     */
    public static function downloadExternalResource($url, $localPath = null) {
        // Se o caminho local não for especificado, tentar detectar automaticamente
        if (!$localPath) {
            $localPath = self::getLocalPath($url);
            if (!$localPath) {
                // Determinar um caminho padrão baseado na URL
                $urlParts = parse_url($url);
                $pathInfo = pathinfo($urlParts['path']);
                $ext = $pathInfo['extension'] ?? '';
                
                if (!$ext) {
                    // Tentar determinar o tipo pelo cabeçalho Content-Type
                    $headers = get_headers($url, 1);
                    $contentType = $headers['Content-Type'] ?? '';
                    
                    if (strpos($contentType, 'javascript') !== false) {
                        $ext = 'js';
                    } elseif (strpos($contentType, 'css') !== false) {
                        $ext = 'css';
                    }
                }
                
                // Criar nome de arquivo baseado na URL
                $filename = md5($url) . '.' . $ext;
                
                // Determinar diretório apropriado
                $dir = 'vendor/external';
                if ($ext === 'js') {
                    $dir = 'vendor/external/js';
                } elseif ($ext === 'css') {
                    $dir = 'vendor/external/css';
                }
                
                $localPath = $dir . '/' . $filename;
            }
        }
        
        // Garantir que o diretório pai exista
        $fullPath = ROOT_PATH . '/public/' . $localPath;
        $dirPath = dirname($fullPath);
        
        if (!file_exists($dirPath)) {
            if (!mkdir($dirPath, 0755, true)) {
                return false;
            }
        }
        
        // Baixar o arquivo
        $content = @file_get_contents($url);
        if ($content === false) {
            return false;
        }
        
        // Salvar localmente
        if (file_put_contents($fullPath, $content) === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Substitui todos os links para recursos externos em uma página
     * 
     * @param string $html Conteúdo HTML
     * @return string HTML com recursos otimizados
     */
    public static function optimizeAllExternalResources($html) {
        // Substituir scripts externos - EXPRESSÃO REGULAR CORRIGIDA
        $html = preg_replace_callback(
            '/<script[^>]*src=(["\'])(.*?)\\1[^>]*><\/script>/i',
            function($matches) {
                $url = $matches[2];
                if (self::isExternalResource($url)) {
                    return self::optimizeExternalResource($url, 'js');
                }
                return $matches[0];
            },
            $html
        );
        
        // Substituir links CSS externos - EXPRESSÃO REGULAR CORRIGIDA
        $html = preg_replace_callback(
            '/<link[^>]*href=(["\'])(.*?)\\1[^>]*>/i',
            function($matches) {
                $url = $matches[2];
                if (strpos($matches[0], 'stylesheet') !== false && self::isExternalResource($url)) {
                    return self::optimizeExternalResource($url, 'css');
                }
                return $matches[0];
            },
            $html
        );
        
        return $html;
    }
}

// Nota: A função global optimizeExternalResource() foi removida para evitar conflito
// com a mesma função definida em app/helpers/init_helpers.php