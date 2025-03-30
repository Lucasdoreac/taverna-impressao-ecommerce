<?php
/**
 * Ferramenta de diagnóstico para recursos externos
 * 
 * Esta ferramenta permite analisar as páginas do site para identificar
 * dependências de recursos externos e fornecer recomendações de otimização.
 * SOMENTE PARA DESENVOLVIMENTO - NÃO USAR EM PRODUÇÃO!
 */

// Carregar configurações
require_once '../app/config/config.php';

// Verificar se estamos em ambiente de desenvolvimento
if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
    header('HTTP/1.1 403 Forbidden');
    die('Acesso negado. Esta ferramenta só pode ser usada em ambiente de desenvolvimento.');
}

// Verificar se a classe ExternalResourceManager existe
if (!class_exists('ExternalResourceManager')) {
    require_once '../app/helpers/ExternalResourceManager.php';
}

// Definir URL padrão para analisar
$defaultUrl = BASE_URL;
$url = isset($_GET['url']) ? $_GET['url'] : $defaultUrl;

// Função para obter o conteúdo da URL
function getPageContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $content = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return "Erro ao acessar a URL: " . $error;
    }
    
    return $content;
}

// Processar análise se foi enviada uma URL
$pageContent = '';
$recommendations = [];
$totalExternal = 0;
$processedUrl = '';

if (isset($_POST['analyze']) && !empty($_POST['url'])) {
    $processedUrl = $_POST['url'];
    $pageContent = getPageContent($processedUrl);
    
    if (strpos($pageContent, 'Erro ao acessar a URL') === false) {
        $recommendations = class_exists('ExternalResourceManager') ? 
            ExternalResourceManager::analyzeHtml($pageContent) : 
            ['Classe ExternalResourceManager não encontrada'];
        
        // Extrair estatísticas dos resultados
        if (count($recommendations) >= 2) {
            // O primeiro item deve conter o total de recursos localizáveis
            if (preg_match('/Recursos que podem ser localizados: (\d+)/', $recommendations[0], $matches)) {
                $localizableResources = (int)$matches[1];
            }
            
            // O segundo item deve conter o total de recursos externos
            if (preg_match('/Total de recursos externos encontrados: (\d+)/', $recommendations[1], $matches)) {
                $totalExternal = (int)$matches[1];
            }
        }
    }
}

// Verificar se deve executar ações adicionais
$action = isset($_POST['action']) ? $_POST['action'] : '';
$actionResult = '';

if ($action === 'download' && isset($_POST['resource']) && !empty($_POST['resource'])) {
    $resource = $_POST['resource'];
    if (class_exists('ExternalResourceManager')) {
        $success = ExternalResourceManager::downloadExternalResource($resource);
        $actionResult = $success ? 
            "Recurso baixado com sucesso: " . $resource : 
            "Erro ao baixar o recurso: " . $resource;
    } else {
        $actionResult = "Classe ExternalResourceManager não encontrada";
    }
}

// Converter URLs encontradas em recomendações para formulários
$resourceForms = [];
if (count($recommendations) > 2) { // Ignorar os primeiros 2 itens (estatísticas)
    foreach ($recommendations as $index => $recommendation) {
        if ($index < 2) continue; // Pular as duas primeiras entradas (estatísticas)
        
        // Extrair URL do recurso
        if (preg_match('/(https?:\/\/[^\s"\']+)/', $recommendation, $matches)) {
            $resourceUrl = $matches[1];
            $isLocalizable = strpos($recommendation, 'pode ser substituído por versão local') !== false;
            
            // Adicionar ao array de formulários
            $resourceForms[] = [
                'url' => $resourceUrl,
                'recommendation' => $recommendation,
                'localizable' => $isLocalizable
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Recursos Externos - Taverna da Impressão</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f8f9fa;
        }
        
        h1, h2, h3 {
            color: #007bff;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input[type="text"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        button, .btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        button:hover, .btn:hover {
            background-color: #0069d9;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .stats {
            display: flex;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            padding: 15px;
            margin-right: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card:last-child {
            margin-right: 0;
        }
        
        .stat-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .stat-card p {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        
        .good {
            background-color: #d4edda;
            color: #155724;
        }
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .critical {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .localizable {
            background-color: #d1ecf1;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 10px;
            margin-right: 5px;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            background-color: #fff;
            padding: 15px 0;
            z-index: 10;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sticky-header">
            <h1>Diagnóstico de Recursos Externos</h1>
            <p>Esta ferramenta analisa páginas do site para identificar dependências de recursos externos e fornecer recomendações de otimização.</p>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="url">URL para análise:</label>
                    <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($url); ?>" placeholder="https://exemplo.com.br" required>
                </div>
                <button type="submit" name="analyze" value="1">Analisar URL</button>
            </form>
        </div>
        
        <?php if (!empty($actionResult)): ?>
            <div class="alert <?php echo strpos($actionResult, 'sucesso') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $actionResult; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($processedUrl)): ?>
            <h2>Resultados para: <?php echo htmlspecialchars($processedUrl); ?></h2>
            
            <?php if (count($recommendations) > 0): ?>
                <div class="stats">
                    <div class="stat-card <?php echo $totalExternal > 0 ? ($totalExternal > 5 ? 'critical' : 'warning') : 'good'; ?>">
                        <h3>Recursos Externos</h3>
                        <p><?php echo $totalExternal; ?></p>
                    </div>
                    
                    <div class="stat-card <?php echo isset($localizableResources) && $localizableResources > 0 ? 'good' : 'warning'; ?>">
                        <h3>Localizáveis</h3>
                        <p><?php echo isset($localizableResources) ? $localizableResources : '0'; ?></p>
                    </div>
                    
                    <div class="stat-card <?php echo ($totalExternal - (isset($localizableResources) ? $localizableResources : 0)) > 0 ? 'warning' : 'good'; ?>">
                        <h3>Não Localizáveis</h3>
                        <p><?php echo $totalExternal - (isset($localizableResources) ? $localizableResources : 0); ?></p>
                    </div>
                </div>
                
                <?php if (count($resourceForms) > 0): ?>
                    <h3>Recursos externos encontrados</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resourceForms as $resource): ?>
                                <tr class="<?php echo $resource['localizable'] ? 'localizable' : ''; ?>">
                                    <td><?php echo htmlspecialchars($resource['url']); ?></td>
                                    <td>
                                        <?php if ($resource['localizable']): ?>
                                            <span class="badge badge-success">Localizável</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Externo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($resource['localizable']): ?>
                                            <form method="post" action="" style="display:inline;">
                                                <input type="hidden" name="url" value="<?php echo htmlspecialchars($processedUrl); ?>">
                                                <input type="hidden" name="resource" value="<?php echo htmlspecialchars($resource['url']); ?>">
                                                <input type="hidden" name="action" value="download">
                                                <button type="submit" class="btn btn-success">Baixar para Local</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>Não localizável</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">
                        Nenhum recurso externo específico identificado.
                    </div>
                <?php endif; ?>
                
                <h3>Recomendações completas</h3>
                <ul>
                    <?php foreach ($recommendations as $recommendation): ?>
                        <li><?php echo htmlspecialchars($recommendation); ?></li>
                    <?php endforeach; ?>
                </ul>
                
            <?php else: ?>
                <div class="alert alert-warning">
                    Não foi possível analisar a página. Verifique se a URL está correta e acessível.
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</body>
</html>
