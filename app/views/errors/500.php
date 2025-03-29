<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro Interno do Servidor - TAVERNA DA IMPRESSÃO</title>
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
            color: #dc3545;
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
                <span class="error-code">500</span>
            </div>
            <div class="mb-4">
                <img src="<?= BASE_URL ?>assets/images/error-500.png" alt="Erro interno do servidor" class="error-img">
            </div>
            <h1 class="mb-4">Erro Interno do Servidor</h1>
            <p class="lead mb-4">Ops! Parece que algo deu errado em nosso servidor. Nossa equipe técnica já foi notificada e está trabalhando para resolver o problema.</p>
            <div class="d-grid gap-2 d-md-block">
                <a href="<?= BASE_URL ?>" class="btn btn-primary">Voltar para Home</a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary">Voltar para página anterior</a>
            </div>
            <?php if (ENVIRONMENT === 'development' && isset($error)): ?>
            <div class="mt-5 text-start">
                <div class="alert alert-danger">
                    <h5>Detalhes do Erro (Apenas Ambiente de Desenvolvimento):</h5>
                    <p><?= $error ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
