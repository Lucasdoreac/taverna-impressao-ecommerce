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
    
    // Personalização
    '/personalizar/:id' => ['CustomizationController', 'index'],
    '/personalizar/upload' => ['CustomizationController', 'upload'],
    
    // Admin
    '/admin' => ['AdminController', 'index'],
    '/admin/produtos' => ['AdminProductController', 'index'],
    '/admin/categorias' => ['AdminCategoryController', 'index'],
    '/admin/pedidos' => ['AdminOrderController', 'index'],
    '/admin/usuarios' => ['AdminUserController', 'index'],
];