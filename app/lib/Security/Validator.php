<?php
/**
 * Validator - Classe para validação de dados de entrada
 * 
 * Esta classe fornece métodos para validar diferentes tipos de dados,
 * ajudando a garantir que apenas dados válidos e seguros sejam processados.
 * 
 * @package     App\Lib\Security
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
class Validator {
    
    /**
     * Valida se o valor é um endereço de email válido
     * 
     * @param string $email Endereço de email a ser validado
     * @return bool Verdadeiro se for um email válido
     */
    public static function isEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida se o valor é uma URL válida
     * 
     * @param string $url URL a ser validada
     * @param bool $requireHttps Se deve exigir o protocolo HTTPS
     * @return bool Verdadeiro se for uma URL válida
     */
    public static function isUrl($url, $requireHttps = false) {
        $result = filter_var($url, FILTER_VALIDATE_URL) !== false;
        
        // Verificar se HTTPS é exigido
        if ($result && $requireHttps) {
            $result = strpos(strtolower($url), 'https://') === 0;
        }
        
        return $result;
    }
    
    /**
     * Valida se o valor é um número inteiro dentro de um intervalo
     * 
     * @param mixed $value Valor a ser validado
     * @param int $min Valor mínimo (opcional)
     * @param int $max Valor máximo (opcional)
     * @return bool Verdadeiro se for um inteiro válido dentro do intervalo
     */
    public static function isInt($value, $min = null, $max = null) {
        // Verificar se é um inteiro
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return false;
        }
        
        // Converter para inteiro
        $intValue = (int)$value;
        
        // Verificar mínimo
        if ($min !== null && $intValue < $min) {
            return false;
        }
        
        // Verificar máximo
        if ($max !== null && $intValue > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida se o valor é um número float dentro de um intervalo
     * 
     * @param mixed $value Valor a ser validado
     * @param float $min Valor mínimo (opcional)
     * @param float $max Valor máximo (opcional)
     * @return bool Verdadeiro se for um float válido dentro do intervalo
     */
    public static function isFloat($value, $min = null, $max = null) {
        // Aceitar vírgula ou ponto como separador decimal
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        
        // Verificar se é um float
        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            return false;
        }
        
        // Converter para float
        $floatValue = (float)$value;
        
        // Verificar mínimo
        if ($min !== null && $floatValue < $min) {
            return false;
        }
        
        // Verificar máximo
        if ($max !== null && $floatValue > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida se o valor corresponde a uma data válida
     * 
     * @param string $date Data a ser validada (pode ser em vários formatos)
     * @param string $format Formato esperado da data (opcional)
     * @return bool Verdadeiro se for uma data válida
     */
    public static function isDate($date, $format = 'Y-m-d') {
        if (empty($date)) {
            return false;
        }
        
        $dateTime = \DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }
    
    /**
     * Valida se a string tem um comprimento dentro do intervalo especificado
     * 
     * @param string $string String a ser validada
     * @param int $min Comprimento mínimo (opcional)
     * @param int $max Comprimento máximo (opcional)
     * @return bool Verdadeiro se o comprimento estiver dentro do intervalo
     */
    public static function hasLength($string, $min = null, $max = null) {
        $length = strlen((string)$string);
        
        // Verificar mínimo
        if ($min !== null && $length < $min) {
            return false;
        }
        
        // Verificar máximo
        if ($max !== null && $length > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida se o valor está contido em uma lista de valores permitidos
     * 
     * @param mixed $value Valor a ser validado
     * @param array $allowedValues Lista de valores permitidos
     * @param bool $strict Se deve usar comparação estrita (===)
     * @return bool Verdadeiro se o valor estiver na lista
     */
    public static function isInList($value, array $allowedValues, $strict = false) {
        return in_array($value, $allowedValues, $strict);
    }
    
    /**
     * Valida se o valor corresponde a um padrão regex
     * 
     * @param string $value Valor a ser validado
     * @param string $pattern Padrão regex
     * @return bool Verdadeiro se o valor corresponder ao padrão
     */
    public static function matchesPattern($value, $pattern) {
        return preg_match($pattern, (string)$value) === 1;
    }
    
    /**
     * Valida se o campo está preenchido (não vazio)
     * 
     * @param mixed $value Valor a ser validado
     * @return bool Verdadeiro se não estiver vazio
     */
    public static function isNotEmpty($value) {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        
        return !empty($value);
    }
    
    /**
     * Valida se o valor é um CPF válido
     * 
     * @param string $cpf CPF a ser validado
     * @return bool Verdadeiro se for um CPF válido
     */
    public static function isCpf($cpf) {
        // Remover caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', (string)$cpf);
        
        // Verificar se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }
        
        // Validar dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$t] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida se o valor é um CNPJ válido
     * 
     * @param string $cnpj CNPJ a ser validado
     * @return bool Verdadeiro se for um CNPJ válido
     */
    public static function isCnpj($cnpj) {
        // Remover caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', (string)$cnpj);
        
        // Verificar se tem 14 dígitos
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }
        
        // Validar primeiro dígito verificador
        $sum = 0;
        $peso = 5;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $peso;
            $peso = ($peso == 2) ? 9 : $peso - 1;
        }
        $d1 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        
        // Validar segundo dígito verificador
        $sum = 0;
        $peso = 6;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $peso;
            $peso = ($peso == 2) ? 9 : $peso - 1;
        }
        $d2 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        
        return ($cnpj[12] == $d1 && $cnpj[13] == $d2);
    }
    
    /**
     * Valida se o valor é um CEP válido
     * 
     * @param string $cep CEP a ser validado
     * @return bool Verdadeiro se for um CEP válido
     */
    public static function isCep($cep) {
        // Remover caracteres não numéricos
        $cep = preg_replace('/[^0-9]/', '', (string)$cep);
        
        // CEP deve ter 8 dígitos
        return strlen($cep) === 8;
    }
    
    /**
     * Valida se o valor é um número de telefone brasileiro válido
     * 
     * @param string $phone Número de telefone a ser validado
     * @return bool Verdadeiro se for um número de telefone válido
     */
    public static function isPhone($phone) {
        // Remover caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', (string)$phone);
        
        // Validar comprimento (8-9 dígitos + até 4 dígitos de DDD/DDI)
        $length = strlen($phone);
        return ($length >= 8 && $length <= 13);
    }
    
    /**
     * Valida uma senha de acordo com requisitos específicos
     * 
     * @param string $password Senha a ser validada
     * @param int $minLength Comprimento mínimo
     * @param bool $requireNumber Se deve exigir pelo menos um número
     * @param bool $requireSpecial Se deve exigir pelo menos um caractere especial
     * @param bool $requireMixed Se deve exigir letras maiúsculas e minúsculas
     * @return bool Verdadeiro se a senha atender aos requisitos
     */
    public static function isStrongPassword($password, $minLength = 8, $requireNumber = true, $requireSpecial = true, $requireMixed = true) {
        // Verificar comprimento mínimo
        if (strlen($password) < $minLength) {
            return false;
        }
        
        // Verificar se contém pelo menos um número
        if ($requireNumber && !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // Verificar se contém pelo menos um caractere especial
        if ($requireSpecial && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }
        
        // Verificar se contém letras maiúsculas e minúsculas
        if ($requireMixed && (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password))) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida se o arquivo é de um tipo permitido
     * 
     * @param array $file Informações do arquivo $_FILES['file']
     * @param array $allowedTypes Tipos MIME permitidos
     * @return bool Verdadeiro se o tipo do arquivo for permitido
     */
    public static function isAllowedFileType($file, array $allowedTypes) {
        if (!isset($file['type']) || empty($file['type'])) {
            return false;
        }
        
        return in_array($file['type'], $allowedTypes);
    }
    
    /**
     * Valida se o tamanho do arquivo está dentro do limite
     * 
     * @param array $file Informações do arquivo $_FILES['file']
     * @param int $maxSize Tamanho máximo em bytes
     * @return bool Verdadeiro se o tamanho do arquivo for válido
     */
    public static function isAllowedFileSize($file, $maxSize) {
        if (!isset($file['size']) || empty($file['size'])) {
            return false;
        }
        
        return $file['size'] <= $maxSize;
    }
    
    /**
     * Valida se o arquivo tem uma extensão permitida
     * 
     * @param array $file Informações do arquivo $_FILES['file']
     * @param array $allowedExtensions Extensões permitidas
     * @return bool Verdadeiro se a extensão do arquivo for permitida
     */
    public static function isAllowedFileExtension($file, array $allowedExtensions) {
        if (!isset($file['name']) || empty($file['name'])) {
            return false;
        }
        
        // Obter extensão do arquivo
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        return in_array($extension, array_map('strtolower', $allowedExtensions));
    }
    
    /**
     * Geral: valida o valor de acordo com o tipo especificado
     * 
     * @param mixed $value Valor a ser validado
     * @param string $type Tipo de validação
     * @param array $options Opções adicionais para a validação
     * @return bool Verdadeiro se o valor for válido
     */
    public static function validate($value, $type, array $options = []) {
        switch (strtolower($type)) {
            case 'email':
                return self::isEmail($value);
                
            case 'url':
                $requireHttps = isset($options['requireHttps']) ? $options['requireHttps'] : false;
                return self::isUrl($value, $requireHttps);
                
            case 'int':
                $min = isset($options['min']) ? $options['min'] : null;
                $max = isset($options['max']) ? $options['max'] : null;
                return self::isInt($value, $min, $max);
                
            case 'float':
                $min = isset($options['min']) ? $options['min'] : null;
                $max = isset($options['max']) ? $options['max'] : null;
                return self::isFloat($value, $min, $max);
                
            case 'date':
                $format = isset($options['format']) ? $options['format'] : 'Y-m-d';
                return self::isDate($value, $format);
                
            case 'length':
                $min = isset($options['min']) ? $options['min'] : null;
                $max = isset($options['max']) ? $options['max'] : null;
                return self::hasLength($value, $min, $max);
                
            case 'in_list':
                if (!isset($options['values']) || !is_array($options['values'])) {
                    return false;
                }
                $strict = isset($options['strict']) ? $options['strict'] : false;
                return self::isInList($value, $options['values'], $strict);
                
            case 'pattern':
                if (!isset($options['pattern'])) {
                    return false;
                }
                return self::matchesPattern($value, $options['pattern']);
                
            case 'not_empty':
                return self::isNotEmpty($value);
                
            case 'cpf':
                return self::isCpf($value);
                
            case 'cnpj':
                return self::isCnpj($value);
                
            case 'cep':
                return self::isCep($value);
                
            case 'phone':
                return self::isPhone($value);
                
            case 'password':
                $minLength = isset($options['minLength']) ? $options['minLength'] : 8;
                $requireNumber = isset($options['requireNumber']) ? $options['requireNumber'] : true;
                $requireSpecial = isset($options['requireSpecial']) ? $options['requireSpecial'] : true;
                $requireMixed = isset($options['requireMixed']) ? $options['requireMixed'] : true;
                return self::isStrongPassword($value, $minLength, $requireNumber, $requireSpecial, $requireMixed);
                
            case 'file_type':
                if (!isset($options['allowedTypes']) || !is_array($options['allowedTypes'])) {
                    return false;
                }
                return self::isAllowedFileType($value, $options['allowedTypes']);
                
            case 'file_size':
                if (!isset($options['maxSize'])) {
                    return false;
                }
                return self::isAllowedFileSize($value, $options['maxSize']);
                
            case 'file_extension':
                if (!isset($options['allowedExtensions']) || !is_array($options['allowedExtensions'])) {
                    return false;
                }
                return self::isAllowedFileExtension($value, $options['allowedExtensions']);
                
            default:
                // Tipo de validação desconhecido
                return false;
        }
    }
}
