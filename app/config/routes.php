<?php
// Definição das rotas da aplicação
$routes = [
    // Páginas públicas
    '/' => ['HomeController', 'index'],
    '/produtos' => ['ProductController', 'index'],
    '/produto/:slug' => ['ProductController', 'show'],
    '/categoria/:slug' => ['CategoryController', 'show'],
    '/busca' => ['SearchController', 'index'],
    
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
    
    // Termos e políticas
    '/termos-modelos-3d' => ['PageController', 'termsModels3d'],
];
