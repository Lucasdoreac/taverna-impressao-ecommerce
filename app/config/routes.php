<?php
// Definição das rotas da aplicação
$routes = [
    '/contato' => ['PageController', 'contato'],
    '/sobre' => ['PageController', 'sobre'],
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
    '/pedido/pendente/:id' => ['OrderController', 'pending'],
    '/pedido/falha/:id' => ['OrderController', 'failure'],
    '/pedido/cancelado/:id' => ['OrderController', 'cancelled'],
    '/pedido/reembolsado/:id' => ['OrderController', 'refunded'],
    '/pedido/pagar/:id' => ['OrderController', 'paymentMethod'],
    
    // Sistema de pagamentos
    '/pagamento/processar' => ['PaymentController', 'process'],
    '/pagamento/pix/:id' => ['PaymentController', 'pix'],
    '/pagamento/boleto/:id' => ['PaymentController', 'boleto'],
    '/pagamento/cartao/:id' => ['PaymentController', 'creditCard'],
    '/pagamento/paypal/:id' => ['PaymentController', 'paypal'],
    '/pagamento/verificar-status' => ['PaymentController', 'checkStatus'],
    '/payment/create-paypal-order' => ['PaymentController', 'createPayPalOrder'],
    '/payment/capture-paypal-order' => ['PaymentController', 'capturePayPalOrder'],
    '/payment/cancel-paypal-order' => ['PaymentController', 'cancelPayPalOrder'],
    '/payment/log-error' => ['PaymentController', 'logError'],
    '/payment/ipn/paypal' => ['PaymentCallbackController', 'paypalIPN'],
    '/webhook/mercadopago' => ['PaymentController', 'webhook', ['gateway' => 'mercadopago']],
    '/webhook/pagseguro' => ['PaymentController', 'webhook', ['gateway' => 'pagseguro']],
    '/webhook/paypal' => ['PaymentController', 'webhook', ['gateway' => 'paypal']],
    
    // Autenticação
    '/login' => ['AuthController', 'login'],
    '/logout' => ['AuthController', 'logout'],
    '/cadastro' => ['AuthController', 'register'],
    '/register' => ['RedirectController', 'redirectTo', ['route' => 'cadastro']], // Rota de redirecionamento para manter compatibilidade
    '/recuperar-senha' => ['AuthController', 'recoverPassword'], // Mantendo apenas uma implementação
    '/redefinir-senha/:id/:token' => ['AuthController', 'resetPassword'],
    
    // Área do cliente
    '/minha-conta' => ['UserAccountController', 'index'],
    '/minha-conta/perfil' => ['UserAccountController', 'profile'],
    '/minha-conta/pedidos' => ['UserAccountController', 'orders'],
    '/minha-conta/pedido/:id' => ['UserAccountController', 'orderDetails'],
    '/minha-conta/enderecos' => ['UserAccountController', 'addresses'],
    '/minha-conta/enderecos/adicionar' => ['UserAccountController', 'addressForm'],
    '/minha-conta/enderecos/editar/:id' => ['UserAccountController', 'addressForm'],
    '/minha-conta/enderecos/excluir/:id' => ['UserAccountController', 'deleteAddress'],
    '/recuperar-senha/enviar' => ['AuthController', 'requestPasswordReset'], // Alterado para usar AuthController
    
    // Preferências de notificação
    '/conta/notification-preferences/async' => ['AsyncNotificationPreferenceController', 'index'],
    '/conta/notification-preferences/async/save' => ['AsyncNotificationPreferenceController', 'save'],
    
    // Sistema de Modelos 3D
    '/customer-models/upload' => ['CustomerModelController', 'upload'],
    '/customer-models/process-upload' => ['CustomerModelController', 'processUpload'],
    '/customer-models/list' => ['CustomerModelController', 'listUserModels'],
    '/customer-models/details/:id' => ['CustomerModelController', 'details'],
    '/customer-models/delete/:id' => ['CustomerModelController', 'delete'],
    
    // Visualizador 3D para Modelos
    '/viewer3d/view/:id' => ['Viewer3DController', 'view'],
    '/viewer3d/get-model/:id/:token' => ['Viewer3DController', 'getModel'],
    '/viewer3d/download/:id/:token' => ['Viewer3DController', 'download'],
    
    // Sistema de Cotação Automatizada - Cliente
    '/customer-quotation/request/:id' => ['CustomerQuotationController', 'requestQuotation'],
    '/customer-quotation/process' => ['CustomerQuotationController', 'processRequest'],
    '/customer-quotation/view/:id' => ['CustomerQuotationController', 'viewQuotation'],
    '/customer-quotation/my' => ['CustomerQuotationController', 'myQuotations'],
    '/customer-quotation/confirm/:id' => ['CustomerQuotationController', 'confirmOrder'],
    '/customer-quotation/process-order' => ['CustomerQuotationController', 'processOrder'],
    '/api/quick-quote/:id' => ['CustomerQuotationController', 'quickQuote'],
    
    // Sistema de Cotação Automatizada - Admin
    '/admin/quotation' => ['AdminQuotationController', 'index'],
    '/admin/quotation/list' => ['AdminQuotationController', 'listQuotations'],
    '/admin/quotation/view/:id' => ['AdminQuotationController', 'viewQuotation'],
    '/admin/quotation/parameters' => ['AdminQuotationController', 'configureParameters'],
    '/admin/quotation/update-parameters' => ['AdminQuotationController', 'updateParameters'],
    '/admin/quotation/test' => ['AdminQuotationController', 'testQuotation'],
    '/admin/quotation/generate-test' => ['AdminQuotationController', 'generateTestQuotation'],
    
    // Personalização
    '/personalizar/:id' => ['CustomizationController', 'index'],
    '/personalizar/upload' => ['CustomizationController', 'upload'],
    
    // Admin - Área geral
    '/admin' => ['AdminController', 'index'],
    '/admin/produtos' => ['AdminProductController', 'index'],
    '/admin/categorias' => ['AdminCategoryController', 'index'],
    '/admin/pedidos' => ['AdminOrderController', 'index'],
    '/admin/usuarios' => ['AdminUserController', 'index'],
    
    // Admin - Pagamentos
    '/admin/pagamentos' => ['AdminPaymentController', 'index'],
    '/admin/pagamentos/configuracoes' => ['AdminPaymentController', 'settings'],
    '/admin/pagamentos/gateways' => ['AdminPaymentController', 'gateways'],
    '/admin/pagamentos/testGateway' => ['AdminPaymentController', 'testGateway'],
    '/admin/pagamentos/transacoes' => ['AdminPaymentController', 'transacoes'],
    '/admin/pagamentos/detalhes/:id' => ['AdminPaymentController', 'details'],
    '/admin/pagamentos/transacao/:id' => ['AdminPaymentController', 'transaction'],
    '/admin/pagamentos/reembolsar/:id' => ['AdminPaymentController', 'refund'],
    '/admin/pagamentos/cancelar/:id' => ['AdminPaymentController', 'cancel'],
    '/admin/pagamentos/webhooks' => ['AdminPaymentController', 'webhooks'],
    
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
    
    // Admin - Dashboard de Monitoramento de Performance (NOVO)
    '/admin/performance_monitoring_dashboard' => ['PerformanceMonitoringDashboardController', 'index'],
    '/admin/performance_monitoring_dashboard/getChartData' => ['PerformanceMonitoringDashboardController', 'getChartData'],
    '/admin/performance_monitoring_dashboard/getAlerts' => ['PerformanceMonitoringDashboardController', 'getAlerts'],
    '/admin/performance_monitoring_dashboard/getSystemMetrics' => ['PerformanceMonitoringDashboardController', 'getSystemMetrics'],
    '/admin/performance_monitoring_dashboard/urlReport' => ['PerformanceMonitoringDashboardController', 'urlReport'],
    '/admin/performance_monitoring_dashboard/alertDetail' => ['PerformanceMonitoringDashboardController', 'alertDetail'],
    '/admin/performance_monitoring_dashboard/thresholds' => ['PerformanceMonitoringDashboardController', 'thresholds'],
    
    // Admin - Monitoramento de Performance (LEGADO)
    '/admin/performance/reports' => ['PerformanceMonitoringController', 'reports'],
    
    // API para Monitoramento de Performance
    '/api/performance/collect' => ['PerformanceMonitoringController', 'collect'],
    '/api/performance/finalize' => ['PerformanceMonitoringController', 'finalize'],
    
    // Sistema de Fila de Impressão 3D - Novo Sistema
    // -- Fila de Impressão
    '/print-queue' => ['PrintQueueController', 'index'],
    '/print-queue/details/:id' => ['PrintQueueController', 'details'],
    '/print-queue/add' => ['PrintQueueController', 'addToQueue'],
    '/print-queue/cancel' => ['PrintQueueController', 'cancel'],
    '/print-queue/update-priority' => ['PrintQueueController', 'updatePriority'],
    '/user/print-queue' => ['PrintQueueController', 'userQueue'],
    '/track/:code' => ['PrintQueueController', 'trackJob'],
    '/track' => ['PrintQueueController', 'trackJob'],
    
    // -- Impressoras
    '/printers' => ['PrinterController', 'index'],
    '/printers/add' => ['PrinterController', 'add'],
    '/printers/create' => ['PrinterController', 'create'],
    '/printers/details/:id' => ['PrinterController', 'details'],
    '/printers/edit/:id' => ['PrinterController', 'edit'],
    '/printers/update' => ['PrinterController', 'update'],
    '/printers/update-status' => ['PrinterController', 'updateStatus'],
    '/printers/register-maintenance' => ['PrinterController', 'registerMaintenance'],
    '/printers/delete' => ['PrinterController', 'delete'],
    
    // -- Trabalhos de Impressão
    '/print-jobs' => ['PrintJobController', 'index'],
    '/print-jobs/details/:id' => ['PrintJobController', 'details'],
    '/print-jobs/assign' => ['PrintJobController', 'assign'],
    '/print-jobs/start' => ['PrintJobController', 'start'],
    '/print-jobs/update-progress' => ['PrintJobController', 'updateProgress'],
    '/print-jobs/complete' => ['PrintJobController', 'complete'],
    '/print-jobs/fail' => ['PrintJobController', 'fail'],
    '/print-jobs/dashboard' => ['PrintJobController', 'dashboard'],
    '/user/print-jobs' => ['PrintJobController', 'userJobs'],
    
    // -- Painel Administrativo Centralizado
    '/admin/print-system' => ['AdminPrintController', 'dashboard'],
    '/admin/print-system/dashboard' => ['AdminPrintController', 'dashboard'],
    '/admin/print-system/queue' => ['AdminPrintController', 'queueManagement'],
    '/admin/print-system/printers' => ['AdminPrintController', 'printerManagement'],
    '/admin/print-system/jobs' => ['AdminPrintController', 'jobManagement'],
    '/admin/print-system/statistics' => ['AdminPrintController', 'statistics'],
    '/admin/print-system/settings' => ['AdminPrintController', 'settings'],
    '/admin/print-system/batch-assignment' => ['AdminPrintController', 'batchAssignment'],
    
    // Sistema de Fila de Impressão 3D - Legado (rotas mantidas para compatibilidade)
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
    
    // Sistema de Fila de Impressão 3D - Cliente (legado)
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
    
    // Status Tracking API
    '/status-tracking' => ['StatusTrackingController', 'showTrackingPage'],
    
    // Notificações API para processos assíncronos
    '/api/async-notifications/status-change' => ['Api\AsyncNotificationsController', 'notifyStatusChange'],
    '/api/async-notifications/progress' => ['Api\AsyncNotificationsController', 'notifyProgress'],
    '/api/async-notifications/results-available' => ['Api\AsyncNotificationsController', 'notifyResultsAvailable'],
    '/api/async-notifications/expiration-warning' => ['Api\AsyncNotificationsController', 'notifyExpirationWarning'],
    '/api/async-notifications/user-notifications' => ['Api\AsyncNotificationsController', 'getUserProcessNotifications'],
    '/api/async-notifications/mark-read' => ['Api\AsyncNotificationsController', 'markProcessNotificationRead'],
    '/api/async-notifications/mark-all-read' => ['Api\AsyncNotificationsController', 'markAllProcessNotificationsRead'],
    
    // Notificações API geral
    '/api/notifications/unread' => ['NotificationsApiController', 'getUnreadNotifications'],
    '/api/notifications/mark-read' => ['NotificationsApiController', 'markAsRead'],
    '/api/notifications/mark-all-read' => ['NotificationsApiController', 'markAllAsRead'],
    '/api/notifications/vapid-public-key' => ['NotificationsApiController', 'getVapidPublicKey'],
    '/api/notifications/subscribe' => ['NotificationsApiController', 'subscribeNotifications'],
    '/api/notifications/unsubscribe' => ['NotificationsApiController', 'unsubscribeNotifications'],
    
    // Termos e políticas
    '/termos-modelos-3d' => ['PageController', 'termsModels3d'],
    
    // Rotas para arquivos estáticos com cache
    '/static/css/:filename' => ['StaticController', 'css'],
    '/static/js/:filename' => ['StaticController', 'js'],
    '/static/image/:filename' => ['StaticController', 'image'],
    '/static/font/:filename' => ['StaticController', 'font'],
    '/static/cache/:filename' => ['StaticController', 'cache'],
];
