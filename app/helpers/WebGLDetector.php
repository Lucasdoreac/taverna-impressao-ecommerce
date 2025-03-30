<?php
/**
 * WebGLDetector - Helper para detecção de capacidades WebGL
 * 
 * Este helper fornece métodos para:
 * - Detectar capacidades WebGL no cliente
 * - Verificar compatibilidade com dispositivos móveis
 * - Oferecer conteúdo alternativo para dispositivos sem suporte
 */
class WebGLDetector {
    
    /**
     * Script JavaScript para detecção de WebGL no cliente
     * Inclui verificação de versão de WebGL e capacidades
     * 
     * @return string Script JavaScript para detecção
     */
    public static function getDetectionScript() {
        return "
        <script>
        // Função para detectar WebGL
        window.tavernaWebGLDetection = (function() {
            // Armazena os resultados da detecção
            let detection = {
                hasWebGL: false,
                webGLVersion: 0,
                renderer: '',
                vendor: '',
                isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
                hasTouch: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
                screenSize: {
                    width: window.innerWidth,
                    height: window.innerHeight
                },
                devicePixelRatio: window.devicePixelRatio || 1,
                supportHDPI: (window.devicePixelRatio || 1) > 1,
                memoryStatus: 'unknown'
            };
            
            // Tenta detectar WebGL e WebGL2
            function detectWebGL() {
                try {
                    // Criar canvas temporário
                    const canvas = document.createElement('canvas');
                    
                    // Tentar WebGL2 primeiro
                    const gl2 = canvas.getContext('webgl2');
                    if (gl2) {
                        detection.hasWebGL = true;
                        detection.webGLVersion = 2;
                        getGPUInfo(gl2);
                        return;
                    }
                    
                    // Tentar WebGL
                    const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                    if (gl) {
                        detection.hasWebGL = true;
                        detection.webGLVersion = 1;
                        getGPUInfo(gl);
                        return;
                    }
                    
                    detection.hasWebGL = false;
                } catch (e) {
                    detection.hasWebGL = false;
                    console.error('Erro ao detectar WebGL:', e);
                }
            }
            
            // Obtém informações sobre a GPU
            function getGPUInfo(gl) {
                try {
                    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                    if (debugInfo) {
                        detection.vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
                        detection.renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
                    }
                    
                    // Tentar estimar memória disponível
                    const ext = gl.getExtension('WEBGL_debug_renderer_info');
                    if (ext) {
                        // Adaptação baseada no dispositivo e renderizador
                        if (detection.isMobile) {
                            if (detection.renderer.indexOf('Adreno') !== -1) {
                                detection.memoryStatus = 'low';
                            } else if (detection.renderer.indexOf('Apple') !== -1) {
                                detection.memoryStatus = 'medium';
                            } else {
                                detection.memoryStatus = 'low';
                            }
                        } else {
                            if (detection.renderer.indexOf('Intel') !== -1) {
                                detection.memoryStatus = 'medium';
                            } else if (detection.renderer.indexOf('NVIDIA') !== -1 || 
                                     detection.renderer.indexOf('AMD') !== -1 || 
                                     detection.renderer.indexOf('Radeon') !== -1) {
                                detection.memoryStatus = 'high';
                            } else {
                                detection.memoryStatus = 'medium';
                            }
                        }
                    }
                } catch (e) {
                    console.error('Erro ao obter informações da GPU:', e);
                }
            }
            
            // Função para indicar os melhores parâmetros para o visualizador 3D
            function getOptimalParameters() {
                const params = {
                    maxPolyCount: 50000, // Padrão
                    useHDTextures: true,
                    antialiasing: true,
                    shadows: true,
                    reflections: true
                };
                
                // Ajustar com base nas capacidades detectadas
                if (detection.isMobile) {
                    params.maxPolyCount = 20000;
                    params.useHDTextures = false;
                    params.shadows = false;
                    params.reflections = false;
                }
                
                if (detection.memoryStatus === 'low') {
                    params.maxPolyCount = 10000;
                    params.useHDTextures = false;
                    params.antialiasing = false;
                    params.shadows = false;
                    params.reflections = false;
                } else if (detection.memoryStatus === 'medium') {
                    params.maxPolyCount = 30000;
                    params.shadows = false;
                }
                
                return params;
            }
            
            // Executar detecção imediatamente
            detectWebGL();
            
            // Expor API
            return {
                getDetectionResults: function() {
                    return detection;
                },
                hasWebGLSupport: function() {
                    return detection.hasWebGL;
                },
                getWebGLVersion: function() {
                    return detection.webGLVersion;
                },
                isMobileDevice: function() {
                    return detection.isMobile;
                },
                getOptimalParameters: getOptimalParameters,
                getDeviceInfo: function() {
                    return {
                        screenSize: detection.screenSize,
                        pixelRatio: detection.devicePixelRatio,
                        hasTouch: detection.hasTouch,
                        renderer: detection.renderer
                    };
                }
            };
        })();
        </script>";
    }
    
    /**
     * HTML para exibir conteúdo alternativo quando WebGL não é suportado
     * 
     * @param string $productName Nome do produto
     * @param string $imagePath Caminho para a imagem principal
     * @return string HTML do conteúdo alternativo
     */
    public static function getFallbackHTML($productName, $imagePath) {
        $fallbackImages = '';
        
        // Imagens de diferentes ângulos
        for ($i = 1; $i <= 4; $i++) {
            $angleImagePath = str_replace('.', "-angle{$i}.", $imagePath);
            $fallbackImages .= "
            <div class='model-angle'>
                <img src='{$angleImagePath}' alt='{$productName} - Ângulo {$i}' 
                     class='img-fluid model-angle-img' onerror=\"this.src='{$imagePath}'\">
                <span>Ângulo {$i}</span>
            </div>";
        }
        
        return "
        <div class='webgl-fallback'>
            <div class='alert alert-info'>
                <i class='bi bi-info-circle-fill'></i>
                <strong>Visualização 3D não disponível</strong>
                <p>Seu dispositivo não suporta visualização 3D interativa. Exibindo imagens do modelo em diferentes ângulos.</p>
            </div>
            
            <div class='model-fallback-images'>
                {$fallbackImages}
            </div>
            
            <div class='mt-3'>
                <p><strong>Especificações da miniatura:</strong></p>
                <ul>
                    <li>Dimensões detalhadas no produto</li>
                    <li>Alta resolução de detalhes</li>
                    <li>Material e acabamento profissional</li>
                </ul>
            </div>
        </div>";
    }
    
    /**
     * CSS para estilizar o conteúdo alternativo
     * 
     * @return string CSS para o fallback
     */
    public static function getFallbackCSS() {
        return "
        <style>
        .webgl-fallback {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .model-fallback-images {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .model-angle {
            width: calc(50% - 15px);
            text-align: center;
            margin-bottom: 15px;
        }
        
        .model-angle-img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .model-angle span {
            display: block;
            margin-top: 5px;
            font-weight: 500;
        }
        
        @media (max-width: 576px) {
            .model-angle {
                width: 100%;
            }
        }
        </style>";
    }
    
    /**
     * Incluir todos os recursos necessários para detecção e fallback
     * 
     * @param string $productName Nome do produto
     * @param string $imagePath Caminho para a imagem principal
     * @return string HTML completo com script de detecção e fallback
     */
    public static function include($productName = '', $imagePath = '') {
        $output = self::getDetectionScript();
        $output .= self::getFallbackCSS();
        
        // Adicionar container para o fallback que será mostrado/escondido via JavaScript
        if (!empty($productName) && !empty($imagePath)) {
            $output .= "
            <div id='webgl-fallback-container' style='display: none;'>
                " . self::getFallbackHTML($productName, $imagePath) . "
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (!window.tavernaWebGLDetection.hasWebGLSupport()) {
                    // Esconder o container do visualizador 3D
                    const viewer3DContainer = document.getElementById('model-viewer-container');
                    if (viewer3DContainer) {
                        viewer3DContainer.style.display = 'none';
                    }
                    
                    // Mostrar o fallback
                    const fallbackContainer = document.getElementById('webgl-fallback-container');
                    if (fallbackContainer) {
                        fallbackContainer.style.display = 'block';
                    }
                }
            });
            </script>";
        }
        
        return $output;
    }
    
    /**
     * Configurar parâmetros ótimos para o visualizador 3D com base na detecção
     * 
     * @return string Script JavaScript para configuração adaptativa
     */
    public static function getOptimizationScript() {
        return "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Verifica se o detector está disponível
            if (window.tavernaWebGLDetection) {
                const detection = window.tavernaWebGLDetection.getDetectionResults();
                const params = window.tavernaWebGLDetection.getOptimalParameters();
                
                // Verifica se o visualizador está presente
                if (window.ModelViewer) {
                    // Configurar ModelViewer com parâmetros otimizados
                    window.ModelViewer.setDefaultQuality(detection.isMobile ? 'low' : 'medium');
                    window.ModelViewer.setMaxPolyCount(params.maxPolyCount);
                    window.ModelViewer.enableHDTextures(params.useHDTextures);
                    window.ModelViewer.enableAntialiasing(params.antialiasing);
                    window.ModelViewer.enableShadows(params.shadows);
                    window.ModelViewer.enableReflections(params.reflections);
                    
                    console.log('Viewer 3D configurado para qualidade otimizada:', 
                                detection.isMobile ? 'Modo móvel' : 'Modo desktop', 
                                'WebGL v' + detection.webGLVersion);
                }
            }
        });
        </script>";
    }
    
    /**
     * Verificar se WebGL é suportado no lado do servidor (através de User-Agent)
     * Obs: Esta verificação é menos precisa que a verificação do lado do cliente
     * 
     * @return bool True se provável que o cliente suporte WebGL
     */
    public static function isLikelySupported() {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Dispositivos muito antigos provavelmente não suportam WebGL
        $oldDevices = [
            'MSIE 8', 'MSIE 9', // Internet Explorer 8 e 9
            'Android 2', 'Android 3', // Android muito antigo
            'Opera Mini', // Opera Mini tem suporte limitado
            'BlackBerry', // BlackBerry antigo
        ];
        
        foreach ($oldDevices as $device) {
            if (strpos($userAgent, $device) !== false) {
                return false;
            }
        }
        
        // A maioria dos navegadores modernos suporta WebGL
        return true;
    }
}
