<?php
// Definição das rotas da aplicação
$routes = [
    // Páginas públicas
    '/' => ['HomeController', 'index'],
    '/produtos' => ['ProductController', 'index'],
    '/produto/:slug' => ['ProductController', 'show'],
    '/categoria/:slug' => ['CategoryController', 'show'],
    '/busca' => ['SearchController', 'index'],
    
    // Nova rota para produtos personalizáveis
    '/personalizados' => ['CustomizationController', 'list'],
    
    // Carrinho e checkout
    '/carrinho' => ['CartController', 'index'],
    '/carrinho/adicionar' => ['CartController', 'add'],
    '/carrinho/atualizar' => ['CartController', 'update'],
    '/carrinho/remover/:id' => ['CartController', 'remove'],
    '/checkout' => ['CheckoutController', 'index'],
    '/checkout/finalizar' => ['CheckoutController', 'finish'],
    '/pedido/sucesso/:id' => ['OrderController', 'success'],
    
    // Autenticação
    '/login' => ['AuthController', 'login'],
    '/logout' => ['AuthController', 'logout'],
    '/cadastro' => ['AuthController', 'register'],
    '/recuperar-senha' => ['AuthController', 'recoverPassword'],
    
    // Área do cliente
    '/minha-conta' => ['AccountController', 'index'],
    '/minha-conta/pedidos' => ['AccountController', 'orders'],
    '/minha-conta/pedido/:id' => ['AccountController', 'orderDetails'],
    '/minha-conta/endereco' => ['AccountController', 'address'],
    
    // Sistema de Modelos 3D
    '/customer-models/upload' => ['CustomerModelController', 'upload'],
    '/customer-models/process-upload' => ['CustomerModelController', 'processUpload'],
    '/customer-models/list' => ['CustomerModelController', 'listUserModels'],
    '/customer-models/details/:id' => ['CustomerModelController', 'details'],
    '/customer-models/delete/:id' => ['CustomerModelController', 'delete'],
    
    // Personalização
    '/personalizar/:id' => ['CustomizationController', 'index'],
    '/personalizar/upload' => ['CustomizationController', 'upload'],
    
    // Admin - Área geral
    '/admin' => ['AdminController', 'index'],
    '/admin/produtos' => ['AdminProductController', 'index'],
    '/admin/categorias' => ['AdminCategoryController', 'index'],
    '/admin/pedidos' => ['AdminOrderController', 'index'],
    '/admin/usuarios' => ['AdminUserController', 'index'],
    
    // Admin - Modelos 3D
    '/admin/customer-models/pending' => ['CustomerModelController', 'pendingModels'],
    '/admin/customer-models/update-status/:id' => ['CustomerModelController', 'updateStatus'],
    
    // Admin - Relatórios
    '/admin/relatorios' => ['AdminDashboardController', 'reports'],
    '/admin/relatorios/vendas' => ['AdminDashboardController', 'salesReport'],
    '/admin/relatorios/produtos' => ['AdminDashboardController', 'productsReport'],
    '/admin/relatorios/clientes' => ['AdminDashboardController', 'customersReport'],
    '/admin/relatorios/categorias' => ['AdminDashboardController', 'categoriesReport'],
    
    // Admin - Performance SQL e Otimizações
    '/admin/performance' => ['AdminPerformanceController', 'index'],
    '/admin/performance/dailyReport' => ['AdminPerformanceController', 'dailyReport'],
    '/admin/performance/analyzeModel' => ['AdminPerformanceController', 'analyzeModel'],
    '/admin/performance/testQuery' => ['AdminPerformanceController', 'testQuery'],
    '/admin/performance/recommendations' => ['AdminPerformanceController', 'recommendations'],
    '/admin/performance/applyOptimizations' => ['AdminPerformanceController', 'applyOptimizations'],
    '/admin/performance/confirmOptimizations' => ['AdminPerformanceController', 'confirmOptimizations'],
    '/admin/performance/optimizationGuide' => ['AdminPerformanceController', 'optimizationGuide'],
    '/admin/performance/testPerformance' => ['AdminPerformanceController', 'testPerformance'],
    '/admin/performance/recentOptimizations' => ['AdminPerformanceController', 'recentOptimizations'],
    
    // Admin - Monitoramento de Performance
    '/admin/performance/reports' => ['PerformanceMonitoringController', 'reports'],
    
    // API para Monitoramento de Performance
    '/api/performance/collect' => ['PerformanceMonitoringController', 'collect'],
    '/api/performance/finalize' => ['PerformanceMonitoringController', 'finalize'],
    
    // Sistema de Fila de Impressão 3D - Admin
    '/admin/print_queue' => ['PrintQueueController', 'index'],
    '/admin/print_queue/details/:id' => ['PrintQueueController', 'details'],
    '/admin/print_queue/printers' => ['PrintQueueController', 'printers'],
    '/admin/print_queue/updateStatus' => ['PrintQueueController', 'updateStatus'],
    '/admin/print_queue/assignPrinter' => ['PrintQueueController', 'assignPrinter'],
    '/admin/print_queue/updatePriority' => ['PrintQueueController', 'updatePriority'],
    '/admin/print_queue/addToQueue' => ['PrintQueueController', 'addToQueue'],
    '/admin/print_queue/updatePrinterStatus' => ['PrintQueueController', 'updatePrinterStatus'],
    '/admin/print_queue/addPrinter' => ['PrintQueueController', 'addPrinter'],
    '/admin/print_queue/deletePrinter/:id' => ['PrintQueueController', 'deletePrinter'],
    
    // Integração de Pedidos com Fila de Impressão
    '/admin/pedidos/:id/fila' => ['OrderController', 'viewPrintQueue'],
    '/admin/pedidos/:id/adicionar-a-fila' => ['OrderController', 'addAllItemsToQueue'],
    '/admin/pedidos/atualizar-status-do-pedido/:id' => ['OrderController', 'updateOrderStatusFromQueue'],
    '/admin/print_queue/por-pedido' => ['PrintQueueController', 'viewByOrder'],
    '/admin/print_queue/relatorio' => ['PrintQueueController', 'productionReport'],
    '/admin/print_queue/dashboard' => ['PrintQueueController', 'dashboard'],
    
    // Sistema de Notificações para Impressão 3D
    '/admin/print_queue/notificacoes' => ['NotificationController', 'index'],
    '/admin/print_queue/notificacoes/config' => ['NotificationController', 'config'],
    '/admin/print_queue/enviar-notificacao' => ['NotificationController', 'send'],
    '/notificacoes/marcar-como-lidas' => ['NotificationController', 'markAsRead'],
    
    // Sistema de Fila de Impressão 3D - Cliente
    '/impressoes' => ['PrintQueueController', 'customerJobs'],
    '/print_queue/markNotificationRead' => ['PrintQueueController', 'markNotificationRead'],
    '/rastrear' => ['PrintQueueController', 'customerTrack'],
    '/rastrear/resultado' => ['PrintQueueController', 'trackResult'],
    
    // Sistema de Monitoramento em Tempo Real do Status da Impressão - Cliente
    '/print-monitor' => ['PrintMonitorController', 'index'],
    '/print-monitor/details/:id' => ['PrintMonitorController', 'details'],
    '/print-monitor/order/:id' => ['PrintMonitorController', 'order'],
    '/api/print-status/:id' => ['PrintMonitorController', 'apiStatus'],
    '/api/print-monitor/add-message' => ['PrintMonitorController', 'apiAddMessage'],
    
    // Sistema de Monitoramento em Tempo Real do Status da Impressão - Admin
    '/admin/impressoes' => ['AdminPrintMonitorController', 'index'],
    '/admin/impressoes/list' => ['AdminPrintMonitorController', 'list'],
    '/admin/impressao/:id' => ['AdminPrintMonitorController', 'details'],
    '/admin/impressoes/add-status' => ['AdminPrintMonitorController', 'addOrUpdateStatus'],
    '/admin/impressoes/add-message' => ['AdminPrintMonitorController', 'addMessage'],
    '/admin/impressoes/add-metrics' => ['AdminPrintMonitorController', 'addMetrics'],
    '/admin/impressoes/action' => ['AdminPrintMonitorController', 'action'],
    '/admin/impressoes/batch-action' => ['AdminPrintMonitorController', 'batchAction'],
    
    // API para Sistema de Monitoramento em Tempo Real do Status da Impressão
    '/api/status/update' => ['PrintStatusApiController', 'update'],
    '/api/status/start' => ['PrintStatusApiController', 'start'],
    '/api/status/:id' => ['PrintStatusApiController', 'get'],
    '/api/status/printer/:id' => ['PrintStatusApiController', 'printerJobs'],
    
    // Termos e políticas
    '/termos-modelos-3d' => ['PageController', 'termsModels3d'],
    
    // Rotas para arquivos estáticos com cache
    '/static/css/:filename' => ['StaticController', 'css'],
    '/static/js/:filename' => ['StaticController', 'js'],
    '/static/image/:filename' => ['StaticController', 'image'],
    '/static/font/:filename' => ['StaticController', 'font'],
    '/static/cache/:filename' => ['StaticController', 'cache'],
];
