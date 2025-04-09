<?php
/**
 * View para gerenciamento de gateways de pagamento
 * 
 * Exibe lista de gateways configurados e permite gerenciá-los
 * 
 * @package     App\Views\Admin\Payment
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
?>

<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/pagamentos">Pagamentos</a></li>
                        <li class="breadcrumb-item active">Gateways</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Sucesso!</h5>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Gateways de Pagamento Configurados</h3>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>admin/pagamentos/configuracoes" class="btn btn-sm btn-primary">
                                    <i class="fas fa-cog"></i> Configurações
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Gateway</th>
                                            <th>Status</th>
                                            <th>Ambiente</th>
                                            <th>Métodos de Pagamento</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($gateways)): ?>
                                            <?php foreach ($gateways as $gateway): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($gateway['display_name']) ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($gateway['name']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($gateway['is_active']): ?>
                                                            <span class="badge badge-success">Ativo</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Inativo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($gateway['is_sandbox']): ?>
                                                            <span class="badge badge-warning">Sandbox (Teste)</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-primary">Produção</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($gateway['payment_methods'])): ?>
                                                            <?php foreach ($gateway['payment_methods'] as $method): ?>
                                                                <?php
                                                                $badgeClass = 'secondary';
                                                                $methodName = '';
                                                                
                                                                switch ($method) {
                                                                    case 'credit_card':
                                                                        $badgeClass = 'info';
                                                                        $methodName = 'Cartão de Crédito';
                                                                        break;
                                                                    case 'boleto':
                                                                        $badgeClass = 'primary';
                                                                        $methodName = 'Boleto';
                                                                        break;
                                                                    case 'pix':
                                                                        $badgeClass = 'success';
                                                                        $methodName = 'PIX';
                                                                        break;
                                                                    case 'paypal':
                                                                        $badgeClass = 'primary';
                                                                        $methodName = 'PayPal';
                                                                        break;
                                                                    default:
                                                                        $methodName = ucfirst($method);
                                                                }
                                                                ?>
                                                                <span class="badge badge-<?= $badgeClass ?> mr-1"><?= $methodName ?></span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Nenhum método configurado</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="<?= BASE_URL ?>admin/pagamentos/configuracoes#<?= $gateway['name'] ?>-tab" class="btn btn-sm btn-info">
                                                            <i class="fas fa-edit"></i> Configurar
                                                        </a>
                                                        <?php if ($gateway['is_active']): ?>
                                                            <button type="button" class="btn btn-sm btn-warning toggle-gateway" data-gateway="<?= htmlspecialchars($gateway['name']) ?>" data-action="disable">
                                                                <i class="fas fa-pause"></i> Desativar
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-success toggle-gateway" data-gateway="<?= htmlspecialchars($gateway['name']) ?>" data-action="enable">
                                                                <i class="fas fa-play"></i> Ativar
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Nenhum gateway configurado</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Documentação de Integração -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Documentação de Integração</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">MercadoPago</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>O MercadoPago oferece suporte para os seguintes métodos de pagamento:</p>
                                            <ul>
                                                <li><strong>Cartão de Crédito</strong> - Com tokenização segura</li>
                                                <li><strong>Boleto Bancário</strong> - Com geração de código de barras</li>
                                                <li><strong>PIX</strong> - Com geração de QR Code</li>
                                            </ul>
                                            
                                            <p>Links úteis:</p>
                                            <ul>
                                                <li><a href="https://www.mercadopago.com.br/developers/pt/docs" target="_blank">Documentação Oficial</a></li>
                                                <li><a href="https://www.mercadopago.com.br/developers/panel" target="_blank">Painel de Desenvolvedores</a></li>
                                                <li><a href="<?= BASE_URL ?>docs/payment/mercadopago_integration.md" target="_blank">Documentação Interna</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">PayPal</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>O PayPal oferece suporte para os seguintes métodos de pagamento:</p>
                                            <ul>
                                                <li><strong>Checkout PayPal</strong> - Redirecionamento para o site do PayPal</li>
                                                <li><strong>PayPal Express Checkout</strong> - Integração direta na página</li>
                                            </ul>
                                            
                                            <p>Links úteis:</p>
                                            <ul>
                                                <li><a href="https://developer.paypal.com/docs/api/overview/" target="_blank">Documentação Oficial</a></li>
                                                <li><a href="https://developer.paypal.com/dashboard/" target="_blank">Painel de Desenvolvedores</a></li>
                                                <li><a href="<?= BASE_URL ?>docs/security/PayPalIntegration.md" target="_blank">Documentação de Segurança</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card card-outline card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">Adicionando Novos Gateways</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>Para adicionar um novo gateway de pagamento ao sistema, siga estes passos:</p>
                                            <ol>
                                                <li>Crie uma nova classe que implemente a interface <code>PaymentGatewayInterface</code></li>
                                                <li>Implemente todos os métodos necessários conforme a documentação</li>
                                                <li>Adicione as configurações necessárias no banco de dados</li>
                                                <li>Configure os métodos de pagamento e URLs de webhook</li>
                                            </ol>
                                            
                                            <p>Exemplo básico de implementação:</p>
                                            <pre><code>// Em app/lib/Payment/Gateways/NovoGateway.php
namespace App\Lib\Payment\Gateways;

use App\Lib\Payment\PaymentGatewayInterface;

class NovoGatewayGateway implements PaymentGatewayInterface {
    private $config;
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function initiateTransaction(array $orderData, array $customerData, array $paymentData): array {
        // Implementação específica
    }
    
    public function checkTransactionStatus(string $transactionId): array {
        // Implementação específica
    }
    
    // Implemente os demais métodos da interface
}
</code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status e Métricas -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Status dos Gateways</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($gateways as $gateway): ?>
                                    <div class="col-md-4">
                                        <div class="info-box <?= $gateway['is_active'] ? '' : 'bg-gray' ?>">
                                            <span class="info-box-icon <?= $gateway['is_active'] ? 'bg-info' : 'bg-gray' ?>">
                                                <i class="fas fa-credit-card"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text"><?= htmlspecialchars($gateway['display_name']) ?></span>
                                                <span class="info-box-number">
                                                    <?php if ($gateway['is_active']): ?>
                                                        <span class="badge badge-success">Operacional</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Inativo</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($gateway['is_sandbox']): ?>
                                                        <span class="badge badge-warning">Sandbox</span>
                                                    <?php endif; ?>
                                                </span>
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: <?= $gateway['is_active'] ? '100%' : '0%' ?>"></div>
                                                </div>
                                                <span class="progress-description">
                                                    <?php if ($gateway['is_active']): ?>
                                                        Serviço ativo e processando transações
                                                    <?php else: ?>
                                                        Serviço desativado
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirmar Ação</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para esta página -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lidar com ativação/desativação de gateway
    var toggleButtons = document.querySelectorAll('.toggle-gateway');
    var currentGateway = '';
    var currentAction = '';
    
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var gateway = this.getAttribute('data-gateway');
            var action = this.getAttribute('data-action');
            
            currentGateway = gateway;
            currentAction = action;
            
            var message = action === 'enable' 
                ? 'Tem certeza que deseja ativar o gateway ' + gateway + '?' 
                : 'Tem certeza que deseja desativar o gateway ' + gateway + '? Esta ação impedirá o processamento de novos pagamentos.';
                
            document.getElementById('confirmMessage').textContent = message;
            $('#confirmModal').modal('show');
        });
    });
    
    document.getElementById('confirmAction').addEventListener('click', function() {
        if (!currentGateway || !currentAction) {
            return;
        }
        
        // Chamar API para ativar/desativar gateway
        fetch('<?= BASE_URL ?>admin/pagamentos/toggleGateway', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                gateway: currentGateway,
                action: currentAction
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recarregar a página para exibir as alterações
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro: ' + error.message);
        });
        
        // Fechar modal
        $('#confirmModal').modal('hide');
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
