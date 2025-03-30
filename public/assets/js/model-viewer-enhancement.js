/**
 * ModelViewerEnhancement - Módulo de otimizações para o visualizador 3D
 * 
 * Este módulo complementa o ModelViewer principal com otimizações específicas
 * baseadas nos resultados dos testes em dispositivos móveis.
 */

/**
 * Constantes para diferentes níveis de LOD
 */
const LOD_LEVELS = {
    VERY_HIGH: 'veryHigh', // 100% dos vértices
    HIGH: 'high',         // 75-100% dos vértices
    MEDIUM: 'medium',     // 50-75% dos vértices
    LOW: 'low',           // 25-50% dos vértices
    VERY_LOW: 'veryLow',  // 10-25% dos vértices
    MINIMAL: 'minimal'    // <10% dos vértices (apenas para dispositivos muito limitados)
};

/**
 * Perfis predefinidos de hardware
 */
const HARDWARE_PROFILES = {
    HIGH_END: 'highEnd',         // Desktop ou dispositivo móvel topo de linha (iPhone recente, iPad Pro, etc)
    MID_RANGE: 'midRange',       // Dispositivo móvel intermediário
    LOW_END: 'lowEnd',           // Dispositivo móvel de entrada
    VERY_LOW_END: 'veryLowEnd',  // Dispositivo móvel muito antigo ou com recursos muito limitados
    WEBGL1_ONLY: 'webgl1Only'    // Dispositivo que suporta apenas WebGL 1
};

/**
 * Aprimoramento do Visualizador 3D
 */
class ModelViewerEnhancement {
    /**
     * Inicializa o módulo de aprimoramento
     * @param {ModelViewer} modelViewer - Instância do ModelViewer a ser aprimorada
     */
    constructor(modelViewer) {
        this.modelViewer = modelViewer;
        this.deviceProfile = null;
        this.browserInfo = this.getBrowserInfo();
        this.gpuInfo = null;
        this.performanceHistory = [];
        this.memoryUsage = {
            lastCleanup: Date.now(),
            cleanupInterval: 30000, // 30 segundos
            peak: 0,
            current: 0,
            limit: 150 // MB, ajustável com base no dispositivo
        };
        
        // Inicializar detecção de hardware
        this.detectHardwareCapabilities();
    }
    
    /**
     * Detecta as capacidades de hardware do dispositivo
     * Inclui detecção ampliada de GPU e browser
     */
    async detectHardwareCapabilities() {
        // Informações básicas
        const isMobile = this.detectMobileDevice();
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        const isAndroid = /Android/.test(navigator.userAgent);
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        const isChrome = /chrome/i.test(navigator.userAgent) && !/edge/i.test(navigator.userAgent);
        
        // Detecção de WebGL
        const webGLVersion = this.detectWebGLVersion();
        
        // Estimativa de memória disponível
        const memoryEstimate = await this.estimateAvailableMemory();
        
        // Detecção de tela
        const screenInfo = {
            width: window.screen.width,
            height: window.screen.height,
            pixelRatio: window.devicePixelRatio || 1
        };
        
        // Tentativa de obtenção de informações da GPU
        const renderer = this.getGPUInfo();
        
        // Teste rápido de performance (FPS em um cenário básico)
        const performanceBenchmark = await this.runPerformanceBenchmark();
        
        // Log de informações coletadas (apenas em desenvolvimento)
        console.log('ModelViewerEnhancement: Hardware detection results', {
            isMobile, isIOS, isAndroid, isSafari, isChrome,
            webGLVersion, memoryEstimate, screenInfo, renderer,
            performanceBenchmark
        });
        
        // Determinar o perfil de hardware
        this.determineHardwareProfile({
            isMobile, isIOS, isAndroid, isSafari, isChrome,
            webGLVersion, memoryEstimate, screenInfo, renderer,
            performanceBenchmark
        });
        
        // Ajustar configurações com base no perfil de hardware
        this.applyOptimizationsForProfile();
        
        // Armazenar informações no localStorage para uso futuro
        this.cacheHardwareInfo({
            profile: this.deviceProfile,
            detectTime: Date.now(),
            isMobile, webGLVersion, renderer,
            memoryEstimate, performanceBenchmark
        });
        
        return this.deviceProfile;
    }
    
    /**
     * Detecta o navegador e versão
     * @returns {Object} Informações do navegador
     */
    getBrowserInfo() {
        const ua = navigator.userAgent;
        let browser = 'unknown';
        let version = 'unknown';
        
        // Chrome
        let match = ua.match(/(chrome|chromium|crios)\/(\d+)/i);
        if (match) {
            browser = 'chrome';
            version = match[2];
        }
        // Firefox
        else if (match = ua.match(/(firefox|fxios)\/(\d+)/i)) {
            browser = 'firefox';
            version = match[2];
        }
        // Safari
        else if (match = ua.match(/version\/(\d+).*safari/i)) {
            browser = 'safari';
            version = match[1];
        }
        // Edge
        else if (match = ua.match(/edge\/(\d+)/i)) {
            browser = 'edge';
            version = match[1];
        }
        // IE
        else if (match = ua.match(/trident.*rv:(\d+)/i)) {
            browser = 'ie';
            version = match[1];
        }
        
        return {
            name: browser,
            version: parseInt(version, 10) || 0,
            userAgent: ua
        };
    }
    
    /**
     * Detecta se o dispositivo é móvel
     * @returns {boolean} Verdadeiro se for um dispositivo móvel
     */
    detectMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
               (window.innerWidth <= 768);
    }
    
    /**
     * Detecta a versão do WebGL disponível
     * @returns {number} Versão do WebGL (0, 1 ou 2)
     */
    detectWebGLVersion() {
        // Tentar WebGL 2
        let canvas = document.createElement('canvas');
        let gl = canvas.getContext('webgl2');
        
        if (gl) {
            return 2;
        }
        
        // Tentar WebGL 1
        gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        
        if (gl) {
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Tenta obter informações da GPU
     * @returns {Object} Informações da GPU
     */
    getGPUInfo() {
        let gpuInfo = {
            renderer: 'unknown',
            vendor: 'unknown'
        };
        
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (gl) {
                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                
                if (debugInfo) {
                    gpuInfo.renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
                    gpuInfo.vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
                }
                
                // Informações sobre limites
                gpuInfo.maxTextureSize = gl.getParameter(gl.MAX_TEXTURE_SIZE);
                gpuInfo.maxViewportDims = gl.getParameter(gl.MAX_VIEWPORT_DIMS);
                gpuInfo.maxVertexAttribs = gl.getParameter(gl.MAX_VERTEX_ATTRIBS);
                gpuInfo.maxVaryingVectors = gl.getParameter(gl.MAX_VARYING_VECTORS);
                gpuInfo.maxVertexUniformVectors = gl.getParameter(gl.MAX_VERTEX_UNIFORM_VECTORS);
                gpuInfo.maxFragmentUniformVectors = gl.getParameter(gl.MAX_FRAGMENT_UNIFORM_VECTORS);
            }
        } catch (e) {
            console.warn('ModelViewerEnhancement: Error getting GPU info', e);
        }
        
        return gpuInfo;
    }
    
    /**
     * Estima a memória disponível no dispositivo
     * @returns {number} Memória estimada em MB
     */
    async estimateAvailableMemory() {
        // Tentar usar API de Performance
        if (window.performance && window.performance.memory) {
            return Math.round(window.performance.memory.jsHeapSizeLimit / 1048576); // Converter para MB
        }
        
        // Para dispositivos móveis
        const isMobile = this.detectMobileDevice();
        
        if (isMobile) {
            // Estimativa baseada em heurísticas
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isHighEndDevice = /iPhone 1[1-9]|iPhone X|iPad Pro/.test(navigator.userAgent) || 
                                  window.devicePixelRatio >= 3;
            
            if (isIOS) {
                return isHighEndDevice ? 512 : 256;
            } else {
                // Android
                return isHighEndDevice ? 384 : 192;
            }
        }
        
        // Desktop
        return 1024; // Estimativa conservadora para desktop
    }
    
    /**
     * Executa um benchmark rápido de performance
     * @returns {Object} Resultados do benchmark
     */
    async runPerformanceBenchmark() {
        return new Promise(resolve => {
            // Criar cena de teste
            const canvas = document.createElement('canvas');
            canvas.width = 256;
            canvas.height = 256;
            document.body.appendChild(canvas);
            
            try {
                const renderer = new THREE.WebGLRenderer({ canvas, antialias: false });
                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 1000);
                camera.position.z = 5;
                
                // Adicionar objeto para teste
                const geometry = new THREE.BoxGeometry(1, 1, 1, 8, 8, 8); // Complexidade média
                const material = new THREE.MeshBasicMaterial({ color: 0xffffff, wireframe: true });
                const mesh = new THREE.Mesh(geometry, material);
                scene.add(mesh);
                
                // Medir FPS
                let frames = 0;
                const startTime = performance.now();
                const maxTestTime = 1000; // 1 segundo
                
                function animate() {
                    frames++;
                    mesh.rotation.x += 0.01;
                    mesh.rotation.y += 0.01;
                    renderer.render(scene, camera);
                    
                    const elapsed = performance.now() - startTime;
                    
                    if (elapsed < maxTestTime) {
                        requestAnimationFrame(animate);
                    } else {
                        // Teste concluído
                        const fps = frames / (elapsed / 1000);
                        
                        // Limpar
                        document.body.removeChild(canvas);
                        renderer.dispose();
                        geometry.dispose();
                        material.dispose();
                        
                        resolve({
                            fps,
                            testDuration: elapsed,
                            frames
                        });
                    }
                }
                
                // Iniciar teste
                animate();
            } catch (e) {
                // Erro ao executar teste
                console.warn('ModelViewerEnhancement: Error running performance benchmark', e);
                document.body.removeChild(canvas);
                
                resolve({
                    fps: 0,
                    testDuration: 0,
                    frames: 0,
                    error: e.message
                });
            }
        });
    }
    
    /**
     * Determina o perfil de hardware do dispositivo
     * @param {Object} data - Dados coletados sobre o dispositivo
     */
    determineHardwareProfile(data) {
        const { 
            isMobile, webGLVersion, memoryEstimate, 
            performanceBenchmark, screenInfo, renderer 
        } = data;
        
        // Caso apenas suporte WebGL 1 ou nenhum
        if (webGLVersion < 1) {
            this.deviceProfile = HARDWARE_PROFILES.VERY_LOW_END;
            return;
        }
        
        if (webGLVersion === 1) {
            this.deviceProfile = HARDWARE_PROFILES.WEBGL1_ONLY;
            return;
        }
        
        // Pontuação baseada nas características do dispositivo
        let score = 0;
        
        // FPS no benchmark
        if (performanceBenchmark && performanceBenchmark.fps) {
            if (performanceBenchmark.fps >= 55) score += 40;
            else if (performanceBenchmark.fps >= 40) score += 30;
            else if (performanceBenchmark.fps >= 25) score += 20;
            else score += 10;
        }
        
        // Memória estimada
        if (memoryEstimate >= 768) score += 30;
        else if (memoryEstimate >= 384) score += 20;
        else if (memoryEstimate >= 192) score += 10;
        else score += 5;
        
        // Tamanho da tela e pixel ratio
        if (screenInfo.width >= 1920 || screenInfo.height >= 1080) score += 10;
        if (screenInfo.pixelRatio >= 2) score += 10;
        
        // Tipo de dispositivo
        if (!isMobile) score += 20;
        
        // Determinação do perfil
        if (score >= 70) {
            this.deviceProfile = HARDWARE_PROFILES.HIGH_END;
        } else if (score >= 50) {
            this.deviceProfile = HARDWARE_PROFILES.MID_RANGE;
        } else if (score >= 30) {
            this.deviceProfile = HARDWARE_PROFILES.LOW_END;
        } else {
            this.deviceProfile = HARDWARE_PROFILES.VERY_LOW_END;
        }
        
        // Ajustar limite de memória com base no perfil
        switch (this.deviceProfile) {
            case HARDWARE_PROFILES.HIGH_END:
                this.memoryUsage.limit = 300;
                break;
            case HARDWARE_PROFILES.MID_RANGE:
                this.memoryUsage.limit = 200;
                break;
            case HARDWARE_PROFILES.LOW_END:
                this.memoryUsage.limit = 120;
                break;
            default:
                this.memoryUsage.limit = 80;
                break;
        }
        
        console.log(`ModelViewerEnhancement: Dispositivo classificado como ${this.deviceProfile} (pontuação: ${score})`);
    }
    
    /**
     * Aplica otimizações com base no perfil de hardware detectado
     */
    applyOptimizationsForProfile() {
        if (!this.modelViewer || !this.deviceProfile) return;
        
        // Configurações do visualizador
        const options = this.modelViewer.options;
        
        // Aplicar otimizações específicas para cada perfil
        switch (this.deviceProfile) {
            case HARDWARE_PROFILES.HIGH_END:
                // Dispositivos de alta capacidade - poucas otimizações
                options.adaptiveQuality = true;
                options.progressiveLoading = true;
                options.lodThresholds = {
                    high: 55,
                    medium: 40,
                    low: 25,
                    veryLow: 15
                };
                break;
                
            case HARDWARE_PROFILES.MID_RANGE:
                // Dispositivos intermediários - otimizações moderadas
                options.adaptiveQuality = true;
                options.progressiveLoading = true;
                options.useWebWorker = true;
                options.lodThresholds = {
                    high: 50,
                    medium: 35,
                    low: 20,
                    veryLow: 10
                };
                
                // Reduzir qualidade de renderização
                if (this.modelViewer.renderer) {
                    this.modelViewer.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
                }
                break;
                
            case HARDWARE_PROFILES.LOW_END:
                // Dispositivos de entrada - otimizações agressivas
                options.adaptiveQuality = true;
                options.progressiveLoading = true;
                options.useWebWorker = false; // Web workers podem ser pesados em dispositivos de baixa capacidade
                options.lodThresholds = {
                    high: 45,
                    medium: 30,
                    low: 15,
                    veryLow: 5
                };
                
                // Reduzir qualidade de renderização
                if (this.modelViewer.renderer) {
                    this.modelViewer.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1));
                    this.modelViewer.renderer.shadowMap.enabled = false;
                }
                
                // Iniciar com LOD mais baixo
                this.applyInitialLOD(LOD_LEVELS.LOW);
                break;
                
            case HARDWARE_PROFILES.VERY_LOW_END:
            case HARDWARE_PROFILES.WEBGL1_ONLY:
                // Dispositivos muito limitados - otimizações máximas
                options.adaptiveQuality = true;
                options.progressiveLoading = true;
                options.useWebWorker = false;
                options.showGrid = false;
                options.showAxes = false;
                options.lodThresholds = {
                    high: 30,
                    medium: 20,
                    low: 10,
                    veryLow: 5
                };
                
                // Reduzir qualidade ao mínimo
                if (this.modelViewer.renderer) {
                    this.modelViewer.renderer.setPixelRatio(1);
                    this.modelViewer.renderer.shadowMap.enabled = false;
                    this.modelViewer.renderer.powerPreference = 'low-power';
                }
                
                // Iniciar com LOD muito baixo
                this.applyInitialLOD(LOD_LEVELS.VERY_LOW);
                break;
        }
        
        // Registrar otimizações aplicadas
        console.log(`ModelViewerEnhancement: Optimizations applied for profile ${this.deviceProfile}`);
    }
    
    /**
     * Aplica um nível de detalhe inicial
     * @param {string} lodLevel - Nível de detalhe a ser aplicado
     */
    applyInitialLOD(lodLevel) {
        if (this.modelViewer && this.modelViewer.applyLOD) {
            setTimeout(() => {
                this.modelViewer.applyLOD(lodLevel);
            }, 100);
        }
    }
    
    /**
     * Armazena informações de hardware para uso futuro
     * @param {Object} info - Informações a serem armazenadas
     */
    cacheHardwareInfo(info) {
        try {
            localStorage.setItem('modelViewerHardwareInfo', JSON.stringify(info));
        } catch (e) {
            console.warn('ModelViewerEnhancement: Unable to cache hardware info', e);
        }
    }
    
    /**
     * Verifica cache de informações de hardware
     * @returns {Object|null} Informações em cache ou null
     */
    getCachedHardwareInfo() {
        try {
            const cached = localStorage.getItem('modelViewerHardwareInfo');
            
            if (cached) {
                const info = JSON.parse(cached);
                
                // Verificar se o cache é recente (menos de 24 horas)
                const maxCacheAge = 24 * 60 * 60 * 1000; // 24 horas
                
                if (Date.now() - info.detectTime < maxCacheAge) {
                    return info;
                }
            }
        } catch (e) {
            console.warn('ModelViewerEnhancement: Error reading cached hardware info', e);
        }
        
        return null;
    }
    
    /**
     * Monitora a performance contínua do visualizador
     * @param {number} fps - FPS atual
     * @param {number} memoryUsage - Uso de memória atual em MB (opcional)
     */
    updatePerformanceMetrics(fps, memoryUsage = null) {
        // Armazenar histórico de FPS
        this.performanceHistory.push({
            timestamp: Date.now(),
            fps
        });
        
        // Manter apenas as últimas 60 amostras
        if (this.performanceHistory.length > 60) {
            this.performanceHistory.shift();
        }
        
        // Atualizar uso de memória
        if (memoryUsage !== null) {
            this.memoryUsage.current = memoryUsage;
            
            if (memoryUsage > this.memoryUsage.peak) {
                this.memoryUsage.peak = memoryUsage;
            }
        }
        
        // Verificar necessidade de limpeza de memória
        this.checkMemoryCleanup();
        
        // Verificar performance e ajustar LOD conforme necessário
        this.adjustLODBasedOnPerformance();
    }
    
    /**
     * Verifica se é necessário executar limpeza de memória
     */
    checkMemoryCleanup() {
        const now = Date.now();
        
        // Verificar se já passou tempo suficiente desde a última limpeza
        if (now - this.memoryUsage.lastCleanup >= this.memoryUsage.cleanupInterval) {
            // Verificar se o uso de memória está próximo do limite
            if (this.memoryUsage.current > this.memoryUsage.limit * 0.8) {
                this.performMemoryCleanup();
            }
            
            this.memoryUsage.lastCleanup = now;
        }
    }
    
    /**
     * Executa limpeza de memória
     */
    performMemoryCleanup() {
        if (!this.modelViewer) return;
        
        console.log('ModelViewerEnhancement: Performing memory cleanup');
        
        try {
            // Liberar caches de GPU
            if (this.modelViewer.renderer) {
                this.modelViewer.renderer.dispose();
            }
            
            // Forçar coleta de lixo
            if (window.gc) {
                window.gc();
            }
            
            // Reduzir LOD temporariamente se necessário
            if (this.memoryUsage.current > this.memoryUsage.limit * 0.9) {
                // Se estamos usando muito mais de 90% do limite, reduzir drasticamente
                this.applyInitialLOD(LOD_LEVELS.LOW);
            }
        } catch (e) {
            console.warn('ModelViewerEnhancement: Error during memory cleanup', e);
        }
    }
    
    /**
     * Ajusta o LOD com base no desempenho atual
     */
    adjustLODBasedOnPerformance() {
        if (!this.modelViewer || this.performanceHistory.length < 10) return;
        
        // Calcular média de FPS recente
        const recentFPS = this.performanceHistory.slice(-10);
        const avgFPS = recentFPS.reduce((sum, item) => sum + item.fps, 0) / recentFPS.length;
        
        // Verificar se o usuário está interagindo
        if (this.modelViewer.userInteracting) return;
        
        // Obter limites de LOD
        const thresholds = this.modelViewer.options.lodThresholds;
        
        // Selecionar LOD apropriado
        let targetLOD = LOD_LEVELS.HIGH;
        
        if (avgFPS <= thresholds.veryLow) {
            targetLOD = LOD_LEVELS.VERY_LOW;
        } else if (avgFPS <= thresholds.low) {
            targetLOD = LOD_LEVELS.LOW;
        } else if (avgFPS <= thresholds.medium) {
            targetLOD = LOD_LEVELS.MEDIUM;
        }
        
        // Aplicar novo LOD se diferente do atual
        if (this.modelViewer.currentLOD !== targetLOD) {
            console.log(`ModelViewerEnhancement: Adjusting LOD to ${targetLOD} (FPS: ${avgFPS.toFixed(1)})`);
            this.modelViewer.applyLOD(targetLOD);
        }
    }
    
    /**
     * Cria LODs adicionais para geometria existente
     * @param {THREE.BufferGeometry} geometry - Geometria original
     * @param {Array<number>} levels - Percentuais para cada nível (ex: [75, 50, 25, 10])
     * @returns {Object} Mapeamento de níveis para geometrias
     */
    createMultiLevelLOD(geometry, levels = [75, 50, 25, 10]) {
        if (!geometry) return null;
        
        const lodLevels = {};
        
        // Geometria original (100%)
        lodLevels[LOD_LEVELS.VERY_HIGH] = geometry.clone();
        
        // Obter atributos de posição
        const positions = geometry.attributes.position;
        const indices = geometry.index ? Array.from(geometry.index.array) : null;
        const vertexCount = indices ? indices.length / 3 : positions.count / 3;
        
        // Criar diferentes níveis de LOD
        levels.forEach((percent, index) => {
            // Determinar nível correspondente
            let levelName;
            switch (index) {
                case 0: levelName = LOD_LEVELS.HIGH; break;
                case 1: levelName = LOD_LEVELS.MEDIUM; break;
                case 2: levelName = LOD_LEVELS.LOW; break;
                case 3: levelName = LOD_LEVELS.VERY_LOW; break;
                case 4: levelName = LOD_LEVELS.MINIMAL; break;
                default: levelName = `lod_${percent}`; break;
            }
            
            // Calcular quantos triângulos manter
            const targetTriangles = Math.floor(vertexCount * (percent / 100));
            
            // Criar geometria simplificada
            const simplifiedGeometry = this.simplifyGeometry(geometry, targetTriangles);
            lodLevels[levelName] = simplifiedGeometry;
        });
        
        return lodLevels;
    }
    
    /**
     * Simplifica uma geometria para um número alvo de triângulos
     * Esta é uma implementação simples de decimação. Em produção, usar
     * algoritmos mais avançados como THREE.SimplifyModifier
     * 
     * @param {THREE.BufferGeometry} geometry - Geometria original
     * @param {number} targetTriangles - Número alvo de triângulos
     * @returns {THREE.BufferGeometry} Geometria simplificada
     */
    simplifyGeometry(geometry, targetTriangles) {
        const simplified = geometry.clone();
        
        // Se a geometria já é simples, retornar cópia
        if (!geometry.index) {
            return simplified;
        }
        
        const indices = Array.from(geometry.index.array);
        const triangleCount = indices.length / 3;
        
        // Se já temos menos triângulos que o alvo, retornar sem modificar
        if (triangleCount <= targetTriangles) {
            return simplified;
        }
        
        // Calcular quais triângulos manter
        const skipRatio = triangleCount / targetTriangles;
        const newIndices = [];
        
        for (let i = 0; i < triangleCount; i++) {
            if (i % skipRatio < 1) {
                // Manter este triângulo
                const baseIdx = i * 3;
                newIndices.push(
                    indices[baseIdx],
                    indices[baseIdx + 1],
                    indices[baseIdx + 2]
                );
            }
        }
        
        // Atualizar índices
        simplified.setIndex(newIndices);
        
        // Recomputar normais
        simplified.computeVertexNormals();
        
        return simplified;
    }
    
    /**
     * Implementa carregamento progressivo otimizado com streaming de vértices
     * @param {string} url - URL do modelo
     * @param {string} fileType - Tipo de arquivo (stl ou obj)
     * @param {Function} onProgress - Callback de progresso
     * @param {Function} onLoad - Callback de carregamento completo
     * @param {Function} onError - Callback de erro
     */
    loadModelProgressively(url, fileType, onProgress, onLoad, onError) {
        // Verificar suporte à Fetch API e Streams
        const supportsStreaming = 'ReadableStream' in window && 'body' in Response.prototype;
        
        if (!supportsStreaming) {
            // Fallback para carregamento padrão
            this.loadModelStandard(url, fileType, onProgress, onLoad, onError);
            return;
        }
        
        // Determinar loader apropriado
        const createLoader = () => {
            switch (fileType.toLowerCase()) {
                case 'stl':
                    return new THREE.STLLoader();
                case 'obj':
                    return new THREE.OBJLoader();
                default:
                    throw new Error(`Tipo de arquivo não suportado: ${fileType}`);
            }
        };
        
        // Iniciar carregamento com streaming
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro ao carregar modelo: ${response.statusText}`);
                }
                
                // Obter tamanho total
                const contentLength = response.headers.get('content-length');
                const totalSize = contentLength ? parseInt(contentLength, 10) : 0;
                let loadedSize = 0;
                
                // Criar reader para stream
                const reader = response.body.getReader();
                const chunks = [];
                
                // Função para processar chunks
                const processStream = ({ done, value }) => {
                    if (done) {
                        // Concluído - combinar chunks e processar modelo
                        const modelData = new Uint8Array(loadedSize);
                        let offset = 0;
                        
                        for (const chunk of chunks) {
                            modelData.set(chunk, offset);
                            offset += chunk.length;
                        }
                        
                        try {
                            // Carregar modelo final
                            const loader = createLoader();
                            let result;
                            
                            if (fileType.toLowerCase() === 'stl') {
                                result = loader.parse(modelData.buffer);
                            } else {
                                // Para OBJ, precisamos converter para texto
                                const decoder = new TextDecoder('utf-8');
                                const text = decoder.decode(modelData);
                                result = loader.parse(text);
                            }
                            
                            // Modelo carregado com sucesso
                            if (onLoad) onLoad(result);
                        } catch (e) {
                            if (onError) onError(e);
                        }
                        
                        return;
                    }
                    
                    // Adicionar chunk
                    loadedSize += value.length;
                    chunks.push(value);
                    
                    // Atualizar progresso
                    if (onProgress && totalSize > 0) {
                        const progress = (loadedSize / totalSize) * 100;
                        onProgress({ loaded: loadedSize, total: totalSize, progress });
                        
                        // A cada 25% de progresso, tentar mostrar uma visualização prévia
                        if (progress % 25 < 5 && progress > 10) {
                            this.tryPreviewWithCurrentChunks(chunks, fileType, loadedSize);
                        }
                    }
                    
                    // Continuar lendo
                    return reader.read().then(processStream);
                };
                
                // Iniciar processamento
                return reader.read().then(processStream);
            })
            .catch(error => {
                console.error('Erro no carregamento progressivo:', error);
                if (onError) onError(error);
                
                // Fallback para carregamento padrão em caso de erro com streaming
                this.loadModelStandard(url, fileType, onProgress, onLoad, onError);
            });
    }
    
    /**
     * Tenta criar uma visualização prévia com os chunks atuais
     * @param {Array<Uint8Array>} chunks - Chunks carregados até o momento
     * @param {string} fileType - Tipo de arquivo
     * @param {number} loadedSize - Tamanho total carregado em bytes
     */
    tryPreviewWithCurrentChunks(chunks, fileType, loadedSize) {
        if (!this.modelViewer || chunks.length === 0) return;
        
        try {
            // Combinar chunks atuais
            const previewData = new Uint8Array(loadedSize);
            let offset = 0;
            
            for (const chunk of chunks) {
                previewData.set(chunk, offset);
                offset += chunk.length;
            }
            
            // Criar visualização preliminar com LOD muito baixo
            console.log('ModelViewerEnhancement: Attempting preview with current data');
            
            // Implementação específica para STL
            if (fileType.toLowerCase() === 'stl') {
                const loader = new THREE.STLLoader();
                const geometry = loader.parse(previewData.buffer);
                
                // Simplificar drasticamente para preview
                const simplifiedGeometry = this.simplifyGeometry(geometry, 500); // Apenas 500 triângulos para preview
                
                // Criar material simplificado
                const material = new THREE.MeshLambertMaterial({
                    color: this.modelViewer.options.modelColor,
                    wireframe: true,
                    transparent: true,
                    opacity: 0.7
                });
                
                // Criar malha para preview
                const previewMesh = new THREE.Mesh(simplifiedGeometry, material);
                
                // Remover preview anterior se existir
                if (this.modelViewer.previewObject) {
                    this.modelViewer.scene.remove(this.modelViewer.previewObject);
                }
                
                // Adicionar novo preview
                this.modelViewer.previewObject = previewMesh;
                this.modelViewer.scene.add(previewMesh);
            }
        } catch (e) {
            console.warn('ModelViewerEnhancement: Failed to create preview', e);
        }
    }
    
    /**
     * Implementação de carregamento padrão como fallback
     * @param {string} url - URL do modelo
     * @param {string} fileType - Tipo de arquivo (stl ou obj)
     * @param {Function} onProgress - Callback de progresso
     * @param {Function} onLoad - Callback de carregamento completo
     * @param {Function} onError - Callback de erro
     */
    loadModelStandard(url, fileType, onProgress, onLoad, onError) {
        let loader;
        
        switch (fileType.toLowerCase()) {
            case 'stl':
                loader = new THREE.STLLoader();
                break;
            case 'obj':
                loader = new THREE.OBJLoader();
                break;
            default:
                if (onError) onError(new Error(`Tipo de arquivo não suportado: ${fileType}`));
                return;
        }
        
        loader.load(
            url,
            result => {
                if (onLoad) onLoad(result);
            },
            progress => {
                if (onProgress) {
                    const percent = progress.total > 0 ? (progress.loaded / progress.total) * 100 : 0;
                    onProgress({
                        loaded: progress.loaded,
                        total: progress.total,
                        progress: percent
                    });
                }
            },
            error => {
                if (onError) onError(error);
            }
        );
    }
}

// Manter referência global para debug
window.ModelViewerEnhancement = ModelViewerEnhancement;