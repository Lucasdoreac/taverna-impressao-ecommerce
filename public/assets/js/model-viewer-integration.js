/**
 * Model Viewer Integration
 * 
 * Este arquivo integra o módulo de otimização (ModelViewerEnhancement) 
 * com o visualizador 3D principal (ModelViewer).
 */

(function() {
    // Armazenar referências para instâncias do visualizador
    window.tavernaViewers = window.tavernaViewers || {};
    
    // Configuração global
    const OPTIMIZATION_ENABLED = true; // Pode ser configurado via localStorage
    
    /**
     * Inicializa o sistema de visualização 3D otimizado
     * @param {string} containerId - ID do container onde o visualizador será renderizado
     * @param {Object} options - Opções de configuração
     * @returns {ModelViewer} Instância do visualizador
     */
    function initOptimizedViewer(containerId, options = {}) {
        // Verificar se as otimizações estão habilitadas
        const optimizationEnabled = localStorage.getItem('tavernaOptimizerEnabled') !== 'false' && OPTIMIZATION_ENABLED;
        
        // Carregar configurações salvas
        const savedConfig = getSavedConfiguration();
        
        // Mesclar opções com configurações salvas
        const mergedOptions = Object.assign({}, options, savedConfig);
        
        // Criar instância do visualizador principal
        const viewer = new ModelViewer({
            containerId: containerId,
            ...mergedOptions
        });
        
        // Registrar visualizador
        window.tavernaViewers[containerId] = viewer;
        
        // Se as otimizações estiverem desabilitadas, retornar apenas o visualizador principal
        if (!optimizationEnabled) {
            console.log('ModelViewerIntegration: Otimizações desabilitadas. Usando visualizador padrão.');
            return viewer;
        }
        
        // Criar instância do módulo de otimização
        const optimizer = new ModelViewerEnhancement(viewer);
        
        // Armazenar referência para o otimizador
        viewer.optimizer = optimizer;
        
        // Adicionar métodos do otimizador ao visualizador
        enhanceViewerWithOptimizationMethods(viewer, optimizer);
        
        // Adicionar eventos de performance
        setupPerformanceMonitoring(viewer, optimizer);
        
        // Inicializar interface do usuário para configurações, se necessário
        if (mergedOptions.showAdvancedOptions) {
            setupConfigurationUI(containerId, viewer, optimizer);
        }
        
        return viewer;
    }
    
    /**
     * Obtém configurações salvas do localStorage
     * @returns {Object} Configurações salvas
     */
    function getSavedConfiguration() {
        try {
            const savedConfig = localStorage.getItem('tavernaViewerConfig');
            return savedConfig ? JSON.parse(savedConfig) : {};
        } catch (e) {
            console.warn('ModelViewerIntegration: Erro ao carregar configurações salvas', e);
            return {};
        }
    }
    
    /**
     * Salva configurações no localStorage
     * @param {Object} config - Configurações a serem salvas
     */
    function saveConfiguration(config) {
        try {
            localStorage.setItem('tavernaViewerConfig', JSON.stringify(config));
        } catch (e) {
            console.warn('ModelViewerIntegration: Erro ao salvar configurações', e);
        }
    }
    
    /**
     * Adiciona métodos do otimizador ao visualizador
     * @param {ModelViewer} viewer - Instância do visualizador
     * @param {ModelViewerEnhancement} optimizer - Instância do otimizador
     */
    function enhanceViewerWithOptimizationMethods(viewer, optimizer) {
        // Substituir método de carregamento de modelo para usar carregamento progressivo otimizado
        const originalLoadModel = viewer.loadModel;
        
        viewer.loadModel = function() {
            // Se o modelo deve usar carregamento progressivo otimizado
            if (this.options.progressiveLoading) {
                const filePath = this.options.filePath;
                const fileType = this.options.fileType.toLowerCase();
                
                // Usar carregamento progressivo otimizado do otimizador
                optimizer.loadModelProgressively(
                    filePath,
                    fileType,
                    progress => {
                        this.updateLoadingProgress(progress.progress);
                    },
                    result => {
                        // Processar resultado do carregamento
                        if (fileType === 'stl') {
                            this.processSTLModel(result);
                        } else if (fileType === 'obj') {
                            this.processOBJModel(result);
                        }
                        
                        // Ajustar LOD com base no dispositivo
                        this.adjustLODBasedOnDevice();
                        
                        // Esconder carregamento
                        this.hideLoading();
                    },
                    error => {
                        console.error('ModelViewer: Erro ao carregar modelo', error);
                        this.hideLoading();
                    }
                );
            } else {
                // Usar método original
                originalLoadModel.call(this);
            }
        };
        
        // Método para ajustar o LOD com base no dispositivo
        viewer.adjustLODBasedOnDevice = function() {
            const deviceProfile = optimizer.deviceProfile;
            
            if (!deviceProfile) return;
            
            // Escolher LOD inicial com base no perfil do dispositivo
            let initialLOD;
            
            switch (deviceProfile) {
                case 'highEnd':
                    initialLOD = 'high';
                    break;
                case 'midRange':
                    initialLOD = 'medium';
                    break;
                case 'lowEnd':
                case 'webgl1Only':
                    initialLOD = 'low';
                    break;
                case 'veryLowEnd':
                    initialLOD = 'veryLow';
                    break;
                default:
                    initialLOD = 'high';
            }
            
            // Aplicar LOD inicial
            if (this.lodLevels && this.lodLevels[initialLOD]) {
                this.applyLOD(initialLOD);
            }
        };
        
        // Método para processar modelo STL carregado
        viewer.processSTLModel = function(geometry) {
            // Criar múltiplos níveis de LOD
            this.lodLevels = optimizer.createMultiLevelLOD(geometry);
            
            // Criar material
            const material = this.createOptimizedMaterial();
            
            // Criar malha com o maior nível de detalhe
            const mesh = new THREE.Mesh(this.lodLevels.veryHigh, material);
            
            // Centralizar o modelo
            this.lodLevels.veryHigh.computeBoundingBox();
            const box = this.lodLevels.veryHigh.boundingBox;
            const center = new THREE.Vector3();
            box.getCenter(center);
            mesh.position.sub(center);
            
            // Normalizar tamanho
            this.normalizeModelSize(mesh);
            
            // Adicionar à cena
            this.object = mesh;
            this.scene.add(mesh);
            
            // Ajustar câmera
            this.adjustCameraToModel();
            
            // Armazenar metadados
            this.modelData = {
                type: 'stl',
                metadata: {
                    vertices: this.lodLevels.veryHigh.attributes.position.count,
                    triangles: this.lodLevels.veryHigh.attributes.position.count / 3
                }
            };
            
            // Definir LOD atual
            this.currentLOD = 'veryHigh';
        };
        
        // Método para processar modelo OBJ carregado
        viewer.processOBJModel = function(object) {
            // Para OBJ, a criação de LOD é mais complexa
            // Aqui lidamos com um objeto que pode ter múltiplas malhas
            this.object = object;
            
            // Centralizar o modelo
            const box = new THREE.Box3().setFromObject(object);
            const center = new THREE.Vector3();
            box.getCenter(center);
            object.position.sub(center);
            
            // Normalizar tamanho
            this.normalizeModelSize(object);
            
            // Adicionar à cena
            this.scene.add(object);
            
            // Ajustar câmera
            this.adjustCameraToModel();
            
            // Definir LOD atual como alto por padrão
            this.currentLOD = 'high';
        };
        
        // Adicionar método para obter estatísticas de desempenho
        viewer.getPerformanceStats = function() {
            return {
                fps: this.fpsMonitor ? this.fpsMonitor.value : 0,
                triangles: this.object ? this.countTriangles() : 0,
                memoryUsage: optimizer.memoryUsage.current,
                lodLevel: this.currentLOD
            };
        };
    }
    
    /**
     * Configura monitoramento de performance
     * @param {ModelViewer} viewer - Instância do visualizador
     * @param {ModelViewerEnhancement} optimizer - Instância do otimizador
     */
    function setupPerformanceMonitoring(viewer, optimizer) {
        // Intervalo para monitoramento de performance
        const performanceInterval = setInterval(() => {
            // Se o visualizador foi destruído, limpar intervalo
            if (!viewer.scene) {
                clearInterval(performanceInterval);
                return;
            }
            
            // Obter FPS atual
            const fps = viewer.fpsMonitor ? viewer.fpsMonitor.value : 0;
            
            // Estimar uso de memória
            let memoryUsage = 0;
            
            if (window.performance && window.performance.memory) {
                memoryUsage = Math.round(window.performance.memory.usedJSHeapSize / 1048576); // Converter para MB
            }
            
            // Atualizar métricas de performance no otimizador
            optimizer.updatePerformanceMetrics(fps, memoryUsage);
            
        }, 1000); // Verificar a cada segundo
    }
    
    /**
     * Configura interface do usuário para configurações avançadas
     * @param {string} containerId - ID do container do visualizador
     * @param {ModelViewer} viewer - Instância do visualizador
     * @param {ModelViewerEnhancement} optimizer - Instância do otimizador
     */
    function setupConfigurationUI(containerId, viewer, optimizer) {
        // Criar container para configurações
        const configContainer = document.createElement('div');
        configContainer.className = 'model-viewer-config';
        configContainer.style.position = 'absolute';
        configContainer.style.top = '10px';
        configContainer.style.right = '10px';
        configContainer.style.backgroundColor = 'rgba(0,0,0,0.7)';
        configContainer.style.padding = '10px';
        configContainer.style.borderRadius = '5px';
        configContainer.style.color = '#fff';
        configContainer.style.fontSize = '12px';
        configContainer.style.zIndex = '1000';
        configContainer.style.display = 'none'; // Inicialmente oculto
        
        // Botão de configurações
        const settingsButton = document.createElement('button');
        settingsButton.className = 'model-viewer-settings-btn';
        settingsButton.innerHTML = '<i class="fas fa-cog"></i>';
        settingsButton.style.position = 'absolute';
        settingsButton.style.top = '10px';
        settingsButton.style.right = '10px';
        settingsButton.style.backgroundColor = 'rgba(0,0,0,0.5)';
        settingsButton.style.color = '#fff';
        settingsButton.style.border = 'none';
        settingsButton.style.borderRadius = '50%';
        settingsButton.style.width = '30px';
        settingsButton.style.height = '30px';
        settingsButton.style.cursor = 'pointer';
        settingsButton.style.zIndex = '1001';
        
        // Conteúdo de configurações
        configContainer.innerHTML = `
            <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;">Configurações Avançadas</h4>
            
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px;">Nível de Qualidade</label>
                <select id="${containerId}-quality" style="width: 100%; padding: 5px;">
                    <option value="veryHigh">Muito Alta</option>
                    <option value="high">Alta</option>
                    <option value="medium">Média</option>
                    <option value="low">Baixa</option>
                    <option value="veryLow">Muito Baixa</option>
                </select>
            </div>
            
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" id="${containerId}-auto-quality" checked> 
                    Qualidade Automática
                </label>
            </div>
            
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" id="${containerId}-progressive" checked> 
                    Carregamento Progressivo
                </label>
            </div>
            
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" id="${containerId}-memory-mgmt" checked> 
                    Gerenciamento de Memória
                </label>
            </div>
            
            <div style="margin-top: 15px; font-size: 11px;">
                <div id="${containerId}-stats"></div>
            </div>
        `;
        
        // Adicionar elementos ao container do visualizador
        const viewerContainer = document.getElementById(containerId);
        viewerContainer.style.position = 'relative';
        viewerContainer.appendChild(settingsButton);
        viewerContainer.appendChild(configContainer);
        
        // Evento de clique no botão de configurações
        settingsButton.addEventListener('click', () => {
            configContainer.style.display = configContainer.style.display === 'none' ? 'block' : 'none';
        });
        
        // Obter elementos de configuração
        const qualitySelect = document.getElementById(`${containerId}-quality`);
        const autoQualityCheckbox = document.getElementById(`${containerId}-auto-quality`);
        const progressiveCheckbox = document.getElementById(`${containerId}-progressive`);
        const memoryMgmtCheckbox = document.getElementById(`${containerId}-memory-mgmt`);
        const statsDiv = document.getElementById(`${containerId}-stats`);
        
        // Definir valores iniciais
        qualitySelect.value = viewer.currentLOD || 'high';
        autoQualityCheckbox.checked = viewer.options.adaptiveQuality !== false;
        progressiveCheckbox.checked = viewer.options.progressiveLoading !== false;
        memoryMgmtCheckbox.checked = true;
        
        // Atualizador de estatísticas
        const updateStats = () => {
            if (statsDiv) {
                const stats = viewer.getPerformanceStats();
                statsDiv.innerHTML = `
                    FPS: ${Math.round(stats.fps)} | 
                    Triângulos: ${stats.triangles} | 
                    Memória: ${stats.memoryUsage} MB | 
                    LOD: ${stats.lodLevel}
                `;
            }
        };
        setInterval(updateStats, 1000);
        
        // Eventos de alteração
        qualitySelect.addEventListener('change', () => {
            const quality = qualitySelect.value;
            
            // Desabilitar adaptação automática
            autoQualityCheckbox.checked = false;
            viewer.options.adaptiveQuality = false;
            
            // Aplicar qualidade selecionada
            if (viewer.lodLevels && viewer.lodLevels[quality]) {
                viewer.applyLOD(quality);
            }
            
            // Salvar configuração
            saveConfiguration({
                adaptiveQuality: false,
                currentLOD: quality
            });
        });
        
        autoQualityCheckbox.addEventListener('change', () => {
            const autoQuality = autoQualityCheckbox.checked;
            viewer.options.adaptiveQuality = autoQuality;
            
            // Salvar configuração
            saveConfiguration({
                adaptiveQuality: autoQuality
            });
            
            // Se automático, iniciar adaptação
            if (autoQuality) {
                optimizer.selectLODBasedOnPerformance();
            }
        });
        
        progressiveCheckbox.addEventListener('change', () => {
            const progressive = progressiveCheckbox.checked;
            viewer.options.progressiveLoading = progressive;
            
            // Salvar configuração
            saveConfiguration({
                progressiveLoading: progressive
            });
        });
        
        memoryMgmtCheckbox.addEventListener('change', () => {
            const memoryMgmt = memoryMgmtCheckbox.checked;
            
            // Configurar intervalo de limpeza de memória
            optimizer.memoryUsage.cleanupInterval = memoryMgmt ? 30000 : 0; // 30 segundos ou desabilitado
            
            // Salvar configuração
            saveConfiguration({
                memoryManagement: memoryMgmt
            });
        });
    }
    
    // Exportar para uso global
    window.initOptimizedViewer = initOptimizedViewer;
    
    // Substituir qualquer inicializador existente
    if (window.initModelViewer) {
        console.log('ModelViewerIntegration: Substituindo inicializador existente');
        window.initModelViewerOriginal = window.initModelViewer;
        window.initModelViewer = initOptimizedViewer;
    }
})();