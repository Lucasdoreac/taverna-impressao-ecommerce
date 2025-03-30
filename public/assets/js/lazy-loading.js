/**
 * Lazy Loading de Imagens
 * 
 * Este script carrega imagens somente quando elas entram no viewport,
 * reduzindo o tempo de carregamento inicial da página e economizando
 * largura de banda para imagens que o usuário nunca verá.
 */
(function() {
    'use strict';
    
    // Verificar se o navegador suporta IntersectionObserver
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img.lazy');
        
        // Criar o observador
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                // Verificar se a imagem está visível
                if (entry.isIntersecting) {
                    const lazyImage = entry.target;
                    
                    // Carregar a imagem de src
                    if (lazyImage.dataset.src) {
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.removeAttribute('data-src');
                    }
                    
                    // Carregar imagem srcset (para imagens responsivas)
                    if (lazyImage.dataset.srcset) {
                        lazyImage.srcset = lazyImage.dataset.srcset;
                        lazyImage.removeAttribute('data-srcset');
                    }
                    
                    // Remover a classe lazy depois que a imagem for carregada
                    lazyImage.classList.remove('lazy');
                    
                    // Parar de observar esta imagem
                    imageObserver.unobserve(lazyImage);
                    
                    // Evento quando a imagem é carregada
                    lazyImage.addEventListener('load', function() {
                        // Ativar animação de fade-in (se estiver usando CSS para isso)
                        lazyImage.classList.add('lazy-loaded');
                    });
                }
            });
        }, {
            // Margem de 200px ao redor do viewport, para pré-carregar imagens
            // pouco antes de entrarem na view
            rootMargin: '200px 0px',
            threshold: 0.01
        });
        
        // Observar todas as imagens com a classe 'lazy'
        lazyImages.forEach(function(lazyImage) {
            imageObserver.observe(lazyImage);
        });
    } else {
        // Fallback para navegadores que não suportam IntersectionObserver
        // Implementar versão simplificada com scroll e setTimeout
        let lazyloadThrottleTimeout;
        const lazyImages = document.querySelectorAll('img.lazy');
        
        function lazyload() {
            if (lazyloadThrottleTimeout) {
                clearTimeout(lazyloadThrottleTimeout);
            }
            
            lazyloadThrottleTimeout = setTimeout(function() {
                const scrollTop = window.pageYOffset;
                
                lazyImages.forEach(function(lazyImage) {
                    if (lazyImage.offsetTop < (window.innerHeight + scrollTop + 200)) {
                        if (lazyImage.dataset.src) {
                            lazyImage.src = lazyImage.dataset.src;
                            lazyImage.removeAttribute('data-src');
                        }
                        
                        if (lazyImage.dataset.srcset) {
                            lazyImage.srcset = lazyImage.dataset.srcset;
                            lazyImage.removeAttribute('data-srcset');
                        }
                        
                        lazyImage.classList.remove('lazy');
                        lazyImage.classList.add('lazy-loaded');
                    }
                });
                
                // Se todas as imagens já foram carregadas, remover os listeners
                if (lazyImages.length === 0) { 
                    document.removeEventListener('scroll', lazyload);
                    window.removeEventListener('resize', lazyload);
                    window.removeEventListener('orientationChange', lazyload);
                }
            }, 20);
        }
        
        // Adicionar listeners para diferentes eventos
        document.addEventListener('scroll', lazyload);
        window.addEventListener('resize', lazyload);
        window.addEventListener('orientationChange', lazyload);
        
        // Executar uma vez na inicialização para carregar imagens visíveis
        setTimeout(lazyload, 20);
    }
    
    // Adicionar CSS para efeito de fade-in
    const style = document.createElement('style');
    style.type = 'text/css';
    style.innerHTML = `
        .lazy {
            opacity: 0;
            transition: opacity 0.3s ease-in;
        }
        
        .lazy-loaded {
            opacity: 1;
        }
        
        /* Placeholder padrão para imagens ainda não carregadas */
        .lazy-placeholder {
            background-color: #f0f0f0;
            display: inline-block;
            position: relative;
        }
        
        .lazy-placeholder::before {
            content: "";
            display: block;
            padding-top: 75%; /* Proporção padrão 4:3 */
        }
    `;
    document.getElementsByTagName('head')[0].appendChild(style);
})();
