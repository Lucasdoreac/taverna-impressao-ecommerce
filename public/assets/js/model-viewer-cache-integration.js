/**
 * Model Viewer Cache Integration
 * 
 * Integração entre o ModelViewer e o sistema de cache de modelos 3D.
 * Este módulo estende o ModelViewer para utilizar o ModelCacheManager.
 */
(function() {
    // Verificar existência do ModelViewer
    if (typeof ModelViewer !== 'function') {
        console.warn('ModelViewer não encontrado. A integração com cache não será aplicada.');
        return;
    }
    
    // Armazenar referência ao construtor original
    const OriginalModelViewer = ModelViewer;
    
    // Verificar se a integração já foi aplicada
    if (OriginalModelViewer.prototype._cacheEnabled) {
        return;
    }
    
    // Marcar integração como aplicada
    OriginalModelViewer.prototype._cacheEnabled = true;
    
    // Estender o protótipo do ModelViewer para adicionar suporte a cache
    
    /**
     * Define o gerenciador de cache para o visualizador
     * @param {ModelCacheManager} cacheManager Instância do gerenciador de cache
     */
    OriginalModelViewer.prototype.setCacheManager = function(cacheManager) {
        this.cacheManager = cacheManager;
        this.log('Cache manager configurado');
    };
    
    /**
     * Verifica se o visualizador tem gerenciador de cache configurado
     * @returns {boolean} Verdadeiro se o gerenciador de cache estiver configurado
     */
    OriginalModelViewer.prototype.hasCacheManager = function() {
        return !!this.cacheManager;
    };
    
    /**
     * Verifica se um modelo está em cache
     * @param {string} fileUrl URL do arquivo de modelo
     * @param {string} modelId ID do modelo no cache
     * @returns {Promise<boolean>} Promessa resolvida com estado do cache
     */
    OriginalModelViewer.prototype.checkModelCache = function(fileUrl, modelId) {
        if (!this.hasCacheManager() || !modelId) {
            return Promise.resolve(false);
        }
        
        return this.cacheManager.hasModel(modelId);
    };
    
    /**
     * Adiciona um modelo ao cache
     * @param {string} modelId ID do modelo
     * @param {ArrayBuffer|string} data Dados do modelo
     * @param {string} modelType Tipo do modelo (stl, obj, etc.)
     * @returns {Promise} Promessa resolvida quando o modelo for adicionado
     */
    OriginalModelViewer.prototype.addModelToCache = function(modelId, data, modelType) {
        if (!this.hasCacheManager() || !modelId || !data) {
            return Promise.resolve(false);
        }
        
        return this.cacheManager.addModel({
            id: modelId,
            data: data,
            modelType: modelType || 'unknown'
        }).then(() => {
            this.log(`Modelo ${modelId} adicionado ao cache`);
            return true;
        }).catch(error => {
            this.log(`Erro ao adicionar modelo ao cache: ${error.message}`);
            return false;
        });
    };
    
    /**
     * Carrega um modelo do cache
     * @param {string} modelId ID do modelo
     * @returns {Promise} Promessa resolvida com os dados do modelo
     */
    OriginalModelViewer.prototype.loadModelFromCache = function(modelId) {
        if (!this.hasCacheManager() || !modelId) {
            return Promise.resolve(null);
        }
        
        return this.cacheManager.getModel(modelId).then(model => {
            if (model) {
                this.log(`Modelo ${modelId} carregado do cache`);
            }
            return model;
        }).catch(error => {
            this.log(`Erro ao carregar modelo do cache: ${error.message}`);
            return null;
        });
    };
    
    // Estender o método loadModel para usar cache quando disponível
    const originalLoadModel = OriginalModelViewer.prototype.loadModel;
    OriginalModelViewer.prototype.loadModel = function() {
        // Se não temos o gerenciador de cache ou o ID do modelo, usar método original
        if (!this.hasCacheManager() || !this.modelId) {
            return originalLoadModel.apply(this, arguments);
        }
        
        const filePath = this.options.filePath;
        const fileType = this.options.fileType.toLowerCase();
        
        // Tentar carregar do cache primeiro
        this.log(`Verificando cache para modelo ${this.modelId}`);
        this.isLoading = true;
        
        return this.loadModelFromCache(this.modelId).then(cachedModel => {
            if (cachedModel && cachedModel.data) {
                this.log(`Usando modelo ${this.modelId} do cache`);
                
                // Determinar o tipo de dados
                let modelData = cachedModel.data;
                if (typeof modelData === 'string' && modelData.startsWith('data:')) {
                    // Dados codificados em base64
                    const base64Match = modelData.match(/^data:.*;base64,(.*)$/);
                    if (base64Match) {
                        const base64Data = base64Match[1];
                        const binaryString = window.atob(base64Data);
                        const bytes = new Uint8Array(binaryString.length);
                        for (let i = 0; i < binaryString.length; i++) {
                            bytes[i] = binaryString.charCodeAt(i);
                        }
                        modelData = bytes.buffer;
                    }
                } else if (typeof modelData === 'string') {
                    // String base64 pura
                    try {
                        const binaryString = window.atob(modelData);
                        const bytes = new Uint8Array(binaryString.length);
                        for (let i = 0; i < binaryString.length; i++) {
                            bytes[i] = binaryString.charCodeAt(i);
                        }
                        modelData = bytes.buffer;
                    } catch (e) {
                        // Não é base64, tratar como string normal
                        const encoder = new TextEncoder();
                        modelData = encoder.encode(modelData).buffer;
                    }
                }
                
                // Processar dados do modelo conforme o tipo
                if (fileType === 'stl') {
                    this.processSTLData(modelData);
                } else if (fileType === 'obj') {
                    this.processOBJData(modelData);
                } else {
                    throw new Error(`Tipo de arquivo não suportado: ${fileType}`);
                }
                
                return true;
            } else {
                // Não encontrado no cache, carregar normalmente
                this.log(`Modelo ${this.modelId} não encontrado no cache, carregando da URL...`);
                
                return new Promise((resolve, reject) => {
                    // Carregar normalmente e adicionar ao cache
                    const xhr = new XMLHttpRequest();
                    xhr.responseType = 'arraybuffer';
                    
                    xhr.onload = () => {
                        if (xhr.status === 200) {
                            const modelData = xhr.response;
                            
                            // Processar dados do modelo
                            try {
                                if (fileType === 'stl') {
                                    this.processSTLData(modelData);
                                } else if (fileType === 'obj') {
                                    this.processOBJData(modelData);
                                } else {
                                    throw new Error(`Tipo de arquivo não suportado: ${fileType}`);
                                }
                                
                                // Adicionar ao cache para uso futuro
                                this.addModelToCache(this.modelId, modelData, fileType)
                                    .then(() => {
                                        this.log(`Modelo ${this.modelId} adicionado ao cache com sucesso`);
                                    })
                                    .catch(e => {
                                        this.log(`Erro ao adicionar modelo ao cache: ${e.message}`);
                                    });
                                
                                resolve(true);
                            } catch (error) {
                                this.log(`Erro ao processar modelo: ${error.message}`);
                                this.hideLoading();
                                reject(error);
                            }
                        } else {
                            this.log(`Erro ao carregar modelo: HTTP ${xhr.status}`);
                            this.hideLoading();
                            reject(new Error(`HTTP ${xhr.status}`));
                        }
                    };
                    
                    xhr.onerror = (error) => {
                        this.log(`Erro de rede ao carregar modelo: ${error}`);
                        this.hideLoading();
                        reject(error);
                    };
                    
                    xhr.onprogress = (event) => {
                        if (event.lengthComputable && this.options.progressiveLoading) {
                            const percent = (event.loaded / event.total) * 100;
                            this.updateLoadingProgress(percent);
                        }
                    };
                    
                    xhr.open('GET', filePath, true);
                    xhr.send();
                });
            }
        }).catch(error => {
            this.log(`Erro no sistema de cache: ${error.message}`);
            this.hideLoading();
            
            // Fallback para carregamento normal em caso de erro
            return originalLoadModel.apply(this, arguments);
        });
    };
    
    /**
     * Processa os dados de um STL
     * @param {ArrayBuffer} data Dados binários do STL
     * @private
     */
    OriginalModelViewer.prototype.processSTLData = function(data) {
        try {
            const loader = new THREE.STLLoader();
            const geometry = loader.parse(data);
            
            // Criar material com opções otimizadas
            const material = this.createOptimizedMaterial();
            
            // Simplificar geometria para dispositivos móveis, se necessário
            let processedGeometry = geometry;
            if (this.isMobile && this.options.optimizeForMobile && 
                geometry.attributes && geometry.attributes.position) {
                // Verificar se o modelo é complexo (mais de 100k vértices)
                const vertexCount = geometry.attributes.position.count;
                
                if (vertexCount > 100000) {
                    processedGeometry = this.simplifyGeometry(geometry);
                }
            }
            
            // Criar malha
            const mesh = new THREE.Mesh(processedGeometry, material);
            
            // Centralizar o modelo
            processedGeometry.computeBoundingBox();
            const box = processedGeometry.boundingBox;
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
            
            this.log(`Modelo STL processado com sucesso a partir de dados em cache`);
        } catch (error) {
            this.log(`Erro ao processar dados STL: ${error.message}`);
            throw error;
        }
    };
    
    /**
     * Processa os dados de um OBJ
     * @param {ArrayBuffer|string} data Dados do OBJ
     * @private
     */
    OriginalModelViewer.prototype.processOBJData = function(data) {
        try {
            const loader = new THREE.OBJLoader();
            
            // Converter ArrayBuffer para string se necessário
            let objContent;
            if (data instanceof ArrayBuffer) {
                objContent = new TextDecoder().decode(data);
            } else {
                objContent = data;
            }
            
            // Carregar OBJ a partir da string
            const object = loader.parse(objContent);
            
            // Otimizar para dispositivos móveis
            if (this.isMobile && this.options.optimizeForMobile) {
                object.traverse((child) => {
                    if (child instanceof THREE.Mesh) {
                        // Aplicar material otimizado
                        if (!child.material || this.shouldReplaceWithOptimizedMaterial(child.material)) {
                            child.material = this.createOptimizedMaterial();
                        }
                        
                        // Simplificar geometria se for muito complexa
                        if (child.geometry && child.geometry.attributes && 
                            child.geometry.attributes.position && 
                            child.geometry.attributes.position.count > 100000) {
                            child.geometry = this.simplifyGeometry(child.geometry);
                        }
                    }
                });
            } else {
                // Se não for dispositivo móvel, apenas aplicar material padrão onde necessário
                object.traverse((child) => {
                    if (child instanceof THREE.Mesh) {
                        if (!child.material) {
                            child.material = new THREE.MeshPhongMaterial({
                                color: this.options.modelColor,
                                specular: 0x111111,
                                shininess: 30
                            });
                        }
                    }
                });
            }
            
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
            
            this.log(`Modelo OBJ processado com sucesso a partir de dados em cache`);
        } catch (error) {
            this.log(`Erro ao processar dados OBJ: ${error.message}`);
            throw error;
        }
    };
    
    // Configurar o ModelViewer para usar ID de modelo para cache
    const originalInit = OriginalModelViewer.prototype.init;
    OriginalModelViewer.prototype.init = function() {
        // Extrair ID do modelo da URL ou opções
        const url = new URL(this.options.filePath, window.location.href);
        this.modelId = url.searchParams.get('id') || this.options.modelId;
        
        // Chamar inicialização original
        originalInit.apply(this, arguments);
        
        // Verificar e usar cache manager global se disponível
        if (!this.cacheManager && window.modelCacheManager) {
            this.setCacheManager(window.modelCacheManager);
        }
        
        // Chamar hooks de inicialização se existirem
        if (window.modelViewerInitHooks && Array.isArray(window.modelViewerInitHooks)) {
            window.modelViewerInitHooks.forEach(hook => {
                if (typeof hook === 'function') {
                    try {
                        hook(this);
                    } catch (e) {
                        console.error('Erro ao executar hook de inicialização:', e);
                    }
                }
            });
        }
    };
    
    // Registrar versão do modelo-viewer com integração de cache
    ModelViewer.version = (ModelViewer.version || '1.0.0') + '-cache';
    
    console.log(`ModelViewer com integração de cache (${ModelViewer.version}) inicializado.`);
})();
