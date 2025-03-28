<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro Interno - TAVERNA DA IMPRESSÃO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }
        .error-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 1rem;
            line-height: 1;
        }
        .error-message {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #343a40;
        }
        .error-details {
            margin-bottom: 2rem;
            color: #6c757d;
        }
        .taverna-footer {
            background-color: #343a40;
            color: white;
            padding: 1rem 0;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <div class="error-message">Erro Interno do Servidor</div>
        
        <div class="error-details">
            <p>Encontramos um problema ao processar sua solicitação.</p>
            <p>Nossa equipe foi notificada e estamos trabalhando para resolver o problema o mais rápido possível.</p>
        </div>
        
        <div class="mt-4">
            <a href="<?= BASE_URL ?>" class="btn btn-primary">Voltar para a página inicial</a>
        </div>
        
        <?php if (ENVIRONMENT === 'development' && isset($error_message)): ?>
        <div class="mt-5 text-start">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    Detalhes do Erro (apenas em ambiente de desenvolvimento)
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Mensagem:</strong> <?= $error_message ?></p>
                    <?php if (isset($error_trace)): ?>
                    <p class="mb-2"><strong>Stack Trace:</strong></p>
                    <pre class="bg-light p-3 small"><?= $error_trace ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <footer class="taverna-footer text-center">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> TAVERNA DA IMPRESSÃO - Todos os direitos reservados</p>
        </div>
    </footer>
</body>
</html>
