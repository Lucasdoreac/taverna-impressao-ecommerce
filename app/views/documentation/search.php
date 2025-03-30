<?php
/**
 * Página de Busca na Documentação
 * Permite aos usuários buscar informações específicas na documentação
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
                
                <!-- Conteúdo da Busca -->
                <div class="doc-content">
                    <section class="doc-section" id="search">
                        <h2>Busca na Documentação</h2>
                        
                        <div class="doc-section-content">
                            <!-- Formulário de Busca -->
                            <div class="search-form-container">
                                <form action="?page=documentation&action=search" method="GET" class="search-form">
                                    <input type="hidden" name="page" value="documentation">
                                    <input type="hidden" name="action" value="search">
                                    <div class="input-group input-group-lg">
                                        <input type="text" name="q" class="form-control" placeholder="Buscar na documentação..." 
                                               value="<?php echo htmlspecialchars($data['query'] ?? ''); ?>">
                                        <span class="input-group-btn">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fa fa-search"></i> Buscar
                                            </button>
                                        </span>
                                    </div>
                                </form>
                                
                                <?php if (!empty($data['query'])): ?>
                                    <p class="search-summary">
                                        Resultados da busca por "<strong><?php echo htmlspecialchars($data['query']); ?></strong>" 
                                        (<?php echo count($data['results']); ?> resultados)
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Resultados da Busca -->
                            <?php if (!empty($data['query'])): ?>
                                <?php if (count($data['results']) > 0): ?>
                                    <div class="search-results">
                                        <?php foreach ($data['results'] as $result): ?>
                                            <div class="search-result-item">
                                                <h3>
                                                    <a href="<?php echo htmlspecialchars($result['url']); ?>">
                                                        <?php echo htmlspecialchars($result['title']); ?>
                                                    </a>
                                                </h3>
                                                <p class="result-section">
                                                    <span class="label label-info">
                                                        <?php echo htmlspecialchars($result['section']); ?>
                                                    </span>
                                                </p>
                                                <p class="result-excerpt">
                                                    <?php echo htmlspecialchars($result['excerpt']); ?>
                                                </p>
                                                <a href="<?php echo htmlspecialchars($result['url']); ?>" class="btn btn-sm btn-default">
                                                    Ver mais <i class="fa fa-arrow-right"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="search-no-results text-center">
                                        <div class="no-results-icon">
                                            <i class="fa fa-search fa-5x text-muted"></i>
                                        </div>
                                        <h3>Nenhum resultado encontrado</h3>
                                        <p>
                                            Não encontramos resultados para sua busca "<strong><?php echo htmlspecialchars($data['query']); ?></strong>".
                                        </p>
                                        <div class="search-suggestions">
                                            <h4>Sugestões:</h4>
                                            <ul>
                                                <li>Verifique a ortografia das palavras-chave</li>
                                                <li>Tente termos mais gerais</li>
                                                <li>Tente termos relacionados</li>
                                                <li>Utilize termos em português</li>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="search-intro">
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <div class="search-tip-icon">
                                                <i class="fa fa-lightbulb-o fa-3x"></i>
                                            </div>
                                            <h4>Dicas de Busca</h4>
                                            <p>
                                                Use termos específicos para encontrar resultados mais relevantes.
                                                Por exemplo, "visualizador 3D" em vez de apenas "visualizador".
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="search-tip-icon">
                                                <i class="fa fa-list fa-3x"></i>
                                            </div>
                                            <h4>Tópicos Populares</h4>
                                            <ul class="list-unstyled">
                                                <li><a href="?page=documentation&action=search&q=visualizador">Visualizador 3D</a></li>
                                                <li><a href="?page=documentation&action=search&q=categorias">Categorias</a></li>
                                                <li><a href="?page=documentation&action=search&q=otimização">Otimizações</a></li>
                                                <li><a href="?page=documentation&action=search&q=filtro">Filtros</a></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="search-tip-icon">
                                                <i class="fa fa-question-circle fa-3x"></i>
                                            </div>
                                            <h4>Precisa de ajuda?</h4>
                                            <p>
                                                Se não conseguir encontrar o que procura, entre em contato com
                                                nosso suporte através da página de contato.
                                            </p>
                                            <a href="?page=contact" class="btn btn-primary btn-sm">
                                                Contatar Suporte
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    
                    <!-- Navegação entre Páginas -->
                    <div class="doc-nav">
                        <div class="row">
                            <div class="col-sm-6">
                                <a href="?page=documentation&action=otimizacao" class="doc-nav-link prev">
                                    <i class="fa fa-chevron-left"></i>
                                    <span>Anterior</span>
                                    <strong>Otimizações de Desempenho</strong>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="?page=documentation&action=index" class="doc-nav-link next">
                                    <span>Voltar para</span>
                                    <strong>Visão Geral</strong>
                                    <i class="fa fa-home"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir o footer geral do site
require_once 'app/views/partials/footer.php';
?>