/**
 * Model Viewer - Visualizador 3D para modelos STL, OBJ e 3MF
 * 
 * Este script utiliza Three.js para renderizar modelos 3D no navegador
 * 
 * @package    Taverna da Impressão 3D
 * @version    1.0.0
 */

// Variáveis globais
let scene, camera, renderer, controls, model, grid;
let lights = {
    ambient: null,
    directional: null,
    hemispheric: null
};

// Elementos DOM
const container = document.getElementById('viewer3d-container');
const canvas = document.getElementById('viewer3d-canvas');
const loadingElement = document.getElementById('viewer-loading');
const errorElement = document.getElementById('viewer-error');

// Controles UI
const wireframeToggle = document.getElementById('wireframe');
const gridToggle = document.getElementById('grid');
const rotateToggle = document.getElementById('rotate');
const lightingSlider = document.getElementById('lighting');
const bgColorPicker = document.getElementById('bgColor');
const resetCameraButton = document.getElementById('resetCamera');

// Opções de configuração
const config = {
    modelUrl: viewerConfig.modelUrl,
    backgroundColor: viewerConfig.backgroundColor || '#f5f5f5',
    gridEnabled: viewerConfig.gridEnabled !== undefined ? viewerConfig.gridEnabled : true,
    defaultCameraPosition: viewerConfig.defaultCameraPosition || [0, 0, 5],
    lightIntensity: viewerConfig.lightIntensity || 1.0,
    autoRotate: viewerConfig.autoRotate || false,
    wireframeEnabled: viewerConfig.wireframeEnabled || false
};

// Estado
let originalCameraPosition = null;
let isInitialized = false;
let isLoading = true;
let hasError = false;

// Detectar o tipo de modelo a partir da URL
const fileExtension = config.modelUrl.split('.').pop().toLowerCase();
const modelType = getModelTypeFromUrl(config.modelUrl);

/**
 * Inicializa o visualizador
 */
function initViewer() {
    if (isInitialized) return;
    
    isInitialized = true;
    
    try {
        // Inicializar cena
        scene = new THREE.Scene();
        scene.background = new THREE.Color(config.backgroundColor);
        
        // Configurar renderizador
        renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            antialias: true,
            alpha: true
        });
        renderer.setPixelRatio(window.devicePixelRatio);
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        
        // Configurar câmera
        const aspect = container.clientWidth / container.clientHeight;
        camera = new THREE.PerspectiveCamera(45, aspect, 0.1, 1000);
        camera.position.set(...config.defaultCameraPosition);
        originalCameraPosition = [...config.defaultCameraPosition];
        
        // Adicionar luzes
        setupLights();
        
        // Adicionar grid
        setupGrid();
        
        // Configurar controles
        setupControls();
        
        // Carregar modelo
        loadModel();
        
        // Adicionar event listeners
        setupEventListeners();
        
        // Iniciar loop de animação
        animate();
    } catch (error) {
        console.error("Erro ao inicializar visualizador:", error);
        showError();
    }
}

/**
 * Configura as luzes da cena
 */
function setupLights() {
    // Luz ambiente
    lights.ambient = new THREE.AmbientLight(0xffffff, 0.4 * config.lightIntensity);
    scene.add(lights.ambient);
    
    // Luz direcional (simula sol)
    lights.directional = new THREE.DirectionalLight(0xffffff, 0.6 * config.lightIntensity);
    lights.directional.position.set(1, 1, 1);
    lights.directional.castShadow = true;
    
    // Configurar sombras
    lights.directional.shadow.mapSize.width = 2048;
    lights.directional.shadow.mapSize.height = 2048;
    lights.directional.shadow.camera.near = 0.5;
    lights.directional.shadow.camera.far = 500;
    
    scene.add(lights.directional);
    
    // Luz hemisférica (mais natural)
    lights.hemispheric = new THREE.HemisphereLight(0xddeeff, 0x0f0e0d, 0.3 * config.lightIntensity);
    scene.add(lights.hemispheric);
}

/**
 * Configura o grid
 */
function setupGrid() {
    grid = new THREE.GridHelper(20, 20, 0x888888, 0x444444);
    grid.visible = config.gridEnabled;
    scene.add(grid);
}

/**
 * Configura os controles da câmera
 */
function setupControls() {
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.1;
    controls.rotateSpeed = 0.7;
    controls.enableZoom = true;
    controls.zoomSpeed = 0.9;
    controls.enablePan = true;
    controls.panSpeed = 0.9;
    controls.minDistance = 0.5;
    controls.maxDistance = 100;
    controls.autoRotate = config.autoRotate;
    controls.autoRotateSpeed = 2.0;
    controls.update();
}

/**
 * Carrega o modelo 3D
 */
function loadModel() {
    showLoading();
    
    try {
        const loader = getModelLoader();
        
        if (!loader) {
            throw new Error("Tipo de modelo não suportado");
        }
        
        loader.load(
            // URL do modelo
            config.modelUrl,
            
            // Callback de sucesso
            function(object) {
                if (modelType === 'stl') {
                    // STL precisa de material
                    const material = new THREE.MeshPhongMaterial({
                        color: 0x3498db,
                        specular: 0x111111,
                        shininess: 100
                    });
                    
                    // Criar malha a partir da geometria carregada
                    model = new THREE.Mesh(object, material);
                } else {
                    // OBJ e 3MF já têm suas próprias malhas/materiais
                    model = object;
                }
                
                // Centralizar o modelo
                centerModel(model);
                
                // Adicionar à cena
                scene.add(model);
                
                // Aplicar configurações iniciais
                applyWireframeMode(config.wireframeEnabled);
                
                // Esconder loader
                hideLoading();
                
                // Ajustar posição da câmera
                fitCameraToModel();
                
                // Sinalizar loading completo
                isLoading = false;
            },
            
            // Callback de progresso
            function(xhr) {
                const percent = (xhr.loaded / xhr.total) * 100;
                console.log('Modelo carregado: ' + percent.toFixed(1) + '%');
            },
            
            // Callback de erro
            function(error) {
                console.error('Erro ao carregar modelo:', error);
                showError();
                isLoading = false;
                hasError = true;
            }
        );
    } catch (error) {
        console.error('Erro ao iniciar carregamento do modelo:', error);
        showError();
        isLoading = false;
        hasError = true;
    }
}

/**
 * Retorna o loader apropriado com base no tipo de modelo
 */
function getModelLoader() {
    switch (modelType) {
        case 'stl':
            return new THREE.STLLoader();
        case 'obj':
            return new THREE.OBJLoader();
        case '3mf':
            return new THREE.ThreeMFLoader();
        default:
            return null;
    }
}

/**
 * Detecta o tipo do modelo a partir da URL
 */
function getModelTypeFromUrl(url) {
    // Primeiro tentar detectar do filename
    const extension = url.split('.').pop().toLowerCase();
    
    if (extension === 'stl') {
        return 'stl';
    } else if (extension === 'obj') {
        return 'obj';
    } else if (extension === '3mf') {
        return '3mf';
    }
    
    // Se não conseguiu detectar, tentar inferir da URL (para URLs sem extensão)
    if (url.includes('/stl/') || url.includes('format=stl')) {
        return 'stl';
    } else if (url.includes('/obj/') || url.includes('format=obj')) {
        return 'obj';
    } else if (url.includes('/3mf/') || url.includes('format=3mf')) {
        return '3mf';
    }
    
    // Padrão (muitos modelos são STL)
    return 'stl';
}

/**
 * Centraliza o modelo na cena
 */
function centerModel(model) {
    // Calcular bounding box
    const box = new THREE.Box3().setFromObject(model);
    const center = box.getCenter(new THREE.Vector3());
    
    // Deslocar geometria para centralizar
    model.position.sub(center);
    
    // Calcular escala apropriada
    const size = box.getSize(new THREE.Vector3());
    const maxDim = Math.max(size.x, size.y, size.z);
    
    // Escalar se o modelo for muito grande ou muito pequeno
    if (maxDim > 20 || maxDim < 0.1) {
        const scale = 5 / maxDim;
        model.scale.set(scale, scale, scale);
    }
    
    // Garantir que o modelo está recebendo e lançando sombras
    model.traverse(function(child) {
        if (child.isMesh) {
            child.castShadow = true;
            child.receiveShadow = true;
        }
    });
}

/**
 * Ajusta a câmera para enquadrar todo o modelo
 */
function fitCameraToModel() {
    if (!model) return;
    
    // Calcular bounding box
    const box = new THREE.Box3().setFromObject(model);
    const size = box.getSize(new THREE.Vector3());
    const center = box.getCenter(new THREE.Vector3());
    
    // Calcular distância apropriada
    const maxDim = Math.max(size.x, size.y, size.z);
    const fov = camera.fov * (Math.PI / 180);
    let cameraZ = Math.abs(maxDim / 2 / Math.tan(fov / 2));
    
    // Aplicar um fator de zoom-out para garantir que todo o modelo seja visível
    cameraZ *= 1.5;
    
    // Posicionar a câmera
    camera.position.set(center.x, center.y, center.z + cameraZ);
    
    // Armazenar posição original
    originalCameraPosition = [center.x, center.y, center.z + cameraZ];
    
    // Definir o ponto de mira da câmera
    const controls = camera.userData.controls;
    if (controls) {
        controls.target.set(center.x, center.y, center.z);
    }
    
    // Atualizar a câmera
    camera.updateProjectionMatrix();
    
    // Atualizar os controles
    if (controls) {
        controls.update();
    }
}

/**
 * Mostra elemento de carregamento
 */
function showLoading() {
    if (loadingElement) {
        loadingElement.style.display = 'flex';
    }
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

/**
 * Oculta elemento de carregamento
 */
function hideLoading() {
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
}

/**
 * Mostra elemento de erro
 */
function showError() {
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
    if (errorElement) {
        errorElement.style.display = 'flex';
    }
}

/**
 * Aplica modo wireframe ao modelo
 */
function applyWireframeMode(enabled) {
    if (!model) return;
    
    model.traverse(function(child) {
        if (child.isMesh && child.material) {
            // Se for um array de materiais
            if (Array.isArray(child.material)) {
                child.material.forEach(function(material) {
                    material.wireframe = enabled;
                });
            } else {
                // Material único
                child.material.wireframe = enabled;
            }
        }
    });
}

/**
 * Atualiza intensidade das luzes
 */
function updateLightIntensity(intensity) {
    if (!lights) return;
    
    lights.ambient.intensity = 0.4 * intensity;
    lights.directional.intensity = 0.6 * intensity;
    lights.hemispheric.intensity = 0.3 * intensity;
}

/**
 * Configura event listeners para controles UI
 */
function setupEventListeners() {
    // Toggle wireframe
    wireframeToggle.addEventListener('change', function() {
        applyWireframeMode(this.checked);
    });
    
    // Toggle grid
    gridToggle.addEventListener('change', function() {
        if (grid) {
            grid.visible = this.checked;
        }
    });
    
    // Toggle auto-rotação
    rotateToggle.addEventListener('change', function() {
        if (controls) {
            controls.autoRotate = this.checked;
        }
    });
    
    // Ajustar intensidade da luz
    lightingSlider.addEventListener('input', function() {
        updateLightIntensity(parseFloat(this.value));
    });
    
    // Alteração de cor de fundo
    bgColorPicker.addEventListener('input', function() {
        if (scene) {
            scene.background = new THREE.Color(this.value);
        }
    });
    
    // Reset da câmera
    resetCameraButton.addEventListener('click', function() {
        resetCamera();
    });
    
    // Redimensionamento da janela
    window.addEventListener('resize', onWindowResize);
}

/**
 * Reseta a câmera para a posição original
 */
function resetCamera() {
    if (!camera || !controls) return;
    
    // Animar transição da câmera para a posição original
    const startPosition = new THREE.Vector3().copy(camera.position);
    const endPosition = new THREE.Vector3(...originalCameraPosition);
    const startTime = Date.now();
    const duration = 1000; // ms
    
    function animateCamera() {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Interpolação suave
        const easing = easeOutQuad(progress);
        
        // Atualizar posição da câmera
        camera.position.lerpVectors(startPosition, endPosition, easing);
        
        // Continuar animação até completar
        if (progress < 1) {
            requestAnimationFrame(animateCamera);
        }
    }
    
    animateCamera();
    
    // Resetar controles
    controls.reset();
    
    // Função de easing
    function easeOutQuad(t) {
        return t * (2 - t);
    }
}

/**
 * Manipulador de evento de redimensionamento da janela
 */
function onWindowResize() {
    if (!camera || !renderer) return;
    
    // Atualizar aspect ratio da câmera
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    
    // Atualizar tamanho do renderer
    renderer.setSize(container.clientWidth, container.clientHeight);
}

/**
 * Loop de animação
 */
function animate() {
    requestAnimationFrame(animate);
    
    if (controls) {
        controls.update();
    }
    
    render();
}

/**
 * Renderiza a cena
 */
function render() {
    if (renderer && scene && camera) {
        renderer.render(scene, camera);
    }
}

// Inicializar visualizador quando a página estiver carregada
document.addEventListener('DOMContentLoaded', function() {
    // Esperar 300ms para garantir que os elementos estejam totalmente renderizados
    setTimeout(initViewer, 300);
    
    // Configurar valores iniciais dos controles
    wireframeToggle.checked = config.wireframeEnabled;
    gridToggle.checked = config.gridEnabled;
    rotateToggle.checked = config.autoRotate;
    lightingSlider.value = config.lightIntensity;
    bgColorPicker.value = config.backgroundColor;
});

// Iniciar imediatamente se o DOM já estiver carregado
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(initViewer, 300);
}
