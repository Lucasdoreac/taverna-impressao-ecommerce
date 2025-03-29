<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Não Encontrada - TAVERNA DA IMPRESSÃO</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .error-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            color: #6c757d;
        }
        .error-img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="error-container text-center">
            <div class="mb-4">
                <span class="error-code">404</span>
            </div>
            <div class="mb-4">
                <img src="<?= BASE_URL ?>assets/images/error-404.png" alt="Página não encontrada" class="error-img">
            </div>
            <h1 class="mb-4">Página Não Encontrada</h1>
            <p class="lead mb-4">A página que você está procurando parece ter sido removida, mudou de nome ou está temporariamente indisponível.</p>
            <div class="d-grid gap-2 d-md-block">
                <a href="<?= BASE_URL ?>" class="btn btn-primary">Voltar para Home</a>
                <a href="<?= BASE_URL ?>produtos" class="btn btn-outline-secondary">Ver Produtos</a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
