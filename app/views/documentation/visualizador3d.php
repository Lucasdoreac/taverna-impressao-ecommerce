<?php
/**
 * Documentação do Visualizador 3D
 * Exibe informações detalhadas sobre como utilizar o visualizador 3D
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
                
                <!-- Conteúdo da Documentação do Visualizador 3D -->
                <div class="doc-content">
                    <?php foreach ($data['content'] as $sectionKey => $section): ?>
                        <section class="doc-section" id="<?php echo htmlspecialchars($sectionKey); ?>">
                            <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                            
                            <div class="doc-section-content">
                                <!-- Conteúdo principal -->
                                <p><?php echo htmlspecialchars($section['content']); ?></p>
                                
                                <!-- Imagem (se existir) -->
                                <?php if (isset($section['image'])): ?>
                                    <div class="doc-image">
                                        <img src="<?php echo htmlspecialchars($section['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($section['imageAlt'] ?? $section['title']); ?>" 
                                             class="img-responsive">
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Lista de itens (se existir) -->
                                <?php if (isset($section['list'])): ?>
                                    <ul class="doc-list">
                                        <?php foreach ($section['list'] as $item): ?>
                                            <li><?php echo htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                
                                <!-- Nota (se existir) -->
                                <?php if (isset($section['note'])): ?>
                                    <div class="doc-note">
                                        <i class="fa fa-info-circle"></i>
                                        <p><?php echo htmlspecialchars($section['note']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                    
                    <!-- Seção de Vídeo Tutorial -->
                    <section class="doc-section" id="video-tutorial">
                        <h2>Vídeo Tutorial</h2>
                        <div class="doc-section-content">
                            <div class="embed-responsive embed-responsive-16by9">
                                <div class="video-placeholder">
                                    <div class="video-placeholder-content">
                                        <i class="fa fa-play-circle fa-5x"></i>
                                        <h3>Tutorial do Visualizador 3D</h3>
                                        <p>Clique para assistir ao vídeo explicativo</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted text-center">
                                Este vídeo demonstra como utilizar todas as funcionalidades do visualizador 3D de forma prática.
                            </p>
                        </div>
                    </section>
                    
                    <!-- Exemplo Interativo -->
                    <section class="doc-section" id="interactive-example">
                        <h2>Exemplo Interativo</h2>
                        <div class="doc-section-content">
                            <div class="interactive-example">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="model-viewer-placeholder">
                                            <div class="model-viewer-placeholder-content">
                                                <i class="fa fa-cube fa-4x"></i>
                                                <h3>Visualizador 3D</h3>
                                                <p>Exemplo interativo do visualizador</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="controls-panel">
                                            <h4>Controles</h4>
                                            <div class="form-group">
                                                <label>Rotação:</label>
                                                <div class="rotation-controls">
                                                    <button class="btn btn-sm btn-default">
                                                        <i class="fa fa-arrow-left"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-default">
                                                        <i class="fa fa-arrow-up"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-default">
                                                        <i class="fa fa-arrow-down"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-default">
                                                        <i class="fa fa-arrow-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Zoom:</label>
                                                <div class="zoom-controls">
                                                    <button class="btn btn-sm btn-default">
                                                        <i class="fa fa-minus"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-default">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Qualidade:</label>
                                                <select class="form-control">
                                                    <option>Muito Alta</option>
                                                    <option selected>Alta</option>
                                                    <option>Média</option>
                                                    <option>Baixa</option>
                                                    <option>Muito Baixa</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <button class="btn btn-primary btn-block">
                                                    <i class="fa fa-refresh"></i> Resetar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-3">
                                    <i class="fa fa-info-circle"></i>
                                    Este é apenas um exemplo demonstrativo. Para testar o visualizador real, acesse a página de um produto que inclua modelo 3D.
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Perguntas Frequentes -->
                    <section class="doc-section" id="faq">
                        <h2>Perguntas Frequentes</h2>
                        <div class="doc-section-content">
                            <div class="panel-group" id="accordion">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#faq1">
                                                O visualizador 3D funciona em dispositivos móveis?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq1" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            Sim, o visualizador 3D foi projetado para funcionar em dispositivos móveis. 
                                            O sistema detecta automaticamente as capacidades do seu dispositivo e ajusta 
                                            as configurações para garantir a melhor experiência possível. Em dispositivos 
                                            mais limitados, a qualidade visual pode ser reduzida para manter um desempenho fluido.
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#faq2">
                                                Por que o visualizador não carrega em meu navegador?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq2" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            O visualizador 3D requer suporte a WebGL, que pode não estar disponível em navegadores 
                                            antigos ou em alguns dispositivos. Verifique se seu navegador está atualizado e se o WebGL 
                                            está habilitado nas configurações. Recomendamos o uso de Chrome, Firefox, ou Safari em suas 
                                            versões mais recentes para a melhor experiência.
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#faq3">
                                                Posso salvar as personalizações que fiz em um modelo 3D?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq3" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            Sim, todas as personalizações feitas são automaticamente salvas quando você adiciona o 
                                            produto ao carrinho. Você pode revisar e modificar essas personalizações até finalizar 
                                            a compra. Note que as personalizações são específicas para cada sessão de navegação e 
                                            não são persistidas entre diferentes visitas ao site a menos que você tenha uma conta e 
                                            esteja logado.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Navegação entre Páginas -->
                    <div class="doc-nav">
                        <div class="row">
                            <div class="col-sm-6">
                                <a href="?page=documentation&action=index" class="doc-nav-link prev">
                                    <i class="fa fa-chevron-left"></i>
                                    <span>Anterior</span>
                                    <strong>Visão Geral</strong>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="?page=documentation&action=categorias" class="doc-nav-link next">
                                    <span>Próximo</span>
                                    <strong>Sistema de Categorias</strong>
                                    <i class="fa fa-chevron-right"></i>
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
