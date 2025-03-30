<?php
/**
 * AssetOptimizerHelper - Classe para otimização de assets (CSS e JavaScript)
 * 
 * Este helper fornece métodos para:
 * - Minificação e combinação de arquivos CSS e JavaScript
 * - Geração de URLs com versão para controle de cache
 * - Compressão de assets estáticos
 * - Lazy loading de imagens e imagens de fundo
 */
class AssetOptimizerHelper {
    /**
     * Diretório de cache para arquivos otimizados
     */
    private static $cacheDir = 'public/assets/cache/';
    
    /**
     * Versão para cache-busting
     */
    private static $version = null;
    
    /**
     * Define se estamos em modo de produção
     */
    private static $productionMode = null;
    
    /**
     * Inicializa o helper
     */
    public static function init() {
        // Verificar se o diretório de cache existe, senão criá-lo
        if (!file_exists(ROOT_PATH . '/' . self::$cacheDir)) {
            mkdir(ROOT_PATH . '/' . self::$cacheDir, 0755, true);
        }
        
        // Verificar se estamos em modo de produção (com base na constante ENVIRONMENT ou derivado)
        if (self::$productionMode === null) {
            self::$productionMode = defined('ENVIRONMENT') && ENVIRONMENT === 'production';
        }
        
        // Inicializar versão baseada no timestamp do último deploy ou commit
        if (self::$version === null) {
            self::$version = defined('ASSETS_VERSION') ? ASSETS_VERSION : date('YmdHi');
        }
    }
    
    /**
     * Retorna o caminho para um arquivo CSS otimizado (minificado e com versão)
     * 
     * @param string|array $files Arquivo ou array de arquivos CSS para otimizar
     * @param boolean $combine Se deve combinar vários arquivos em um único (default: true)
     * @return string URL do arquivo CSS otimizado
     */
    public static function css($files, $combine = true) {
        self::init();
        
        // Se não estamos em modo de produção, apenas retornar os arquivos originais
        if (!self::$productionMode) {
            if (is_array($files)) {
                $urls = [];
                foreach ($files as $file) {
                    $urls[] = '<link rel="stylesheet" href="' . BASE_URL . 'assets/css/' . $file . '?v=' . self::$version . '">';
                }
                return implode("\n", $urls);
            } else {
                return '<link rel="stylesheet" href="' . BASE_URL . 'assets/css/' . $files . '?v=' . self::$version . '">';
            }
        }
        
        // Converter para array se for um único arquivo
        if (!is_array($files)) {
            $files = [$files];
        }
        
        // Se não combinar, processar cada arquivo individualmente
        if (!$combine && count($files) > 1) {
            $urls = [];
            foreach ($files as $file) {
                $urls[] = self::css($file, true);
            }
            return implode("\n", $urls);
        }
        
        // Gerar um nome de arquivo combinado baseado nos arquivos de entrada
        $outputFilename = 'combined-' . md5(implode('', $files)) . '.min.css';
        $outputPath = ROOT_PATH . '/' . self::$cacheDir . $outputFilename;
        $outputUrl = BASE_URL . str_replace('public/', '', self::$cacheDir) . $outputFilename;
        
        // Verificar se o arquivo combinado já existe e se está atualizado
        $regenerate = !file_exists($outputPath);
        
        if (!$regenerate) {
            $outputModified = filemtime($outputPath);
            
            // Verificar se algum dos arquivos originais foi modificado
            foreach ($files as $file) {
                $filePath = ROOT_PATH . '/public/assets/css/' . $file;
                if (!file_exists($filePath) || filemtime($filePath) > $outputModified) {
                    $regenerate = true;
                    break;
                }
            }
        }
        
        // Regenerar o arquivo combinado se necessário
        if ($regenerate) {
            $combinedContent = '';
            
            foreach ($files as $file) {
                $filePath = ROOT_PATH . '/public/assets/css/' . $file;
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    
                    // Minificar CSS
                    $content = self::minifyCSS($content);
                    
                    // Corrigir caminhos relativos
                    $content = self::fixCSSPaths($content, $file);
                    
                    $combinedContent .= "/* {$file} */\n" . $content . "\n";
                }
            }
            
            // Salvar arquivo combinado
            file_put_contents($outputPath, $combinedContent);
        }
        
        // Retornar tag link para o arquivo combinado
        return '<link rel="stylesheet" href="' . $outputUrl . '?v=' . self::$version . '">';
    }
    
    /**
     * Retorna o caminho para um arquivo JavaScript otimizado (minificado e com versão)
     * 
     * @param string|array $files Arquivo ou array de arquivos JavaScript para otimizar
     * @param boolean $combine Se deve combinar vários arquivos em um único (default: true)
     * @param boolean $defer Se deve adicionar o atributo defer (default: true)
     * @return string URL do arquivo JavaScript otimizado
     */
    public static function js($files, $combine = true, $defer = true) {
        self::init();
        
        // Se não estamos em modo de produção, apenas retornar os arquivos originais
        if (!self::$productionMode) {
            $deferAttr = $defer ? ' defer' : '';
            
            if (is_array($files)) {
                $urls = [];
                foreach ($files as $file) {
                    $urls[] = '<script src="' . BASE_URL . 'assets/js/' . $file . '?v=' . self::$version . '"' . $deferAttr . '></script>';
                }
                return implode("\n", $urls);
            } else {
                return '<script src="' . BASE_URL . 'assets/js/' . $files . '?v=' . self::$version . '"' . $deferAttr . '></script>';
            }
        }
        
        // Converter para array se for um único arquivo
        if (!is_array($files)) {
            $files = [$files];
        }
        
        // Se não combinar, processar cada arquivo individualmente
        if (!$combine && count($files) > 1) {
            $urls = [];
            foreach ($files as $file) {
                $urls[] = self::js($file, true, $defer);
            }
            return implode("\n", $urls);
        }
        
        // Gerar um nome de arquivo combinado baseado nos arquivos de entrada
        $outputFilename = 'combined-' . md5(implode('', $files)) . '.min.js';
        $outputPath = ROOT_PATH . '/' . self::$cacheDir . $outputFilename;
        $outputUrl = BASE_URL . str_replace('public/', '', self::$cacheDir) . $outputFilename;
        
        // Verificar se o arquivo combinado já existe e se está atualizado
        $regenerate = !file_exists($outputPath);
        
        if (!$regenerate) {
            $outputModified = filemtime($outputPath);
            
            // Verificar se algum dos arquivos originais foi modificado
            foreach ($files as $file) {
                $filePath = ROOT_PATH . '/public/assets/js/' . $file;
                if (!file_exists($filePath) || filemtime($filePath) > $outputModified) {
                    $regenerate = true;
                    break;
                }
            }
        }
        
        // Regenerar o arquivo combinado se necessário
        if ($regenerate) {
            $combinedContent = '';
            
            foreach ($files as $file) {
                $filePath = ROOT_PATH . '/public/assets/js/' . $file;
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    
                    // Minificar JavaScript
                    $content = self::minifyJS($content);
                    
                    $combinedContent .= "/* {$file} */\n" . $content . "\n";
                }
            }
            
            // Salvar arquivo combinado
            file_put_contents($outputPath, $combinedContent);
        }
        
        // Retornar tag script para o arquivo combinado
        $deferAttr = $defer ? ' defer' : '';
        return '<script src="' . $outputUrl . '?v=' . self::$version . '"' . $deferAttr . '></script>';
    }
    
    /**
     * Retorna o caminho para uma imagem com versão para controle de cache
     * 
     * @param string $image Caminho relativo da imagem
     * @return string URL da imagem com versão
     */
    public static function image($image) {
        self::init();
        
        // Verificar se a imagem está no diretório correto
        if (strpos($image, 'assets/images/') === 0) {
            $imagePath = $image;
        } else {
            $imagePath = 'assets/images/' . $image;
        }
        
        return BASE_URL . $imagePath . '?v=' . self::$version;
    }
    
    /**
     * Retorna uma tag de imagem com atributos para lazy loading
     * 
     * @param string $image Caminho relativo da imagem
     * @param string $alt Texto alternativo
     * @param string $class Classes CSS adicionais
     * @param array $attributes Atributos adicionais
     * @return string Tag de imagem com lazy loading
     */
    public static function lazyImage($image, $alt = '', $class = '', $attributes = []) {
        self::init();
        
        // Verificar se a imagem está no diretório correto
        if (strpos($image, 'assets/images/') === 0) {
            $imagePath = $image;
        } else {
            $imagePath = 'assets/images/' . $image;
        }
        
        // Gerar versão para quebra de cache
        $imageUrl = BASE_URL . $imagePath . '?v=' . self::$version;
        
        // Gerar placeholder de baixa qualidade ou SVG
        $placeholder = BASE_URL . 'assets/images/placeholder.svg';
        
        // Construir atributos adicionais
        $attributesStr = '';
        foreach ($attributes as $key => $value) {
            $attributesStr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        // Tag de imagem com lazy loading
        return '<img src="' . $placeholder . '" data-src="' . $imageUrl . '" alt="' . htmlspecialchars($alt) . '" class="lazy ' . $class . '"' . $attributesStr . '>';
    }
    
    /**
     * Retorna uma tag de imagem com atributos para lazy loading a partir de uma URL completa
     * 
     * @param string $imageUrl URL completa da imagem
     * @param string $alt Texto alternativo
     * @param string $class Classes CSS adicionais
     * @param array $attributes Atributos adicionais
     * @return string Tag de imagem com lazy loading
     */
    public static function lazyImageUrl($imageUrl, $alt = '', $class = '', $attributes = []) {
        self::init();
        
        // Gerar placeholder de baixa qualidade ou SVG
        $placeholder = BASE_URL . 'assets/images/placeholder.svg';
        
        // Construir atributos adicionais
        $attributesStr = '';
        foreach ($attributes as $key => $value) {
            $attributesStr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        // Tag de imagem com lazy loading
        return '<img src="' . $placeholder . '" data-src="' . $imageUrl . '" alt="' . htmlspecialchars($alt) . '" class="lazy ' . $class . '"' . $attributesStr . '>';
    }
    
    /**
     * Retorna um elemento div com background-image e lazy loading
     * 
     * @param string $imageUrl URL da imagem
     * @param string $class Classes CSS adicionais
     * @param array $attributes Atributos adicionais
     * @return string Tag div com lazy loading para background image
     */
    public static function lazyBackgroundImage($imageUrl, $class = '', $attributes = []) {
        self::init();
        
        // Construir atributos adicionais
        $attributesStr = '';
        foreach ($attributes as $key => $value) {
            $attributesStr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        // Elemento div com lazy loading
        return '<div class="lazy-bg ' . $class . '" data-bg="' . $imageUrl . '"' . $attributesStr . '></div>';
    }
    
    /**
     * Minifica conteúdo CSS
     * 
     * @param string $css Conteúdo CSS a ser minificado
     * @return string CSS minificado
     */
    private static function minifyCSS($css) {
        // Remover comentários
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remover espaços em branco desnecessários
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remover espaços antes e depois de {:;,}
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        
        // Remover ponto-e-vírgula desnecessário antes de fechar chaves
        $css = preg_replace('/;}/', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Minifica conteúdo JavaScript
     * 
     * @param string $js Conteúdo JavaScript a ser minificado
     * @return string JavaScript minificado
     */
    private static function minifyJS($js) {
        // Esta é uma implementação simples. Para produção real,
        // é recomendado usar uma biblioteca mais robusta como JShrink ou JSMin
        
        // Remover comentários de linha única
        $js = preg_replace('!//.*!', '', $js);
        
        // Remover comentários de múltiplas linhas
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        
        // Remover espaços em branco desnecessários
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }
    
    /**
     * Corrige caminhos relativos em arquivos CSS
     * 
     * @param string $css Conteúdo CSS
     * @param string $originalFile Nome do arquivo original
     * @return string CSS com caminhos corrigidos
     */
    private static function fixCSSPaths($css, $originalFile) {
        // Obter o diretório do arquivo original
        $originalDir = dirname($originalFile);
        if ($originalDir === '.') {
            $originalDir = '';
        } else {
            $originalDir .= '/';
        }
        
        // Corrigir urls relativas
        $css = preg_replace_callback('/url\([\'"]?([^\'")]+)[\'"]?\)/', function($matches) use ($originalDir) {
            $url = $matches[1];
            
            // Ignorar URLs absolutas ou data:
            if (preg_match('/^(https?:|data:|\/)/', $url)) {
                return "url({$url})";
            }
            
            // Corrigir caminho relativo
            return "url(../css/{$originalDir}{$url})";
        }, $css);
        
        return $css;
    }
    
    /**
     * Define o modo de produção
     * 
     * @param boolean $mode Modo de produção
     */
    public static function setProductionMode($mode) {
        self::$productionMode = (bool) $mode;
    }
    
    /**
     * Define a versão para cache-busting
     * 
     * @param string $version Versão
     */
    public static function setVersion($version) {
        self::$version = $version;
    }
    
    /**
     * Limpa a pasta de cache
     */
    public static function clearCache() {
        self::init();
        
        $cacheDir = ROOT_PATH . '/' . self::$cacheDir;
        if (is_dir($cacheDir)) {
            $files = scandir($cacheDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    unlink($cacheDir . $file);
                }
            }
            return true;
        }
        
        return false;
    }
}