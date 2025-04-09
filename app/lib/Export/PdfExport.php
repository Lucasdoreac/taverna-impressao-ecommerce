<?php
namespace App\Lib\Export;

/**
 * Classe PdfExport
 * 
 * Responsável por gerar relatórios em formato PDF com garantias de segurança
 * 
 * @package App\Lib\Export
 */
class PdfExport
{
    /**
     * @var array Configurações do PDF
     */
    private $config;
    
    /**
     * @var array Dados a serem exportados
     */
    private $data;
    
    /**
     * @var string Título do relatório
     */
    private $title;
    
    /**
     * Construtor
     * 
     * @param string $title Título do relatório
     * @param array $config Configurações do PDF (opcional)
     */
    public function __construct($title, $config = [])
    {
        $this->title = $title;
        
        // Configurações padrão
        $defaultConfig = [
            'orientation' => 'P', // Portrait
            'unit' => 'mm',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'author' => 'Taverna da Impressão 3D',
            'creator' => 'Sistema de Relatórios',
            'logo' => null,
            'header_text' => 'Relatório gerado em ' . date('d/m/Y H:i:s'),
            'footer_text' => 'Página {PAGE_NUM} de {PAGE_COUNT}',
        ];
        
        // Mesclar configurações padrão com as fornecidas
        $this->config = array_merge($defaultConfig, $config);
        
        // Inicializar dados
        $this->data = [];
    }
    
    /**
     * Define os dados a serem exportados
     * 
     * @param array $data Dados para exportação
     * @return self
     */
    public function setData(array $data)
    {
        // Verificação de segurança para dados
        if (!$this->validateData($data)) {
            throw new \InvalidArgumentException("Dados inválidos para exportação PDF");
        }
        
        $this->data = $data;
        return $this;
    }
    
    /**
     * Valida os dados para garantir que estão em formato seguro
     * 
     * @param array $data Dados a serem validados
     * @return bool True se os dados são válidos
     */
    private function validateData(array $data)
    {
        if (empty($data)) {
            return false;
        }
        
        // Garantir que os dados estão em formato tabular consistente
        if (isset($data[0]) && is_array($data[0])) {
            $firstRowKeys = array_keys($data[0]);
            foreach ($data as $row) {
                if (!is_array($row) || array_keys($row) !== $firstRowKeys) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Gera o PDF e retorna o conteúdo
     * 
     * @return string Conteúdo binário do PDF
     */
    public function generate()
    {
        // Configuração do mPDF
        $mpdfConfig = [
            'mode' => 'utf-8',
            'format' => $this->config['format'],
            'orientation' => $this->config['orientation'],
            'margin_left' => $this->config['margin_left'],
            'margin_right' => $this->config['margin_right'],
            'margin_top' => $this->config['margin_top'],
            'margin_bottom' => $this->config['margin_bottom'],
            'tempDir' => '/tmp',  // Diretório temporário
        ];
        
        // Criar instância do mPDF
        $mpdf = new \Mpdf\Mpdf($mpdfConfig);
        
        // Configurar metadados do documento
        $mpdf->SetTitle($this->title);
        $mpdf->SetAuthor($this->config['author']);
        $mpdf->SetCreator($this->config['creator']);
        
        // Definir cabeçalho e rodapé
        $mpdf->SetHeader($this->config['header_text']);
        $mpdf->SetFooter($this->config['footer_text']);
        
        // Adicionar conteúdo
        $html = '<html><head>';
        $html .= '<style>
            body { font-family: Arial, sans-serif; }
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #f0f0f0; font-weight: bold; }
            th, td { border: 1px solid #ddd; padding: 8px; }
            .numeric { text-align: right; }
            .header { text-align: center; font-weight: bold; font-size: 18pt; margin-bottom: 20px; }
            .subtitle { text-align: center; font-size: 14pt; margin-bottom: 15px; }
        </style>';
        $html .= '</head><body>';
        
        // Adicionar cabeçalho
        $html .= $this->renderHeader();
        
        // Adicionar corpo
        $html .= $this->renderBody();
        
        $html .= '</body></html>';
        
        // Escrever conteúdo HTML no PDF
        $mpdf->WriteHTML($html);
        
        // Retornar o conteúdo do PDF como string
        return $mpdf->Output('', 'S');
    }
    
    /**
     * Envia o PDF para download
     * 
     * @param string $filename Nome do arquivo para download
     * @return void
     */
    public function download($filename)
    {
        $content = $this->generate();
        
        // Sanitização do nome do arquivo para segurança
        $filename = $this->sanitizeFilename($filename);
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }
        
        // Headers para download seguro
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $content;
        exit;
    }
    
    /**
     * Sanitiza um nome de arquivo para segurança
     * 
     * @param string $filename Nome do arquivo
     * @return string Nome do arquivo sanitizado
     */
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
    
    /**
     * Renderiza o cabeçalho do PDF
     * 
     * @return string HTML do cabeçalho
     */
    private function renderHeader()
    {
        $header = '<table width="100%"><tr>';
        
        // Logo, se configurado
        if ($this->config['logo']) {
            $header .= '<td width="20%"><img src="' . $this->config['logo'] . '" /></td>';
        }
        
        // Título
        $header .= '<td style="text-align: center; font-weight: bold; font-size: 18pt;">' . 
                   htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . 
                   '</td>';
        
        // Data/hora
        $header .= '<td width="30%" style="text-align: right; font-size: 9pt;">' . 
                   htmlspecialchars($this->config['header_text'], ENT_QUOTES, 'UTF-8') . 
                   '</td>';
                   
        $header .= '</tr></table><hr />';
        
        return $header;
    }
    
    /**
     * Renderiza o corpo do relatório
     * 
     * @return string HTML do corpo
     */
    private function renderBody()
    {
        if (empty($this->data)) {
            return '<p>Nenhum dado disponível para este relatório.</p>';
        }
        
        $body = '<table border="1" cellpadding="2" cellspacing="0" width="100%">';
        
        // Cabeçalho da tabela
        $body .= '<tr style="background-color: #f0f0f0; font-weight: bold;">';
        foreach (array_keys($this->data[0]) as $column) {
            $body .= '<th>' . htmlspecialchars($column, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $body .= '</tr>';
        
        // Dados da tabela
        foreach ($this->data as $row) {
            $body .= '<tr>';
            foreach ($row as $value) {
                // Sanitização para segurança
                if (is_numeric($value)) {
                    $body .= '<td style="text-align: right;">' . $value . '</td>';
                } else {
                    $body .= '<td>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
                }
            }
            $body .= '</tr>';
        }
        
        $body .= '</table>';
        return $body;
    }
}
