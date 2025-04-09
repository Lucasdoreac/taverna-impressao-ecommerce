<?php
/**
 * Scanner Utils - Funções utilitárias para o scanner de vulnerabilidades CWE
 * 
 * Este arquivo contém funções de suporte para o escaneamento de código
 * em busca de vulnerabilidades de segurança.
 * 
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

/**
 * Verifica se uma string corresponde a um padrão glob
 * 
 * Esta função emula o comportamento da função fnmatch do PHP,
 * que pode não estar disponível em alguns sistemas.
 * 
 * @param string $pattern Padrão glob
 * @param string $string String a ser verificada
 * @return bool Verdadeiro se a string corresponder ao padrão
 */
if (!function_exists('fnmatch')) {
    function fnmatch($pattern, $string) {
        $pattern = preg_quote($pattern, '/');
        
        // Converter wildcards em expressões regulares
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = str_replace('\?', '.', $pattern);
        
        return preg_match('/^' . $pattern . '$/i', $string);
    }
}

/**
 * Formata um caminho de arquivo para exibição
 * 
 * @param string $path Caminho completo do arquivo
 * @param string $rootDir Diretório raiz para relativizar o caminho
 * @return string Caminho formatado para exibição
 */
function formatFilePath($path, $rootDir) {
    $relativePath = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $path);
    return str_replace('\\', '/', $relativePath);
}

/**
 * Obtém a extensão de um arquivo a partir do nome
 * 
 * @param string $filename Nome do arquivo
 * @return string Extensão do arquivo em minúsculas
 */
function getFileExtension($filename) {
    $parts = explode('.', $filename);
    return strtolower(end($parts));
}

/**
 * Verifica se um arquivo é um arquivo de texto
 * 
 * @param string $filepath Caminho do arquivo
 * @return bool Verdadeiro se for um arquivo de texto
 */
function isTextFile($filepath) {
    $finfo = new finfo(FILEINFO_MIME);
    $mimeType = $finfo->file($filepath);
    
    // Verificar se o MIME type indica um arquivo de texto
    return (
        strpos($mimeType, 'text/') === 0 ||
        strpos($mimeType, 'application/javascript') !== false ||
        strpos($mimeType, 'application/json') !== false ||
        strpos($mimeType, 'application/xml') !== false
    );
}

/**
 * Formata a severidade de uma vulnerabilidade com cores para o terminal
 * 
 * @param string $severity Nível de severidade (critical, high, medium, low)
 * @return string Texto formatado com códigos de cores ANSI
 */
function formatSeverity($severity) {
    $severityColors = [
        'critical' => "\033[1;31m", // Vermelho brilhante
        'high'     => "\033[0;31m", // Vermelho
        'medium'   => "\033[0;33m", // Amarelo
        'low'      => "\033[0;32m"  // Verde
    ];
    
    $reset = "\033[0m";
    
    $severity = strtolower($severity);
    $color = isset($severityColors[$severity]) ? $severityColors[$severity] : '';
    
    return $color . ucfirst($severity) . $reset;
}

/**
 * Formata um trecho de código para exibição no terminal
 * 
 * @param string $code Código a ser formatado
 * @param int $width Largura máxima do código formatado
 * @return string Código formatado
 */
function formatCode($code, $width = 100) {
    // Limitar largura
    if (strlen($code) > $width) {
        $code = substr($code, 0, $width - 3) . '...';
    }
    
    // Escapar caracteres especiais
    $code = str_replace("\n", '\n', $code);
    $code = str_replace("\r", '\r', $code);
    $code = str_replace("\t", '\t', $code);
    
    return $code;
}

/**
 * Gera um resumo do escaneamento em formato de texto
 * 
 * @param array $results Resultados do escaneamento
 * @return string Resumo em formato de texto
 */
function generateTextSummary($results) {
    $summary = "==============================================\n";
    $summary .= "    RESUMO DO ESCANEAMENTO DE SEGURANÇA    \n";
    $summary .= "==============================================\n\n";
    
    $summary .= "Total de arquivos: {$results['summary']['totalFiles']}\n";
    $summary .= "Arquivos escaneados: {$results['summary']['scannedFiles']}\n";
    $summary .= "Vulnerabilidades encontradas: {$results['summary']['vulnerabilitiesFound']}\n";
    $summary .= "Tempo de execução: {$results['executionTime']} segundos\n\n";
    
    $summary .= "Tipos de Vulnerabilidade Encontrados:\n";
    
    foreach ($results['summary']['vulnerabilityTypes'] as $vulnType) {
        $summary .= "  - {$vulnType['id']}: {$vulnType['type']} ({$vulnType['count']} ocorrências, Severidade: " . ucfirst($vulnType['severity']) . ")\n";
    }
    
    $summary .= "\n";
    $summary .= "Os resultados detalhados estão disponíveis nos arquivos de relatório.\n";
    $summary .= "==============================================\n";
    
    return $summary;
}

/**
 * Verifica se há atualizações disponíveis para o scanner
 * 
 * @param string $currentVersion Versão atual do scanner
 * @return array Informações sobre atualizações
 */
function checkForUpdates($currentVersion) {
    // Este é apenas um exemplo, você pode implementar a verificação real
    return [
        'hasUpdates' => false,
        'latestVersion' => $currentVersion,
        'updateUrl' => '',
        'releaseNotes' => ''
    ];
}

/**
 * Grupo de mitigações recomendadas para diferentes tipos de vulnerabilidade
 * 
 * @return array Mitigações recomendadas por tipo de vulnerabilidade
 */
function getRecommendedMitigations() {
    return [
        'CWE-79' => [
            'title' => 'Cross-site Scripting (XSS)',
            'description' => 'Vulnerabilidades XSS permitem que atacantes injetem scripts maliciosos em páginas web.',
            'recommendations' => [
                'Use SecurityManager::sanitize() ou htmlentities() para sanitizar dados antes de exibi-los.',
                'Implemente Content Security Policy (CSP) para limitar fontes de script.',
                'Use frameworks que escapam automaticamente conteúdo (como Twig, React, etc.).',
                'Valide entrada do usuário usando listas brancas.',
                'Use CSRF tokens para proteger formulários.'
            ],
            'code_example' => "// Código inseguro\necho \$_GET['input'];\n\n// Código seguro\necho SecurityManager::sanitize(\$_GET['input']);"
        ],
        'CWE-89' => [
            'title' => 'SQL Injection',
            'description' => 'Vulnerabilidades de SQL Injection permitem que atacantes executem comandos SQL maliciosos.',
            'recommendations' => [
                'Use prepared statements ou consultas parametrizadas para todas as consultas SQL.',
                'Use ORMs que protegem contra SQL Injection.',
                'Valide entrada do usuário usando listas brancas.',
                'Aplique princípio do privilégio mínimo para contas de banco de dados.'
            ],
            'code_example' => "// Código inseguro\n\$query = \"SELECT * FROM users WHERE username = '\$username'\";\n\n// Código seguro\n\$stmt = \$pdo->prepare(\"SELECT * FROM users WHERE username = ?\");\n\$stmt->execute([\$username]);"
        ],
        'CWE-22' => [
            'title' => 'Path Traversal',
            'description' => 'Vulnerabilidades de Path Traversal permitem que atacantes acessem arquivos fora dos diretórios pretendidos.',
            'recommendations' => [
                'Valide e normalize caminhos de arquivo usando realpath().',
                'Restrinja acesso a diretórios específicos.',
                'Use listas brancas para validar nomes de arquivo.',
                'Evite passar entrada do usuário diretamente para funções de manipulação de arquivos.'
            ],
            'code_example' => "// Código inseguro\n\$file = \$_GET['file'];\ninclude(\$file);\n\n// Código seguro\n\$file = basename(\$_GET['file']);\n\$safePath = realpath(SAFE_DIR . '/' . \$file);\nif (\$safePath && strpos(\$safePath, realpath(SAFE_DIR)) === 0) {\n    include(\$safePath);\n}"
        ]
    ];
}

/**
 * Obtém informações sobre recursos de segurança do PHP
 * 
 * @return array Informações sobre recursos de segurança
 */
function getPhpSecurityInfo() {
    $info = [
        'php_version' => PHP_VERSION,
        'safe_mode' => ini_get('safe_mode'),
        'allow_url_fopen' => ini_get('allow_url_fopen'),
        'allow_url_include' => ini_get('allow_url_include'),
        'open_basedir' => ini_get('open_basedir'),
        'disable_functions' => ini_get('disable_functions'),
        'display_errors' => ini_get('display_errors'),
        'log_errors' => ini_get('log_errors'),
        'error_reporting' => ini_get('error_reporting')
    ];
    
    // Verificar extensões de segurança
    $securityExtensions = [
        'openssl' => extension_loaded('openssl'),
        'filter' => extension_loaded('filter'),
        'hash' => extension_loaded('hash'),
        'json' => extension_loaded('json'),
        'mbstring' => extension_loaded('mbstring')
    ];
    
    $info['security_extensions'] = $securityExtensions;
    
    return $info;
}
