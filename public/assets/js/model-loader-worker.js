/**
 * model-loader-worker.js
 * 
 * Web Worker para processamento assíncrono de modelos 3D
 * Permite carregar e processar modelos sem bloquear a thread principal
 */

// Importações necessárias para o worker
importScripts('https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js');
importScripts('https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.min.js');
importScripts('https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/OBJLoader.min.js');
importScripts('https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/MTLLoader.min.js');
importScripts('https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/modifiers/SimplifyModifier.js');

// Receber mensagens da thread principal
self.addEventListener('message', function(e) {
    const data = e.data;
    
    // Verificar o tipo de mensagem
    if (data.type === 'loadModel') {
        // Iniciar carregamento do modelo
        loadModel(data.filePath, data.fileType, data.options);
    } else if (data.type === 'simplifyGeometry') {
        // Simplificar geometria
        simplifyGeometry(data.geometry, data.targetPercentage);
    }
});

/**
 * Carrega um modelo 3D de forma assíncrona
 * 
 * @param {string} filePath - Caminho para o arquivo do modelo
 * @param {string} fileType - Tipo de arquivo (stl ou obj)
 * @param {Object} options - Opções adicionais para o carregamento
 */
function loadModel(filePath, fileType, options) {
    // Enviar mensagem de início de carregamento
    self.postMessage({
        type: 'loadingStarted'
    });
    
    if (fileType === 'stl') {
        loadSTLModel(filePath, options);
    } else if (fileType === 'obj') {
        loadOBJModel(filePath, options);
    } else {
        // Enviar erro para a thread principal
        self.postMessage({
            type: 'error',
            error: 'Tipo de arquivo não suportado: ' + fileType
        });
    }
}

/**
 * Carrega um modelo STL
 * 
 * @param {string} filePath - Caminho para o arquivo STL
 * @param {Object} options - Opções de carregamento
 */
function loadSTLModel(filePath, options) {
    // Criar loader para STL
    const loader = new THREE.STLLoader();
    
    // Configurar requisição XHR para monitorar progresso
    const xhr = new XMLHttpRequest();
    xhr.onprogress = function(event) {
        if (event.lengthComputable) {
            const percentage = Math.round((event.loaded / event.total) * 100);
            self.postMessage({
                type: 'loadingProgress',
                progress: percentage
            });
        }
    };
    
    // Carregar o modelo
    loader.load(
        filePath,
        function(geometry) {
            // Processar geometria
            processSTLGeometry(geometry, options);
        },
        function(xhr) {
            // Progresso
            if (xhr.lengthComputable) {
                const percentage = Math.round((xhr.loaded / xhr.total) * 100);
                self.postMessage({
                    type: 'loadingProgress',
                    progress: percentage
                });
            }
        },
        function(error) {
            // Enviar erro para a thread principal
            self.postMessage({
                type: 'error',
                error: 'Erro ao carregar modelo STL: ' + error
            });
        }
    );
}

/**
 * Processa a geometria de um modelo STL carregado
 * 
 * @param {THREE.BufferGeometry} geometry - Geometria original
 * @param {Object} options - Opções de processamento
 */
function processSTLGeometry(geometry, options) {
    // Calcular informações básicas da geometria
    geometry.computeBoundingBox();
    const box = geometry.boundingBox;
    
    // Preparar diferentes níveis de detalhe (LOD)
    const lodLevels = prepareLODLevels(geometry, options);
    
    // Preparar meta-dados
    const metadata = {
        boundingBox: {
            min: [box.min.x, box.min.y, box.min.z],
            max: [box.max.x, box.max.y, box.max.z]
        },
        vertexCount: geometry.attributes.position.count,
        format: 'stl'
    };
    
    // Enviar geometria processada para a thread principal
    self.postMessage({
        type: 'modelLoaded',
        lodLevels: lodLevels,
        metadata: metadata
    });
}

/**
 * Carrega um modelo OBJ
 * 
 * @param {string} filePath - Caminho para o arquivo OBJ
 * @param {Object} options - Opções de carregamento
 */
function loadOBJModel(filePath, options) {
    // Criar loader para OBJ
    const loader = new THREE.OBJLoader();
    
    // Tentar carregar arquivo MTL correspondente
    const mtlPath = filePath.replace('.obj', '.mtl');
    
    // Verificar a existência do arquivo MTL
    const mtlXhr = new XMLHttpRequest();
    mtlXhr.open('HEAD', mtlPath, true);
    mtlXhr.onreadystatechange = function() {
        if (mtlXhr.readyState === 4) {
            if (mtlXhr.status === 200) {
                // MTL existe, carregá-lo primeiro
                const mtlLoader = new THREE.MTLLoader();
                mtlLoader.load(
                    mtlPath,
                    function(materials) {
                        materials.preload();
                        loader.setMaterials(materials);
                        loadOBJFile(loader, filePath, options);
                    },
                    null,
                    function() {
                        // Erro ao carregar MTL, continuar sem materiais
                        loadOBJFile(loader, filePath, options);
                    }
                );
            } else {
                // MTL não existe, carregar OBJ diretamente
                loadOBJFile(loader, filePath, options);
            }
        }
    };
    mtlXhr.send();
}

/**
 * Carrega um arquivo OBJ usando o loader fornecido
 * 
 * @param {THREE.OBJLoader} loader - Loader OBJ configurado
 * @param {string} filePath - Caminho para o arquivo OBJ
 * @param {Object} options - Opções de carregamento
 */
function loadOBJFile(loader, filePath, options) {
    // Configurar requisição XHR para monitorar progresso
    const xhr = new XMLHttpRequest();
    xhr.onprogress = function(event) {
        if (event.lengthComputable) {
            const percentage = Math.round((event.loaded / event.total) * 100);
            self.postMessage({
                type: 'loadingProgress',
                progress: percentage
            });
        }
    };
    
    // Carregar o modelo
    loader.load(
        filePath,
        function(object) {
            // Processar objeto OBJ
            processOBJObject(object, options);
        },
        function(xhr) {
            // Progresso
            if (xhr.lengthComputable) {
                const percentage = Math.round((xhr.loaded / xhr.total) * 100);
                self.postMessage({
                    type: 'loadingProgress',
                    progress: percentage
                });
            }
        },
        function(error) {
            // Enviar erro para a thread principal
            self.postMessage({
                type: 'error',
                error: 'Erro ao carregar modelo OBJ: ' + error
            });
        }
    );
}

/**
 * Processa um objeto OBJ carregado
 * 
 * @param {THREE.Object3D} object - Objeto OBJ carregado
 * @param {Object} options - Opções de processamento
 */
function processOBJObject(object, options) {
    // Extrair geometrias e materiais
    const geometries = [];
    const materials = [];
    
    // Percorrer o objeto para encontrar malhas
    object.traverse(function(child) {
        if (child instanceof THREE.Mesh) {
            geometries.push({
                geometry: child.geometry,
                name: child.name || 'mesh-' + geometries.length
            });
            
            // Extrair informações do material (se existir)
            if (child.material) {
                const materialData = {
                    name: child.material.name || 'material-' + materials.length,
                    color: child.material.color ? 
                        [child.material.color.r, child.material.color.g, child.material.color.b] : 
                        [1, 1, 1]
                };
                materials.push(materialData);
            }
        }
    });
    
    // Calcular bounding box do objeto completo
    const box = new THREE.Box3().setFromObject(object);
    
    // Preparar diferentes níveis de detalhe (LOD) para cada geometria
    const lodModels = [];
    for (let i = 0; i < geometries.length; i++) {
        const lodLevels = prepareLODLevels(geometries[i].geometry, options);
        lodModels.push({
            name: geometries[i].name,
            lodLevels: lodLevels,
            materialIndex: i < materials.length ? i : -1
        });
    }
    
    // Preparar meta-dados
    const metadata = {
        boundingBox: {
            min: [box.min.x, box.min.y, box.min.z],
            max: [box.max.x, box.max.y, box.max.z]
        },
        format: 'obj',
        materials: materials
    };
    
    // Enviar dados processados para a thread principal
    self.postMessage({
        type: 'objModelLoaded',
        models: lodModels,
        metadata: metadata
    });
}

/**
 * Prepara diferentes níveis de detalhe (LOD) para uma geometria
 * 
 * @param {THREE.BufferGeometry} geometry - Geometria original
 * @param {Object} options - Opções de processamento
 * @returns {Array} Array com diferentes níveis de detalhe
 */
function prepareLODLevels(geometry, options) {
    // Verificar se a geometria é complexa o suficiente para justificar LOD
    const vertexCount = geometry.attributes.position.count;
    const isComplex = vertexCount > 10000;
    
    // Se não for complexa, apenas retornar a geometria original
    if (!isComplex) {
        return [{
            level: 'high',
            vertexCount: vertexCount,
            vertices: serializeGeometry(geometry)
        }];
    }
    
    // Definir níveis de LOD baseados na complexidade do modelo
    const lodLevels = [];
    
    // Nível original (alta qualidade)
    lodLevels.push({
        level: 'high',
        vertexCount: vertexCount,
        vertices: serializeGeometry(geometry)
    });
    
    // Usar SimplifyModifier para criar versões simplificadas
    try {
        // Nível médio (~50% do original)
        if (vertexCount > 20000) {
            const mediumGeometry = simplifyGeometry(geometry.clone(), 0.5);
            lodLevels.push({
                level: 'medium',
                vertexCount: mediumGeometry.attributes.position.count,
                vertices: serializeGeometry(mediumGeometry)
            });
        }
        
        // Nível baixo (~25% do original)
        if (vertexCount > 50000) {
            const lowGeometry = simplifyGeometry(geometry.clone(), 0.25);
            lodLevels.push({
                level: 'low',
                vertexCount: lowGeometry.attributes.position.count,
                vertices: serializeGeometry(lowGeometry)
            });
        }
        
        // Nível muito baixo (~10% do original)
        if (vertexCount > 100000) {
            const veryLowGeometry = simplifyGeometry(geometry.clone(), 0.1);
            lodLevels.push({
                level: 'veryLow',
                vertexCount: veryLowGeometry.attributes.position.count,
                vertices: serializeGeometry(veryLowGeometry)
            });
        }
    } catch (error) {
        // Em caso de erro na simplificação, apenas usar a geometria original
        console.error('Erro ao criar LODs:', error);
    }
    
    return lodLevels;
}

/**
 * Simplifica uma geometria usando SimplifyModifier
 * 
 * @param {THREE.BufferGeometry} geometry - Geometria a ser simplificada
 * @param {number} targetRatio - Razão alvo de vértices (0-1)
 * @returns {THREE.BufferGeometry} Geometria simplificada
 */
function simplifyGeometry(geometry, targetRatio) {
    // Usar SimplifyModifier para simplificação de alta qualidade
    try {
        const modifier = new THREE.SimplifyModifier();
        const count = geometry.attributes.position.count;
        const targetCount = Math.max(4, Math.floor(count * targetRatio));
        const reduction = count - targetCount;
        
        // Aplicar simplificação
        const simplified = modifier.modify(geometry, reduction);
        
        // Recalcular normais
        simplified.computeVertexNormals();
        
        return simplified;
    } catch (error) {
        // Fallback para simplificação básica se SimplifyModifier falhar
        console.error('Erro no SimplifyModifier:', error);
        return fallbackSimplifyGeometry(geometry, targetRatio);
    }
}

/**
 * Método de simplificação básica como fallback
 * 
 * @param {THREE.BufferGeometry} geometry - Geometria a ser simplificada
 * @param {number} targetRatio - Razão alvo de vértices (0-1)
 * @returns {THREE.BufferGeometry} Geometria simplificada
 */
function fallbackSimplifyGeometry(geometry, targetRatio) {
    // Clone a geometria
    const simplified = geometry.clone();
    
    // Se não tivermos atributos de posição, não podemos simplificar
    if (!simplified.attributes.position) {
        return simplified;
    }
    
    // Configurar novo array de posições
    const oldPositions = simplified.attributes.position.array;
    const oldCount = oldPositions.length / 3;
    const stride = Math.max(1, Math.round(1 / targetRatio));
    const newCount = Math.ceil(oldCount / stride);
    
    // Criar nova geometria com menos vértices
    const newPositions = new Float32Array(newCount * 3);
    const newNormals = simplified.attributes.normal ? new Float32Array(newCount * 3) : null;
    
    // Copiar apenas alguns vértices (simplificação básica)
    let index = 0;
    for (let i = 0; i < oldCount; i += stride) {
        newPositions[index * 3] = oldPositions[i * 3];
        newPositions[index * 3 + 1] = oldPositions[i * 3 + 1];
        newPositions[index * 3 + 2] = oldPositions[i * 3 + 2];
        
        if (newNormals && simplified.attributes.normal) {
            newNormals[index * 3] = simplified.attributes.normal.array[i * 3];
            newNormals[index * 3 + 1] = simplified.attributes.normal.array[i * 3 + 1];
            newNormals[index * 3 + 2] = simplified.attributes.normal.array[i * 3 + 2];
        }
        
        index++;
    }
    
    // Criar nova geometria
    const result = new THREE.BufferGeometry();
    result.setAttribute('position', new THREE.BufferAttribute(newPositions, 3));
    
    if (newNormals) {
        result.setAttribute('normal', new THREE.BufferAttribute(newNormals, 3));
    } else {
        result.computeVertexNormals();
    }
    
    return result;
}

/**
 * Serializa uma geometria para transferência
 * 
 * @param {THREE.BufferGeometry} geometry - Geometria a ser serializada
 * @returns {Object} Dados serializados
 */
function serializeGeometry(geometry) {
    // Extrair dados da geometria
    const positions = geometry.attributes.position.array;
    const normals = geometry.attributes.normal ? geometry.attributes.normal.array : null;
    
    // Criar objeto serializado
    return {
        positions: Array.from(positions),
        normals: normals ? Array.from(normals) : null,
        indices: geometry.index ? Array.from(geometry.index.array) : null
    };
}
