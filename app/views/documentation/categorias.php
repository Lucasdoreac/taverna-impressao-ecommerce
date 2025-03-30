<?php
/**
 * Documentação do Sistema de Categorias
 * Exibe informações detalhadas sobre como utilizar o sistema de categorias
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
                
                <!-- Conteúdo da Documentação do Sistema de Categorias -->
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
                    
                    <!-- Estrutura da Navegação de Categorias -->
                    <section class="doc-section" id="category-structure">
                        <h2>Estrutura de Navegação de Categorias</h2>
                        <div class="doc-section-content">
                            <div class="category-structure-diagram">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="category-diagram">
                                            <div class="category-node main-category">
                                                <span>Categoria Principal</span>
                                                <div class="category-children">
                                                    <div class="category-node sub-category">
                                                        <span>Subcategoria 1</span>
                                                        <div class="category-children">
                                                            <div class="category-node sub-sub-category">
                                                                <span>Subcategoria 1.1</span>
                                                            </div>
                                                            <div class="category-node sub-sub-category">
                                                                <span>Subcategoria 1.2</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="category-node sub-category">
                                                        <span>Subcategoria 2</span>
                                                    </div>
                                                    <div class="category-node sub-category">
                                                        <span>Subcategoria 3</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-center text-muted">
                                    Exemplo da estrutura hierárquica de categorias do sistema
                                </p>
                            </div>
                            
                            <div class="doc-note">
                                <i class="fa fa-info-circle"></i>
                                <p>As categorias podem ter múltiplos níveis, permitindo uma organização detalhada dos produtos. Você sempre pode ver o caminho completo da categoria atual através do "breadcrumb" (navegação em migalhas de pão) no topo da página.</p>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Filtros e Ordenação -->
                    <section class="doc-section" id="filters-sorting">
                        <h2>Filtros e Ordenação</h2>
                        <div class="doc-section-content">
                            <p>
                                Nosso sistema de categorias oferece ferramentas poderosas para filtrar e ordenar os produtos,
                                ajudando você a encontrar exatamente o que está procurando.
                            </p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="feature-block filter-block">
                                        <h3>Opções de Filtro</h3>
                                        <ul>
                                            <li><strong>Disponibilidade:</strong> Filtre entre produtos em pronta entrega ou sob encomenda</li>
                                            <li><strong>Preço:</strong> Defina um intervalo de preço para os produtos</li>
                                            <li><strong>Tempo de Impressão:</strong> Filtre por tempo estimado de produção</li>
                                            <li><strong>Tipo de Filamento:</strong> Escolha o material de impressão</li>
                                            <li><strong>Personalizável:</strong> Exiba apenas produtos que podem ser personalizados</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-block sorting-block">
                                        <h3>Opções de Ordenação</h3>
                                        <ul>
                                            <li><strong>Relevância:</strong> Produtos mais relevantes primeiro</li>
                                            <li><strong>Preço (Menor-Maior):</strong> Do mais barato ao mais caro</li>
                                            <li><strong>Preço (Maior-Menor):</strong> Do mais caro ao mais barato</li>
                                            <li><strong>Mais Recentes:</strong> Produtos adicionados mais recentemente</li>
                                            <li><strong>Mais Populares:</strong> Produtos com mais vendas</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="filter-example">
                                <h4>Como Aplicar Filtros:</h4>
                                <ol>
                                    <li>Navegue até a categoria desejada</li>
                                    <li>Utilize o painel de filtros à esquerda da tela</li>
                                    <li>Selecione as opções desejadas</li>
                                    <li>Clique em "Aplicar Filtros" para atualizar a lista de produtos</li>
                                </ol>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Breadcrumb Navigation -->
                    <section class="doc-section" id="breadcrumb">
                        <h2>Navegação com Breadcrumb</h2>
                        <div class="doc-section-content">
                            <p>
                                A navegação por "breadcrumb" (migalhas de pão) permite que você veja e navegue facilmente
                                pelo caminho hierárquico da categoria atual. Isso ajuda a entender onde você está na estrutura
                                do site e facilita navegar para níveis superiores.
                            </p>
                            
                            <div class="breadcrumb-example">
                                <div class="example-breadcrumb">
                                    <ol class="breadcrumb">
                                        <li><a href="#">Início</a></li>
                                        <li><a href="#">Miniaturas</a></li>
                                        <li><a href="#">Personagens</a></li>
                                        <li class="active">Heróis</li>
                                    </ol>
                                </div>
                                
                                <p class="text-center text-muted">
                                    Exemplo de navegação breadcrumb para a categoria "Heróis"
                                </p>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="feature-box">
                                        <h4><i class="fa fa-check-circle text-success"></i> Vantagens do Breadcrumb</h4>
                                        <ul>
                                            <li>Mostra exatamente onde você está na hierarquia do site</li>
                                            <li>Permite voltar facilmente para categorias superiores</li>
                                            <li>Facilita a navegação entre diferentes níveis de categorias</li>
                                            <li>Proporciona contexto sobre a organização dos produtos</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-box">
                                        <h4><i class="fa fa-lightbulb-o text-warning"></i> Dica de Uso</h4>
                                        <p>
                                            Use o breadcrumb para navegar para categorias mais amplas quando quiser
                                            expandir suas opções de produtos. Por exemplo, se você está vendo produtos
                                            na categoria "Heróis" mas quer ver todos os tipos de personagens, basta
                                            clicar em "Personagens" no breadcrumb.
                                        </p>
                                    </div>
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
                                                Como encontro produtos específicos em uma categoria?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq1" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            Para encontrar produtos específicos dentro de uma categoria, você pode utilizar
                                            as ferramentas de filtro disponíveis no painel lateral. Defina características
                                            como faixa de preço, disponibilidade ou tipo de material. Adicionalmente, você
                                            pode usar a funcionalidade de ordenação para listar produtos por relevância,
                                            preço ou data de adição. Se souber exatamente o que procura, utilize a barra
                                            de busca no topo da página.
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#faq2">
                                                Qual a diferença entre categorias e subcategorias?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq2" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            As categorias principais representam divisões amplas de produtos, enquanto
                                            subcategorias oferecem classificações mais específicas dentro dessas divisões.
                                            Por exemplo, "Miniaturas" pode ser uma categoria principal, com subcategorias
                                            como "Personagens", "Monstros" e "Cenários". A estrutura hierárquica permite
                                            navegar do geral para o específico, ajudando a encontrar exatamente o que
                                            você procura de forma intuitiva.
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#faq3">
                                                Posso ver produtos de múltiplas subcategorias ao mesmo tempo?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq3" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            Sim, para visualizar produtos de múltiplas subcategorias simultaneamente,
                                            navegue até a categoria pai que contém todas as subcategorias de interesse.
                                            Por exemplo, se você quer ver todos os tipos de "Personagens" ao invés de
                                            apenas "Heróis", basta clicar em "Personagens" no breadcrumb ou no menu
                                            lateral de categorias. Isso mostrará produtos de todas as subcategorias
                                            sob "Personagens".
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
                                <a href="?page=documentation&action=visualizador3d" class="doc-nav-link prev">
                                    <i class="fa fa-chevron-left"></i>
                                    <span>Anterior</span>
                                    <strong>Visualizador 3D</strong>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="?page=documentation&action=otimizacao" class="doc-nav-link next">
                                    <span>Próximo</span>
                                    <strong>Otimizações de Desempenho</strong>
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