<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= STORE_NAME ?> - Materiais impressos para RPG</title>
    <meta name="description" content="Loja especializada em materiais impressos para RPG, incluindo fichas, mapas, livros e acessórios para jogadores e mestres.">
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Otimizado -->
    <?php
    // Verificar se o AssetOptimizerHelper está carregado
    if (class_exists('AssetOptimizerHelper')) {
        echo AssetOptimizerHelper::css(['style.css', 'placeholders.css']);
    } else {
        // Fallback para o método convencional
        echo '<link rel="stylesheet" href="'.BASE_URL.'assets/css/style.css">';
        echo '<link rel="stylesheet" href="'.BASE_URL.'assets/css/placeholders.css">';
    }
    ?>
    
    <!-- Cache Control -->
    <?php
    // Adicionar headers para cache em arquivos estáticos
    if (class_exists('CacheHelper')) {
        // Aplicar versão a qualquer URL que possa precisar de cache-busting
        $version = CacheHelper::getVersionedUrl('', null);
    }
    ?>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <!-- Top Header -->
            <div class="top-header">
                <div class="contact-info">
                    <span><i class="fas fa-envelope"></i> <?= STORE_EMAIL ?></span>
                    <span class="ml-3"><i class="fas fa-phone"></i> <?= STORE_PHONE ?></span>
                </div>
                <div class="user-actions">
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="<?= BASE_URL ?>minha-conta"><i class="fas fa-user-circle"></i> Minha Conta</a>
                        <a href="<?= BASE_URL ?>logout" class="ml-3"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>login"><i class="fas fa-sign-in-alt"></i> Entrar</a>
                        <a href="<?= BASE_URL ?>cadastro" class="ml-3"><i class="fas fa-user-plus"></i> Cadastre-se</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Nav -->
            <div class="main-nav">
                <a href="<?= BASE_URL ?>" class="logo">TAVERNA DA IMPRESSÃO</a>
                
                <form action="<?= BASE_URL ?>busca" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="O que você procura?" required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <a href="<?= BASE_URL ?>carrinho" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                        <span class="cart-count"><?= $_SESSION['cart_count'] ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        
        <!-- Categorias Menu -->
        <nav class="nav-menu">
            <div class="container">
                <ul>
                    <li>
                        <a href="<?= BASE_URL ?>">Home</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>produtos">Produtos</a>
                    </li>
                    <li>
                        <a href="#">Categorias <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown">
                            <li><a href="<?= BASE_URL ?>categoria/fichas-de-personagem">Fichas de Personagem</a></li>
                            <li><a href="<?= BASE_URL ?>categoria/mapas-de-aventura">Mapas de Aventura</a></li>
                            <li><a href="<?= BASE_URL ?>categoria/livros-e-modulos">Livros e Módulos</a></li>
                            <li><a href="<?= BASE_URL ?>categoria/telas-do-mestre">Telas do Mestre</a></li>
                            <li><a href="<?= BASE_URL ?>categoria/cards-e-tokens">Cards e Tokens</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>personalizados">Personalizados</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>sobre">Sobre Nós</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>contato">Contato</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    
    <!-- Conteúdo Principal -->