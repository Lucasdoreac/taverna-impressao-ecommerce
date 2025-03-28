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
                <img src="<?= BASE_URL ?>assets/images/payment-methods.png" alt="Formas de Pagamento" class="payment-methods">
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>