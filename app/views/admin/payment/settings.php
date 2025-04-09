<?php
/**
 * View para configurações de pagamento
 * 
 * Permite configurar métodos de pagamento e gateways
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
                        <li class="breadcrumb-item active">Configurações</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Sucesso!</h5>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="payment-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="payment-methods-tab" data-toggle="pill" href="#payment-methods" role="tab" aria-controls="payment-methods" aria-selected="true">
                                        Métodos de Pagamento
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="mercadopago-tab" data-toggle="pill" href="#mercadopago-config" role="tab" aria-controls="mercadopago-config" aria-selected="false">
                                        MercadoPago
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="paypal-tab" data-toggle="pill" href="#paypal-config" role="tab" aria-controls="paypal-config" aria-selected="false">
                                        PayPal
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="webhooks-tab" data-toggle="pill" href="#webhooks-config" role="tab" aria-controls="webhooks-config" aria-selected="false">
                                        URLs de Webhooks
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="payment-tabs-content">
                                <!-- Métodos de Pagamento -->
                                <div class="tab-pane fade show active" id="payment-methods" role="tabpanel" aria-labelledby="payment-methods-tab">
                                    <form action="<?= BASE_URL ?>admin/pagamentos/saveSettings" method="post" id="payment-methods-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="mode" value="payment_methods">
                                        
                                        <div class="alert alert-info">
                                            <i class="icon fas fa-info-circle"></i>
                                            Configure quais métodos de pagamento estarão disponíveis para seus clientes.
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" id="payment-methods-table">
                                                <thead>
                                                    <tr>
                                                        <th width="10%">ID</th>
                                                        <th width="25%">Nome</th>
                                                        <th width="20%">Gateway</th>
                                                        <th width="20%">Ícone</th>
                                                        <th width="15%">Status</th>
                                                        <th width="10%">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($paymentMethods as $index => $method): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="text" class="form-control" name="methods[<?= $index ?>][id]" value="<?= htmlspecialchars($method['id']) ?>" required>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control" name="methods[<?= $index ?>][name]" value="<?= htmlspecialchars($method['name']) ?>" required>
                                                            </td>
                                                            <td>
                                                                <select class="form-control" name="methods[<?= $index ?>][gateway]" required>
                                                                    <option value="">Selecione...</option>
                                                                    <?php foreach ($gateways as $gateway): ?>
                                                                        <option value="<?= htmlspecialchars($gateway['name']) ?>" <?= $gateway['name'] === $method['gateway'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($gateway['display_name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control" name="methods[<?= $index ?>][icon]" value="<?= htmlspecialchars($method['icon'] ?? '') ?>" placeholder="fa-credit-card">
                                                            </td>
                                                            <td>
                                                                <div class="custom-control custom-switch">
                                                                    <input type="checkbox" class="custom-control-input" id="method-active-<?= $index ?>" name="methods[<?= $index ?>][active]" value="1" <?= ($method['active'] ?? false) ? 'checked' : '' ?>>
                                                                    <label class="custom-control-label" for="method-active-<?= $index ?>">Ativo</label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger delete-method" data-index="<?= $index ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    
                                                    <!-- Template para novo método -->
                                                    <tr id="new-method-template" style="display: none;">
                                                        <td>
                                                            <input type="text" class="form-control" name="methods[{index}][id]" value="" required>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="methods[{index}][name]" value="" required>
                                                        </td>
                                                        <td>
                                                            <select class="form-control" name="methods[{index}][gateway]" required>
                                                                <option value="">Selecione...</option>
                                                                <?php foreach ($gateways as $gateway): ?>
                                                                    <option value="<?= htmlspecialchars($gateway['name']) ?>">
                                                                        <?= htmlspecialchars($gateway['display_name']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="methods[{index}][icon]" value="" placeholder="fa-credit-card">
                                                        </td>
                                                        <td>
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox" class="custom-control-input" id="method-active-{index}" name="methods[{index}][active]" value="1">
                                                                <label class="custom-control-label" for="method-active-{index}">Ativo</label>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger delete-method" data-index="{index}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button type="button" id="add-payment-method" class="btn btn-success">
                                                <i class="fas fa-plus"></i> Adicionar Método
                                            </button>
                                            
                                            <button type="submit" class="btn btn-primary float-right">
                                                <i class="fas fa-save"></i> Salvar Métodos
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Configuração do MercadoPago -->
                                <div class="tab-pane fade" id="mercadopago-config" role="tabpanel" aria-labelledby="mercadopago-tab">
                                    <form action="<?= BASE_URL ?>admin/pagamentos/saveSettings" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="mode" value="gateway_config">
                                        <input type="hidden" name="gateway" value="mercadopago">
                                        
                                        <div class="alert alert-info">
                                            <i class="icon fas fa-info-circle"></i>
                                            Configure suas credenciais do MercadoPago para processar pagamentos. As credenciais podem ser obtidas no <a href="https://www.mercadopago.com.br/developers/panel" target="_blank">Painel do MercadoPago</a>.
                                        </div>
                                        
                                        <?php 
                                        // Obter configurações do MercadoPago
                                        $mpConfig = [];
                                        foreach ($gateways as $gateway) {
                                            if ($gateway['name'] === 'mercadopago') {
                                                $mpConfig = $gateway;
                                                break;
                                            }
                                        }
                                        ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="mp-active">Status</label>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="mp-active" name="config[active]" value="1" <?= ($mpConfig['is_active'] ?? false) ? 'checked' : '' ?>>
                                                        <label class="custom-control-label" for="mp-active">Gateway Ativo</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="mp-display-name">Nome de Exibição</label>
                                                    <input type="text" class="form-control" id="mp-display-name" name="config[display_name]" value="<?= htmlspecialchars($mpConfig['display_name'] ?? 'MercadoPago') ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="mp-sandbox">Ambiente</label>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="mp-sandbox" name="config[sandbox]" value="1" <?= ($mpConfig['is_sandbox'] ?? true) ? 'checked' : '' ?>>
                                                        <label class="custom-control-label" for="mp-sandbox">Modo Sandbox (Teste)</label>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        <i class="fas fa-exclamation-triangle text-warning"></i> 
                                                        Desative o modo Sandbox apenas em produção!
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="mp-access-token">Access Token</label>
                                                    <input type="password" class="form-control" id="mp-access-token" name="config[access_token]" value="<?= htmlspecialchars($mpConfig['access_token'] ?? '') ?>" required autocomplete="new-password">
                                                    <small class="form-text text-muted">
                                                        Token de acesso privado do MercadoPago.
                                                    </small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="mp-public-key">Public Key</label>
                                                    <input type="text" class="form-control" id="mp-public-key" name="config[public_key]" value="<?= htmlspecialchars($mpConfig['public_key'] ?? '') ?>" required>
                                                    <small class="form-text text-muted">
                                                        Chave pública do MercadoPago para o frontend.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Métodos de Pagamento Aceitos</label>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="mp-credit-card" name="config[payment_methods][]" value="credit_card" <?= in_array('credit_card', $mpConfig['payment_methods'] ?? []) ? 'checked' : '' ?>>
                                                                <label class="custom-control-label" for="mp-credit-card">Cartão de Crédito</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-4">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="mp-boleto" name="config[payment_methods][]" value="boleto" <?= in_array('boleto', $mpConfig['payment_methods'] ?? []) ? 'checked' : '' ?>>
                                                                <label class="custom-control-label" for="mp-boleto">Boleto Bancário</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-4">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="mp-pix" name="config[payment_methods][]" value="pix" <?= in_array('pix', $mpConfig['payment_methods'] ?? []) ? 'checked' : '' ?>>
                                                                <label class="custom-control-label" for="mp-pix">PIX</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="mp-webhook-url">URL de Webhook</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="mp-webhook-url" value="<?= BASE_URL ?>webhook/mercadopago" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary copy-webhook-url" type="button" data-clipboard-target="#mp-webhook-url">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                Configure esta URL no painel do MercadoPago para receber notificações.
                                            </small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvar Configurações
                                        </button>
                                        
                                        <button type="button" class="btn btn-info" id="test-mercadopago">
                                            <i class="fas fa-vial"></i> Testar Conexão
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Configuração do PayPal -->
                                <div class="tab-pane fade" id="paypal-config" role="tabpanel" aria-labelledby="paypal-tab">
                                    <form action="<?= BASE_URL ?>admin/pagamentos/saveSettings" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="mode" value="gateway_config">
                                        <input type="hidden" name="gateway" value="paypal">
                                        
                                        <div class="alert alert-info">
                                            <i class="icon fas fa-info-circle"></i>
                                            Configure suas credenciais do PayPal para processar pagamentos. As credenciais podem ser obtidas no <a href="https://developer.paypal.com/dashboard/" target="_blank">Painel de Desenvolvedores do PayPal</a>.
                                        </div>
                                        
                                        <?php 
                                        // Obter configurações do PayPal
                                        $ppConfig = [];
                                        foreach ($gateways as $gateway) {
                                            if ($gateway['name'] === 'paypal') {
                                                $ppConfig = $gateway;
                                                break;
                                            }
                                        }
                                        ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="pp-active">Status</label>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="pp-active" name="config[active]" value="1" <?= ($ppConfig['is_active'] ?? false) ? 'checked' : '' ?>>
                                                        <label class="custom-control-label" for="pp-active">Gateway Ativo</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="pp-display-name">Nome de Exibição</label>
                                                    <input type="text" class="form-control" id="pp-display-name" name="config[display_name]" value="<?= htmlspecialchars($ppConfig['display_name'] ?? 'PayPal') ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="pp-sandbox">Ambiente</label>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="pp-sandbox" name="config[sandbox]" value="1" <?= ($ppConfig['is_sandbox'] ?? true) ? 'checked' : '' ?>>
                                                        <label class="custom-control-label" for="pp-sandbox">Modo Sandbox (Teste)</label>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        <i class="fas fa-exclamation-triangle text-warning"></i> 
                                                        Desative o modo Sandbox apenas em produção!
                                                    </small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="pp-currency">Moeda</label>
                                                    <select class="form-control" id="pp-currency" name="config[currency]">
                                                        <option value="BRL" <?= ($ppConfig['currency'] ?? 'BRL') === 'BRL' ? 'selected' : '' ?>>Real Brasileiro (BRL)</option>
                                                        <option value="USD" <?= ($ppConfig['currency'] ?? 'BRL') === 'USD' ? 'selected' : '' ?>>Dólar Americano (USD)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="pp-client-id">Client ID</label>
                                                    <input type="text" class="form-control" id="pp-client-id" name="config[client_id]" value="<?= htmlspecialchars($ppConfig['client_id'] ?? '') ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="pp-client-secret">Client Secret</label>
                                                    <input type="password" class="form-control" id="pp-client-secret" name="config[client_secret]" value="<?= htmlspecialchars($ppConfig['client_secret'] ?? '') ?>" required autocomplete="new-password">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="pp-webhook-id">Webhook ID (opcional)</label>
                                                    <input type="text" class="form-control" id="pp-webhook-id" name="config[webhook_id]" value="<?= htmlspecialchars($ppConfig['webhook_id'] ?? '') ?>">
                                                    <small class="form-text text-muted">
                                                        ID do webhook configurado no PayPal (usado para verificação de autenticidade).
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="pp-webhook-url">URL de Webhook</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="pp-webhook-url" value="<?= BASE_URL ?>webhook/paypal" readonly>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-secondary copy-webhook-url" type="button" data-clipboard-target="#pp-webhook-url">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        Configure esta URL no painel do PayPal para receber notificações de webhook.
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="pp-ipn-url">URL de IPN</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="pp-ipn-url" value="<?= BASE_URL ?>payment/ipn/paypal" readonly>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-secondary copy-webhook-url" type="button" data-clipboard-target="#pp-ipn-url">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        Configure esta URL no perfil IPN do PayPal (opcional, usado pelo sistema antigo).
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvar Configurações
                                        </button>
                                        
                                        <button type="button" class="btn btn-info" id="test-paypal">
                                            <i class="fas fa-vial"></i> Testar Conexão
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- URLs de Webhooks -->
                                <div class="tab-pane fade" id="webhooks-config" role="tabpanel" aria-labelledby="webhooks-tab">
                                    <div class="alert alert-info">
                                        <i class="icon fas fa-info-circle"></i>
                                        Configure estas URLs nos respectivos gateways de pagamento para receber notificações de atualização de status.
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Gateway</th>
                                                    <th>Tipo</th>
                                                    <th>URL</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>MercadoPago</td>
                                                    <td>Webhook</td>
                                                    <td>
                                                        <input type="text" class="form-control" id="mp-webhook-url-copy" value="<?= BASE_URL ?>webhook/mercadopago" readonly>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-secondary copy-webhook-url" data-clipboard-target="#mp-webhook-url-copy">
                                                            <i class="fas fa-copy"></i> Copiar
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>PayPal</td>
                                                    <td>Webhook</td>
                                                    <td>
                                                        <input type="text" class="form-control" id="pp-webhook-url-copy" value="<?= BASE_URL ?>webhook/paypal" readonly>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-secondary copy-webhook-url" data-clipboard-target="#pp-webhook-url-copy">
                                                            <i class="fas fa-copy"></i> Copiar
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>PayPal</td>
                                                    <td>IPN</td>
                                                    <td>
                                                        <input type="text" class="form-control" id="pp-ipn-url-copy" value="<?= BASE_URL ?>payment/ipn/paypal" readonly>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-secondary copy-webhook-url" data-clipboard-target="#pp-ipn-url-copy">
                                                            <i class="fas fa-copy"></i> Copiar
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h5>Instruções de Configuração</h5>
                                        
                                        <div class="card card-outline card-primary">
                                            <div class="card-header">
                                                <h3 class="card-title">MercadoPago</h3>
                                            </div>
                                            <div class="card-body">
                                                <ol>
                                                    <li>Acesse o <a href="https://www.mercadopago.com.br/developers/panel" target="_blank">Painel do MercadoPago</a></li>
                                                    <li>Vá para a seção "Webhooks"</li>
                                                    <li>Clique em "Criar webhook"</li>
                                                    <li>Selecione os eventos: 
                                                        <ul>
                                                            <li>payment.created</li>
                                                            <li>payment.updated</li>
                                                        </ul>
                                                    </li>
                                                    <li>Cole a URL de webhook</li>
                                                    <li>Salve a configuração</li>
                                                </ol>
                                            </div>
                                        </div>
                                        
                                        <div class="card card-outline card-primary">
                                            <div class="card-header">
                                                <h3 class="card-title">PayPal</h3>
                                            </div>
                                            <div class="card-body">
                                                <h6>Configuração de Webhook (API REST v2)</h6>
                                                <ol>
                                                    <li>Acesse o <a href="https://developer.paypal.com/dashboard/applications/" target="_blank">Painel de Aplicativos do PayPal</a></li>
                                                    <li>Selecione sua aplicação</li>
                                                    <li>Na seção "Webhooks", clique em "Add Webhook"</li>
                                                    <li>Cole a URL de webhook</li>
                                                    <li>Selecione os eventos:
                                                        <ul>
                                                            <li>PAYMENT.AUTHORIZATION.CREATED</li>
                                                            <li>PAYMENT.AUTHORIZATION.VOIDED</li>
                                                            <li>PAYMENT.CAPTURE.COMPLETED</li>
                                                            <li>PAYMENT.CAPTURE.DENIED</li>
                                                            <li>PAYMENT.CAPTURE.PENDING</li>
                                                            <li>PAYMENT.CAPTURE.REFUNDED</li>
                                                        </ul>
                                                    </li>
                                                    <li>Salve a configuração</li>
                                                    <li>Copie o "Webhook ID" gerado e insira no campo correspondente acima</li>
                                                </ol>
                                                
                                                <h6>Configuração de IPN (opcional)</h6>
                                                <ol>
                                                    <li>Acesse a <a href="https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify" target="_blank">Configuração de IPN</a></li>
                                                    <li>Clique em "Choose IPN Settings"</li>
                                                    <li>Selecione "Receive IPN messages (Enabled)"</li>
                                                    <li>Cole a URL de IPN</li>
                                                    <li>Salve a configuração</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para esta página -->
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Clipboard.js
    var clipboard = new ClipboardJS('.copy-webhook-url');
    
    clipboard.on('success', function(e) {
        var btn = e.trigger;
        var originalText = btn.innerHTML;
        
        // Alterar texto do botão temporariamente
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        
        // Restaurar texto original
        setTimeout(function() {
            btn.innerHTML = originalText;
        }, 2000);
        
        e.clearSelection();
    });
    
    // Adicionar método de pagamento
    var methodIndex = <?= count($paymentMethods) ?>;
    
    document.getElementById('add-payment-method').addEventListener('click', function() {
        var templateRow = document.getElementById('new-method-template').innerHTML;
        var newRow = templateRow.replace(/{index}/g, methodIndex);
        
        var tbody = document.querySelector('#payment-methods-table tbody');
        var tr = document.createElement('tr');
        tr.innerHTML = newRow;
        tbody.insertBefore(tr, document.getElementById('new-method-template'));
        
        methodIndex++;
    });
    
    // Remover método de pagamento
    document.querySelector('#payment-methods-table').addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-method') || e.target.parentElement.classList.contains('delete-method')) {
            if (confirm('Tem certeza que deseja remover este método de pagamento?')) {
                var button = e.target.classList.contains('delete-method') ? e.target : e.target.parentElement;
                button.closest('tr').remove();
            }
        }
    });
    
    // Testar conexão com MercadoPago
    document.getElementById('test-mercadopago').addEventListener('click', function() {
        var accessToken = document.getElementById('mp-access-token').value;
        var sandbox = document.getElementById('mp-sandbox').checked;
        
        if (!accessToken) {
            alert('Preencha o Access Token para testar a conexão.');
            return;
        }
        
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';
        this.disabled = true;
        
        // Fazer a requisição para o endpoint de teste
        fetch('<?= BASE_URL ?>admin/pagamentos/testGateway', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                gateway: 'mercadopago',
                access_token: accessToken,
                sandbox: sandbox
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Conexão estabelecida com sucesso! Gateway operacional.');
            } else {
                alert('Erro ao testar conexão: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro ao testar conexão: ' + error.message);
        })
        .finally(() => {
            this.innerHTML = '<i class="fas fa-vial"></i> Testar Conexão';
            this.disabled = false;
        });
    });
    
    // Testar conexão com PayPal
    document.getElementById('test-paypal').addEventListener('click', function() {
        var clientId = document.getElementById('pp-client-id').value;
        var clientSecret = document.getElementById('pp-client-secret').value;
        var sandbox = document.getElementById('pp-sandbox').checked;
        
        if (!clientId || !clientSecret) {
            alert('Preencha o Client ID e o Client Secret para testar a conexão.');
            return;
        }
        
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';
        this.disabled = true;
        
        // Fazer a requisição para o endpoint de teste
        fetch('<?= BASE_URL ?>admin/pagamentos/testGateway', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                gateway: 'paypal',
                client_id: clientId,
                client_secret: clientSecret,
                sandbox: sandbox
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Conexão estabelecida com sucesso! Gateway operacional.');
            } else {
                alert('Erro ao testar conexão: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro ao testar conexão: ' + error.message);
        })
        .finally(() => {
            this.innerHTML = '<i class="fas fa-vial"></i> Testar Conexão';
            this.disabled = false;
        });
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
