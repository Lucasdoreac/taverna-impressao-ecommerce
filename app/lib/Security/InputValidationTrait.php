<?php
/**
 * InputValidationTrait
 * 
 * Trait que fornece métodos de validação de entrada para controllers.
 * 
 * @package Taverna\Security
 * @author Claude Sonnet
 * @version 1.0.0
 */
trait InputValidationTrait {
    /**
     * Valida e sanitiza um parâmetro GET
     * 
     * @param string $field Nome do parâmetro
     * @param string $type Tipo de validação
     * @param array $options Opções de validação
     * @return mixed Valor validado e sanitizado ou null se inválido
     */
    protected function getValidatedParam($field, $type, array $options = []) {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::validate('GET', $field, $type, $options);
    }
    
    /**
     * Valida e sanitiza um parâmetro POST
     * 
     * @param string $field Nome do parâmetro
     * @param string $type Tipo de validação
     * @param array $options Opções de validação
     * @return mixed Valor validado e sanitizado ou null se inválido
     */
    protected function postValidatedParam($field, $type, array $options = []) {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::validate('POST', $field, $type, $options);
    }
    
    /**
     * Valida e sanitiza um parâmetro REQUEST (GET ou POST)
     * 
     * @param string $field Nome do parâmetro
     * @param string $type Tipo de validação
     * @param array $options Opções de validação
     * @return mixed Valor validado e sanitizado ou null se inválido
     */
    protected function requestValidatedParam($field, $type, array $options = []) {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::validate('REQUEST', $field, $type, $options);
    }
    
    /**
     * Valida e sanitiza um arquivo enviado
     * 
     * @param string $field Nome do parâmetro
     * @param array $options Opções de validação
     * @return array Dados do arquivo validados ou null se inválido
     */
    protected function fileValidatedParam($field, array $options = []) {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::validate('FILES', $field, 'file', $options);
    }
    
    /**
     * Valida e sanitiza um parâmetro de uma requisição JSON
     * 
     * @param string $field Nome do parâmetro
     * @param string $type Tipo de validação
     * @param array $options Opções de validação
     * @return mixed Valor validado e sanitizado ou null se inválido
     */
    protected function jsonValidatedParam($field, $type, array $options = []) {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::validate('JSON', $field, $type, $options);
    }
    
    /**
     * Valida e sanitiza múltiplos parâmetros GET
     * 
     * @param array $validations Array com validações a serem aplicadas
     * @return array Array com os valores validados e sanitizados
     */
    protected function getValidatedParams(array $validations) {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::validateAll('GET', $validations);
    }
    
    /**
     * Valida e sanitiza múltiplos parâmetros POST
     * 
     * @param array $validations Array com validações a serem aplicadas
     * @return array Array com os valores validados e sanitizados
     */
    protected function postValidatedParams(array $validations) {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::validateAll('POST', $validations);
    }
    
    /**
     * Verifica se houve erros de validação
     * 
     * @return bool True se houver erros, false caso contrário
     */
    protected function hasValidationErrors() {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::hasErrors();
    }
    
    /**
     * Obtém todos os erros de validação
     * 
     * @return array Array com erros de validação
     */
    protected function getValidationErrors() {
        require_once dirname(__FILE__) . '/InputValidator.php';
        return InputValidator::getErrors();
    }
    
    /**
     * Limpa os erros de validação
     * 
     * @return void
     */
    protected function clearValidationErrors() {
        require_once dirname(__FILE__) . '/InputValidator.php';
        InputValidator::clearErrors();
    }
    
    /**
     * Valida e processa um upload de arquivo com segurança
     * 
     * @param string $field Nome do campo de arquivo
     * @param string $destinationDir Diretório de destino
     * @param array $options Opções de validação e processamento
     * @return array|null Dados do arquivo processado ou null em caso de erro
     */
    protected function processValidatedFileUpload($field, $destinationDir, array $options = []) {
        try {
            // Validar o arquivo primeiro
            $file = $this->fileValidatedParam($field, $options);
            
            if (!$file) {
                return null;
            }
            
            // Carregar o SecurityManager para processar o upload
            require_once dirname(__FILE__) . '/SecurityManager.php';
            
            // Processar o upload com segurança
            return SecurityManager::processFileUpload($file, $destinationDir, $options);
        } catch (Exception $e) {
            // Adicionar erro de validação
            require_once dirname(__FILE__) . '/InputValidator.php';
            InputValidator::addError($field, $e->getMessage());
            return null;
        }
    }
}