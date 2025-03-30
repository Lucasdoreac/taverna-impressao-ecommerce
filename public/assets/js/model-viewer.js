/**
 * ModelViewer - Classe para visualização 3D de modelos STL/OBJ
 * 
 * Utiliza Three.js para renderizar modelos 3D diretamente no navegador
 * com controles para rotação, zoom e outras interações.
 */
class ModelViewer {
    /**
     * Construtor
     * @param {Object} options - Opções de configuração
     */
    constructor(options) {
        // Configurações padrão
        this.options = Object.assign({
            containerId: '', // ID do elemento container
            filePath: '', // Caminho para o arquivo 3D
            fileType: 'stl', // Tipo de arquivo: 'stl' ou 'obj'
            backgroundColor: '#f8f9fa', // Cor de fundo
            modelColor: '#6c757d', // Cor do modelo
            autoRotate: true, // Rotação automática
            showGrid: true, // Mostrar grade
            showAxes: false, // Mostrar eixos
            showControls: true, // Mostrar controles na UI
            showStats: false, // Mostrar estatísticas de desempenho
            enableZoom: true, // Permitir zoom
            enablePan: true, // Permitir pan
            rotationSpeed: 0.005, // Velocidade de rotação automática
            optimizeForMobile: true, // Otimizar para dispositivos móveis
            progressiveLoading: true, // Carregamento progressivo de modelos grandes
        }, options);
        
        // Validar opções obrigatórias
        if (!this.options.containerId || !this.options.filePath) {
            console.error('ModelViewer: containerId e filePath são obrigatórios');
            return;
        }
        
        // Inicializar propriedades
        this.container = document.getElementById(this.options.containerId);
        if (!this.container) {
            console.error(`ModelViewer: Container com ID '${this.options.containerId}' não encontrado`);
            return;
        }
        
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.object = null;
        this.isLoading = true;
        this.isAutoRotating = this.options.autoRotate;
        
        // Detectar dispositivo móvel
        this.isMobile = this.detectMobileDevice();
        
        // Inicializar o visualizador
        this.init();
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
     * Inicializa o visualizador
     */
    init() {
        // Mostrar carregamento
        this.showLoading();
        
        // Criar cena, câmera, renderer e controles
        this.setupScene();
        this.setupCamera();
        this.setupRenderer();
        this.setupControls();
        this.setupLights();
        
        // Adicionar grade se necessário e não estiver em dispositivo móvel
        if (this.options.showGrid && (!this.isMobile || !this.options.optimizeForMobile)) {
            this.setupGrid();
        }
        
        // Adicionar eixos se necessário e não estiver em dispositivo móvel
        if (this.options.showAxes && (!this.isMobile || !this.options.optimizeForMobile)) {
            this.setupAxes();
        }
        
        // Carregar o modelo
        this.loadModel();
        
        // Adicionar controles à UI se necessário
        if (this.options.showControls) {
            this.setupUIControls();
        }
        
        // Iniciar animação
        this.animate();
        
        // Lidar com redimensionamento da janela
        window.addEventListener('resize', this.onWindowResize.bind(this));
    }
    
    /**
     * Configura a cena
     */
    setupScene() {
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(this.options.backgroundColor);
    }
    
    /**
     * Configura a câmera
     */
    setupCamera() {
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;
        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 2000);
        this.camera.position.set(0, 0, 100);
    }
    
    /**
     * Configura o renderer com base no dispositivo
     */
    setupRenderer() {
        // Ajustar antialias e pixel ratio com base no dispositivo
        const pixelRatio = this.isMobile && this.options.optimizeForMobile ? 
                          Math.min(window.devicePixelRatio, 1.5) : 
                          window.devicePixelRatio;
        
        const useAntialias = !(this.isMobile && this.options.optimizeForMobile);
        
        this.renderer = new THREE.WebGLRenderer({ 
            antialias: useAntialias,
            powerPreference: 'high-performance',
            precision: this.isMobile && this.options.optimizeForMobile ? 'mediump' : 'highp'
        });
        
        this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
        this.renderer.setPixelRatio(pixelRatio);
        
        // Otimizações adicionais para dispositivos móveis
        if (this.isMobile && this.options.optimizeForMobile) {
            this.renderer.shadowMap.enabled = false;
        }
        
        this.container.appendChild(this.renderer.domElement);
    }
    
    /**
     * Configura os controles de orbital
     */
    setupControls() {
        this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.25;
        this.controls.screenSpacePanning = false;
        this.controls.maxPolarAngle = Math.PI;
        this.controls.enableZoom = this.options.enableZoom;
        this.controls.enablePan = this.options.enablePan;
        this.controls.autoRotate = this.isAutoRotating;
        this.controls.autoRotateSpeed = 5.0;
        
        // Reduzir sensibilidade de rotação em dispositivos móveis
        if (this.isMobile && this.options.optimizeForMobile) {
            this.controls.rotateSpeed = 0.7;
            this.controls.zoomSpeed = 0.7;
        }
    }
    
    /**
     * Configura as luzes da cena ajustadas para o tipo de dispositivo
     */
    setupLights() {
        // Simplificar a iluminação em dispositivos móveis
        if (this.isMobile && this.options.optimizeForMobile) {
            // Luz ambiente (mais forte em dispositivos móveis para compensar menos luzes)
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
            this.scene.add(ambientLight);
            
            // Apenas uma luz direcional
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.9);
            directionalLight.position.set(1, 1, 1);
            this.scene.add(directionalLight);
        } else {
            // Luz ambiente
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            this.scene.add(ambientLight);
            
            // Luz direcional 1
            const directionalLight1 = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight1.position.set(1, 1, 1);
            this.scene.add(directionalLight1);
            
            // Luz direcional 2
            const directionalLight2 = new THREE.DirectionalLight(0xffffff, 0.5);
            directionalLight2.position.set(-1, -1, -1);
            this.scene.add(directionalLight2);
            
            // Luz hemisférica para realçar detalhes
            const hemiLight = new THREE.HemisphereLight(0xffffff, 0x444444, 0.4);
            hemiLight.position.set(0, 100, 0);
            this.scene.add(hemiLight);
        }
    }
    
    /**
     * Configura a grade
     */
    setupGrid() {
        // Grade simplificada para dispositivos móveis
        const gridDivisions = this.isMobile && this.options.optimizeForMobile ? 10 : 20;
        const gridHelper = new THREE.GridHelper(100, gridDivisions, 0x888888, 0x444444);
        gridHelper.material.opacity = 0.5;
        gridHelper.material.transparent = true;
        this.scene.add(gridHelper);
    }
    
    /**
     * Configura os eixos
     */
    setupAxes() {
        const axesHelper = new THREE.AxesHelper(50);
        this.scene.add(axesHelper);
    }
    
    /**
     * Exibe a animação de carregamento
     */
    showLoading() {
        const loadingElement = document.createElement('div');
        loadingElement.className = 'model-viewer-loading';
        loadingElement.innerHTML = '<div class="model-viewer-loading-spinner"></div>';
        this.container.appendChild(loadingElement);
        this.loadingElement = loadingElement;
    }
    
    /**
     * Remove a animação de carregamento
     */
    hideLoading() {
        if (this.loadingElement && this.loadingElement.parentNode) {
            this.loadingElement.parentNode.removeChild(this.loadingElement);
        }
        this.isLoading = false;
    }
    
    /**
     * Atualiza o indicador de progresso
     * @param {number} percent - Porcentagem de conclusão
     */
    updateLoadingProgress(percent) {
        if (this.loadingElement) {
            // Adicionar texto de porcentagem ao elemento de carregamento
            const progressText = this.loadingElement.querySelector('.model-viewer-loading-progress');
            if (progressText) {
                progressText.textContent = `${Math.round(percent)}%`;
            } else if (percent > 0) {
                const text = document.createElement('div');
                text.className = 'model-viewer-loading-progress';
                text.textContent = `${Math.round(percent)}%`;
                this.loadingElement.appendChild(text);
            }
        }
    }
    
    /**
     * Carrega o modelo 3D
     */
    loadModel() {
        const filePath = this.options.filePath;
        const fileType = this.options.fileType.toLowerCase();
        
        // Escolher o loader correto com base no tipo de arquivo
        if (fileType === 'stl') {
            this.loadSTLModel(filePath);
        } else if (fileType === 'obj') {
            this.loadOBJModel(filePath);
        } else {
            console.error(`ModelViewer: Tipo de arquivo não suportado: ${fileType}`);
            this.hideLoading();
        }
    }
    
    /**
     * Carrega um modelo STL
     * @param {string} filePath - Caminho para o arquivo STL
     */
    loadSTLModel(filePath) {
        const loader = new THREE.STLLoader();
        
        // Implementar carregamento progressivo se habilitado
        if (this.options.progressiveLoading) {
            const xhr = new XMLHttpRequest();
            xhr.onprogress = (event) => {
                if (event.lengthComputable) {
                    const percent = (event.loaded / event.total) * 100;
                    this.updateLoadingProgress(percent);
                }
            };
            
            loader.load(
                filePath,
                (geometry) => {
                    // Criar material com opções otimizadas para dispositivos móveis
                    const material = this.createOptimizedMaterial();
                    
                    // Simplificar geometria para dispositivos móveis, se necessário
                    if (this.isMobile && this.options.optimizeForMobile && geometry.attributes && geometry.attributes.position) {
                        // Verificar se o modelo é complexo (mais de 100k vértices)
                        const vertexCount = geometry.attributes.position.count;
                        
                        if (vertexCount > 100000) {
                            geometry = this.simplifyGeometry(geometry);
                        }
                    }
                    
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
                },
                (xhr) => {
                    // Progresso de carregamento
                    const percent = (xhr.loaded / xhr.total) * 100;
                    this.updateLoadingProgress(percent);
                },
                (error) => {
                    // Erro de carregamento
                    console.error('ModelViewer: Erro ao carregar modelo STL', error);
                    this.hideLoading();
                }
            );
        } else {
            // Carregamento padrão sem progresso visual
            loader.load(
                filePath,
                (geometry) => {
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
                },
                (xhr) => {
                    // Progresso de carregamento
                    const percent = (xhr.loaded / xhr.total) * 100;
                    console.log(`ModelViewer: ${Math.round(percent)}% carregado`);
                },
                (error) => {
                    // Erro de carregamento
                    console.error('ModelViewer: Erro ao carregar modelo STL', error);
                    this.hideLoading();
                }
            );
        }
    }
    
    /**
     * Cria um material otimizado com base no tipo de dispositivo
     * @returns {THREE.Material} Material otimizado
     */
    createOptimizedMaterial() {
        if (this.isMobile && this.options.optimizeForMobile) {
            // Material mais simples para dispositivos móveis
            return new THREE.MeshLambertMaterial({
                color: this.options.modelColor,
                flatShading: true
            });
        } else {
            // Material padrão de alta qualidade para desktop
            return new THREE.MeshPhongMaterial({
                color: this.options.modelColor,
                specular: 0x111111,
                shininess: 30
            });
        }
    }
    
    /**
     * Simplifica a geometria para melhor desempenho em dispositivos móveis
     * @param {THREE.BufferGeometry} geometry - Geometria original
     * @returns {THREE.BufferGeometry} Geometria simplificada
     */
    simplifyGeometry(geometry) {
        // Nota: Uma implementação completa usaria SimplifyModifier do THREE.js
        // Como é uma simplificação básica, apenas reduzimos o detalhe
        
        console.log('ModelViewer: Simplificando modelo para dispositivo móvel');
        
        // Essa é uma abordagem simples de decimação que pode ser melhorada
        // em implementações futuras com algoritmos mais sofisticados
        const positions = geometry.attributes.position.array;
        const indices = [];
        
        // Reduzir a resolução (preservar 1 a cada N vértices)
        const decimationFactor = 3; // Ajuste conforme necessário
        
        for (let i = 0; i < positions.length / 9; i++) {
            if (i % decimationFactor === 0) {
                const idx = i * 9;
                indices.push(idx, idx + 3, idx + 6);
            }
        }
        
        const simplified = new THREE.BufferGeometry();
        simplified.setAttribute('position', geometry.attributes.position);
        simplified.setIndex(indices);
        simplified.computeVertexNormals();
        
        return simplified;
    }
    
    /**
     * Carrega um modelo OBJ
     * @param {string} filePath - Caminho para o arquivo OBJ
     */
    loadOBJModel(filePath) {
        const loader = new THREE.OBJLoader();
        
        // Verificar se existe um arquivo MTL correspondente
        const mtlPath = filePath.replace('.obj', '.mtl');
        
        // Tentar carregar arquivo MTL primeiro
        const mtlLoader = new THREE.MTLLoader();
        mtlLoader.load(
            mtlPath,
            (materials) => {
                materials.preload();
                
                // Aplicar materiais ao loader OBJ
                loader.setMaterials(materials);
                this.loadOBJWithMaterials(loader, filePath);
            },
            null, // onProgress
            () => {
                // MTL não encontrado ou erro, carregar OBJ com material padrão
                this.loadOBJWithMaterials(loader, filePath);
            }
        );
    }
    
    /**
     * Carrega um modelo OBJ com materiais
     * @param {THREE.OBJLoader} loader - Loader OBJ
     * @param {string} filePath - Caminho para o arquivo OBJ
     */
    loadOBJWithMaterials(loader, filePath) {
        // Implementar carregamento progressivo se habilitado
        if (this.options.progressiveLoading) {
            const xhr = new XMLHttpRequest();
            xhr.onprogress = (event) => {
                if (event.lengthComputable) {
                    const percent = (event.loaded / event.total) * 100;
                    this.updateLoadingProgress(percent);
                }
            };
        }
        
        loader.load(
            filePath,
            (object) => {
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
            },
            (xhr) => {
                // Progresso de carregamento
                if (this.options.progressiveLoading) {
                    const percent = (xhr.loaded / xhr.total) * 100;
                    this.updateLoadingProgress(percent);
                } else {
                    const percent = (xhr.loaded / xhr.total) * 100;
                    console.log(`ModelViewer: ${Math.round(percent)}% carregado`);
                }
            },
            (error) => {
                // Erro de carregamento
                console.error('ModelViewer: Erro ao carregar modelo OBJ', error);
                this.hideLoading();
            }
        );
    }
    
    /**
     * Verifica se o material deve ser substituído por um otimizado
     * @param {THREE.Material} material - Material a ser verificado
     * @returns {boolean} Verdadeiro se deve substituir
     */
    shouldReplaceWithOptimizedMaterial(material) {
        // Substituir materiais complexos por mais simples em dispositivos móveis
        return material instanceof THREE.MeshPhongMaterial || 
               material instanceof THREE.MeshStandardMaterial;
    }
    
    /**
     * Normaliza o tamanho do modelo para caber na visualização
     * @param {THREE.Object3D} object - Objeto 3D a ser normalizado
     */
    normalizeModelSize(object) {
        // Calcular tamanho atual
        let box;
        if (object.geometry) {
            object.geometry.computeBoundingBox();
            box = object.geometry.boundingBox;
        } else {
            box = new THREE.Box3().setFromObject(object);
        }
        
        const size = new THREE.Vector3();
        box.getSize(size);
        const maxDim = Math.max(size.x, size.y, size.z);
        
        // Fator de escala para normalizar para um tamanho razoável
        const scaleFactor = 50 / maxDim;
        
        // Aplicar escala
        object.scale.set(scaleFactor, scaleFactor, scaleFactor);
    }
    
    /**
     * Ajusta a câmera para melhor visualização do modelo
     */
    adjustCameraToModel() {
        if (!this.object) return;
        
        // Calcular bounding box
        let box;
        if (this.object.geometry) {
            this.object.geometry.computeBoundingBox();
            box = this.object.geometry.boundingBox;
        } else {
            box = new THREE.Box3().setFromObject(this.object);
        }
        
        // Posicionar câmera baseado no tamanho do modelo
        const size = new THREE.Vector3();
        box.getSize(size);
        const maxDim = Math.max(size.x, size.y, size.z);
        
        // Ajustar distância para dispositivos móveis (mais próximo para melhor visualização)
        const distance = this.isMobile && this.options.optimizeForMobile ? maxDim * 2.0 : maxDim * 2.5;
        
        this.camera.position.set(distance, distance, distance);
        this.camera.lookAt(new THREE.Vector3(0, 0, 0));
        this.controls.update();
    }
    
    /**
     * Configura controles de interface do usuário
     */
    setupUIControls() {
        // Interface adaptada para toque em dispositivos móveis
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        // Criar container para controles
        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'model-viewer-controls';
        
        // Ajustar classe para dispositivos de toque
        if (isTouchDevice) {
            controlsContainer.classList.add('model-viewer-controls-touch');
        }
        
        // Botão de rotação automática
        const rotateBtn = this.createControlButton('fas fa-sync-alt', 'Alternar rotação automática', () => {
            this.isAutoRotating = !this.isAutoRotating;
            this.controls.autoRotate = this.isAutoRotating;
            rotateBtn.classList.toggle('active', this.isAutoRotating);
        });
        rotateBtn.classList.toggle('active', this.isAutoRotating);
        
        // Botão de reset
        const resetBtn = this.createControlButton('fas fa-home', 'Redefinir visualização', () => {
            this.resetView();
        });
        
        // Botão de fullscreen em dispositivos não móveis ou apenas se explicitamente solicitado
        if (!this.isMobile || !this.options.optimizeForMobile) {
            const fullscreenBtn = this.createControlButton('fas fa-expand', 'Tela cheia', () => {
                this.toggleFullscreen();
            });
            controlsContainer.appendChild(fullscreenBtn);
        }
        
        // Adicionar botões ao container
        controlsContainer.appendChild(rotateBtn);
        controlsContainer.appendChild(resetBtn);
        
        // Adicionar container ao container principal
        this.container.appendChild(controlsContainer);
    }
    
    /**
     * Cria um botão de controle
     * @param {string} iconClass - Classe de ícone Font Awesome
     * @param {string} title - Texto de tooltip
     * @param {Function} onClick - Função de callback para clique
     * @returns {HTMLElement} Elemento do botão
     */
    createControlButton(iconClass, title, onClick) {
        const button = document.createElement('button');
        button.className = 'model-viewer-control-btn';
        button.title = title;
        button.innerHTML = `<i class="${iconClass}"></i>`;
        button.addEventListener('click', onClick);
        
        // Aumentar tamanho dos botões em dispositivos de toque
        if (this.isMobile) {
            button.classList.add('model-viewer-control-btn-large');
        }
        
        return button;
    }
    
    /**
     * Redefine a visualização do modelo
     */
    resetView() {
        // Resetar rotação e zoom
        this.controls.reset();
        
        // Reposicionar câmera
        this.adjustCameraToModel();
    }
    
    /**
     * Alterna modo de tela cheia
     */
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            // Entrar em tela cheia
            if (this.container.requestFullscreen) {
                this.container.requestFullscreen();
            } else if (this.container.mozRequestFullScreen) {
                this.container.mozRequestFullScreen();
            } else if (this.container.webkitRequestFullscreen) {
                this.container.webkitRequestFullscreen();
            } else if (this.container.msRequestFullscreen) {
                this.container.msRequestFullscreen();
            }
        } else {
            // Sair da tela cheia
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    }
    
    /**
     * Manipula evento de redimensionamento da janela
     */
    onWindowResize() {
        // Atualizar tamanho da câmera e renderer
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;
        
        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
        
        // Verificar se houve mudança significativa no tamanho que justifique
        // reclassificar como mobile/desktop
        const wasMobile = this.isMobile;
        this.isMobile = this.detectMobileDevice();
        
        // Se o estado mobile/desktop mudou, ajustar configurações
        if (wasMobile !== this.isMobile && this.options.optimizeForMobile) {
            this.adjustForDeviceType();
        }
    }
    
    /**
     * Ajusta configurações com base no tipo de dispositivo
     */
    adjustForDeviceType() {
        if (this.isMobile) {
            // Otimizações para dispositivos móveis
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
            
            // Simplificar renderização
            if (this.object) {
                this.scene.remove(this.object);
                
                if (this.object.traverse) {
                    this.object.traverse((child) => {
                        if (child instanceof THREE.Mesh) {
                            if (this.shouldReplaceWithOptimizedMaterial(child.material)) {
                                child.material = this.createOptimizedMaterial();
                            }
                        }
                    });
                } else if (this.object instanceof THREE.Mesh) {
                    this.object.material = this.createOptimizedMaterial();
                }
                
                this.scene.add(this.object);
            }
        } else {
            // Restaurar configurações de alta qualidade
            this.renderer.setPixelRatio(window.devicePixelRatio);
        }
    }
    
    /**
     * Loop de animação
     */
    animate() {
        requestAnimationFrame(this.animate.bind(this));
        
        // Atualizar controles
        if (this.controls) {
            this.controls.update();
        }
        
        // Limitar taxa de quadros em dispositivos móveis para economizar bateria
        if (this.isMobile && this.options.optimizeForMobile) {
            // Usar throttling simples para dispositivos móveis
            const now = Date.now();
            if (!this.lastRenderTime || now - this.lastRenderTime >= 33) { // ~30 FPS
                this.lastRenderTime = now;
                this.render();
            }
        } else {
            // Renderização normal para desktop
            this.render();
        }
    }
    
    /**
     * Renderiza a cena
     */
    render() {
        if (this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }
    
    /**
     * Atualiza as opções do visualizador
     * @param {Object} options - Novas opções
     */
    updateOptions(options) {
        // Atualizar opções
        this.options = Object.assign(this.options, options);
        
        // Atualizar características baseadas nas novas opções
        if ('backgroundColor' in options) {
            this.scene.background = new THREE.Color(this.options.backgroundColor);
        }
        
        if ('autoRotate' in options) {
            this.isAutoRotating = this.options.autoRotate;
            this.controls.autoRotate = this.isAutoRotating;
        }
        
        if ('enableZoom' in options) {
            this.controls.enableZoom = this.options.enableZoom;
        }
        
        if ('enablePan' in options) {
            this.controls.enablePan = this.options.enablePan;
        }
        
        // Se a cor do modelo mudou, atualizar
        if ('modelColor' in options && this.object) {
            this.updateModelColor(this.options.modelColor);
        }
        
        // Se a otimização para dispositivos móveis mudou
        if ('optimizeForMobile' in options && this.isMobile) {
            this.adjustForDeviceType();
        }
    }
    
    /**
     * Atualiza a cor do modelo
     * @param {string} color - Nova cor no formato hexadecimal
     */
    updateModelColor(color) {
        if (!this.object) return;
        
        // Atualizar cor do modelo (diferentes conforme tipo de objeto)
        if (this.object.material) {
            // Caso de um único material
            this.object.material.color.set(color);
        } else {
            // Caso de múltiplos materiais ou grupo de objetos
            this.object.traverse((child) => {
                if (child instanceof THREE.Mesh && child.material) {
                    if (Array.isArray(child.material)) {
                        child.material.forEach((mat) => {
                            mat.color.set(color);
                        });
                    } else {
                        child.material.color.set(color);
                    }
                }
            });
        }
    }
    
    /**
     * Limpa a visualização e remove elementos criados
     */
    dispose() {
        // Remover event listeners
        window.removeEventListener('resize', this.onWindowResize.bind(this));
        
        // Limpar cena
        if (this.scene) {
            this.clearScene();
        }
        
        // Remover renderer do DOM
        if (this.renderer && this.renderer.domElement) {
            const canvas = this.renderer.domElement;
            if (canvas.parentNode) {
                canvas.parentNode.removeChild(canvas);
            }
            this.renderer.dispose();
        }
        
        // Remover controles
        if (this.controls) {
            this.controls.dispose();
        }
        
        // Remover elementos de UI
        const controls = this.container.querySelector('.model-viewer-controls');
        if (controls) {
            controls.parentNode.removeChild(controls);
        }
    }
    
    /**
     * Limpa a cena, removendo todos os objetos
     */
    clearScene() {
        while (this.scene.children.length > 0) {
            const object = this.scene.children[0];
            this.scene.remove(object);
            
            // Liberar memória de geometrias e materiais
            if (object.geometry) {
                object.geometry.dispose();
            }
            
            if (object.material) {
                if (Array.isArray(object.material)) {
                    object.material.forEach(material => material.dispose());
                } else {
                    object.material.dispose();
                }
            }
        }
    }
}
