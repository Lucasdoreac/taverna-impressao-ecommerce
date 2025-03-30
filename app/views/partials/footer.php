    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Sobre a Empresa -->
                <div class="footer-column">
                    <h3 class="footer-title">TAVERNA DA IMPRESSÃO</h3>
                    <p>Sua loja especializada em materiais impressos para RPG, com foco em qualidade e personalização para elevar suas aventuras.</p>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-discord"></i></a>
                    </div>
                </div>
                
                <!-- Links Rápidos -->
                <div class="footer-column">
                    <h3 class="footer-title">Links Rápidos</h3>
                    <ul>
                        <li><a href="<?= BASE_URL ?>">Home</a></li>
                        <li><a href="<?= BASE_URL ?>produtos">Produtos</a></li>
                        <li><a href="<?= BASE_URL ?>personalizados">Personalizados</a></li>
                        <li><a href="<?= BASE_URL ?>sobre">Sobre Nós</a></li>
                        <li><a href="<?= BASE_URL ?>contato">Contato</a></li>
                        <li><a href="<?= BASE_URL ?>termos">Termos e Condições</a></li>
                    </ul>
                </div>
                
                <!-- Categorias -->
                <div class="footer-column">
                    <h3 class="footer-title">Categorias</h3>
                    <ul>
                        <li><a href="<?= BASE_URL ?>categoria/fichas-de-personagem">Fichas de Personagem</a></li>
                        <li><a href="<?= BASE_URL ?>categoria/mapas-de-aventura">Mapas de Aventura</a></li>
                        <li><a href="<?= BASE_URL ?>categoria/livros-e-modulos">Livros e Módulos</a></li>
                        <li><a href="<?= BASE_URL ?>categoria/telas-do-mestre">Telas do Mestre</a></li>
                        <li><a href="<?= BASE_URL ?>categoria/cards-e-tokens">Cards e Tokens</a></li>
                    </ul>
                </div>
                
                <!-- Contato -->
                <div class="footer-column">
                    <h3 class="footer-title">Contato</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Rua Exemplo, 123 - Cidade</li>
                        <li><i class="fas fa-phone"></i> <?= STORE_PHONE ?></li>
                        <li><i class="fas fa-envelope"></i> <?= STORE_EMAIL ?></li>
                        <li><i class="fas fa-clock"></i> Seg-Sex: 9h às 18h</li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> TAVERNA DA IMPRESSÃO - Todos os direitos reservados. Desenvolvido com <i class="fas fa-heart"></i></p>
                
                <!-- Usar lazy loading para imagem de formas de pagamento -->
                <?php
                if (class_exists('AssetOptimizerHelper')) {
                    echo AssetOptimizerHelper::lazyImage('payment-methods.png', 'Formas de Pagamento', 'payment-methods');
                } else {
                    echo '<img src="' . BASE_URL . 'assets/images/payment-methods.png" alt="Formas de Pagamento" class="payment-methods">';
                }
                ?>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <?php
    // Carregar jQuery otimizado
    if (class_exists('ResourceOptimizerHelper')) {
        echo optimizeExternalResource('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js', 'js', ['defer' => false]);
    } else {
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>' . PHP_EOL;
    }
    
    // Carregar scripts otimizados
    if (class_exists('AssetOptimizerHelper')) {
        // Carregar script.js e lazy-loading.js otimizados
        echo AssetOptimizerHelper::js(['script.js', 'lazy-loading.js'], true, true);
    } else {
        // Fallback para o método convencional
        echo '<script src="' . BASE_URL . 'assets/js/script.js" defer></script>' . PHP_EOL;
        echo '<script src="' . BASE_URL . 'assets/js/lazy-loading.js" defer></script>' . PHP_EOL;
    }
    ?>
    
    <!-- Análise de carregamento para desenvolvimento -->
    <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development' && isset($_GET['analyze_loading'])): ?>
    <script>
        // Script para medir desempenho de carregamento
        document.addEventListener('DOMContentLoaded', function() {
            // Coletar métricas do navegador
            const navigationTiming = performance.getEntriesByType('navigation')[0];
            const resourceTiming = performance.getEntriesByType('resource');
            
            // Calcular métricas
            const pageLoadTime = navigationTiming.loadEventEnd - navigationTiming.navigationStart;
            const domContentLoaded = navigationTiming.domContentLoadedEventEnd - navigationTiming.navigationStart;
            const firstPaint = performance.getEntriesByName('first-paint')[0]?.startTime || 'N/A';
            
            // Calcular estatísticas de recursos
            const totalResourcesSize = resourceTiming.reduce((sum, resource) => sum + resource.transferSize, 0) / 1024;
            const externalResources = resourceTiming.filter(resource => !resource.name.includes(window.location.host));
            const externalResourcesSize = externalResources.reduce((sum, resource) => sum + resource.transferSize, 0) / 1024;
            
            // Mostrar resultado
            const performanceResults = document.createElement('div');
            performanceResults.style.position = 'fixed';
            performanceResults.style.bottom = '0';
            performanceResults.style.left = '0';
            performanceResults.style.right = '0';
            performanceResults.style.padding = '10px';
            performanceResults.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            performanceResults.style.color = '#fff';
            performanceResults.style.fontFamily = 'monospace';
            performanceResults.style.fontSize = '12px';
            performanceResults.style.zIndex = '9999';
            
            performanceResults.innerHTML = `
                <div style="max-width: 800px; margin: 0 auto;">
                    <h3 style="color: #fff; margin: 0 0 10px;">Métricas de Performance</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li>⏰ Tempo de Carregamento Total: ${pageLoadTime.toFixed(0)}ms</li>
                        <li>🎯 DOMContentLoaded: ${domContentLoaded.toFixed(0)}ms</li>
                        <li>🎨 First Paint: ${typeof firstPaint === 'number' ? firstPaint.toFixed(0) + 'ms' : firstPaint}</li>
                        <li>📦 Tamanho Total de Recursos: ${totalResourcesSize.toFixed(2)}KB</li>
                        <li>🌐 Recursos Externos: ${externalResources.length} (${externalResourcesSize.toFixed(2)}KB)</li>
                    </ul>
                    <div style="margin-top: 10px; text-align: right;">
                        <button onclick="this.parentNode.parentNode.parentNode.remove()" style="background: #333; color: #fff; border: none; padding: 5px 10px; cursor: pointer;">Fechar</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(performanceResults);
        });
    </script>
    <?php endif; ?>
</body>
</html>