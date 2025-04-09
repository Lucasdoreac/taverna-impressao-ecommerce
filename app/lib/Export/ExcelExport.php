<?php
namespace App\Lib\Export;

/**
 * Classe ExcelExport
 * 
 * Responsável por gerar relatórios em formato Excel com garantias de segurança
 * 
 * @package App\Lib\Export
 */
class ExcelExport
{
    /**
     * @var array Configurações do Excel
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
     * @var array Cabeçalhos das colunas
     */
    private $headers;
    
    /**
     * Construtor
     * 
     * @param string $title Título do relatório
     * @param array $config Configurações do Excel (opcional)
     */
    public function __construct($title, $config = [])
    {
        $this->title = $title;
        
        // Configurações padrão
        $defaultConfig = [
            'creator' => 'Taverna da Impressão 3D',
            'company' => 'Taverna da Impressão 3D',
            'subject' => 'Relatório',
            'description' => 'Relatório gerado pelo sistema',
            'keywords' => 'relatório, taverna, impressão 3d',
            'category' => 'Relatórios',
            'sheet_name' => 'Relatório',
            'auto_filters' => true,
            'freeze_pane' => 'A2', // Congela primeira linha
        ];
        
        // Mesclar configurações padrão com as fornecidas
        $this->config = array_merge($defaultConfig, $config);
        
        // Inicializar dados
        $this->data = [];
        $this->headers = [];
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
            throw new \InvalidArgumentException("Dados inválidos para exportação Excel");
        }
        
        $this->data = $data;
        
        // Extrair cabeçalhos das colunas se não definidos
        if (empty($this->headers) && !empty($data)) {
            $this->headers = array_keys($data[0]);
        }
        
        return $this;
    }
    
    /**
     * Define cabeçalhos das colunas explicitamente
     * 
     * @param array $headers Cabeçalhos a serem usados
     * @return self
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
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
                if (!is_array($row) || array_diff_key($firstRowKeys, $row)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Gera o Excel e retorna o conteúdo
     * 
     * @return string Conteúdo binário do Excel
     */
    public function generate()
    {
        // Como não temos PHPSpreadsheet disponível, vamos implementar uma versão com fputcsv
        // que criará um arquivo CSV que pode ser aberto no Excel
        
        // Criar um arquivo temporário
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_export_');
        
        // Abrir o arquivo temporário para escrita
        $fp = fopen($tempFile, 'w');
        
        // Adicionar BOM para compatibilidade com Excel
        fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Escrever cabeçalho com o título
        fputcsv($fp, [$this->title]);
        fputcsv($fp, []); // Linha em branco após o título
        
        // Escrever cabeçalhos das colunas
        fputcsv($fp, $this->headers);
        
        // Escrever os dados
        foreach ($this->data as $row) {
            // Sanitizar valores para prevenir injeção de fórmulas
            $sanitizedRow = [];
            foreach ($row as $key => $value) {
                $sanitizedRow[] = $this->sanitizeValue($value);
            }
            fputcsv($fp, $sanitizedRow);
        }
        
        // Fechar o arquivo
        fclose($fp);
        
        // Ler o arquivo para a memória
        $content = file_get_contents($tempFile);
        
        // Remover o arquivo temporário
        unlink($tempFile);
        
        // Retornar o conteúdo
        return $content;
    }
    
    /**
     * Envia o Excel para download
     * 
     * @param string $filename Nome do arquivo para download
     * @return void
     */
    public function download($filename)
    {
        $content = $this->generate();
        
        // Sanitização do nome do arquivo para segurança
        $filename = $this->sanitizeFilename($filename);
        if (!str_ends_with(strtolower($filename), '.csv')) {
            $filename .= '.csv';
        }
        
        // Headers para download seguro
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: max-age=0');
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
     * Aplica formatação condicional para células
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Planilha
     * @param string $range Range para aplicar formatação
     * @param array $rules Regras de formatação
     * @return void
     */
    private function applyConditionalFormatting($sheet, $range, $rules)
    {
        // Método simulado - será implementado com PHPSpreadsheet
        // Exemplo de como seria:
        /*
        $conditionalStyles = [];
        
        foreach ($rules as $rule) {
            $conditional = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditional->setConditionType($rule['type']);
            $conditional->setOperatorType($rule['operator']);
            $conditional->setConditions($rule['conditions']);
            $conditional->getStyle()->applyFromArray($rule['style']);
            $conditionalStyles[] = $conditional;
        }
        
        $sheet->getStyle($range)->setConditionalStyles($conditionalStyles);
        */
    }
    
    /**
     * Adiciona cabeçalho ao documento
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Planilha
     * @return void
     */
    private function addHeader($sheet)
    {
        // Método simulado - será implementado com PHPSpreadsheet
        // Exemplo de como seria:
        /*
        // Título
        $sheet->setCellValue('A1', $this->title);
        $sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headers)) . '1');
        
        // Estilo do título
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        */
    }
    
    /**
     * Aplica proteção contra injeção de fórmulas maliciosas
     * 
     * @param string $value Valor a ser sanitizado
     * @return string Valor seguro
     */
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
}
