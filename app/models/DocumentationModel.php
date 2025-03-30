<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Modelo de Documentação para Usuários
 * Responsável por gerenciar o conteúdo da documentação
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class DocumentationModel {
    private $contentPath;
    private $sectionsData;
    
    /**
     * Construtor
     * Inicializa as propriedades e caminhos
     */
    public function __construct() {
        $this->contentPath = 'app/data/documentation/';
        $this->initSections();
    }
    
    /**
     * Inicializa as seções da documentação
     * Define a estrutura e hierarquia do conteúdo
     */
    private function initSections() {
        $this->sectionsData = [
            'index' => [
                'title' => 'Visão Geral',
                'url' => '?page=documentation&action=index',
                'icon' => 'fa-home',
                'description' => 'Visão geral da documentação e introdução às funcionalidades do sistema'
            ],
            'visualizador3d' => [
                'title' => 'Visualizador 3D',
                'url' => '?page=documentation&action=visualizador3d',
                'icon' => 'fa-cube',
                'description' => 'Como utilizar o visualizador 3D para produtos personalizáveis',
                'subsections' => [
                    'introducao' => 'Introdução ao Visualizador 3D',
                    'controles' => 'Controles e Navegação',
                    'customizacao' => 'Personalização de Modelos',
                    'performance' => 'Otimizações de Performance',
                    'troubleshooting' => 'Solução de Problemas Comuns'
                ]
            ],
            'categorias' => [
                'title' => 'Sistema de Categorias',
                'url' => '?page=documentation&action=categorias',
                'icon' => 'fa-sitemap',
                'description' => 'Como navegar e utilizar o sistema de categorias hierárquicas',
                'subsections' => [
                    'navegacao' => 'Navegação por Categorias',
                    'filtros' => 'Filtros Avançados',
                    'ordenacao' => 'Opções de Ordenação',
                    'breadcrumb' => 'Navegação por Breadcrumb'
                ]
            ],
            'otimizacao' => [
                'title' => 'Otimizações de Desempenho',
                'url' => '?page=documentation&action=otimizacao',
                'icon' => 'fa-bolt',
                'description' => 'Melhorias de desempenho e suas vantagens para o usuário',
                'subsections' => [
                    'lazyloading' => 'Carregamento Otimizado de Imagens',
                    'cache' => 'Sistema de Cache',
                    'mobile' => 'Otimizações para Dispositivos Móveis'
                ]
            ],
            'search' => [
                'title' => 'Busca na Documentação',
                'url' => '?page=documentation&action=search',
                'icon' => 'fa-search',
                'description' => 'Buscar conteúdo específico na documentação'
            ]
        ];
    }
    
    /**
     * Retorna todas as seções de documentação
     * 
     * @return array Informações de todas as seções
     */
    public function getSections() {
        return $this->sectionsData;
    }
    
    /**
     * Retorna a visão geral da documentação
     * 
     * @return array Dados para a página inicial da documentação
     */
    public function getOverview() {
        // Estrutura da visão geral
        $overview = [
            'welcome' => [
                'title' => 'Bem-vindo à Documentação da Taverna da Impressão',
                'content' => 'Esta documentação foi criada para ajudar você a aproveitar ao máximo nossa plataforma de e-commerce especializada em produtos impressos em 3D para RPG. Aqui você encontrará instruções detalhadas sobre como navegar, buscar, personalizar e comprar produtos.'
            ],
            'highlights' => [
                'title' => 'Destaques da Plataforma',
                'items' => [
                    'Visualizador 3D interativo para personalização de produtos',
                    'Sistema de categorias hierárquicas para navegação intuitiva',
                    'Carregamento otimizado de imagens e recursos para melhor desempenho',
                    'Interface responsiva adaptada para dispositivos móveis e desktop'
                ]
            ],
            'quickstart' => [
                'title' => 'Guia de Início Rápido',
                'steps' => [
                    'Navegue pelas categorias usando o menu lateral ou superior',
                    'Utilize os filtros para encontrar produtos específicos',
                    'Visualize produtos em 3D antes de comprá-los',
                    'Personalize produtos conforme suas preferências',
                    'Adicione ao carrinho e finalize a compra'
                ]
            ]
        ];
        
        return $overview;
    }
    
    /**
     * Retorna o conteúdo de uma seção específica
     * 
     * @param string $section Nome da seção
     * @return array Conteúdo estruturado da seção
     */
    public function getContent($section) {
        if (!isset($this->sectionsData[$section])) {
            return $this->getErrorContent('Seção não encontrada');
        }
        
        // Em um cenário real, poderíamos recuperar o conteúdo de um banco de dados
        // ou de arquivos JSON/Markdown. Para este exemplo, usaremos dados estáticos.
        
        switch ($section) {
            case 'visualizador3d':
                return $this->getVisualizador3DContent();
            case 'categorias':
                return $this->getCategoriasContent();
            case 'otimizacao':
                return $this->getOtimizacaoContent();
            default:
                return $this->getErrorContent('Conteúdo não disponível');
        }
    }
    
    /**
     * Retorna o conteúdo da documentação do Visualizador 3D
     * 
     * @return array Dados estruturados da documentação
     */
    private function getVisualizador3DContent() {
        return [
            'introducao' => [
                'title' => 'Introdução ao Visualizador 3D',
                'content' => 'O Visualizador 3D da Taverna da Impressão permite que você visualize e personalize produtos 3D antes da compra. Esta ferramenta interativa oferece uma experiência imersiva e detalhada para que você possa tomar decisões informadas sobre seus produtos.',
                'image' => 'public/assets/images/docs/visualizador-3d-overview.jpg',
                'imageAlt' => 'Visão geral do visualizador 3D'
            ],
            'controles' => [
                'title' => 'Controles e Navegação',
                'content' => 'Para navegar pelo visualizador 3D, você pode:',
                'list' => [
                    'Rotacionar o modelo: Clique e arraste com o botão esquerdo do mouse',
                    'Zoom: Use a roda do mouse ou pinça em dispositivos de toque',
                    'Mover o modelo: Clique e arraste com o botão direito do mouse ou arraste com dois dedos em dispositivos de toque',
                    'Resetar visualização: Clique no botão "Resetar câmera" abaixo do visualizador'
                ],
                'image' => 'public/assets/images/docs/visualizador-controles.jpg',
                'imageAlt' => 'Controles do visualizador 3D'
            ],
            'customizacao' => [
                'title' => 'Personalização de Modelos',
                'content' => 'Você pode personalizar os modelos 3D de várias maneiras:',
                'list' => [
                    'Cores: Selecione diferentes cores para partes específicas do modelo',
                    'Texturas: Aplique texturas predefinidas para dar aparências diferentes',
                    'Escala: Ajuste o tamanho de elementos específicos quando disponível',
                    'Acessórios: Adicione ou remova elementos opcionais do modelo'
                ],
                'image' => 'public/assets/images/docs/visualizador-customizacao.jpg',
                'imageAlt' => 'Opções de personalização'
            ],
            'performance' => [
                'title' => 'Otimizações de Performance',
                'content' => 'Para melhorar sua experiência, o visualizador 3D possui várias otimizações:',
                'list' => [
                    'Detecção automática: Identifica as capacidades do seu dispositivo e ajusta configurações',
                    'Níveis de detalhe: Adapta a complexidade do modelo à capacidade do dispositivo',
                    'Carregamento progressivo: Exibe uma versão simplificada do modelo enquanto carrega os detalhes',
                    'Configurações de qualidade: Permite ajustar manualmente a qualidade vs. performance'
                ],
                'note' => 'Para dispositivos com menor capacidade, o sistema automaticamente reduz a qualidade visual para garantir uma experiência fluida.'
            ],
            'troubleshooting' => [
                'title' => 'Solução de Problemas Comuns',
                'content' => 'Se você encontrar problemas com o visualizador 3D:',
                'list' => [
                    'Modelo não carrega: Verifique se o WebGL está ativado em seu navegador',
                    'Desempenho lento: Reduza a qualidade nas configurações do visualizador',
                    'Cores ou texturas incorretas: Limpe o cache do navegador e recarregue a página',
                    'Controles não respondem: Certifique-se de clicar dentro da área do visualizador'
                ],
                'note' => 'Se os problemas persistirem, tente utilizar outro navegador ou dispositivo. O Chrome e Firefox oferecem melhor suporte ao WebGL necessário para o visualizador 3D.'
            ]
        ];
    }
    
    /**
     * Retorna o conteúdo da documentação do Sistema de Categorias
     * 
     * @return array Dados estruturados da documentação
     */
    private function getCategoriasContent() {
        return [
            'navegacao' => [
                'title' => 'Navegação por Categorias',
                'content' => 'O sistema de categorias da Taverna da Impressão foi projetado para proporcionar uma navegação intuitiva por nossa ampla gama de produtos. As categorias são organizadas hierarquicamente, permitindo que você navegue do mais geral ao mais específico.',
                'list' => [
                    'Menu lateral: Acessível em todas as páginas, mostra categorias principais',
                    'Subcategorias: Ao selecionar uma categoria, as subcategorias relacionadas são exibidas',
                    'Navegação multinível: Permite acessar categorias em qualquer nível da hierarquia',
                    'Menu responsivo: Adapta-se a diferentes tamanhos de tela e dispositivos'
                ],
                'image' => 'public/assets/images/docs/categorias-menu.jpg',
                'imageAlt' => 'Menu de categorias'
            ],
            'filtros' => [
                'title' => 'Filtros Avançados',
                'content' => 'Além da navegação por categorias, você pode refinar sua busca usando diversos filtros:',
                'list' => [
                    'Preço: Define faixas de preço mínimo e máximo',
                    'Disponibilidade: Filtra por produtos em estoque ou pré-venda',
                    'Material: Seleciona o tipo de material de impressão 3D',
                    'Complexidade: Filtra por nível de detalhes ou complexidade do modelo',
                    'Tempo de produção: Seleciona por tempo estimado de produção'
                ],
                'note' => 'Os filtros podem ser combinados para uma busca mais precisa. Utilize o botão "Limpar filtros" para reiniciar sua pesquisa.',
                'image' => 'public/assets/images/docs/categorias-filtros.jpg',
                'imageAlt' => 'Filtros avançados'
            ],
            'ordenacao' => [
                'title' => 'Opções de Ordenação',
                'content' => 'Você pode ordenar os resultados de diversas maneiras:',
                'list' => [
                    'Relevância: Ordenação padrão baseada na popularidade e correspondência',
                    'Preço (menor-maior): Produtos ordenados do mais barato ao mais caro',
                    'Preço (maior-menor): Produtos ordenados do mais caro ao mais barato',
                    'Lançamento: Produtos mais recentes primeiro',
                    'Avaliações: Ordenado por avaliações dos clientes'
                ],
                'image' => 'public/assets/images/docs/categorias-ordenacao.jpg',
                'imageAlt' => 'Opções de ordenação'
            ],
            'breadcrumb' => [
                'title' => 'Navegação por Breadcrumb',
                'content' => 'O breadcrumb (trilha de navegação) mostra o caminho completo até a categoria atual e permite voltar a qualquer nível anterior rapidamente.',
                'list' => [
                    'Localização atual: Visualize exatamente onde você está na hierarquia de categorias',
                    'Navegação rápida: Clique em qualquer categoria anterior para voltar',
                    'Contexto completo: Veja toda a hierarquia desde a Home até a categoria atual'
                ],
                'image' => 'public/assets/images/docs/categorias-breadcrumb.jpg',
                'imageAlt' => 'Exemplo de breadcrumb'
            ]
        ];
    }
    
    /**
     * Retorna o conteúdo da documentação de Otimizações
     * 
     * @return array Dados estruturados da documentação
     */
    private function getOtimizacaoContent() {
        return [
            'lazyloading' => [
                'title' => 'Carregamento Otimizado de Imagens',
                'content' => 'A Taverna da Impressão implementa carregamento lazy (preguiçoso) para imagens, garantindo que apenas as imagens visíveis na tela sejam carregadas inicialmente. Isso proporciona:',
                'list' => [
                    'Carregamento mais rápido das páginas',
                    'Menor consumo de dados para conexões limitadas',
                    'Melhor experiência em dispositivos móveis',
                    'Menor uso de recursos do dispositivo'
                ],
                'note' => 'O carregamento das imagens acontece automaticamente conforme você rola a página, sem necessidade de intervenção.'
            ],
            'cache' => [
                'title' => 'Sistema de Cache',
                'content' => 'Utilizamos técnicas avançadas de cache para melhorar sua experiência:',
                'list' => [
                    'Recursos estáticos: Imagens, CSS e JavaScript são armazenados localmente',
                    'Conteúdo dinâmico: Produtos e categorias populares são pré-carregados',
                    'Validação inteligente: O sistema verifica automaticamente se há versões mais recentes disponíveis',
                    'Persistência: Os dados permanecem disponíveis mesmo offline (em navegadores compatíveis)'
                ],
                'note' => 'O cache é gerenciado automaticamente. Para forçar um recarregamento completo, pressione Ctrl+F5 (ou Cmd+Shift+R no Mac).'
            ],
            'mobile' => [
                'title' => 'Otimizações para Dispositivos Móveis',
                'content' => 'Nossa plataforma é totalmente responsiva e otimizada para dispositivos móveis:',
                'list' => [
                    'Interfaces adaptativas: Elementos se ajustam a diferentes tamanhos de tela',
                    'Tamanho reduzido de imagens: Versões otimizadas são servidas para dispositivos móveis',
                    'Gestos de toque: Navegação intuitiva pensada para interação por toque',
                    'Visualizador 3D adaptativo: Ajusta a complexidade de acordo com a capacidade do dispositivo'
                ],
                'note' => 'Em dispositivos com menor capacidade, algumas funções avançadas podem ter comportamento simplificado para garantir desempenho adequado.'
            ]
        ];
    }
    
    /**
     * Realiza busca no conteúdo da documentação
     * 
     * @param string $query Termo de busca
     * @return array Resultados da busca
     */
    public function searchContent($query) {
        if (empty($query)) {
            return [];
        }
        
        $results = [];
        $query = strtolower(trim($query));
        
        // Para cada seção, verificar se o conteúdo contém o termo buscado
        foreach (['visualizador3d', 'categorias', 'otimizacao'] as $section) {
            $content = $this->getContent($section);
            
            foreach ($content as $subsectionKey => $subsection) {
                // Buscar no título
                if (isset($subsection['title']) && strpos(strtolower($subsection['title']), $query) !== false) {
                    $results[] = $this->formatSearchResult($section, $subsectionKey, $subsection, 'title');
                    continue; // Evitar duplicação
                }
                
                // Buscar no conteúdo principal
                if (isset($subsection['content']) && strpos(strtolower($subsection['content']), $query) !== false) {
                    $results[] = $this->formatSearchResult($section, $subsectionKey, $subsection, 'content');
                    continue; // Evitar duplicação
                }
                
                // Buscar nos itens de lista
                if (isset($subsection['list'])) {
                    foreach ($subsection['list'] as $listItem) {
                        if (strpos(strtolower($listItem), $query) !== false) {
                            $results[] = $this->formatSearchResult($section, $subsectionKey, $subsection, 'list');
                            break; // Evitar duplicação
                        }
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Formata um resultado de busca
     * 
     * @param string $section Seção da documentação
     * @param string $subsectionKey Chave da subseção
     * @param array $subsection Dados da subseção
     * @param string $matchType Tipo de correspondência (título, conteúdo, lista)
     * @return array Resultado formatado
     */
    private function formatSearchResult($section, $subsectionKey, $subsection, $matchType) {
        $sectionTitle = $this->sectionsData[$section]['title'];
        $subsectionTitle = $subsection['title'] ?? 'Seção sem título';
        
        $url = $this->sectionsData[$section]['url'] . '#' . $subsectionKey;
        
        $excerpt = '';
        switch ($matchType) {
            case 'title':
                $excerpt = 'Correspondência no título: ' . $subsectionTitle;
                break;
            case 'content':
                $excerpt = $this->createExcerpt($subsection['content']);
                break;
            case 'list':
                $excerpt = 'Lista de itens contendo o termo buscado';
                break;
        }
        
        return [
            'section' => $sectionTitle,
            'subsection' => $subsectionTitle,
            'url' => $url,
            'excerpt' => $excerpt
        ];
    }
    
    /**
     * Cria um resumo a partir de um texto longo
     * 
     * @param string $text Texto original
     * @param int $length Tamanho máximo do resumo
     * @return string Resumo formatado
     */
    private function createExcerpt($text, $length = 150) {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $excerpt = substr($text, 0, $length);
        $lastSpace = strrpos($excerpt, ' ');
        
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }
    
    /**
     * Retorna conteúdo de erro formatado
     * 
     * @param string $message Mensagem de erro
     * @return array Conteúdo de erro formatado
     */
    private function getErrorContent($message) {
        return [
            'error' => [
                'title' => 'Erro na Documentação',
                'content' => $message,
                'suggestion' => 'Por favor, tente navegar pelo menu principal ou utilize a busca para encontrar o conteúdo desejado.'
            ]
        ];
    }
}
?>