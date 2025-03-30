<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Controller de Documentação para Usuários
 * Responsável por gerenciar as requisições e exibição das páginas de documentação
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class DocumentationController {
    private $model;
    private $baseView = 'documentation/';
    
    /**
     * Construtor
     * Inicializa o modelo de documentação
     */
    public function __construct() {
        require_once 'app/models/DocumentationModel.php';
        $this->model = new DocumentationModel();
    }
    
    /**
     * Página inicial da documentação
     * Exibe uma visão geral das funcionalidades documentadas
     */
    public function index() {
        $data = [
            'title' => 'Documentação de Usuário - Taverna da Impressão',
            'sections' => $this->model->getSections(),
            'overview' => $this->model->getOverview(),
            'activePage' => 'index'
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'index.php';
    }
    
    /**
     * Documentação do Visualizador 3D
     * Exibe informações detalhadas sobre como utilizar o visualizador 3D
     */
    public function visualizador3d() {
        $data = [
            'title' => 'Visualizador 3D - Documentação de Usuário',
            'sections' => $this->model->getSections(),
            'content' => $this->model->getContent('visualizador3d'),
            'activePage' => 'visualizador3d'
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'visualizador3d.php';
    }
    
    /**
     * Documentação do Sistema de Categorias
     * Exibe informações sobre como navegar e utilizar o sistema de categorias
     */
    public function categorias() {
        $data = [
            'title' => 'Sistema de Categorias - Documentação de Usuário',
            'sections' => $this->model->getSections(),
            'content' => $this->model->getContent('categorias'),
            'activePage' => 'categorias'
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'categorias.php';
    }
    
    /**
     * Documentação de Otimizações
     * Exibe informações sobre as otimizações de desempenho e seus benefícios
     */
    public function otimizacao() {
        $data = [
            'title' => 'Otimizações de Desempenho - Documentação de Usuário',
            'sections' => $this->model->getSections(),
            'content' => $this->model->getContent('otimizacao'),
            'activePage' => 'otimizacao'
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'otimizacao.php';
    }
    
    /**
     * Sistema de Busca na Documentação
     * Permite aos usuários buscar informações específicas na documentação
     * 
     * @param string $query Termo de busca
     */
    public function search($query = '') {
        // Se a requisição for via GET, obter a query da URL
        if (isset($_GET['q']) && empty($query)) {
            $query = htmlspecialchars($_GET['q']);
        }
        
        $data = [
            'title' => 'Busca na Documentação - Taverna da Impressão',
            'sections' => $this->model->getSections(),
            'query' => $query,
            'results' => $this->model->searchContent($query),
            'activePage' => 'search'
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'search.php';
    }
    
    /**
     * Método para verificar e criar diretórios necessários se não existirem
     * Usado principalmente na primeira execução
     */
    private function ensureDirectoriesExist() {
        $dirs = [
            'app/views/' . $this->baseView,
            'app/views/partials'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Método para tratamento de erros
     * Exibe uma página de erro amigável ao usuário
     * 
     * @param string $message Mensagem de erro
     */
    private function showError($message) {
        $data = [
            'title' => 'Erro - Documentação de Usuário',
            'message' => $message
        ];
        
        // Renderizar a view de erro
        require_once 'app/views/error.php';
    }
}
?>