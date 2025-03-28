<?php
/**
 * ReportHelper - Classe para geração de relatórios em diferentes formatos
 * 
 * Esta classe facilita a geração de relatórios em CSV e PDF a partir de dados
 * fornecidos pelo sistema.
 */
class ReportHelper {
    
    /**
     * Gera um arquivo CSV a partir de um conjunto de dados
     * 
     * @param array $data Dados do relatório
     * @param array $headers Cabeçalhos das colunas
     * @param string $filename Nome do arquivo para download
     */
    public static function generateCSV($data, $headers, $filename = 'relatorio.csv') {
        // Definir cabeçalhos para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Criar arquivo CSV
        $output = fopen('php://output', 'w');
        
        // Adicionar BOM para suporte a caracteres UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // Cabeçalhos do CSV
        fputcsv($output, $headers);
        
        // Dados do CSV
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Gera um arquivo PDF a partir de um conjunto de dados
     * 
     * @param array $data Dados do relatório
     * @param array $headers Cabeçalhos das colunas
     * @param string $title Título do relatório
     * @param string $filename Nome do arquivo para download
     * @param array $config Configurações adicionais para o PDF
     */
    public static function generatePDF($data, $headers, $title, $filename = 'relatorio.pdf', $config = []) {
        // Verificar se a classe mPDF existe
        if (!class_exists('Mpdf\Mpdf')) {
            // Se não existir, usar biblioteca alternativa ou mostrar erro
            self::generatePDFAlternative($data, $headers, $title, $filename, $config);
            return;
        }
        
        // Configurações padrão
        $defaultConfig = [
            'orientation' => 'P', // P = Retrato, L = Paisagem
            'logo' => true,  // Incluir logo da loja
            'footer' => true, // Incluir rodapé com data e numeração
            'dateRange' => '', // Período do relatório
            'extraInfo' => [] // Informações adicionais para o cabeçalho
        ];
        
        // Mesclar configurações
        $config = array_merge($defaultConfig, $config);
        
        // Inicializar mPDF
        $mpdf = new \Mpdf\Mpdf([
            'orientation' => $config['orientation'],
            'margin_top' => 40,
            'margin_header' => 10,
            'margin_bottom' => 20,
            'margin_footer' => 10
        ]);
        
        // Definir metadados do documento
        $mpdf->SetTitle(STORE_NAME . ' - ' . $title);
        $mpdf->SetAuthor(STORE_NAME);
        $mpdf->SetCreator(STORE_NAME);
        
        // Cabeçalho e rodapé
        if ($config['footer']) {
            $mpdf->SetHTMLFooter('
                <table width="100%">
                    <tr>
                        <td width="33%" style="font-size: 8pt;">Gerado em: ' . date('d/m/Y H:i:s') . '</td>
                        <td width="33%" align="center" style="font-size: 8pt;">' . STORE_NAME . '</td>
                        <td width="33%" align="right" style="font-size: 8pt;">Página {PAGENO} de {nb}</td>
                    </tr>
                </table>');
        }
        
        // Iniciar conteúdo HTML
        $html = '
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 10pt;
                line-height: 1.5;
            }
            h1 {
                font-size: 16pt;
                color: #333;
                text-align: center;
                margin-bottom: 20px;
            }
            h2 {
                font-size: 14pt;
                color: #444;
                margin: 5px 0;
            }
            .subtitle {
                font-size: 11pt;
                color: #666;
                text-align: center;
                margin-bottom: 20px;
            }
            .meta {
                margin-bottom: 15px;
                font-size: 9pt;
                color: #666;
            }
            .meta-item {
                margin-bottom: 3px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            table, th, td {
                border: 1px solid #ddd;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
                text-align: left;
                padding: 8px;
            }
            td {
                padding: 6px 8px;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
        </style>
        
        <div class="header">
            <h1>' . $title . '</h1>';
        
        // Adicionar subtítulo com período se disponível
        if (!empty($config['dateRange'])) {
            $html .= '<div class="subtitle">' . $config['dateRange'] . '</div>';
        }
        
        // Adicionar metadados extras se disponíveis
        if (!empty($config['extraInfo'])) {
            $html .= '<div class="meta">';
            foreach ($config['extraInfo'] as $key => $value) {
                $html .= '<div class="meta-item"><strong>' . $key . ':</strong> ' . $value . '</div>';
            }
            $html .= '</div>';
        }
        
        // Tabela de dados
        $html .= '
        <table>
            <thead>
                <tr>';
        
        // Cabeçalhos
        foreach ($headers as $header) {
            $html .= '<th>' . $header . '</th>';
        }
        
        $html .= '
                </tr>
            </thead>
            <tbody>';
        
        // Dados da tabela
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
        
        // Finalizar HTML
        $html .= '</div>';
        
        // Escrever HTML no PDF
        $mpdf->WriteHTML($html);
        
        // Saída do PDF
        $mpdf->Output($filename, 'D');
        exit;
    }
    
    /**
     * Método alternativo para gerar PDF sem usar mPDF
     * Usa a biblioteca TCPDF que pode já estar incluída via Composer
     * ou recorre a uma solução de HTML para download
     */
    private static function generatePDFAlternative($data, $headers, $title, $filename, $config) {
        // Verificar se TCPDF está disponível
        if (class_exists('TCPDF')) {
            // Implementar geração com TCPDF
            // ...
        } else {
            // Solução alternativa: gerar HTML para visualização
            header('Content-Type: text/html; charset=utf-8');
            
            echo '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>' . $title . '</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                        line-height: 1.5;
                    }
                    h1 {
                        color: #333;
                    }
                    .notice {
                        background-color: #f8f9fa;
                        border-left: 4px solid #007bff;
                        margin: 20px 0;
                        padding: 15px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #f2f2f2;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    .text-center {
                        text-align: center;
                    }
                    .print-button {
                        display: inline-block;
                        background-color: #007bff;
                        color: white;
                        padding: 10px 15px;
                        text-decoration: none;
                        border-radius: 4px;
                        margin-bottom: 20px;
                    }
                    @media print {
                        .no-print {
                            display: none;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="no-print">
                    <div class="notice">
                        <p>A biblioteca PDF não está disponível no servidor. Exibindo relatório em formato HTML.</p>
                        <a href="#" class="print-button" onclick="window.print(); return false;">Imprimir Relatório</a>
                    </div>
                </div>
                
                <h1>' . $title . '</h1>';
                
            // Adicionar subtítulo com período se disponível
            if (!empty($config['dateRange'])) {
                echo '<p class="text-center">' . $config['dateRange'] . '</p>';
            }
            
            // Tabela de dados
            echo '<table>
                <thead>
                    <tr>';
            
            // Cabeçalhos
            foreach ($headers as $header) {
                echo '<th>' . $header . '</th>';
            }
            
            echo '</tr>
                </thead>
                <tbody>';
            
            // Dados da tabela
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . $cell . '</td>';
                }
                echo '</tr>';
            }
            
            echo '</tbody>
                </table>
                
                <div class="text-center">
                    <p>Gerado em: ' . date('d/m/Y H:i:s') . ' | ' . STORE_NAME . '</p>
                </div>
            </body>
            </html>';
            
            exit;
        }
    }
}