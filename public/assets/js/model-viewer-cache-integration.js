/**
 * ModelViewerCacheIntegration - Integração entre ModelViewer e sistema de cache
 * 
 * Este componente conecta o visualizador de modelos 3D (ModelViewer) com o
 * sistema de cache de modelos (ModelCacheManager), permitindo o carregamento
 * eficiente e acesso rápido a modelos previamente baixados.
 */
(function() {
    // Armazenar referência às funções originais
    const originalModelViewerLoadModel = ModelViewer.prototype.loadModel;
    
    /**
     * Estende o ModelViewer com funcionalidades de cache
     */
    function extendModelViewer() {
        // Verificar se ModelViewer está disponível
        if (typeof ModelViewer !== 'function') {
            console.warn('ModelViewerCacheIntegration: ModelViewer não encontrado. A integração de cache não será aplicada.');
            return;
        }
        
        // Adicionar propriedades relacionadas ao cache
        ModelViewer.prototype.cacheManager = null;
        ModelViewer.prototype.modelId = null;
        
        /**
         * Define o gerenciador de cache para o visualizador
         * @param {ModelCacheManager} cacheManager - Instância do gerenciador de cache
         */
        ModelViewer.prototype.setCacheManager = function(cacheManager) {
            this.cacheManager = cacheManager;
            
            if (this.options.debug) {
                console.log('ModelViewerCacheIntegration: Gerenciador de cache conectado ao ModelViewer');
            }
        };
        
        /**
         * Define o ID do modelo sendo visualizado
         * @param {string} modelId - Identificador único do modelo
         */
        ModelViewer.prototype.setModelId = function(modelId) {
            this.modelId = modelId;
            
            if (this.options.debug) {
                console.log(`ModelViewerCacheIntegration: ID do modelo definido: ${modelId}`);
            }
        };
        
        /**
         * Sobrescreve o método loadModel para verificar o cache antes de baixar
         * @param {string} overrideFilePath - Caminho opcional para substituir o caminho padrão
         */
        ModelViewer.prototype.loadModel = function(overrideFilePath) {
            // Usar caminho substituído ou padrão
            const filePath = overrideFilePath || this.options.filePath;
            const fileType = this.options.fileType.toLowerCase();
            
            // Verificar se o cache está disponível
            if (!this.cacheManager) {
                // Cache não disponível, usar método original
                if (this.options.debug) {
                    console.log('ModelViewerCacheIntegration: Cache não disponível, carregando normalmente');
                }
                return originalModelViewerLoadModel.call(this, filePath);
            }
            
            // Verificar se temos um modelId
            if (!this.modelId) {
                // Extrair modelId da URL se presente
                const url = new URL(filePath, window.location.href);
                this.modelId = url.searchParams.get('id');
                
                if (!this.modelId) {
                    // Não foi possível determinar o modelId, usar método original
                    if (this.options.debug) {
                        console.log('ModelViewerCacheIntegration: Sem ID de modelo, carregando normalmente');
                    }
                    return originalModelViewerLoadModel.call(this, filePath);
                }
            }
            
            // Atualizar indicador de progresso
            this.updateLoadingProgress(0);
            
            // Verificar se o modelo está em cache
            this.cacheManager.hasModel(this.modelId)
                .then(inCache => {
                    if (inCache) {
                        // Modelo está em cache, carregar dele
                        if (this.options.debug) {
                            console.log(`ModelViewerCacheIntegration: Modelo ${this.modelId} encontrado no cache`);
                        }
                        
                        this.updateLoadingProgress(20);
                        return this.loadModelFromCache();
                    } else {
                        // Modelo não está em cache, baixar normalmente
                        if (this.options.debug) {
                            console.log(`ModelViewerCacheIntegration: Modelo ${this.modelId} não encontrado no cache, baixando`);
                        }
                        
                        this.updateLoadingProgress(10);
                        return this.loadModelAndCache(filePath, fileType);
                    }
                })
                .catch(error => {
                    // Erro ao verificar cache, usar método original
                    console.warn('ModelViewerCacheIntegration: Erro ao verificar cache', error);
                    originalModelViewerLoadModel.call(this, filePath);
                });
        };
        
        /**
         * Carrega um modelo a partir do cache
         * @returns {Promise} Promessa resolvida quando o modelo for carregado
         */
        ModelViewer.prototype.loadModelFromCache = function() {
            return new Promise((resolve, reject) => {
                if (!this.cacheManager || !this.modelId) {
                    reject(new Error('Cache manager ou model ID não disponível'));
                    return;
                }
                
                // Obter modelo do cache
                this.cacheManager.getModel(this.modelId)
                    .then(modelData => {
                        if (!modelData || !modelData.data) {
                            reject(new Error('Dados do modelo não encontrados no cache'));
                            return;
                        }
                        
                        // Atualizar indicador de progresso
                        this.updateLoadingProgress(50);
                        
                        // Verificar tipo de modelo
                        const modelType = modelData.modelType || this.options.fileType;
                        
                        // Carregar modelo do cache
                        if (modelType === 'stl') {
                            this.loadCachedSTLModel(modelData.data);
                        } else if (modelType === 'obj') {
                            this.loadCachedOBJModel(modelData.data);
                        } else {
                            reject(new Error(`Tipo de modelo não suportado: ${modelType}`));
                        }
                        
                        resolve();
                    })
                    .catch(error => {
                        console.warn('ModelViewerCacheIntegration: Erro ao carregar do cache', error);
                        
                        // Fallback para carregamento normal
                        originalModelViewerLoadModel.call(this, this.options.filePath);
                        reject(error);
                    });
            });
        };
        
        /**
         * Carrega um modelo STL a partir do cache
         * @param {string|ArrayBuffer} modelData - Dados do modelo em cache
         */
        ModelViewer.prototype.loadCachedSTLModel = function(modelData) {
            try {
                let geometry;
                
                // Processar dados conforme o tipo
                if (typeof modelData === 'string') {
                    // Verificar se é uma string base64 ou conteúdo direto
                    if (modelData.startsWith('data:') || modelData.startsWith('SOLID') || modelData.startsWith('solid')) {
                        // Conteúdo direto STL
                        const loader = new THREE.STLLoader();
                        geometry = loader.parse(modelData);
                    } else {
                        // Assumir base64
                        const binaryString = window.atob(modelData);
                        const bytes = new Uint8Array(binaryString.length);
                        for (let i = 0; i < binaryString.length; i++) {
                            bytes[i] = binaryString.charCodeAt(i);
                        }
                        
                        const loader = new THREE.STLLoader();
                        geometry = loader.parse(bytes.buffer);
                    }
                } else if (modelData instanceof ArrayBuffer) {
                    // Dados binários
                    const loader = new THREE.STLLoader();
                    geometry = loader.parse(modelData);
                } else {
                    throw new Error('Formato de dados STL não suportado');
                }
                
                // Criar material
                const material = this.createOptimizedMaterial();
                
                // Criar malha
                const mesh = new THREE.Mesh(geometry, material);
                
                // Centralizar o modelo
                geometry.computeBoundingBox();
                const box = geometry.boundingBox;
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
                
                // Esconder carregamento
                this.hideLoading();
                
                // Log de sucesso
                if (this.options.debug) {
                    console.log('ModelViewerCacheIntegration: Modelo STL carregado do cache com sucesso');
                }
                
                // Atualizar indicador de progresso
                this.updateLoadingProgress(100);
            } catch (error) {
                console.error('ModelViewerCacheIntegration: Erro ao carregar STL do cache', error);
                
                // Fallback para carregamento normal
                originalModelViewerLoadModel.call(this, this.options.filePath);
            }
        };
        
        /**
         * Carrega um modelo OBJ a partir do cache
         * @param {string|ArrayBuffer} modelData - Dados do modelo em cache
         */
        ModelViewer.prototype.loadCachedOBJModel = function(modelData) {
            try {
                let objContent;
                
                // Processar dados conforme o tipo
                if (typeof modelData === 'string') {
                    objContent = modelData;
                } else {
                    // Converter ArrayBuffer para string
                    const decoder = new TextDecoder('utf-8');
                    objContent = decoder.decode(modelData);
                }
                
                // Carregar modelo OBJ a partir do conteúdo
                const loader = new THREE.OBJLoader();
                const object = loader.parse(objContent);
                
                // Aplicar material
                object.traverse((child) => {
                    if (child instanceof THREE.Mesh) {
                        child.material = this.createOptimizedMaterial();
                    }
                });
                
                // Centralizar o modelo
                const box = new THREE.Box3().setFromObject(object);
                const center = new THREE.Vector3();
                box.getCenter(center);
                object.position.sub(center);
                
                // Normalizar tamanho
                this.normalizeModelSize(object);
                
                // Adicionar à cena
                this.object = object;
                this.scene.add(object);
                
                // Ajustar câmera
                this.adjustCameraToModel();
                
                // Esconder carregamento
                this.hideLoading();
                
                // Log de sucesso
                if (this.options.debug) {
                    console.log('ModelViewerCacheIntegration: Modelo OBJ carregado do cache com sucesso');
                }
                
                // Atualizar indicador de progresso
                this.updateLoadingProgress(100);
            } catch (error) {
                console.error('ModelViewerCacheIntegration: Erro ao carregar OBJ do cache', error);
                
                // Fallback para carregamento normal
                originalModelViewerLoadModel.call(this, this.options.filePath);
            }
        };
        
        /**
         * Carrega um modelo e adiciona ao cache
         * @param {string} filePath - Caminho do arquivo
         * @param {string} fileType - Tipo de arquivo (stl ou obj)
         * @returns {Promise} Promessa resolvida quando o modelo for carregado
         */
        ModelViewer.prototype.loadModelAndCache = function(filePath, fileType) {
            return new Promise((resolve, reject) => {
                // URL para requisição
                const url = new URL(filePath, window.location.href);
                
                // Realizar requisição para obter o arquivo
                fetch(url.toString())
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        // Atualizar indicador de progresso
                        this.updateLoadingProgress(40);
                        
                        // Obter dados
                        return response.arrayBuffer();
                    })
                    .then(arrayBuffer => {
                        // Atualizar indicador de progresso
                        this.updateLoadingProgress(70);
                        
                        // Adicionar ao cache
                        if (this.cacheManager && this.modelId) {
                            this.cacheManager.addModel({
                                id: this.modelId,
                                modelType: fileType,
                                data: arrayBuffer,
                                uri: filePath
                            }).catch(err => {
                                console.warn('ModelViewerCacheIntegration: Erro ao adicionar modelo ao cache', err);
                                // Continuar carregamento mesmo se o cache falhar
                            });
                        }
                        
                        // Carregar modelo com o loader apropriado
                        if (fileType === 'stl') {
                            const loader = new THREE.STLLoader();
                            const geometry = loader.parse(arrayBuffer);
                            
                            // Criar material
                            const material = this.createOptimizedMaterial();
                            
                            // Criar malha
                            const mesh = new THREE.Mesh(geometry, material);
                            
                            // Centralizar o modelo
                            geometry.computeBoundingBox();
                            const box = geometry.boundingBox;
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
                            
                            // Esconder carregamento
                            this.hideLoading();
                            
                            // Atualizar indicador de progresso
                            this.updateLoadingProgress(100);
                            
                            resolve();
                        } else if (fileType === 'obj') {
                            // Para OBJ, primeiro converter para texto
                            const decoder = new TextDecoder('utf-8');
                            const objContent = decoder.decode(arrayBuffer);
                            
                            // Carregar modelo OBJ a partir do conteúdo
                            const loader = new THREE.OBJLoader();
                            const object = loader.parse(objContent);
                            
                            // Aplicar material
                            object.traverse((child) => {
                                if (child instanceof THREE.Mesh) {
                                    child.material = this.createOptimizedMaterial();
                                }
                            });
                            
                            // Centralizar o modelo
                            const box = new THREE.Box3().setFromObject(object);
                            const center = new THREE.Vector3();
                            box.getCenter(center);
                            object.position.sub(center);
                            
                            // Normalizar tamanho
                            this.normalizeModelSize(object);
                            
                            // Adicionar à cena
                            this.object = object;
                            this.scene.add(object);
                            
                            // Ajustar câmera
                            this.adjustCameraToModel();
                            
                            // Esconder carregamento
                            this.hideLoading();
                            
                            // Atualizar indicador de progresso
                            this.updateLoadingProgress(100);
                            
                            resolve();
                        } else {
                            reject(new Error(`Tipo de arquivo não suportado: ${fileType}`));
                        }
                    })
                    .catch(error => {
                        console.error('ModelViewerCacheIntegration: Erro ao carregar modelo', error);
                        
                        // Fallback para método original para evitar quebra total
                        originalModelViewerLoadModel.call(this, filePath);
                        reject(error);
                    });
            });
        };
    }
    
    // Array de hooks de inicialização do ModelViewer
    window.modelViewerInitHooks = window.modelViewerInitHooks || [];
    
    // Adicionar hook para quando o DOM estiver carregado
    document.addEventListener('DOMContentLoaded', function() {
        // Estender ModelViewer com funcionalidades de cache
        extendModelViewer();
        
        // Verificar se temos algum visualizador para inicializar
        if (typeof ModelViewer === 'function') {
            console.log('ModelViewerCacheIntegration: Visualizador 3D estendido com suporte a cache');
        }
    });
    
    // Hook para inicializar visualizadores existentes
    function initializeExistingViewers() {
        // Verificar se janela model3dCacheInfo está disponível
        if (window.model3dCacheInfo && window.model3dCacheInfo.enabled) {
            // Verificar se há informações sobre modelos a pré-carregar
            if (window.model3dCacheInfo.preloadModels && window.model3dCacheInfo.preloadModels.length > 0) {
                console.log(`ModelViewerCacheIntegration: ${window.model3dCacheInfo.preloadModels.length} modelos disponíveis para pré-carregamento`);
            }
        }
    }
    
    // Inicializar quando a janela estiver totalmente carregada
    window.addEventListener('load', initializeExistingViewers);
})();
