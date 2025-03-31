/**
 * ModelCacheManager - Gerenciador de cache para modelos 3D no cliente
 * 
 * Este componente implementa um sistema de cache para modelos 3D (STL/OBJ) no navegador
 * utilizando IndexedDB para armazenamento. Trabalha em conjunto com o ModelViewer
 * para permitir carregamento rápido de modelos e reduzir transferência de dados.
 */
class ModelCacheManager {
    /**
     * Construtor
     * @param {Object} options - Opções de configuração
     */
    constructor(options = {}) {
        // Configurações padrão
        this.options = Object.assign({
            dbName: 'modelCache',
            dbVersion: 1,
            storeName: 'models',
            maxCacheSize: 50 * 1024 * 1024, // 50MB
            maxEntries: 100,
            expirationTime: 30 * 24 * 60 * 60 * 1000, // 30 dias em ms
            version: '1.0.0',
            debug: false,
            autoInit: true
        }, options);
        
        // Inicialização das propriedades
        this.db = null;
        this.initialized = false;
        this.indexedDBSupported = this.checkIndexedDBSupport();
        this.localStorageSupported = this.checkLocalStorageSupport();
        this.ready = false;
        this.readyCallbacks = [];
        
        if (!this.indexedDBSupported && !this.localStorageSupported) {
            this.log('Nem IndexedDB nem localStorage são suportados. Cache de modelos 3D desabilitado.');
            return;
        }
        
        // Inicializar automaticamente se configurado
        if (this.options.autoInit) {
            this.init();
        }
    }
    
    /**
     * Inicializa o gerenciador de cache
     * @returns {Promise} Promessa resolvida quando a inicialização for concluída
     */
    init() {
        if (this.initialized) {
            return Promise.resolve(this);
        }
        
        this.initialized = true;
        
        if (this.indexedDBSupported) {
            return this.initIndexedDB();
        } else if (this.localStorageSupported) {
            return this.initLocalStorage();
        } else {
            return Promise.reject(new Error('Nenhum mecanismo de armazenamento suportado'));
        }
    }
    
    /**
     * Inicializa o armazenamento com IndexedDB
     * @returns {Promise} Promessa resolvida quando o IndexedDB estiver pronto
     * @private
     */
    initIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.options.dbName, this.options.dbVersion);
            
            request.onerror = (event) => {
                this.log('Erro ao abrir o IndexedDB:', event.target.error);
                reject(event.target.error);
            };
            
            request.onsuccess = (event) => {
                this.db = event.target.result;
                this.log('IndexedDB inicializado com sucesso.');
                this.ready = true;
                this.callReadyCallbacks();
                resolve(this);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Verificar se o store já existe e excluir se estiver atualizando
                if (db.objectStoreNames.contains(this.options.storeName)) {
                    db.deleteObjectStore(this.options.storeName);
                }
                
                // Criar novo store
                const store = db.createObjectStore(this.options.storeName, { keyPath: 'id' });
                
                // Criar índices
                store.createIndex('timestamp', 'timestamp', { unique: false });
                store.createIndex('lastAccessed', 'lastAccessed', { unique: false });
                store.createIndex('modelType', 'modelType', { unique: false });
                store.createIndex('size', 'size', { unique: false });
                
                this.log('IndexedDB schema atualizado.');
            };
        });
    }
    
    /**
     * Inicializa o armazenamento com localStorage (fallback)
     * @returns {Promise} Promessa resolvida quando localStorage estiver pronto
     * @private
     */
    initLocalStorage() {
        return new Promise((resolve) => {
            try {
                // Verificar e inicializar o metadados do cache
                if (!localStorage.getItem('modelCache_metadata')) {
                    localStorage.setItem('modelCache_metadata', JSON.stringify({
                        version: this.options.version,
                        lastCleaned: Date.now()
                    }));
                }
                
                // Inicializar índice de modelos se não existir
                if (!localStorage.getItem('modelCache_index')) {
                    localStorage.setItem('modelCache_index', JSON.stringify({}));
                }
                
                this.log('LocalStorage inicializado como fallback.');
                this.ready = true;
                this.callReadyCallbacks();
                resolve(this);
            } catch (e) {
                this.log('Erro ao inicializar localStorage:', e);
                this.ready = false;
                resolve(this);
            }
        });
    }
    
    /**
     * Verifica se o navegador suporta IndexedDB
     * @returns {boolean} Verdadeiro se IndexedDB for suportado
     * @private
     */
    checkIndexedDBSupport() {
        const indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
        return !!indexedDB;
    }
    
    /**
     * Verifica se o navegador suporta localStorage
     * @returns {boolean} Verdadeiro se localStorage for suportado
     * @private
     */
    checkLocalStorageSupport() {
        try {
            const testKey = 'modelCache_test';
            localStorage.setItem(testKey, testKey);
            localStorage.removeItem(testKey);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    /**
     * Executa callbacks quando o gerenciador estiver pronto
     * @param {Function} callback - Função a ser executada
     * @returns {Promise} Promessa resolvida quando o callback for executado
     */
    onReady(callback) {
        if (typeof callback !== 'function') {
            return Promise.resolve();
        }
        
        if (this.ready) {
            callback(this);
            return Promise.resolve();
        }
        
        return new Promise((resolve) => {
            this.readyCallbacks.push(() => {
                callback(this);
                resolve();
            });
        });
    }
    
    /**
     * Executa todos os callbacks registrados
     * @private
     */
    callReadyCallbacks() {
        while (this.readyCallbacks.length > 0) {
            const callback = this.readyCallbacks.shift();
            try {
                callback();
            } catch (e) {
                this.log('Erro ao executar callback:', e);
            }
        }
    }
    
    /**
     * Adiciona um modelo ao cache
     * @param {Object} modelData - Dados do modelo para armazenar
     * @returns {Promise} Promessa resolvida quando o modelo for adicionado
     */
    addModel(modelData) {
        if (!this.ready) {
            return this.onReady(() => this.addModel(modelData));
        }
        
        // Validar dados do modelo
        if (!modelData || !modelData.id || !modelData.data) {
            return Promise.reject(new Error('Dados do modelo inválidos'));
        }
        
        // Adicionar campos de metadados
        const timestamp = Date.now();
        const model = Object.assign({}, modelData, {
            timestamp: timestamp,
            lastAccessed: timestamp,
            size: this.getDataSize(modelData.data)
        });
        
        // Verificar espaço
        return this.checkSpace(model.size)
            .then(() => {
                // Armazenar o modelo
                if (this.indexedDBSupported) {
                    return this.addModelToIndexedDB(model);
                } else if (this.localStorageSupported) {
                    return this.addModelToLocalStorage(model);
                } else {
                    throw new Error('Nenhum mecanismo de armazenamento disponível');
                }
            });
    }
    
    /**
     * Adiciona um modelo ao IndexedDB
     * @param {Object} model - Dados completos do modelo
     * @returns {Promise} Promessa resolvida quando o modelo for adicionado
     * @private
     */
    addModelToIndexedDB(model) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.options.storeName], 'readwrite');
            
            transaction.onerror = (event) => {
                this.log('Erro na transação:', event.target.error);
                reject(event.target.error);
            };
            
            const store = transaction.objectStore(this.options.storeName);
            const request = store.put(model);
            
            request.onsuccess = () => {
                this.log(`Modelo ${model.id} adicionado ao cache IndexedDB.`);
                resolve(model.id);
            };
            
            request.onerror = (event) => {
                this.log('Erro ao adicionar modelo:', event.target.error);
                reject(event.target.error);
            };
        });
    }
    
    /**
     * Adiciona um modelo ao localStorage
     * @param {Object} model - Dados completos do modelo
     * @returns {Promise} Promessa resolvida quando o modelo for adicionado
     * @private
     */
    addModelToLocalStorage(model) {
        return new Promise((resolve, reject) => {
            try {
                // Atualizar índice
                const index = JSON.parse(localStorage.getItem('modelCache_index') || '{}');
                
                // Adicionar informações ao índice
                index[model.id] = {
                    id: model.id,
                    timestamp: model.timestamp,
                    lastAccessed: model.lastAccessed,
                    size: model.size,
                    modelType: model.modelType || 'unknown'
                };
                
                // Salvar índice
                localStorage.setItem('modelCache_index', JSON.stringify(index));
                
                // Armazenar dados do modelo separadamente
                // Converter para string para armazenamento
                let modelContent;
                if (typeof model.data === 'string') {
                    modelContent = model.data;
                } else if (model.data instanceof ArrayBuffer) {
                    modelContent = this.arrayBufferToBase64(model.data);
                } else {
                    modelContent = JSON.stringify(model.data);
                }
                
                localStorage.setItem(`modelCache_${model.id}`, modelContent);
                
                this.log(`Modelo ${model.id} adicionado ao cache localStorage.`);
                resolve(model.id);
            } catch (e) {
                this.log('Erro ao adicionar modelo ao localStorage:', e);
                reject(e);
            }
        });
    }
    
    /**
     * Obtém um modelo do cache
     * @param {string} modelId - Identificador do modelo
     * @returns {Promise} Promessa resolvida com os dados do modelo
     */
    getModel(modelId) {
        if (!this.ready) {
            return this.onReady(() => this.getModel(modelId));
        }
        
        if (!modelId) {
            return Promise.reject(new Error('ID do modelo é obrigatório'));
        }
        
        // Obter o modelo do cache apropriado
        let modelPromise;
        if (this.indexedDBSupported) {
            modelPromise = this.getModelFromIndexedDB(modelId);
        } else if (this.localStorageSupported) {
            modelPromise = this.getModelFromLocalStorage(modelId);
        } else {
            modelPromise = Promise.reject(new Error('Nenhum mecanismo de armazenamento disponível'));
        }
        
        // Atualizar último acesso
        return modelPromise.then(model => {
            if (model) {
                this.updateLastAccessed(modelId);
            }
            return model;
        });
    }
    
    /**
     * Obtém um modelo do IndexedDB
     * @param {string} modelId - Identificador do modelo
     * @returns {Promise} Promessa resolvida com os dados do modelo
     * @private
     */
    getModelFromIndexedDB(modelId) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.options.storeName], 'readonly');
            const store = transaction.objectStore(this.options.storeName);
            const request = store.get(modelId);
            
            request.onsuccess = (event) => {
                const model = event.target.result;
                if (!model) {
                    this.log(`Modelo ${modelId} não encontrado no cache.`);
                    resolve(null);
                    return;
                }
                
                // Verificar expiração
                if (this.isExpired(model)) {
                    this.log(`Modelo ${modelId} expirado, removendo do cache.`);
                    this.removeModel(modelId).catch(e => this.log('Erro ao remover modelo expirado:', e));
                    resolve(null);
                    return;
                }
                
                this.log(`Modelo ${modelId} recuperado do cache.`);
                resolve(model);
            };
            
            request.onerror = (event) => {
                this.log('Erro ao obter modelo:', event.target.error);
                reject(event.target.error);
            };
        });
    }
    
    /**
     * Obtém um modelo do localStorage
     * @param {string} modelId - Identificador do modelo
     * @returns {Promise} Promessa resolvida com os dados do modelo
     * @private
     */
    getModelFromLocalStorage(modelId) {
        return new Promise((resolve, reject) => {
            try {
                // Obter índice
                const index = JSON.parse(localStorage.getItem('modelCache_index') || '{}');
                const metadata = index[modelId];
                
                if (!metadata) {
                    this.log(`Modelo ${modelId} não encontrado no localStorage.`);
                    resolve(null);
                    return;
                }
                
                // Verificar expiração
                if (this.isExpired(metadata)) {
                    this.log(`Modelo ${modelId} expirado, removendo do cache.`);
                    this.removeModel(modelId).catch(e => this.log('Erro ao remover modelo expirado:', e));
                    resolve(null);
                    return;
                }
                
                // Obter dados do modelo
                const modelContent = localStorage.getItem(`modelCache_${modelId}`);
                if (!modelContent) {
                    this.log(`Dados do modelo ${modelId} não encontrados, removendo do índice.`);
                    delete index[modelId];
                    localStorage.setItem('modelCache_index', JSON.stringify(index));
                    resolve(null);
                    return;
                }
                
                // Reconstruir objeto completo
                const model = Object.assign({}, metadata, {
                    data: modelContent
                });
                
                this.log(`Modelo ${modelId} recuperado do localStorage.`);
                resolve(model);
            } catch (e) {
                this.log('Erro ao obter modelo do localStorage:', e);
                reject(e);
            }
        });
    }
    
    /**
     * Verifica se um modelo está expirado
     * @param {Object} model - Dados do modelo
     * @returns {boolean} Verdadeiro se o modelo estiver expirado
     * @private
     */
    isExpired(model) {
        if (!model || !model.timestamp) {
            return true;
        }
        
        const now = Date.now();
        const age = now - model.timestamp;
        
        return age > this.options.expirationTime;
    }
    
    /**
     * Atualiza timestamp de último acesso de um modelo
     * @param {string} modelId - Identificador do modelo
     * @returns {Promise} Promessa resolvida quando a atualização for concluída
     * @private
     */
    updateLastAccessed(modelId) {
        const now = Date.now();
        
        if (this.indexedDBSupported) {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([this.options.storeName], 'readwrite');
                const store = transaction.objectStore(this.options.storeName);
                
                // Primeiro obter o modelo
                const getRequest = store.get(modelId);
                
                getRequest.onsuccess = (event) => {
                    const model = event.target.result;
                    if (!model) {
                        resolve();
                        return;
                    }
                    
                    // Atualizar timestamp de último acesso
                    model.lastAccessed = now;
                    
                    // Salvar de volta
                    const putRequest = store.put(model);
                    
                    putRequest.onsuccess = () => {
                        resolve();
                    };
                    
                    putRequest.onerror = (event) => {
                        this.log('Erro ao atualizar último acesso:', event.target.error);
                        reject(event.target.error);
                    };
                };
                
                getRequest.onerror = (event) => {
                    this.log('Erro ao obter modelo para atualização:', event.target.error);
                    reject(event.target.error);
                };
            });
        } else if (this.localStorageSupported) {
            return new Promise((resolve, reject) => {
                try {
                    // Atualizar índice
                    const index = JSON.parse(localStorage.getItem('modelCache_index') || '{}');
                    
                    if (index[modelId]) {
                        index[modelId].lastAccessed = now;
                        localStorage.setItem('modelCache_index', JSON.stringify(index));
                    }
                    
                    resolve();
                } catch (e) {
                    this.log('Erro ao atualizar último acesso no localStorage:', e);
                    reject(e);
                }
            });
        }
        
        return Promise.resolve();
    }
    
    /**
     * Remove um modelo do cache
     * @param {string} modelId - Identificador do modelo
     * @returns {Promise} Promessa resolvida quando o modelo for removido
     */
    removeModel(modelId) {
        if (!this.ready) {
            return this.onReady(() => this.removeModel(modelId));
        }
        
        if (!modelId) {
            return Promise.reject(new Error('ID do modelo é obrigatório'));
        }
        
        if (this.indexedDBSupported) {
            return this.removeModelFromIndexedDB(modelId);
        } else if (this.localStorageSupported) {
            return this.removeModelFromLocalStorage(modelId);
        }
        
        return Promise.resolve();
    }
    
    /**
     * Remove um modelo do IndexedDB
     * @param {string} modelId - Identificador do modelo
     * @returns {Promise} Promessa resolvida quando o modelo for removido
     * @private
     */
    removeModelFromIndexedDB(modelId) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.options.storeName], 'readwrite');
            const store = transaction.objectStore(this.options.storeName);
            const request = store.delete(modelId);
            
            request.onsuccess = () => {
                this.log(`Modelo ${modelId} removido do cache IndexedDB.`);
                resolve();
            };
            
            request.onerror = (event) => {
                this.log('Erro ao remover modelo:', event.target.error);
                reject(event.target.error);
            };
        });
    }
    
    /**
     * Remove um modelo do localStorage
     * @param {string} modelId - Identificador do modelo
     * @returns {Promise} Promessa resolvida quando o modelo for removido
     * @private
     */
    removeModelFromLocalStorage(modelId) {
        return new Promise((resolve, reject) => {
            try {
                // Remover do índice
                const index = JSON.parse(localStorage.getItem('modelCache_index') || '{}');
                
                if (index[modelId]) {
                    delete index[modelId];
                    localStorage.setItem('modelCache_index', JSON.stringify(index));
                }
                
                // Remover dados do modelo
                localStorage.removeItem(`modelCache_${modelId}`);
                
                this.log(`Modelo ${modelId} removido do localStorage.`);
                resolve();
            } catch (e) {
                this.log('Erro ao remover modelo do localStorage:', e);
                reject(e);
            }
        });
    }
    
    /**
     * Limpa o cache, removendo modelos antigos ou pouco acessados
     * @param {number} targetSize - Tamanho alvo para o cache após limpeza (em bytes)
     * @returns {Promise} Promessa resolvida quando a limpeza for concluída
     */
    cleanCache(targetSize = null) {
        if (!this.ready) {
            return this.onReady(() => this.cleanCache(targetSize));
        }
        
        // Definir tamanho alvo se não especificado
        targetSize = targetSize || (this.options.maxCacheSize * 0.7); // 70% do máximo
        
        if (this.indexedDBSupported) {
            return this.cleanIndexedDBCache(targetSize);
        } else if (this.localStorageSupported) {
            return this.cleanLocalStorageCache(targetSize);
        }
        
        return Promise.resolve();
    }
    
    /**
     * Limpa o cache do IndexedDB
     * @param {number} targetSize - Tamanho alvo para o cache
     * @returns {Promise} Promessa resolvida quando a limpeza for concluída
     * @private
     */
    cleanIndexedDBCache(targetSize) {
        return new Promise((resolve, reject) => {
            // Obter todos os modelos
            this.getAllModels()
                .then(models => {
                    // Calcular tamanho atual
                    let currentSize = models.reduce((sum, model) => sum + (model.size || 0), 0);
                    
                    // Se já estiver abaixo do tamanho alvo, não precisa limpar
                    if (currentSize <= targetSize) {
                        this.log(`Cache já está dentro do limite (${currentSize} bytes).`);
                        resolve();
                        return;
                    }
                    
                    // Ordenar por último acesso (mais antigos primeiro)
                    models.sort((a, b) => (a.lastAccessed || 0) - (b.lastAccessed || 0));
                    
                    // Remover modelos antigos até atingir o tamanho alvo
                    const removePromises = [];
                    let removed = 0;
                    
                    for (const model of models) {
                        if (currentSize <= targetSize) {
                            break;
                        }
                        
                        currentSize -= (model.size || 0);
                        removePromises.push(this.removeModel(model.id));
                        removed++;
                    }
                    
                    return Promise.all(removePromises)
                        .then(() => {
                            this.log(`Limpeza concluída. ${removed} modelo(s) removido(s).`);
                            resolve();
                        });
                })
                .catch(error => {
                    this.log('Erro durante limpeza do cache:', error);
                    reject(error);
                });
        });
    }
    
    /**
     * Limpa o cache do localStorage
     * @param {number} targetSize - Tamanho alvo para o cache
     * @returns {Promise} Promessa resolvida quando a limpeza for concluída
     * @private
     */
    cleanLocalStorageCache(targetSize) {
        return new Promise((resolve, reject) => {
            try {
                // Obter índice
                const index = JSON.parse(localStorage.getItem('modelCache_index') || '{}');
                
                // Criar array de modelos a partir do índice
                const models = Object.values(index);
                
                // Calcular tamanho atual
                let currentSize = models.reduce((sum, model) => sum + (model.size || 0), 0);
                
                // Se já estiver abaixo do tamanho alvo, não precisa limpar
                if (currentSize <= targetSize) {
                    this.log(`Cache já está dentro do limite (${currentSize} bytes).`);
                    resolve();
                    return;
                }
                
                // Ordenar por último acesso (mais antigos primeiro)
                models.sort((a, b) => (a.lastAccessed || 0) - (b.lastAccessed || 0));
                
                // Remover modelos antigos até atingir o tamanho alvo
                let removed = 0;
                
                for (const model of models) {
                    if (currentSize <= targetSize) {
                        break;
                    }
                    
                    // Remover do índice
                    delete index[model.id];
                    
                    // Remover dados do modelo
                    localStorage.removeItem(`modelCache_${model.id}`);
                    
                    currentSize -= (model.size || 0);
                    removed++;
                }
                
                // Salvar índice atualizado
                localStorage.setItem('modelCache_index', JSON.stringify(index));
                
                // Atualizar metadados de limpeza
                const metadata = JSON.parse(localStorage.getItem('modelCache_metadata') || '{}');
                metadata.lastCleaned = Date.now();
                localStorage.setItem('modelCache_metadata', JSON.stringify(metadata));
                
                this.log(`Limpeza concluída. ${removed} modelo(s) removido(s).`);
                resolve();
            } catch (e) {
                this.log('Erro durante limpeza do localStorage:', e);
                reject(e);
            }
        });
    }
    
    /**
     * Obtém todos os modelos no cache
     * @returns {Promise} Promessa resolvida com array de modelos
     * @private
     */
    getAllModels() {
        if (!this.ready) {
            return this.onReady(() => this.getAllModels());
        }
        
        if (this.indexedDBSupported) {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([this.options.storeName], 'readonly');
                const store = transaction.objectStore(this.options.storeName);
                const request = store.getAll();
                
                request.onsuccess = (event) => {
                    resolve(event.target.result || []);
                };
                
                request.onerror = (event) => {
                    this.log('Erro ao obter modelos:', event.target.error);
                    reject(event.target.error);
                };
            });
        } else if (this.localStorageSupported) {
            return new Promise((resolve) => {
                const index = JSON.parse(localStorage.getItem('modelCache_index') || '{}');
                const models = Object.values(index);
                resolve(models);
            });
        }
        
        return Promise.resolve([]);
    }
    
    /**
     * Verifica se há espaço disponível para armazenar um novo modelo
     * @param {number} size - Tamanho do modelo em bytes
     * @returns {Promise} Promessa resolvida se houver espaço suficiente
     * @private
     */
    checkSpace(size) {
        return new Promise((resolve, reject) => {
            // Se o tamanho não for especificado, assumir como OK
            if (!size) {
                resolve();
                return;
            }
            
            // Verificar se o modelo é maior que o cache máximo permitido
            if (size > this.options.maxCacheSize) {
                reject(new Error(`Modelo muito grande (${size} bytes) para o tamanho máximo de cache (${this.options.maxCacheSize} bytes)`));
                return;
            }
            
            // Obter tamanho e contagem atuais
            this.getCacheStats()
                .then(stats => {
                    // Verificar número de entradas
                    if (stats.count >= this.options.maxEntries) {
                        // Limpar para reduzir entradas
                        return this.cleanCache()
                            .then(() => {
                                // Verificar novamente após limpeza
                                return this.getCacheStats();
                            });
                    }
                    
                    return stats;
                })
                .then(stats => {
                    // Verificar se há espaço suficiente
                    const projectedSize = stats.size + size;
                    
                    if (projectedSize > this.options.maxCacheSize) {
                        // Limpar para liberar espaço
                        return this.cleanCache(this.options.maxCacheSize - size)
                            .then(() => {
                                // Agora deve haver espaço suficiente
                                resolve();
                            });
                    } else {
                        // Já há espaço suficiente
                        resolve();
                    }
                })
                .catch(error => {
                    this.log('Erro ao verificar espaço:', error);
                    
                    // Em caso de erro, permitir o armazenamento (melhor que falhar)
                    resolve();
                });
        });
    }
    
    /**
     * Obtém estatísticas sobre o cache
     * @returns {Promise} Promessa resolvida com estatísticas
     */
    getCacheStats() {
        if (!this.ready) {
            return this.onReady(() => this.getCacheStats());
        }
        
        return this.getAllModels()
            .then(models => {
                const stats = {
                    count: models.length,
                    size: models.reduce((sum, model) => sum + (model.size || 0), 0),
                    oldestTimestamp: models.length > 0 ? Math.min(...models.map(m => m.timestamp || 0)) : 0,
                    newestTimestamp: models.length > 0 ? Math.max(...models.map(m => m.timestamp || 0)) : 0,
                    modelTypes: {}
                };
                
                // Contar tipos de modelo
                models.forEach(model => {
                    const type = model.modelType || 'unknown';
                    stats.modelTypes[type] = (stats.modelTypes[type] || 0) + 1;
                });
                
                return stats;
            });
    }
    
    /**
     * Limpa todo o cache
     * @returns {Promise} Promessa resolvida quando todo o cache for limpo
     */
    clearCache() {
        if (!this.ready) {
            return this.onReady(() => this.clearCache());
        }
        
        if (this.indexedDBSupported) {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([this.options.storeName], 'readwrite');
                const store = transaction.objectStore(this.options.storeName);
                const request = store.clear();
                
                request.onsuccess = () => {
                    this.log('Cache limpo completamente.');
                    resolve();
                };
                
                request.onerror = (event) => {
                    this.log('Erro ao limpar cache:', event.target.error);
                    reject(event.target.error);
                };
            });
        } else if (this.localStorageSupported) {
            return new Promise((resolve) => {
                try {
                    // Obter índice atual
                    const index = JSON.parse(localStorage.getItem('modelCache_index') || '{}');
                    
                    // Remover todos os itens de modelo
                    Object.keys(index).forEach(modelId => {
                        localStorage.removeItem(`modelCache_${modelId}`);
                    });
                    
                    // Limpar índice
                    localStorage.setItem('modelCache_index', '{}');
                    
                    // Atualizar metadados
                    const metadata = JSON.parse(localStorage.getItem('modelCache_metadata') || '{}');
                    metadata.lastCleaned = Date.now();
                    localStorage.setItem('modelCache_metadata', JSON.stringify(metadata));
                    
                    this.log('Cache localStorage limpo completamente.');
                    resolve();
                } catch (e) {
                    this.log('Erro ao limpar cache localStorage:', e);
                    resolve();
                }
            });
        }
        
        return Promise.resolve();
    }
    
    /**
     * Calcula o tamanho aproximado dos dados
     * @param {any} data - Dados a serem medidos
     * @returns {number} Tamanho em bytes
     * @private
     */
    getDataSize(data) {
        if (!data) {
            return 0;
        }
        
        if (data instanceof ArrayBuffer) {
            return data.byteLength;
        }
        
        if (typeof data === 'string') {
            // Aproximação grosseira: 2 bytes por caractere
            return data.length * 2;
        }
        
        // Para objetos, converter para string e medir
        try {
            const str = JSON.stringify(data);
            return str.length * 2;
        } catch (e) {
            // Se não puder ser convertido para string, usar tamanho fixo
            return 1024; // 1KB como estimativa
        }
    }
    
    /**
     * Converte ArrayBuffer para string Base64
     * @param {ArrayBuffer} buffer - Buffer a ser convertido
     * @returns {string} String Base64
     * @private
     */
    arrayBufferToBase64(buffer) {
        const binary = [];
        const bytes = new Uint8Array(buffer);
        
        for (let i = 0; i < bytes.byteLength; i++) {
            binary.push(String.fromCharCode(bytes[i]));
        }
        
        return window.btoa(binary.join(''));
    }
    
    /**
     * Converte string Base64 para ArrayBuffer
     * @param {string} base64 - String Base64
     * @returns {ArrayBuffer} Buffer convertido
     * @private
     */
    base64ToArrayBuffer(base64) {
        const binaryString = window.atob(base64);
        const bytes = new Uint8Array(binaryString.length);
        
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        
        return bytes.buffer;
    }
    
    /**
     * Função de log para depuração
     * @private
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[ModelCacheManager]', ...args);
        }
    }
    
    /**
     * Verifica se um modelo está no cache
     * @param {string} modelId - Identificador do modelo
     * @returns {Promise<boolean>} Promessa resolvida com verdadeiro se o modelo estiver em cache
     */
    hasModel(modelId) {
        return this.getModel(modelId).then(model => !!model);
    }
    
    /**
     * Obtém a URL do modelo com parâmetros de cache
     * @param {string} modelUrl - URL original do modelo
     * @param {string} modelId - Identificador do modelo para cache
     * @returns {string} URL com parâmetros de cache
     */
    getUrlWithCacheParams(modelUrl, modelId) {
        // Adicionar parâmetros para invalidação de cache e tracking
        const url = new URL(modelUrl, window.location.href);
        url.searchParams.set('v', this.options.version);
        url.searchParams.set('id', modelId);
        url.searchParams.set('t', Date.now());
        
        return url.toString();
    }
}

// Exportar para uso como módulo
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModelCacheManager;
}
