/**
 * Lazy Loading de Imagens
 * 
 * Este script carrega imagens somente quando elas entram no viewport,
 * reduzindo o tempo de carregamento inicial da página e economizando
 * largura de banda para imagens que o usuário nunca verá.
 */
(function() {
    'use strict';
    
    // Adicionar CSS para lazy backgrounds
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
        
        /* Estilos para lazy backgrounds */
        .lazy-bg {
            background-image: none !important;
            background-color: #f0f0f0;
            transition: background-image 0.5s ease-in, background-color 0.5s ease-in;
        }
        
        .lazy-bg-loaded {
            background-color: transparent;
        }
    `;
    document.getElementsByTagName('head')[0].appendChild(style);
    
    // Verificar se o navegador suporta IntersectionObserver
    if ('IntersectionObserver' in window) {
        // Lazy loading para imagens <img>
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
        
        // Lazy loading para background images
        const lazyBackgrounds = document.querySelectorAll('.lazy-bg');
        
        // Criar o observador para backgrounds
        const bgObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                // Verificar se o elemento está visível
                if (entry.isIntersecting) {
                    const lazyBackground = entry.target;
                    
                    // Aplicar background image
                    if (lazyBackground.dataset.bg) {
                        lazyBackground.style.backgroundImage = 'url(' + lazyBackground.dataset.bg + ')';
                        lazyBackground.removeAttribute('data-bg');
                    }
                    
                    // Remover a classe lazy-bg depois que o background for carregado
                    lazyBackground.classList.remove('lazy-bg');
                    
                    // Adicionar classe para fade-in
                    lazyBackground.classList.add('lazy-bg-loaded');
                    
                    // Parar de observar este elemento
                    bgObserver.unobserve(lazyBackground);
                }
            });
        }, {
            // Margem de 200px ao redor do viewport
            rootMargin: '200px 0px',
            threshold: 0.01
        });
        
        // Observar todos os elementos com a classe 'lazy-bg'
        lazyBackgrounds.forEach(function(lazyBackground) {
            bgObserver.observe(lazyBackground);
        });
    } else {
        // Fallback para navegadores que não suportam IntersectionObserver
        // Implementar versão simplificada com scroll e setTimeout
        let lazyloadThrottleTimeout;
        
        // Função para lazy loading de imagens
        function lazyloadImages() {
            const lazyImages = document.querySelectorAll('img.lazy');
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
        }
        
        // Função para lazy loading de backgrounds
        function lazyloadBackgrounds() {
            const lazyBackgrounds = document.querySelectorAll('.lazy-bg');
            const scrollTop = window.pageYOffset;
            
            lazyBackgrounds.forEach(function(lazyBackground) {
                if (lazyBackground.offsetTop < (window.innerHeight + scrollTop + 200)) {
                    if (lazyBackground.dataset.bg) {
                        lazyBackground.style.backgroundImage = 'url(' + lazyBackground.dataset.bg + ')';
                        lazyBackground.removeAttribute('data-bg');
                    }
                    
                    lazyBackground.classList.remove('lazy-bg');
                    lazyBackground.classList.add('lazy-bg-loaded');
                }
            });
        }
        
        // Função combinada para lazy loading
        function lazyload() {
            if (lazyloadThrottleTimeout) {
                clearTimeout(lazyloadThrottleTimeout);
            }
            
            lazyloadThrottleTimeout = setTimeout(function() {
                lazyloadImages();
                lazyloadBackgrounds();
                
                // Verificar se todos os elementos foram processados
                const lazyElements = document.querySelectorAll('img.lazy, .lazy-bg');
                if (lazyElements.length === 0) { 
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
    
    // Função para re-inicializar o lazy loading após carregamento dinâmico
    window.initLazyLoading = function() {
        if ('IntersectionObserver' in window) {
            // Re-inicializar para imagens
            const newLazyImages = document.querySelectorAll('img.lazy');
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                        img.addEventListener('load', function() {
                            img.classList.add('lazy-loaded');
                        });
                    }
                });
            }, { rootMargin: '200px 0px', threshold: 0.01 });
            
            newLazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
            
            // Re-inicializar para backgrounds
            const newLazyBgs = document.querySelectorAll('.lazy-bg');
            const bgObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const bg = entry.target;
                        if (bg.dataset.bg) {
                            bg.style.backgroundImage = 'url(' + bg.dataset.bg + ')';
                            bg.removeAttribute('data-bg');
                        }
                        bg.classList.remove('lazy-bg');
                        bg.classList.add('lazy-bg-loaded');
                        observer.unobserve(bg);
                    }
                });
            }, { rootMargin: '200px 0px', threshold: 0.01 });
            
            newLazyBgs.forEach(function(bg) {
                bgObserver.observe(bg);
            });
        } else {
            // Fallback para navegadores que não suportam IntersectionObserver
            setTimeout(function() {
                const lazyElements = document.querySelectorAll('img.lazy, .lazy-bg');
                const scrollTop = window.pageYOffset;
                
                lazyElements.forEach(function(el) {
                    if (el.offsetTop < (window.innerHeight + scrollTop + 200)) {
                        if (el.tagName.toLowerCase() === 'img') {
                            if (el.dataset.src) {
                                el.src = el.dataset.src;
                                el.removeAttribute('data-src');
                            }
                            el.classList.remove('lazy');
                            el.classList.add('lazy-loaded');
                        } else {
                            if (el.dataset.bg) {
                                el.style.backgroundImage = 'url(' + el.dataset.bg + ')';
                                el.removeAttribute('data-bg');
                            }
                            el.classList.remove('lazy-bg');
                            el.classList.add('lazy-bg-loaded');
                        }
                    }
                });
            }, 20);
        }
    };
})();