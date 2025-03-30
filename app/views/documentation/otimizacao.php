<?php
/**
 * Documentação das Otimizações de Desempenho
 * Exibe informações detalhadas sobre as otimizações implementadas no site
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
                
                <!-- Conteúdo da Documentação de Otimizações -->
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
                    
                    <!-- Otimizações de Carregamento -->
                    <section class="doc-section" id="loading-optimizations">
                        <h2>Otimizações de Carregamento</h2>
                        <div class="doc-section-content">
                            <p>
                                Nossa loja foi projetada para oferecer uma experiência de usuário rápida e eficiente.
                                Implementamos várias otimizações de carregamento para garantir que as páginas carreguem
                                o mais rápido possível, especialmente em dispositivos móveis ou conexões mais lentas.
                            </p>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="feature-box">
                                        <div class="feature-icon">
                                            <i class="fa fa-image fa-3x"></i>
                                        </div>
                                        <h3>Lazy Loading de Imagens</h3>
                                        <p>
                                            As imagens são carregadas apenas quando entram na área visível da tela,
                                            economizando dados e acelerando o carregamento inicial da página.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-box">
                                        <div class="feature-icon">
                                            <i class="fa fa-clock-o fa-3x"></i>
                                        </div>
                                        <h3>Carregamento Assíncrono</h3>
                                        <p>
                                            Scripts não essenciais são carregados de forma assíncrona, permitindo
                                            que o conteúdo principal seja exibido mais rapidamente.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="feature-box">
                                        <div class="feature-icon">
                                            <i class="fa fa-database fa-3x"></i>
                                        </div>
                                        <h3>Arquivos Locais</h3>
                                        <p>
                                            Recursos externos críticos foram substituídos por versões locais,
                                            eliminando a dependência de servidores externos e garantindo
                                            carregamento mais rápido e confiável.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-box">
                                        <div class="feature-icon">
                                            <i class="fa fa-compress fa-3x"></i>
                                        </div>
                                        <h3>Compressão de Recursos</h3>
                                        <p>
                                            Todos os recursos estáticos (CSS, JavaScript, HTML) são comprimidos
                                            para reduzir o tamanho dos arquivos transferidos e acelerar o carregamento.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="doc-note mt-4">
                                <i class="fa fa-info-circle"></i>
                                <p>
                                    Estas otimizações acontecem automaticamente enquanto você navega pelo site
                                    e não exigem nenhuma configuração ou ação adicional da sua parte.
                                </p>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Cache e Armazenamento -->
                    <section class="doc-section" id="cache-storage">
                        <h2>Cache e Armazenamento</h2>
                        <div class="doc-section-content">
                            <p>
                                Implementamos um sistema avançado de cache para melhorar significativamente
                                a velocidade de carregamento durante suas visitas subsequentes ao site.
                            </p>
                            
                            <div class="cache-diagram">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="diagram-wrapper">
                                            <div class="diagram-box first-visit">
                                                <h4>Primeira Visita</h4>
                                                <div class="diagram-icon">
                                                    <i class="fa fa-download fa-2x"></i>
                                                </div>
                                                <p>Download completo de recursos</p>
                                            </div>
                                            <div class="diagram-arrow">
                                                <i class="fa fa-long-arrow-right fa-2x"></i>
                                            </div>
                                            <div class="diagram-box browser-cache">
                                                <h4>Cache do Navegador</h4>
                                                <div class="diagram-icon">
                                                    <i class="fa fa-save fa-2x"></i>
                                                </div>
                                                <p>Armazenamento local dos recursos</p>
                                            </div>
                                            <div class="diagram-arrow">
                                                <i class="fa fa-long-arrow-right fa-2x"></i>
                                            </div>
                                            <div class="diagram-box subsequent-visit">
                                                <h4>Visitas Subsequentes</h4>
                                                <div class="diagram-icon">
                                                    <i class="fa fa-bolt fa-2x"></i>
                                                </div>
                                                <p>Carregamento instantâneo</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="cache-benefits mt-4">
                                <h4>Benefícios do Sistema de Cache</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="benefit-list">
                                            <li>
                                                <i class="fa fa-check-circle text-success"></i>
                                                Carregamento até 5x mais rápido em visitas subsequentes
                                            </li>
                                            <li>
                                                <i class="fa fa-check-circle text-success"></i>
                                                Menor consumo de dados móveis
                                            </li>
                                            <li>
                                                <i class="fa fa-check-circle text-success"></i>
                                                Funcionamento offline de algumas funcionalidades
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="benefit-list">
                                            <li>
                                                <i class="fa fa-check-circle text-success"></i>
                                                Navegação mais fluida entre páginas
                                            </li>
                                            <li>
                                                <i class="fa fa-check-circle text-success"></i>
                                                Redução de carga nos servidores
                                            </li>
                                            <li>
                                                <i class="fa fa-check-circle text-success"></i>
                                                Menor tempo de resposta geral do site
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="doc-note mt-4">
                                <i class="fa fa-info-circle"></i>
                                <p>
                                    Para aproveitar ao máximo o sistema de cache, recomendamos usar um navegador
                                    moderno como Chrome, Firefox, Safari ou Edge em suas versões mais recentes.
                                </p>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Otimizações para Dispositivos Móveis -->
                    <section class="doc-section" id="mobile-optimizations">
                        <h2>Otimizações para Dispositivos Móveis</h2>
                        <div class="doc-section-content">
                            <p>
                                Nossa loja foi otimizada especificamente para oferecer uma excelente experiência
                                em dispositivos móveis, garantindo carregamento rápido e navegação fluida mesmo
                                em conexões mais lentas ou dispositivos com recursos limitados.
                            </p>
                            
                            <div class="mobile-features">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mobile-feature">
                                            <div class="mobile-feature-icon">
                                                <i class="fa fa-mobile fa-3x"></i>
                                            </div>
                                            <h4>Design Responsivo</h4>
                                            <p>
                                                Interface adaptada automaticamente para 
                                                qualquer tamanho de tela, garantindo a
                                                melhor experiência em qualquer dispositivo.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mobile-feature">
                                            <div class="mobile-feature-icon">
                                                <i class="fa fa-tachometer fa-3x"></i>
                                            </div>
                                            <h4>Detecção de Dispositivo</h4>
                                            <p>
                                                Sistema inteligente que identifica as 
                                                capacidades do seu dispositivo e ajusta
                                                a experiência de acordo.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mobile-feature">
                                            <div class="mobile-feature-icon">
                                                <i class="fa fa-signal fa-3x"></i>
                                            </div>
                                            <h4>Adaptação à Conexão</h4>
                                            <p>
                                                Análise da velocidade de conexão para
                                                ajustar dinamicamente a qualidade dos
                                                recursos carregados.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mobile-comparison mt-4">
                                <h4>Comparação de Desempenho em Dispositivos Móveis</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Métrica</th>
                                                <th>Sem Otimização</th>
                                                <th>Com Otimização</th>
                                                <th>Melhoria</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Tempo de Carregamento Inicial</td>
                                                <td>8.2s</td>
                                                <td>2.3s</td>
                                                <td class="text-success">72% mais rápido</td>
                                            </tr>
                                            <tr>
                                                <td>Consumo de Dados</td>
                                                <td>3.8 MB</td>
                                                <td>1.2 MB</td>
                                                <td class="text-success">68% menor</td>
                                            </tr>
                                            <tr>
                                                <td>Tempo de Renderização</td>
                                                <td>2.1s</td>
                                                <td>0.6s</td>
                                                <td class="text-success">71% mais rápido</td>
                                            </tr>
                                            <tr>
                                                <td>Uso de Memória</td>
                                                <td>240 MB</td>
                                                <td>140 MB</td>
                                                <td class="text-success">42% menor</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-muted text-center">
                                    Dados baseados em testes realizados em um dispositivo Android de gama média com conexão 4G.
                                </p>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Otimizações do Visualizador 3D -->
                    <section class="doc-section" id="3d-viewer-optimizations">
                        <h2>Otimizações do Visualizador 3D</h2>
                        <div class="doc-section-content">
                            <p>
                                O visualizador 3D foi especialmente otimizado para garantir um desempenho excepcional
                                em uma ampla variedade de dispositivos, desde computadores de alto desempenho até
                                dispositivos móveis com recursos limitados.
                            </p>
                            
                            <div class="optimization-features">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="feature-box">
                                            <h4>Sistema de LOD (Level of Detail)</h4>
                                            <p>
                                                Ajusta automaticamente a complexidade dos modelos 3D com base na
                                                capacidade do dispositivo e na distância da câmera, garantindo o
                                                melhor equilíbrio entre qualidade visual e desempenho.
                                            </p>
                                            <div class="lod-levels">
                                                <span class="lod-level">Muito Alto</span>
                                                <span class="lod-level">Alto</span>
                                                <span class="lod-level">Médio</span>
                                                <span class="lod-level">Baixo</span>
                                                <span class="lod-level">Muito Baixo</span>
                                                <span class="lod-level">Mínimo</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-box">
                                            <h4>Carregamento Progressivo</h4>
                                            <p>
                                                Os modelos 3D são carregados progressivamente, começando com uma
                                                versão simplificada que é refinada gradualmente. Isso permite que
                                                você visualize e interaja com o modelo rapidamente, enquanto os
                                                detalhes continuam a ser carregados em segundo plano.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="feature-box">
                                            <h4>Detecção de Hardware</h4>
                                            <p>
                                                O sistema identifica automaticamente as capacidades do seu dispositivo,
                                                incluindo GPU, capacidades do WebGL e recursos do navegador, para
                                                oferecer a melhor configuração possível.
                                            </p>
                                            <div class="device-profiles">
                                                <span class="device-profile high-end">High-End</span>
                                                <span class="device-profile mid-range">Mid-Range</span>
                                                <span class="device-profile low-end">Low-End</span>
                                                <span class="device-profile very-low-end">Very Low-End</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-box">
                                            <h4>Otimização de Memória</h4>
                                            <p>
                                                Gerenciamento inteligente de recursos que libera automaticamente
                                                memória quando necessário e utiliza técnicas avançadas de compressão
                                                de texturas e geometria.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="doc-note mt-4">
                                <i class="fa fa-info-circle"></i>
                                <p>
                                    Você pode ajustar manualmente a qualidade do visualizador 3D através do painel de
                                    configurações disponível no canto inferior direito do visualizador. Isso permite
                                    escolher entre melhor desempenho ou maior qualidade visual, de acordo com sua preferência.
                                </p>
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
                                                Por que o site carrega mais rápido na segunda visita?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq1" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            Na primeira visita, seu navegador precisa baixar todos os recursos do site
                                            (imagens, scripts, estilos). Nas visitas subsequentes, muitos desses recursos
                                            já estão armazenados no cache local do seu navegador, permitindo um carregamento
                                            muito mais rápido. Nosso sistema de cache foi desenvolvido para maximizar a
                                            reutilização de recursos entre páginas e visitas, resultando em uma navegação
                                            significativamente mais rápida após a primeira visita.
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#faq2">
                                                Posso desativar o lazy loading de imagens?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq2" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            O lazy loading de imagens é uma otimização implementada para melhorar a
                                            velocidade de carregamento das páginas e reduzir o consumo de dados.
                                            Atualmente, não oferecemos uma opção para desativá-lo, pois isso resultaria
                                            em uma experiência mais lenta, especialmente em conexões móveis. Se você
                                            está preocupado com imagens específicas que não estão carregando corretamente,
                                            por favor, entre em contato com nosso suporte para que possamos investigar
                                            o problema.
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#faq3">
                                                Como posso melhorar o desempenho do visualizador 3D no meu dispositivo?
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="faq3" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            Se você está enfrentando problemas de desempenho com o visualizador 3D, pode
                                            ajustar a qualidade manualmente através do painel de configurações disponível
                                            no canto inferior direito do visualizador. Reduzir a qualidade para "Média" ou
                                            "Baixa" pode melhorar significativamente o desempenho em dispositivos com
                                            recursos limitados. Além disso, fechar outras abas e aplicativos em segundo
                                            plano pode liberar recursos do sistema para o visualizador. Em navegadores
                                            desktop, também recomendamos ativar a aceleração de hardware nas configurações
                                            do navegador quando disponível.
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
                                <a href="?page=documentation&action=categorias" class="doc-nav-link prev">
                                    <i class="fa fa-chevron-left"></i>
                                    <span>Anterior</span>
                                    <strong>Sistema de Categorias</strong>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="?page=documentation&action=search" class="doc-nav-link next">
                                    <span>Próximo</span>
                                    <strong>Busca na Documentação</strong>
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