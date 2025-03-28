/**
 * Scripts principais para TAVERNA DA IMPRESSÃO
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar funcionalidades quando o DOM estiver carregado
    initMobileMenu();
    initProductActions();
    initImageZoom();
});

/**
 * Inicializa o menu mobile
 */
function initMobileMenu() {
    // Toggle para o menu mobile
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            const navMenu = document.querySelector('.nav-menu ul');
            navMenu.classList.toggle('active');
        });
    }
    
    // Dropdown em dispositivos móveis
    const dropdownLinks = document.querySelectorAll('.nav-menu li > a:not(:only-child)');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const screenWidth = window.innerWidth;
            if (screenWidth < 768) {
                e.preventDefault();
                const dropdown = this.nextElementSibling;
                const isVisible = dropdown.style.display === 'block';
                
                // Esconder todos os dropdowns
                document.querySelectorAll('.nav-menu .dropdown').forEach(dd => {
                    dd.style.display = 'none';
                });
                
                // Mostrar o dropdown atual (se não estava visível)
                if (!isVisible) {
                    dropdown.style.display = 'block';
                }
            }
        });
    });
}

/**
 * Inicializa ações de produtos (adicionar ao carrinho, etc)
 */
function initProductActions() {
    // Botões de adicionar ao carrinho
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            
            // Adicionar efeito visual de feedback
            this.classList.add('adding');
            
            // Simular adição ao carrinho (substituir por AJAX real)
            setTimeout(() => {
                this.classList.remove('adding');
                this.classList.add('added');
                
                // Mostrar mensagem de sucesso
                showNotification('Produto adicionado ao carrinho!', 'success');
                
                // Atualizar contador do carrinho
                updateCartCount(1);
                
                // Resetar estado do botão após alguns segundos
                setTimeout(() => {
                    this.classList.remove('added');
                }, 2000);
            }, 800);
        });
    });
    
    // Seletores de quantidade
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        const decrementBtn = input.previousElementSibling;
        const incrementBtn = input.nextElementSibling;
        
        if (decrementBtn && incrementBtn) {
            decrementBtn.addEventListener('click', () => {
                const currentValue = parseInt(input.value);
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                    triggerChangeEvent(input);
                }
            });
            
            incrementBtn.addEventListener('click', () => {
                const currentValue = parseInt(input.value);
                input.value = currentValue + 1;
                triggerChangeEvent(input);
            });
        }
    });
}

/**
 * Inicializa zoom de imagem em páginas de produto
 */
function initImageZoom() {
    const productImage = document.querySelector('.product-main-image');
    if (productImage) {
        productImage.addEventListener('mousemove', function(e) {
            const { left, top, width, height } = this.getBoundingClientRect();
            const x = (e.clientX - left) / width * 100;
            const y = (e.clientY - top) / height * 100;
            
            this.style.backgroundPosition = `${x}% ${y}%`;
        });
        
        productImage.addEventListener('mouseleave', function() {
            this.style.backgroundPosition = 'center';
        });
    }
    
    // Galeria de imagens em páginas de produto
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const mainImage = document.querySelector('.product-main-image');
            if (mainImage) {
                const newImageUrl = this.dataset.image;
                mainImage.style.backgroundImage = `url(${newImageUrl})`;
                
                // Remover classe ativa de todos os thumbnails
                thumbnails.forEach(t => t.classList.remove('active'));
                
                // Adicionar classe ativa ao thumbnail clicado
                this.classList.add('active');
            }
        });
    });
}

/**
 * Mostra notificação na página
 */
function showNotification(message, type = 'info') {
    // Verificar se já existe uma notificação
    let notification = document.querySelector('.notification');
    
    if (!notification) {
        // Criar elemento de notificação
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    // Adicionar classe de tipo
    notification.className = `notification ${type}`;
    
    // Definir mensagem
    notification.textContent = message;
    
    // Mostrar notificação
    notification.classList.add('show');
    
    // Esconder após alguns segundos
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

/**
 * Atualiza contador do carrinho
 */
function updateCartCount(addedItems = 0) {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const currentCount = parseInt(cartCount.textContent || '0');
        const newCount = currentCount + addedItems;
        
        cartCount.textContent = newCount;
        
        // Destacar o contador com animação
        cartCount.classList.add('pulse');
        setTimeout(() => {
            cartCount.classList.remove('pulse');
        }, 300);
    }
}

/**
 * Dispara evento de alteração em um input
 */
function triggerChangeEvent(element) {
    const event = new Event('change', { bubbles: true });
    element.dispatchEvent(event);
}