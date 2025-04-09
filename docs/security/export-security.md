# Documentação de Segurança: Módulo de Exportação de Relatórios

## Função
O módulo de exportação de relatórios permite a geração de relatórios em formatos PDF e CSV (compatível com Excel) para análise de dados fora do sistema. Este componente implementa diversas medidas de segurança para garantir que dados sensíveis sejam protegidos e para mitigar potenciais vetores de ataque.

## Implementação

### Classes Principais
- `PdfExport`: Responsável pela geração segura de relatórios em formato PDF
- `ExcelExport`: Responsável pela geração segura de relatórios em formato CSV compatível com Excel

### Principais Medidas de Segurança Implementadas

#### 1. Validação de Dados
```php
private function validateData(array $data)
{
    if (empty($data)) {
        return false;
    }
    
    // Garantir que os dados estão em formato tabular consistente
    if (isset($data[0]) && is_array($data[0])) {
        $firstRowKeys = array_keys($data[0]);
        foreach ($data as $row) {
            if (!is_array($row) || array_diff_key($firstRowKeys, $row)) {
                return false;
            }
        }
    }
    
    return true;
}
```

#### 2. Sanitização de Nomes de Arquivo
```php
private function sanitizeFilename($filename)
{
    // Remover caracteres inválidos
    $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
    
    // Garantir comprimento máximo
    if (strlen($filename) > 100) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $basename = substr($basename, 0, 90); // Max 90 chars para o nome
        $filename = $basename . '.' . $extension;
    }
    
    return $filename;
}
```

#### 3. Prevenção de Injeção de Fórmulas no Excel
```php
private function sanitizeValue($value)
{
    // Prevenir injeção de fórmulas
    if (is_string($value) && strlen($value) > 0) {
        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@'])) {
            // Prefixar com apóstrofo para evitar interpretação como fórmula
            return "'" . $value;
        }
    }
    return $value;
}
```

#### 4. Headers HTTP Seguros para Download
```php
// Headers para download seguro de PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Headers para download seguro de CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: max-age=0');
header('Pragma: public');
```

#### 5. Sanitização de Saída para PDF
```php
// Sanitização para segurança em renderBody()
if (is_numeric($value)) {
    $body .= '<td style="text-align: right;">' . $value . '</td>';
} else {
    $body .= '<td>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
}
```

## Uso Correto

### Exemplo de uso do PdfExport
```php
// Exemplo de uso do PdfExport no controller
$pdfExport = new PdfExport('Relatório de Vendas', [
    'author' => 'Sistema de Relatórios - Taverna da Impressão 3D',
    'header_text' => 'Relatório gerado em ' . date('d/m/Y H:i:s'),
    'footer_text' => 'Taverna da Impressão 3D - Confidencial - Página {PAGE_NUM} de {PAGE_COUNT}'
]);

// Definir dados (após validação prévia)
$pdfExport->setData($data);

// Download do PDF
$pdfExport->download('relatorio_vendas_' . date('Ymd'));
```

### Exemplo de uso do ExcelExport
```php
// Exemplo de uso do ExcelExport no controller
$excelExport = new ExcelExport('Relatório de Produtos', [
    'creator' => 'Taverna da Impressão 3D',
    'subject' => 'Relatório de Produtos',
    'keywords' => 'relatório, produtos, taverna, impressão 3d'
]);

// Definir cabeçalhos e dados
$headers = ['ID', 'Produto', 'Categoria', 'Preço', 'Estoque'];
$excelExport->setHeaders($headers);
$excelExport->setData($data);

// Download do CSV
$excelExport->download('relatorio_produtos_' . date('Ymd'));
```

## Vulnerabilidades Mitigadas

- **Injeção de Código**: Sanitização de saída para prevenir XSS em PDFs
- **Path Traversal**: Sanitização de nomes de arquivo para downloads
- **Formula Injection**: Sanitização de valores para prevenir injeção de fórmulas em planilhas
- **Sobrecarga de Recursos**: Validação de dados para garantir que não há dados malformados
- **Divulgação de Informações**: Controle de acesso implementado no AdminReportController

## Testes de Segurança

- **Teste 1**: Injeção de fórmulas em células Excel
  - Descrição: Tentativa de injeção de fórmulas maliciosas em dados exportados para CSV
  - Resultado: As fórmulas são desativadas pelo método sanitizeValue()

- **Teste 2**: Path Traversal em nome de arquivo
  - Descrição: Tentativa de manipular caminhos com nomes como "../../../etc/passwd"
  - Resultado: Caracteres inválidos são substituídos pelo método sanitizeFilename()

- **Teste 3**: Injeção de HTML/JavaScript em PDF
  - Descrição: Tentativa de injeção de código em relatórios PDF
  - Resultado: O conteúdo é sanitizado via htmlspecialchars() antes da renderização

- **Teste 4**: Limite de taxa para exportação
  - Descrição: Verificação de proteção contra sobrecarga por muitas requisições
  - Resultado: RateLimiter no AdminReportController limita a 5 exportações por minuto

- **Teste 5**: Verificação de token CSRF
  - Descrição: Tentativa de realizar exportação sem token CSRF válido
  - Resultado: A exportação é bloqueada pelo AdminReportController