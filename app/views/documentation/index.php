<?php
/**
 * Página principal da documentação para usuários
 * Exibe uma visão geral das funcionalidades disponíveis
 * 
 * @version 1.0
 */

// Incluir o header geral do site
require_once 'app/views/partials/header.php';
?>

<div class="documentation-container">
    <div class="container">
        <div class="row">
            <!-- Sidebar de Navegação -->
            <div class="col-md-3">
                <?php require_once 'app/views/partials/documentation_sidebar.php'; ?>
            </div>
            
            <!-- Conteúdo Principal -->
            <div class="col-md-9">
                <!-- Header da Documentação -->
                <?php require_once 'app/views/partials/documentation_header.php'; ?>
                
                <!-- Conteúdo da Visão Geral -->
                <div class="doc-content">
                    <!-- Boas-vindas -->
                    <section class="doc-section" id="welcome">
                        <h2><?php echo htmlspecialchars($data['overview']['welcome']['title']); ?></h2>
                        <div class="doc-section-content">
                            <p><?php echo htmlspecialchars($data['overview']['welcome']['content']); ?></p>
                        </div>
                    </section>
                    
                    <!-- Destaques da Plataforma -->
                    <section class="doc-section" id="highlights">
                        <h2><?php echo htmlspecialchars($data['overview']['highlights']['title']); ?></h2>
                        <div class="doc-section-content">
                            <div class="row">
                                <?php foreach ($data['sections'] as $sectionKey => $section): ?>
                                    <?php if ($sectionKey != 'index' && $sectionKey != 'search'): ?>
                                        <div class="col-md-4 col-sm-6">
                                            <div class="feature-box">
                                                <div class="feature-icon">
                                                    <i class="fa <?php echo htmlspecialchars($section['icon']); ?> fa-3x"></i>
                                                </div>
                                                <h3 class="feature-title">
                                                    <?php echo htmlspecialchars($section['title']); ?>
                                                </h3>
                                                <p class="feature-description">
                                                    <?php echo htmlspecialchars($section['description']); ?>
                                                </p>
                                                <a href="<?php echo htmlspecialchars($section['url']); ?>" class="btn btn-sm btn-primary">
                                                    Saiba Mais
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Guia de Início Rápido -->
                    <section class="doc-section" id="quickstart">
                        <h2><?php echo htmlspecialchars($data['overview']['quickstart']['title']); ?></h2>
                        <div class="doc-section-content">
                            <div class="quick-start-guide">
                                <ol class="steps-list">
                                    <?php foreach ($data['overview']['quickstart']['steps'] as $index => $step): ?>
                                        <li class="step-item">
                                            <div class="step-number"><?php echo $index + 1; ?></div>
                                            <div class="step-content">
                                                <?php echo htmlspecialchars($step); ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Lista de Recursos Destacados -->
                    <section class="doc-section" id="featured-resources">
                        <h2>Recursos em Destaque</h2>
                        <div class="doc-section-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="resource-card">
                                        <img src="public/assets/images/docs/visualizador-3d-preview.jpg" alt="Visualizador 3D" class="img-responsive">
                                        <div class="resource-card-body">
                                            <h3>Visualizador 3D</h3>
                                            <p>Explore e personalize modelos 3D antes de comprar</p>
                                            <a href="?page=documentation&action=visualizador3d" class="btn btn-info btn-sm">Ver Documentação</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="resource-card">
                                        <img src="public/assets/images/docs/categorias-preview.jpg" alt="Sistema de Categorias" class="img-responsive">
                                        <div class="resource-card-body">
                                            <h3>Sistema de Categorias</h3>
                                            <p>Navegue facilmente por nossa ampla gama de produtos</p>
                                            <a href="?page=documentation&action=categorias" class="btn btn-info btn-sm">Ver Documentação</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Seção de Suporte -->
                    <section class="doc-section" id="support">
                        <h2>Precisa de Ajuda?</h2>
                        <div class="doc-section-content">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="support-option">
                                        <i class="fa fa-search fa-3x"></i>
                                        <h3>Busca na Documentação</h3>
                                        <p>Procure informações específicas usando nossa ferramenta de busca</p>
                                        <a href="?page=documentation&action=search" class="btn btn-default">Buscar</a>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="support-option">
                                        <i class="fa fa-envelope fa-3x"></i>
                                        <h3>Contato por Email</h3>
                                        <p>Entre em contato com nossa equipe de suporte por email</p>
                                        <a href="?page=contact" class="btn btn-default">Contatar</a>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="support-option">
                                        <i class="fa fa-comments fa-3x"></i>
                                        <h3>Chat ao Vivo</h3>
                                        <p>Converse em tempo real com um representante durante o horário comercial</p>
                                        <button class="btn btn-default" id="liveChatBtn">Iniciar Chat</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir o footer geral do site
require_once 'app/views/partials/footer.php';
?>
