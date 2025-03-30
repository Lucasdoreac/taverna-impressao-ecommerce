/**
 * Taverna da Impressão - Documentação de Usuários
 * JavaScript para funcionalidades interativas da documentação
 * 
 * @version 1.0
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Inicialização dos componentes
    initSidebar();
    initAnchors();
    initSearch();
    initVideoPlaceholders();
    initInteractiveExample();
    highlightSearchTerms();
    
    /**
     * Inicializa o sidebar móvel
     */
    function initSidebar() {
        const toggleBtn = document.getElementById('toggleSidebarBtn');
        const closeBtn = document.getElementById('closeSidebarBtn');
        const sidebar = document.querySelector('.doc-sidebar');
        const backdrop = document.querySelector('.doc-sidebar-backdrop');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                backdrop.classList.toggle('visible');
                document.body.classList.toggle('sidebar-open');
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.remove('visible');
                document.body.classList.remove('sidebar-open');
            });
        }
        
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.remove('visible');
                document.body.classList.remove('sidebar-open');
            });
        }
    }
    
    /**
     * Inicializa o comportamento de rolagem suave para âncoras
     */
    function initAnchors() {
        document.querySelectorAll('.doc-submenu-link, a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                // Ignorar links que não apontam para âncoras
                if (!this.getAttribute('href').startsWith('#') || this.getAttribute('href') === '#') {
                    return;
                }
                
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    e.preventDefault();
                    
                    // Em dispositivos móveis, fechar o sidebar ao clicar em um link
                    if (window.innerWidth < 992) {
                        const sidebar = document.querySelector('.doc-sidebar');
                        const backdrop = document.querySelector('.doc-sidebar-backdrop');
                        if (sidebar && sidebar.classList.contains('open')) {
                            sidebar.classList.remove('open');
                            backdrop.classList.remove('visible');
                            document.body.classList.remove('sidebar-open');
                        }
                    }
                    
                    // Rolar suavemente até o destino
                    window.scrollTo({
                        top: targetElement.offsetTop - 70, // Ajustar para altura do header
                        behavior: 'smooth'
                    });
                    
                    // Atualizar URL
                    history.pushState(null, null, '#' + targetId);
                }
            });
        });
        
        // Navegar para âncora na URL, se houver
        if (window.location.hash) {
            const hashId = window.location.hash.substring(1);
            const targetElement = document.getElementById(hashId);
            
            if (targetElement) {
                // Pequeno atraso para garantir que a página esteja totalmente carregada
                setTimeout(function() {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70,
                        behavior: 'smooth'
                    });
                }, 300);
            }
        }
    }
    
    /**
     * Inicializa a funcionalidade de busca
     */
    function initSearch() {
        const searchForm = document.querySelector('.doc-search-form');
        const searchInput = document.querySelector('.doc-search-input');
        
        if (searchForm && searchInput) {
            // Focar no campo de busca quando em página de busca
            if (window.location.href.includes('action=search')) {
                searchInput.focus();
            }
            
            // Limpar busca vazia
            searchForm.addEventListener('submit', function(e) {
                if (!searchInput.value.trim()) {
                    e.preventDefault();
                }
            });
        }
    }
    
    /**
     * Inicializa os placeholders de vídeo
     */
    function initVideoPlaceholders() {
        const videoPlaceholders = document.querySelectorAll('.video-placeholder');
        
        videoPlaceholders.forEach(placeholder => {
            placeholder.addEventListener('click', function() {
                // Em uma implementação real, substituiríamos por um player de vídeo
                // Aqui apenas simulamos a troca por uma mensagem
                const message = document.createElement('div');
                message.className = 'embed-responsive-item video-message';
                message.innerHTML = '<div class="video-message-content">' +
                                     '<i class="fa fa-video-camera fa-3x"></i>' +
                                     '<h3>Vídeo em produção</h3>' +
                                     '<p>O vídeo tutorial estará disponível em breve.</p>' +
                                     '</div>';
                
                // Substituir o placeholder pela mensagem
                this.parentNode.appendChild(message);
                this.style.display = 'none';
            });
        });
    }
    
    /**
     * Inicializa o exemplo interativo do visualizador 3D
     */
    function initInteractiveExample() {
        const modelViewer = document.querySelector('.model-viewer-placeholder');
        const rotationBtns = document.querySelectorAll('.rotation-controls button');
        const zoomBtns = document.querySelectorAll('.zoom-controls button');
        const qualitySelect = document.querySelector('.controls-panel select');
        const resetBtn = document.querySelector('.controls-panel .btn-primary');
        
        if (!modelViewer) {
            return;
        }
        
        // Simular rotação
        rotationBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Animação simples de feedback
                modelViewer.classList.add('rotating');
                setTimeout(() => {
                    modelViewer.classList.remove('rotating');
                }, 300);
            });
        });
        
        // Simular zoom
        zoomBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Animação simples de feedback
                if (this.querySelector('.fa-plus')) {
                    modelViewer.classList.add('zooming-in');
                    setTimeout(() => {
                        modelViewer.classList.remove('zooming-in');
                    }, 300);
                } else {
                    modelViewer.classList.add('zooming-out');
                    setTimeout(() => {
                        modelViewer.classList.remove('zooming-out');
                    }, 300);
                }
            });
        });
        
        // Simular alteração de qualidade
        if (qualitySelect) {
            qualitySelect.addEventListener('change', function() {
                // Feedback visual
                const quality = this.value;
                modelViewer.querySelector('h3').textContent = `Visualizador 3D (${quality})`;
                
                // Simular carga
                modelViewer.classList.add('loading');
                setTimeout(() => {
                    modelViewer.classList.remove('loading');
                }, 500);
            });
        }
        
        // Resetar visualizador
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                // Resetar select para "Alta"
                if (qualitySelect) {
                    qualitySelect.value = 'Alta';
                }
                
                // Animação de reset
                modelViewer.classList.add('resetting');
                setTimeout(() => {
                    modelViewer.classList.remove('resetting');
                    modelViewer.querySelector('h3').textContent = 'Visualizador 3D';
                }, 500);
            });
        }
    }
    
    /**
     * Destaca os termos de busca nos resultados
     */
    function highlightSearchTerms() {
        // Obter query da URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('q');
        
        if (!searchQuery) {
            return;
        }
        
        // Obter todos os elementos que contêm texto
        const textContainers = document.querySelectorAll('.search-result h3, .search-result .excerpt');
        
        textContainers.forEach(container => {
            const originalText = container.textContent;
            const regex = new RegExp('(' + escapeRegExp(searchQuery) + ')', 'gi');
            const highlightedText = originalText.replace(regex, '<span class="search-highlight">$1</span>');
            
            // Atualizar o HTML se houver correspondências
            if (highlightedText !== originalText) {
                container.innerHTML = highlightedText;
            }
        });
    }
    
    /**
     * Escapa caracteres especiais para uso em RegExp
     */
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    /**
     * Inicializa o painel de FAQ
     */
    const accordionToggles = document.querySelectorAll('[data-toggle="collapse"]');
    if (accordionToggles.length > 0) {
        accordionToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    // Fechar todos os painéis abertos
                    document.querySelectorAll('.panel-collapse.collapse.in').forEach(panel => {
                        if (panel.id !== targetId.substring(1)) {
                            panel.classList.remove('in');
                        }
                    });
                    
                    // Alternar o estado do painel alvo
                    targetElement.classList.toggle('in');
                }
            });
        });
    }
    
    /**
     * Inicializa o botão de chat ao vivo
     */
    const liveChatBtn = document.getElementById('liveChatBtn');
    if (liveChatBtn) {
        liveChatBtn.addEventListener('click', function() {
            alert('O chat ao vivo estará disponível em breve. Por favor, utilize o formulário de contato por enquanto.');
        });
    }
    
    /**
     * Adiciona comportamento para pré-carregamento de páginas
     * Isso pode melhorar a velocidade de navegação percebida pelo usuário
     */
    document.querySelectorAll('.doc-menu-link, .doc-nav-link').forEach(link => {
        // Ignorar links de âncora
        if (link.getAttribute('href').startsWith('#')) {
            return;
        }
        
        link.addEventListener('mouseenter', function() {
            const url = this.getAttribute('href');
            
            // Prefetch da página
            if (url && !url.startsWith('#') && !prefetchedUrls.includes(url)) {
                const prefetch = document.createElement('link');
                prefetch.rel = 'prefetch';
                prefetch.href = url;
                document.head.appendChild(prefetch);
                
                prefetchedUrls.push(url);
            }
        });
    });
    
    // Armazenar URLs pré-carregadas
    const prefetchedUrls = [];
    
    /**
     * Detecta se o usuário está prestes a sair da página
     * Pode ser útil para salvar progresso ou exibir prompt
     */
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            // O usuário está saindo da página ou mudando de aba
            // Em uma implementação real, poderíamos salvar o estado ou progresso
            console.log('Usuário deixou a página de documentação');
        }
    });
});

/**
 * Adicionar classes CSS de animação com base na rolagem
 */
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('.doc-section');
    
    sections.forEach(section => {
        const sectionTop = section.getBoundingClientRect().top;
        const windowHeight = window.innerHeight;
        
        // Animação ao entrar no viewport
        if (sectionTop < windowHeight - 100) {
            section.classList.add('in-view');
        }
    });
});

/**
 * Detectar quando um elemento está visível no viewport
 */
function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}
