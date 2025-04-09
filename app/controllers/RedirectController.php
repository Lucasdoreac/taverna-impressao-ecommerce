<?php
/**
 * RedirectController - Controlador para gerenciar redirecionamentos
 * 
 * Este controlador é usado para criar redirecionamentos permanentes para rotas
 * que foram renomeadas ou movidas, mantendo a compatibilidade com URLs antigas.
 * 
 * @package Taverna\Controllers
 * @author Taverna da Impressão
 * @version 1.0.0
 */
class RedirectController {
    
    /**
     * Redireciona para uma rota específica
     * 
     * @param array $params Parâmetros da rota
     * @return void
     */
    public function redirectTo($params = []) {
        // Verificar se a rota de destino foi especificada
        if (!isset($params['route'])) {
            // Redirecionar para a página inicial se não houver rota específica
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Obter a rota de destino
        $targetRoute = $params['route'];
        
        // Adicionar código HTTP 301 para redirecionamento permanente
        header('HTTP/1.1 301 Moved Permanently');
        
        // Redirecionar para a rota de destino
        header('Location: ' . BASE_URL . $targetRoute);
        exit;
    }
}