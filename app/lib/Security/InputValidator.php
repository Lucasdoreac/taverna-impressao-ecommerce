<?php
/**
 * InputValidator
 * 
 * Classe responsável pela validação e sanitização de entrada de dados.
 * Implementa validação centralizada para uso consistente em todos os controllers.
 * 
 * @package Taverna\Security
 * @author Claude Sonnet
 * @version 1.1.0
 */
class InputValidator {
    /** @var array Armazena erros de validação */
    private static $errors = [];
    
    /** @var bool Indica se deve lançar exceções em caso de erro de validação */
    private static $throwExceptions = false;
    
    /**
     * Configura o comportamento do validador
     * 
     * @param array $options Opções de configuração
     * @return void
     */
    public static function configure(array $options = []) {
        self::$throwExceptions = isset($options['throwExceptions']) ? (bool)$options['throwExceptions'] : false;
    }
    
    /**
     * Valida e sanitiza um valor de entrada com base no tipo especificado
     * 
     * @param string $source Fonte dos dados ('GET', 'POST', 'REQUEST', 'FILES', 'JSON', 'COOKIE')
     * @param string $field Nome do campo a ser validado
     * @param string $type Tipo de validação ('string', 'int', 'float', 'email', 'url', 'date', 'datetime', 'bool', 'array', 'file')
     * @param array $options Opções adicionais de validação
     * @return mixed Valor validado e sanitizado ou null se inválido
     * @throws \Exception Se a validação falhar e throwExceptions estiver habilitado
     */
    public static function validate($source, $field, $type, array $options = []) {
        // Carregar valor da fonte especificada
        $value = self::getValueFromSource($source, $field);
        
        // Verificar se é obrigatório
        $required = isset($options['required']) ? (bool)$options['required'] : false;
        
        // Se o campo for obrigatório e não existir ou for vazio
        if ($required && ($value === null || $value === '')) {
            $errorMsg = isset($options['requiredMessage']) ? 
                $options['requiredMessage'] : 
                "O campo '{$field}' é obrigatório.";
            
            self::addError($field, $errorMsg);
            
            if (self::$throwExceptions) {
                throw new Exception($errorMsg);
            }
            
            return null;
        }
        
        // Se o valor for vazio e não obrigatório, retorna o padrão ou null
        if (($value === null || $value === '') && !$required) {
            return isset($options['default']) ? $options['default'] : null;
        }
        
        // Validar e sanitizar com base no tipo
        $validatedValue = self::validateByType($value, $type, $options, $field);
        
        return $validatedValue;
    }
    
    /**
     * Valida um valor para uso em operações de banco de dados
     * Não realiza sanitização para evitar interferência com prepared statements
     * 
     * @param string $source Fonte dos dados
     * @param string $field Nome do campo
     * @param string $type Tipo de validação
     * @param array $options Opções de validação
     * @return mixed Valor validado mas não sanitizado
     * @throws \Exception Se a validação falhar e throwExceptions estiver habilitado
     */
    public static function validateForDatabase($source, $field, $type, array $options = []) {
        // Forçar sanitize=false para garantir que o valor original seja preservado
        $options['sanitize'] = false;
        
        // Utilizar o método de validação padrão
        return self::validate($source, $field, $type, $options);
    }
    
    /**
     * Obtém um valor da fonte especificada
     * 
     * @param string $source Fonte dos dados
     * @param string $field Nome do campo
     * @return mixed Valor ou null se não existir
     */
    private static function getValueFromSource($source, $field) {
        $source = strtoupper($source);
        
        switch ($source) {
            case 'GET':
                return isset($_GET[$field]) ? $_GET[$field] : null;
                
            case 'POST':
                return isset($_POST[$field]) ? $_POST[$field] : null;
                
            case 'REQUEST':
                return isset($_REQUEST[$field]) ? $_REQUEST[$field] : null;
                
            case 'FILES':
                return isset($_FILES[$field]) ? $_FILES[$field] : null;
                
            case 'JSON':
                if (!isset($GLOBALS['_JSON'])) {
                    $input = file_get_contents('php://input');
                    $GLOBALS['_JSON'] = json_decode($input, true) ?: [];
                }
                return isset($GLOBALS['_JSON'][$field]) ? $GLOBALS['_JSON'][$field] : null;
                
            case 'COOKIE':
                return isset($_COOKIE[$field]) ? $_COOKIE[$field] : null;
                
            default:
                return null;
        }
    }
    
    /**
     * Valida um valor com base no tipo especificado
     * 
     * @param mixed $value Valor a ser validado
     * @param string $type Tipo de validação
     * @param array $options Opções de validação
     * @param string $field Nome do campo (para mensagens de erro)
     * @return mixed Valor validado e sanitizado ou null se inválido
     */
    private static function validateByType($value, $type, $options, $field) {
        $type = strtolower($type);
        
        try {
            switch ($type) {
                case 'string':
                    return self::validateString($value, $options, $field);
                    
                case 'int':
                    return self::validateInt($value, $options, $field);
                    
                case 'float':
                    return self::validateFloat($value, $options, $field);
                    
                case 'email':
                    return self::validateEmail($value, $options, $field);
                    
                case 'url':
                    return self::validateUrl($value, $options, $field);
                    
                case 'date':
                    return self::validateDate($value, $options, $field);
                    
                case 'datetime':
                    return self::validateDateTime($value, $options, $field);
                    
                case 'bool':
                    return self::validateBool($value, $options, $field);
                    
                case 'array':
                    return self::validateArray($value, $options, $field);
                    
                case 'file':
                    return self::validateFile($value, $options, $field);
                    
                case 'slug':
                    return self::validateSlug($value, $options, $field);
                    
                case 'enum':
                    return self::validateEnum($value, $options, $field);
                    
                case 'phone':
                    return self::validatePhone($value, $options, $field);
                    
                case 'cpf':
                    return self::validateCpf($value, $options, $field);
                    
                case 'cnpj':
                    return self::validateCnpj($value, $options, $field);
                    
                case 'cep':
                    return self::validateCep($value, $options, $field);
                    
                default:
                    self::addError($field, "Tipo de validação '{$type}' não suportado.");
                    return null;
            }
        } catch (Exception $e) {
            self::addError($field, $e->getMessage());
            
            if (self::$throwExceptions) {
                throw $e;
            }
            
            return null;
        }
    }
    
    /**
     * Valida uma string
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateString($value, $options, $field) {
        // Converter para string e remover espaços extras
        $value = is_string($value) ? trim($value) : (string)$value;
        
        // Verificar tamanho mínimo
        if (isset($options['minLength']) && strlen($value) < $options['minLength']) {
            $errorMsg = isset($options['minLengthMessage']) ? 
                $options['minLengthMessage'] : 
                "O campo '{$field}' deve ter pelo menos {$options['minLength']} caracteres.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar tamanho máximo
        if (isset($options['maxLength']) && strlen($value) > $options['maxLength']) {
            $errorMsg = isset($options['maxLengthMessage']) ? 
                $options['maxLengthMessage'] : 
                "O campo '{$field}' deve ter no máximo {$options['maxLength']} caracteres.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar padrão (regex)
        if (isset($options['pattern']) && !preg_match($options['pattern'], $value)) {
            $errorMsg = isset($options['patternMessage']) ? 
                $options['patternMessage'] : 
                "O campo '{$field}' não está no formato esperado.";
            
            throw new Exception($errorMsg);
        }
        
        // Sanitizar a string (remover HTML se sanitize=true)
        if (!isset($options['sanitize']) || $options['sanitize'] !== false) {
            // Utilizar sanitização avançada contra XSS ao invés do htmlspecialchars básico
            $value = self::sanitizeAdvancedXSS($value);
        }
        
        return $value;
    }
    
    /**
     * Sanitização avançada contra ataques XSS
     * Implementa proteção contra técnicas de codificação e ofuscação
     * 
     * @param string $value Valor a ser sanitizado
     * @return string Valor sanitizado
     */
    public static function sanitizeAdvancedXSS($value) {
        // Verificar se o valor é uma string
        if (!is_string($value)) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
        
        // Iterativamente decodificar a string para lidar com codificação em camadas
        $prev = '';
        $current = $value;
        $iterations = 0;
        $maxIterations = 5; // Limite para evitar loops infinitos
        
        while ($prev !== $current && $iterations < $maxIterations) {
            $prev = $current;
            // Decodifica entidades HTML potencialmente maliciosas
            $current = html_entity_decode($current, ENT_QUOTES, 'UTF-8');
            $iterations++;
        }
        
        // Sanitização básica após decodificação
        $sanitized = htmlspecialchars($current, ENT_QUOTES, 'UTF-8');
        
        // Remoção de vetores de ataque baseados em atributos
        // Remover atributos de evento JavaScript (on*)
        $sanitized = preg_replace('/\bon\w+\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $sanitized);
        
        // Remover atributos data- que podem ser usados em navegadores modernos para XSS
        $sanitized = preg_replace('/\bdata-\w+\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $sanitized);
        
        // Remover referências a javascript: em URLs
        $sanitized = preg_replace('/javascript\s*:/i', '', $sanitized);
        
        // Remover expressões CSS potencialmente perigosas
        $sanitized = preg_replace('/expression\s*\(.*?\)/i', '', $sanitized);
        
        return $sanitized;
    }
    
    /**
     * Valida um inteiro
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return int Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateInt($value, $options, $field) {
        // Verificar se é um inteiro válido
        if (!is_numeric($value) || (string)(int)$value !== (string)$value) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve ser um número inteiro.";
            
            throw new Exception($errorMsg);
        }
        
        $value = (int)$value;
        
        // Verificar valor mínimo
        if (isset($options['min']) && $value < $options['min']) {
            $errorMsg = isset($options['minMessage']) ? 
                $options['minMessage'] : 
                "O campo '{$field}' deve ser maior ou igual a {$options['min']}.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar valor máximo
        if (isset($options['max']) && $value > $options['max']) {
            $errorMsg = isset($options['maxMessage']) ? 
                $options['maxMessage'] : 
                "O campo '{$field}' deve ser menor ou igual a {$options['max']}.";
            
            throw new Exception($errorMsg);
        }
        
        return $value;
    }
    
    /**
     * Valida um float
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return float Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateFloat($value, $options, $field) {
        // Verificar se é um float válido
        if (!is_numeric($value)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve ser um número decimal.";
            
            throw new Exception($errorMsg);
        }
        
        $value = (float)$value;
        
        // Verificar valor mínimo
        if (isset($options['min']) && $value < $options['min']) {
            $errorMsg = isset($options['minMessage']) ? 
                $options['minMessage'] : 
                "O campo '{$field}' deve ser maior ou igual a {$options['min']}.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar valor máximo
        if (isset($options['max']) && $value > $options['max']) {
            $errorMsg = isset($options['maxMessage']) ? 
                $options['maxMessage'] : 
                "O campo '{$field}' deve ser menor ou igual a {$options['max']}.";
            
            throw new Exception($errorMsg);
        }
        
        return $value;
    }
    
    /**
     * Valida um e-mail
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateEmail($value, $options, $field) {
        $value = trim((string)$value);
        
        // Verificar formato de e-mail
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um endereço de e-mail válido.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar domínios proibidos (opcional)
        if (isset($options['blockedDomains']) && is_array($options['blockedDomains'])) {
            $domain = substr($value, strpos($value, '@') + 1);
            
            if (in_array($domain, $options['blockedDomains'])) {
                $errorMsg = isset($options['blockedDomainMessage']) ? 
                    $options['blockedDomainMessage'] : 
                    "O domínio de e-mail '{$domain}' não é permitido.";
                
                throw new Exception($errorMsg);
            }
        }
        
        return $value;
    }
    
    /**
     * Valida uma URL
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateUrl($value, $options, $field) {
        $value = trim((string)$value);
        
        // Verificar formato de URL
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter uma URL válida.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar protocolo (opcional)
        if (isset($options['protocols']) && is_array($options['protocols'])) {
            $protocol = parse_url($value, PHP_URL_SCHEME);
            
            if (!in_array($protocol, $options['protocols'])) {
                $allowedProtocols = implode(', ', $options['protocols']);
                $errorMsg = isset($options['protocolMessage']) ? 
                    $options['protocolMessage'] : 
                    "O protocolo da URL deve ser um dos seguintes: {$allowedProtocols}.";
                
                throw new Exception($errorMsg);
            }
        }
        
        return $value;
    }
    
    /**
     * Valida uma data
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateDate($value, $options, $field) {
        $value = trim((string)$value);
        
        // Formato padrão: YYYY-MM-DD
        $format = isset($options['format']) ? $options['format'] : 'Y-m-d';
        
        // Criar objeto DateTime
        $date = \DateTime::createFromFormat($format, $value);
        
        // Verificar se a data é válida
        if (!$date || $date->format($format) !== $value) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter uma data válida no formato {$format}.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar data mínima (opcional)
        if (isset($options['minDate'])) {
            $minDate = new \DateTime($options['minDate']);
            
            if ($date < $minDate) {
                $errorMsg = isset($options['minDateMessage']) ? 
                    $options['minDateMessage'] : 
                    "A data no campo '{$field}' deve ser maior ou igual a {$minDate->format($format)}.";
                
                throw new Exception($errorMsg);
            }
        }
        
        // Verificar data máxima (opcional)
        if (isset($options['maxDate'])) {
            $maxDate = new \DateTime($options['maxDate']);
            
            if ($date > $maxDate) {
                $errorMsg = isset($options['maxDateMessage']) ? 
                    $options['maxDateMessage'] : 
                    "A data no campo '{$field}' deve ser menor ou igual a {$maxDate->format($format)}.";
                
                throw new Exception($errorMsg);
            }
        }
        
        return $value;
    }
    
    /**
     * Valida um datetime
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateDateTime($value, $options, $field) {
        $value = trim((string)$value);
        
        // Formato padrão: YYYY-MM-DD HH:MM:SS
        $format = isset($options['format']) ? $options['format'] : 'Y-m-d H:i:s';
        
        // Criar objeto DateTime
        $date = \DateTime::createFromFormat($format, $value);
        
        // Verificar se o datetime é válido
        if (!$date || $date->format($format) !== $value) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter uma data e hora válidas no formato {$format}.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar datetime mínimo (opcional)
        if (isset($options['minDateTime'])) {
            $minDateTime = new \DateTime($options['minDateTime']);
            
            if ($date < $minDateTime) {
                $errorMsg = isset($options['minDateTimeMessage']) ? 
                    $options['minDateTimeMessage'] : 
                    "A data e hora no campo '{$field}' devem ser maiores ou iguais a {$minDateTime->format($format)}.";
                
                throw new Exception($errorMsg);
            }
        }
        
        // Verificar datetime máximo (opcional)
        if (isset($options['maxDateTime'])) {
            $maxDateTime = new \DateTime($options['maxDateTime']);
            
            if ($date > $maxDateTime) {
                $errorMsg = isset($options['maxDateTimeMessage']) ? 
                    $options['maxDateTimeMessage'] : 
                    "A data e hora no campo '{$field}' devem ser menores ou iguais a {$maxDateTime->format($format)}.";
                
                throw new Exception($errorMsg);
            }
        }
        
        return $value;
    }
    
    /**
     * Valida um booleano
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return bool Valor validado e sanitizado
     */
    private static function validateBool($value, $options, $field) {
        // Valores considerados como true
        $trueValues = isset($options['trueValues']) ? 
            $options['trueValues'] : 
            [true, 1, '1', 'true', 'yes', 'y', 'on'];
        
        // Valores considerados como false
        $falseValues = isset($options['falseValues']) ? 
            $options['falseValues'] : 
            [false, 0, '0', 'false', 'no', 'n', 'off'];
        
        // Converter para string para comparação
        if (!is_string($value) && !is_bool($value) && !is_numeric($value)) {
            $value = (string)$value;
        }
        
        // Verificar se é true
        if (in_array($value, $trueValues, true) || in_array($value, $trueValues, false)) {
            return true;
        }
        
        // Verificar se é false
        if (in_array($value, $falseValues, true) || in_array($value, $falseValues, false)) {
            return false;
        }
        
        // Valor padrão se não for reconhecido
        return isset($options['default']) ? (bool)$options['default'] : false;
    }
    
    /**
     * Valida um array
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return array Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateArray($value, $options, $field) {
        // Converter para array se for string
        if (is_string($value) && isset($options['delimiter'])) {
            $value = explode($options['delimiter'], $value);
        }
        
        // Verificar se é um array
        if (!is_array($value)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve ser um array.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar tamanho mínimo (opcional)
        if (isset($options['minItems']) && count($value) < $options['minItems']) {
            $errorMsg = isset($options['minItemsMessage']) ? 
                $options['minItemsMessage'] : 
                "O campo '{$field}' deve conter pelo menos {$options['minItems']} itens.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar tamanho máximo (opcional)
        if (isset($options['maxItems']) && count($value) > $options['maxItems']) {
            $errorMsg = isset($options['maxItemsMessage']) ? 
                $options['maxItemsMessage'] : 
                "O campo '{$field}' deve conter no máximo {$options['maxItems']} itens.";
            
            throw new Exception($errorMsg);
        }
        
        // Validar itens individualmente (opcional)
        if (isset($options['itemType'])) {
            $validatedItems = [];
            
            foreach ($value as $key => $item) {
                try {
                    $itemField = "{$field}[{$key}]";
                    $validatedItem = self::validateByType($item, $options['itemType'], $options, $itemField);
                    $validatedItems[$key] = $validatedItem;
                } catch (Exception $e) {
                    throw new Exception("Erro no item {$key} do campo '{$field}': " . $e->getMessage());
                }
            }
            
            $value = $validatedItems;
        }
        
        return $value;
    }
    
    /**
     * Valida um arquivo
     * 
     * @param mixed $value Valor a ser validado ($_FILES[campo])
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return array Dados do arquivo validados
     * @throws Exception Se a validação falhar
     */
    private static function validateFile($value, $options, $field) {
        // Verificar se é um upload de arquivo válido
        if (!is_array($value) || !isset($value['tmp_name']) || !isset($value['name']) || !isset($value['error'])) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve ser um arquivo.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar se não houve erro no upload
        if ($value['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = self::getFileUploadErrorMessage($value['error'], $field, $options);
            throw new Exception($errorMsg);
        }
        
        // Verificar tamanho máximo (opcional)
        if (isset($options['maxSize']) && $value['size'] > $options['maxSize']) {
            $maxSizeFormatted = self::formatFileSize($options['maxSize']);
            $errorMsg = isset($options['maxSizeMessage']) ? 
                $options['maxSizeMessage'] : 
                "O arquivo no campo '{$field}' deve ter no máximo {$maxSizeFormatted}.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar extensão (opcional)
        if (isset($options['allowedExtensions']) && is_array($options['allowedExtensions'])) {
            $extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $options['allowedExtensions'])) {
                $allowedExtensions = implode(', ', $options['allowedExtensions']);
                $errorMsg = isset($options['extensionMessage']) ? 
                    $options['extensionMessage'] : 
                    "O arquivo no campo '{$field}' deve ter uma das seguintes extensões: {$allowedExtensions}.";
                
                throw new Exception($errorMsg);
            }
        }
        
        // Verificar tipo MIME (opcional)
        if (isset($options['allowedMimeTypes']) && is_array($options['allowedMimeTypes'])) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($value['tmp_name']);
            
            if (!in_array($mimeType, $options['allowedMimeTypes'])) {
                $allowedMimeTypes = implode(', ', $options['allowedMimeTypes']);
                $errorMsg = isset($options['mimeTypeMessage']) ? 
                    $options['mimeTypeMessage'] : 
                    "O arquivo no campo '{$field}' deve ser de um dos seguintes tipos: {$allowedMimeTypes}.";
                
                throw new Exception($errorMsg);
            }
        }
        
        return $value;
    }
    
    /**
     * Valida um slug
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateSlug($value, $options, $field) {
        $value = trim((string)$value);
        
        // Padrão de slug: letras minúsculas, números e hífens
        $pattern = '/^[a-z0-9-]+$/';
        
        if (!preg_match($pattern, $value)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter apenas letras minúsculas, números e hífens.";
            
            throw new Exception($errorMsg);
        }
        
        return $value;
    }
    
    /**
     * Valida um valor enum (lista de valores permitidos)
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return mixed Valor validado
     * @throws Exception Se a validação falhar
     */
    private static function validateEnum($value, $options, $field) {
        // Verificar se as opções permitidas foram fornecidas
        if (!isset($options['allowedValues']) || !is_array($options['allowedValues'])) {
            throw new Exception("As opções permitidas não foram especificadas para o campo '{$field}'.");
        }
        
        // Verificar se o valor está na lista de permitidos
        if (!in_array($value, $options['allowedValues'], true)) {
            $allowedValues = implode(', ', $options['allowedValues']);
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O valor do campo '{$field}' deve ser um dos seguintes: {$allowedValues}.";
            
            throw new Exception($errorMsg);
        }
        
        return $value;
    }
    
    /**
     * Valida um número de telefone
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validatePhone($value, $options, $field) {
        $value = preg_replace('/\D/', '', (string)$value);
        
        // Verificar tamanho (padrão Brasil)
        $minLength = isset($options['minLength']) ? $options['minLength'] : 10; // DDD + número
        $maxLength = isset($options['maxLength']) ? $options['maxLength'] : 11; // DDD + 9 + número
        
        if (strlen($value) < $minLength || strlen($value) > $maxLength) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um número de telefone válido.";
            
            throw new Exception($errorMsg);
        }
        
        return $value;
    }
    
    /**
     * Valida um CPF
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateCpf($value, $options, $field) {
        $value = preg_replace('/\D/', '', (string)$value);
        
        // Verificar tamanho (CPF tem 11 dígitos)
        if (strlen($value) !== 11) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um CPF válido.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1+$/', $value)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um CPF válido.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $value[$c] * (($t + 1) - $c);
            }
            
            $d = ((10 * $d) % 11) % 10;
            
            if ($value[$c] != $d) {
                $errorMsg = isset($options['invalidMessage']) ? 
                    $options['invalidMessage'] : 
                    "O campo '{$field}' deve conter um CPF válido.";
                
                throw new Exception($errorMsg);
            }
        }
        
        return $value;
    }
    
    /**
     * Valida um CNPJ
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateCnpj($value, $options, $field) {
        $value = preg_replace('/\D/', '', (string)$value);
        
        // Verificar tamanho (CNPJ tem 14 dígitos)
        if (strlen($value) !== 14) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um CNPJ válido.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1+$/', $value)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um CNPJ válido.";
            
            throw new Exception($errorMsg);
        }
        
        // Verificar dígitos verificadores
        $sum = 0;
        $weight = 5;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $value[$i] * $weight;
            $weight = ($weight == 2) ? 9 : $weight - 1;
        }
        
        $result = $sum % 11;
        
        if ($value[12] != ($result < 2 ? 0 : 11 - $result)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um CNPJ válido.";
            
            throw new Exception($errorMsg);
        }
        
        $sum = 0;
        $weight = 6;
        
        for ($i = 0; $i < 13; $i++) {
            $sum += $value[$i] * $weight;
            $weight = ($weight == 2) ? 9 : $weight - 1;
        }
        
        $result = $sum % 11;
        
        if ($value[13] != ($result < 2 ? 0 : 11 - $result)) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um CNPJ válido.";
            
            throw new Exception($errorMsg);
        }
        
        return $value;
    }
    
    /**
     * Valida um CEP brasileiro
     * 
     * @param mixed $value Valor a ser validado
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return string Valor validado e sanitizado
     * @throws Exception Se a validação falhar
     */
    private static function validateCep($value, $options, $field) {
        $value = preg_replace('/\D/', '', (string)$value);
        
        // Verificar tamanho (CEP tem 8 dígitos)
        if (strlen($value) !== 8) {
            $errorMsg = isset($options['invalidMessage']) ? 
                $options['invalidMessage'] : 
                "O campo '{$field}' deve conter um CEP válido.";
            
            throw new Exception($errorMsg);
        }
        
        return $value;
    }
    
    /**
     * Adiciona um erro de validação
     * 
     * @param string $field Nome do campo
     * @param string $message Mensagem de erro
     * @return void
     */
    private static function addError($field, $message) {
        if (!isset(self::$errors[$field])) {
            self::$errors[$field] = [];
        }
        
        self::$errors[$field][] = $message;
    }
    
    /**
     * Obtém todos os erros de validação
     * 
     * @return array Array com erros de validação
     */
    public static function getErrors() {
        return self::$errors;
    }
    
    /**
     * Verifica se houve erros de validação
     * 
     * @return bool True se houver erros, false caso contrário
     */
    public static function hasErrors() {
        return !empty(self::$errors);
    }
    
    /**
     * Limpa os erros de validação
     * 
     * @return void
     */
    public static function clearErrors() {
        self::$errors = [];
    }
    
    /**
     * Formata o tamanho de arquivo para exibição
     * 
     * @param int $size Tamanho em bytes
     * @return string Tamanho formatado
     */
    private static function formatFileSize($size) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Obtém mensagem de erro de upload de arquivo
     * 
     * @param int $errorCode Código de erro
     * @param string $field Nome do campo
     * @param array $options Opções de validação
     * @return string Mensagem de erro
     */
    private static function getFileUploadErrorMessage($errorCode, $field, $options) {
        $messageKey = "uploadErrorMessage{$errorCode}";
        
        if (isset($options[$messageKey])) {
            return $options[$messageKey];
        }
        
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "O arquivo no campo '{$field}' excede o tamanho máximo permitido pelo servidor.";
                
            case UPLOAD_ERR_FORM_SIZE:
                return "O arquivo no campo '{$field}' excede o tamanho máximo permitido pelo formulário.";
                
            case UPLOAD_ERR_PARTIAL:
                return "O arquivo no campo '{$field}' foi enviado apenas parcialmente.";
                
            case UPLOAD_ERR_NO_FILE:
                return "Nenhum arquivo foi enviado no campo '{$field}'.";
                
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Erro no servidor: diretório temporário não encontrado.";
                
            case UPLOAD_ERR_CANT_WRITE:
                return "Erro no servidor: falha ao escrever o arquivo no disco.";
                
            case UPLOAD_ERR_EXTENSION:
                return "Erro no servidor: upload interrompido por uma extensão PHP.";
                
            default:
                return "Erro desconhecido no upload do arquivo no campo '{$field}'.";
        }
    }
    
    /**
     * Método auxiliar que realiza validação e sanitização de parâmetros de entrada
     * 
     * @param string $source Fonte dos dados ('GET', 'POST', 'REQUEST', 'FILES', 'JSON', 'COOKIE')
     * @param array $validations Array com validações a serem aplicadas
     * @return array Array com os valores validados e sanitizados
     * @throws Exception Se a validação falhar e throwExceptions estiver habilitado
     */
    public static function validateAll($source, array $validations) {
        $results = [];
        
        foreach ($validations as $field => $validation) {
            if (!isset($validation['type'])) {
                throw new Exception("Tipo de validação não especificado para o campo '{$field}'.");
            }
            
            $type = $validation['type'];
            unset($validation['type']);
            
            $results[$field] = self::validate($source, $field, $type, $validation);
        }
        
        return $results;
    }
}