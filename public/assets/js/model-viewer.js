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
        
        // Inicializar o visualizador
        this.init();
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
        
        // Adicionar grade se necessário
        if (this.options.showGrid) {
            this.setupGrid();
        }
        
        // Adicionar eixos se necessário
        if (this.options.showAxes) {
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
     * Configura o renderer
     */
    setupRenderer() {
        this.renderer = new THREE.WebGLRenderer({ antialias: true });
        this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
        this.renderer.setPixelRatio(window.devicePixelRatio);
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
    }
    
    /**
     * Configura as luzes da cena
     */
    setupLights() {
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
    
    /**
     * Configura a grade
     */
    setupGrid() {
        const gridHelper = new THREE.GridHelper(100, 20, 0x888888, 0x444444);
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
        
        loader.load(
            filePath,
            (geometry) => {
                // Criar material
                const material = new THREE.MeshPhongMaterial({
                    color: this.options.modelColor,
                    specular: 0x111111,
                    shininess: 30
                });
                
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
        loader.load(
            filePath,
            (object) => {
                // Se não houver materiais, aplicar material padrão
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
                const percent = (xhr.loaded / xhr.total) * 100;
                console.log(`ModelViewer: ${Math.round(percent)}% carregado`);
            },
            (error) => {
                // Erro de carregamento
                console.error('ModelViewer: Erro ao carregar modelo OBJ', error);
                this.hideLoading();
            }
        );
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
        const distance = maxDim * 2.5;
        
        this.camera.position.set(distance, distance, distance);
        this.camera.lookAt(new THREE.Vector3(0, 0, 0));
        this.controls.update();
    }
    
    /**
     * Configura controles de interface do usuário
     */
    setupUIControls() {
        // Criar container para controles
        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'model-viewer-controls';
        
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
        
        // Botão de fullscreen
        const fullscreenBtn = this.createControlButton('fas fa-expand', 'Tela cheia', () => {
            this.toggleFullscreen();
        });
        
        // Adicionar botões ao container
        controlsContainer.appendChild(rotateBtn);
        controlsContainer.appendChild(resetBtn);
        controlsContainer.appendChild(fullscreenBtn);
        
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
        
        // Renderizar cena
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
