<?php
/**
 * PaymentGatewayInterface - Interface para integração com gateways de pagamento
 * 
 * Esta interface define o contrato que todas as implementações de gateway de pagamento
 * devem seguir, garantindo interoperabilidade e facilitando a adição de novos gateways.
 * 
 * @package     App\Lib\Payment
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
namespace App\Lib\Payment;

interface PaymentGatewayInterface {
    /**
     * Inicializa uma transação de pagamento
     * 
     * @param array $orderData Dados do pedido (id, número, total, items, etc.)
     * @param array $customerData Dados do cliente (nome, email, cpf, telefone, etc.)
     * @param array $paymentData Dados específicos do pagamento (método, parcelas, etc.)
     * @return array Dados da transação inicializada com chaves 'success', 'transaction_id' e 'redirect_url' (se aplicável)
     * @throws \Exception Em caso de falha na inicialização
     */
    public function initiateTransaction(array $orderData, array $customerData, array $paymentData): array;
    
    /**
     * Verifica o status de uma transação existente
     * 
     * @param string $transactionId ID da transação a ser verificada
     * @return array Informações atualizadas sobre a transação
     * @throws \Exception Em caso de falha na consulta
     */
    public function checkTransactionStatus(string $transactionId): array;
    
    /**
     * Processa um callback/webhook do gateway de pagamento
     * 
     * @param array $requestData Dados recebidos no callback (geralmente $_POST ou corpo da requisição)
     * @return array Informações processadas com chave 'success' indicando resultado
     * @throws \Exception Em caso de falha no processamento
     */
    public function handleCallback(array $requestData): array;
    
    /**
     * Cancela uma transação existente
     * 
     * @param string $transactionId ID da transação a ser cancelada
     * @param string $reason Motivo do cancelamento (opcional)
     * @return array Resultado do cancelamento com chave 'success' indicando resultado
     * @throws \Exception Em caso de falha no cancelamento
     */
    public function cancelTransaction(string $transactionId, string $reason = ''): array;
    
    /**
     * Reembolsa uma transação existente (total ou parcial)
     * 
     * @param string $transactionId ID da transação a ser reembolsada
     * @param float $amount Valor a ser reembolsado (opcional, se null faz reembolso total)
     * @param string $reason Motivo do reembolso (opcional)
     * @return array Resultado do reembolso com chave 'success' indicando resultado
     * @throws \Exception Em caso de falha no reembolso
     */
    public function refundTransaction(string $transactionId, ?float $amount = null, string $reason = ''): array;
    
    /**
     * Gera um token para uso futuro (comum em cartões de crédito)
     * 
     * @param array $cardData Dados do cartão a ser tokenizado
     * @return string Token gerado
     * @throws \Exception Em caso de falha na tokenização
     */
    public function generateToken(array $cardData): string;
    
    /**
     * Obtém dados de configuração necessários para o frontend
     * 
     * @param string $paymentMethod Método de pagamento específico (opcional)
     * @return array Configurações para o frontend (chaves públicas, IDs, etc.)
     */
    public function getFrontendConfig(?string $paymentMethod = null): array;
}
