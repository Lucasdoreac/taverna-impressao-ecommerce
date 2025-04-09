<?php
/**
 * View para visualização de cotação
 * 
 * Esta view exibe os detalhes de uma cotação gerada
 * 
 * @var array $quotation Dados da cotação
 * @var array $model Dados do modelo
 * @var string $csrfToken Token CSRF para operações seguras
 */

// Definir o título da página
$pageTitle = 'Cotação #' . htmlspecialchars($quotation['id']);

// Inclusão do cabeçalho do site
include_once __DIR__ . '/../shared/header.php';

// Funções auxiliares
function getStatusLabel($status) {
    $labels = [
        'draft' => '<span class="status-badge status-draft">Rascunho</span>',
        'pending' => '<span class="status-badge status-pending">Aguardando Aprovação</span>',
        'approved' => '<span class="status-badge status-approved">Aprovada</span>',
        'rejected' => '<span class="status-badge status-rejected">Rejeitada</span>',
        'expired' => '<span class="status-badge status-expired">Expirada</span>',
        'converted' => '<span class="status-badge status-converted">Convertida em Pedido</span>'
    ];
    
    return $labels[$status] ?? '<span class="status-badge status-unknown">Desconhecido</span>';
}

function getMaterialLabel($material_id) {
    $labels = [
        'pla' => 'PLA (Ácido Polilático)',
        'abs' => 'ABS (Acrilonitrila Butadieno Estireno)',
        'petg' => 'PETG (Polietileno Tereftalato Glicol)',
        'tpu' => 'TPU (Poliuretano Termoplástico)',
        'resin' => 'Resina (Fotopolímero)'
    ];
    
    return $labels[$material_id] ?? 'Material Desconhecido';
}

function getComplexityLabel($complexity) {
    $labels = [
        'simple' => 'Simples',
        'moderate' => 'Moderada',
        'complex' => 'Complexa',
        'very_complex' => 'Muito Complexa'
    ];
    
    return $labels[$complexity] ?? 'Desconhecida';
}

// Formatar data de validade
$expiryDate = new DateTime($quotation['expires_at']);
$now = new DateTime();
$isExpired = $expiryDate < $now;
?>

<main class="quotation-view-container">
    <div class="page-header">
        <div class="header-top">
            <h1>Cotação #<?= htmlspecialchars($quotation['id']) ?></h1>
            <?= getStatusLabel($quotation['status']) ?>
        </div>
        <div class="breadcrumbs">
            <a href="/customer-quotation/my">Minhas Cotações</a> &gt;
            <span>Cotação #<?= htmlspecialchars($quotation['id']) ?></span>
        </div>
    </div>

    <div class="quotation-actions">
        <div class="valid-until">
            <?php if ($isExpired): ?>
                <span class="expired-warning"><i class="fas fa-exclamation-circle"></i> Esta cotação expirou em <?= $expiryDate->format('d/m/Y') ?></span>
            <?php else: ?>
                <span class="valid-text"><i class="fas fa-check-circle"></i> Válida até <?= $expiryDate->format('d/m/Y') ?></span>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons">
            <a href="/customer-quotation/confirm/<?= $quotation['id'] ?>" class="btn btn-primary <?= ($isExpired || $quotation['status'] !== 'approved') ? 'disabled' : '' ?>">
                <i class="fas fa-shopping-cart"></i> Confirmar Pedido
            </a>
            
            <a href="/customer-models/details/<?= $quotation['model_id'] ?>" class="btn btn-outline">
                <i class="fas fa-cube"></i> Ver Modelo
            </a>
            
            <button class="btn btn-outline btn-print">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <div class="quotation-content">
        <!-- Dados da cotação -->
        <div class="quotation-card">
            <div class="card-header">
                <h2>Resumo da Cotação</h2>
            </div>
            <div class="card-body">
                <div class="summary-row">
                    <span class="summary-label">Modelo:</span>
                    <span class="summary-value"><?= htmlspecialchars($model['original_name']) ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Data da Cotação:</span>
                    <span class="summary-value"><?= (new DateTime($quotation['created_at']))->format('d/m/Y H:i') ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Material:</span>
                    <span class="summary-value"><?= getMaterialLabel($quotation['material_info']['id']) ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Complexidade:</span>
                    <span class="summary-value"><?= getComplexityLabel($quotation['complexity_level']) ?> (<?= $quotation['complexity_score'] ?>/100)</span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Impressão Urgente:</span>
                    <span class="summary-value"><?= isset($quotation['is_urgent']) && $quotation['is_urgent'] ? 'Sim' : 'Não' ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Tempo de Impressão:</span>
                    <span class="summary-value"><?= floor($quotation['estimated_print_time'] / 60) ?>h <?= $quotation['estimated_print_time'] % 60 ?>min</span>
                </div>
                
                <?php if (!empty($quotation['notes'])): ?>
                <div class="summary-row notes-row">
                    <span class="summary-label">Observações:</span>
                    <div class="notes-content">
                        <?= nl2br(htmlspecialchars($quotation['notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Detalhes de entrega -->
        <div class="quotation-card">
            <div class="card-header">
                <h2>Opções de Entrega</h2>
            </div>
            <div class="card-body">
                <?php foreach ($quotation['delivery_options'] as $option): ?>
                <div class="delivery-option <?= $option['type'] === 'express' ? 'delivery-express' : '' ?>">
                    <div class="delivery-header">
                        <h3><?= htmlspecialchars($option['name']) ?></h3>
                        <?php if ($option['type'] === 'express'): ?>
                        <span class="express-badge">Express</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="delivery-details">
                        <div class="delivery-info">
                            <div class="delivery-row">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Previsão: <?= (new DateTime($option['delivery_date']))->format('d/m/Y') ?></span>
                            </div>
                            <div class="delivery-row">
                                <i class="fas fa-clock"></i>
                                <span><?= $option['days'] ?> <?= $option['days'] > 1 ? 'dias úteis' : 'dia útil' ?></span>
                            </div>
                            <div class="delivery-row">
                                <i class="fas fa-info-circle"></i>
                                <span><?= htmlspecialchars($option['description']) ?></span>
                            </div>
                        </div>
                        
                        <div class="delivery-price">
                            <?php if ($option['additional_cost'] > 0): ?>
                            <span class="additional-cost">+ R$ <?= number_format($option['additional_cost'], 2, ',', '.') ?></span>
                            <?php else: ?>
                            <span class="no-additional-cost">Sem custo adicional</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Detalhes de custo -->
        <div class="quotation-card">
            <div class="card-header">
                <h2>Detalhamento de Custos</h2>
            </div>
            <div class="card-body">
                <div class="costs-breakdown">
                    <div class="cost-row">
                        <span class="cost-label">Material (<?= htmlspecialchars($quotation['material_info']['name']) ?>):</span>
                        <span class="cost-value">R$ <?= number_format($quotation['costs']['material'], 2, ',', '.') ?></span>
                    </div>
                    
                    <div class="cost-row">
                        <span class="cost-label">Impressão:</span>
                        <span class="cost-value">R$ <?= number_format($quotation['costs']['printing'], 2, ',', '.') ?></span>
                    </div>
                    
                    <?php if (!empty($quotation['costs']['additional'])): ?>
                        <?php foreach ($quotation['costs']['additional'] as $option => $details): ?>
                        <div class="cost-row">
                            <span class="cost-label"><?= htmlspecialchars($details['name']) ?>:</span>
                            <span class="cost-value">R$ <?= number_format($details['cost'], 2, ',', '.') ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="total-row">
                        <span class="total-label">Valor Total:</span>
                        <span class="total-value">R$ <?= number_format($quotation['final_price'], 2, ',', '.') ?></span>
                    </div>
                </div>
                
                <div class="price-notice">
                    <p><i class="fas fa-info-circle"></i> Preços válidos por 7 dias a partir da data da cotação.</p>
                    <p>Para confirmar o pedido com esta cotação, clique em "Confirmar Pedido".</p>
                </div>
            </div>
        </div>
        
        <!-- Seção de Termos -->
        <div class="quotation-card">
            <div class="card-header">
                <h2>Termos e Condições</h2>
            </div>
            <div class="card-body">
                <div class="terms-content">
                    <p><strong>1. Validade da Cotação:</strong> Esta cotação é válida por 7 dias corridos a partir da data de emissão.</p>
                    <p><strong>2. Material:</strong> A cotação é baseada no material selecionado. Alterações de material podem resultar em custos diferentes.</p>
                    <p><strong>3. Prazo de Entrega:</strong> O prazo de entrega é estimado e pode variar de acordo com a demanda atual e complexidade do modelo.</p>
                    <p><strong>4. Qualidade de Impressão:</strong> A qualidade de impressão está diretamente relacionada à qualidade do modelo 3D fornecido.</p>
                    <p><strong>5. Pagamento:</strong> O pagamento deve ser realizado antecipadamente para iniciar o processo de impressão.</p>
                    <p><strong>6. Propriedade Intelectual:</strong> O cliente declara ser o proprietário dos direitos do modelo 3D ou possuir autorização para sua reprodução.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    .quotation-view-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        margin-bottom: 30px;
    }
    
    .header-top {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 10px;
    }
    
    .page-header h1 {
        margin: 0;
        font-size: 1.8rem;
        color: #333;
    }
    
    .breadcrumbs {
        font-size: 0.9rem;
        color: #777;
    }
    
    .breadcrumbs a {
        color: #3498db;
        text-decoration: none;
    }
    
    .breadcrumbs a:hover {
        text-decoration: underline;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-draft {
        background-color: #f1f1f1;
        color: #777;
    }
    
    .status-pending {
        background-color: #fff4de;
        color: #f39c12;
    }
    
    .status-approved {
        background-color: #eafaf1;
        color: #27ae60;
    }
    
    .status-rejected {
        background-color: #fdeaea;
        color: #e74c3c;
    }
    
    .status-expired {
        background-color: #f5f5f5;
        color: #7f8c8d;
    }
    
    .status-converted {
        background-color: #e8f4fd;
        color: #3498db;
    }
    
    .quotation-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 8px;
    }
    
    .valid-until {
        display: flex;
        align-items: center;
    }
    
    .expired-warning {
        color: #e74c3c;
    }
    
    .valid-text {
        color: #27ae60;
    }
    
    .valid-until i {
        margin-right: 5px;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background-color: #3498db;
        color: white;
        border: none;
    }
    
    .btn-primary:hover {
        background-color: #2980b9;
    }
    
    .btn-primary.disabled {
        background-color: #bdc3c7;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    .btn-outline {
        background-color: transparent;
        color: #555;
        border: 1px solid #ccc;
    }
    
    .btn-outline:hover {
        background-color: #f5f5f5;
        border-color: #aaa;
    }
    
    .quotation-content {
        display: grid;
        grid-template-columns: 1fr;
        gap: 25px;
    }
    
    .quotation-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .card-header {
        padding: 15px;
        background-color: #f5f5f5;
        border-bottom: 1px solid #eee;
    }
    
    .card-header h2 {
        margin: 0;
        font-size: 1.2rem;
        color: #333;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .summary-row {
        display: flex;
        margin-bottom: 12px;
    }
    
    .summary-label {
        flex: 0 0 180px;
        font-weight: 500;
        color: #555;
    }
    
    .summary-value {
        flex: 1;
        color: #333;
    }
    
    .notes-row {
        align-items: flex-start;
    }
    
    .notes-content {
        background-color: #f9f9f9;
        padding: 10px;
        border-radius: 4px;
        white-space: pre-line;
    }
    
    .delivery-option {
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .delivery-option:last-child {
        margin-bottom: 0;
    }
    
    .delivery-express {
        background-color: #f2f9fe;
        border: 1px solid #d4e9f7;
    }
    
    .delivery-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .delivery-header h3 {
        margin: 0;
        font-size: 1.1rem;
    }
    
    .express-badge {
        background-color: #3498db;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .delivery-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .delivery-info {
        flex: 1;
    }
    
    .delivery-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 5px;
        color: #555;
        font-size: 0.9rem;
    }
    
    .delivery-row i {
        width: 16px;
        color: #777;
    }
    
    .delivery-price {
        text-align: right;
        padding-left: 15px;
    }
    
    .additional-cost {
        color: #e74c3c;
        font-weight: 500;
    }
    
    .no-additional-cost {
        color: #27ae60;
    }
    
    .costs-breakdown {
        margin-bottom: 20px;
    }
    
    .cost-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .cost-label {
        color: #555;
    }
    
    .cost-value {
        font-weight: 500;
    }
    
    .total-row {
        display: flex;
        justify-content: space-between;
        padding: 15px 0;
        margin-top: 10px;
        border-top: 2px solid #eee;
    }
    
    .total-label {
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .total-value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #3498db;
    }
    
    .price-notice {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
        margin-top: 20px;
    }
    
    .price-notice p {
        margin: 5px 0;
        font-size: 0.9rem;
        color: #555;
    }
    
    .price-notice i {
        color: #3498db;
        margin-right: 5px;
    }
    
    .terms-content p {
        margin: 10px 0;
        font-size: 0.9rem;
        color: #555;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .quotation-actions {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .action-buttons {
            width: 100%;
            flex-direction: column;
        }
        
        .summary-row {
            flex-direction: column;
        }
        
        .summary-label {
            margin-bottom: 5px;
        }
        
        .delivery-details {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .delivery-price {
            width: 100%;
            text-align: left;
            padding: 10px 0 0 0;
        }
    }
    
    @media print {
        body {
            background-color: white;
        }
        
        .header-site, .footer-site, .action-buttons, .navigation {
            display: none !important;
        }
        
        .quotation-view-container {
            padding: 0;
        }
        
        .quotation-actions {
            border: 1px solid #ddd;
        }
        
        .quotation-card {
            box-shadow: none;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .delivery-option, .costs-breakdown, .price-notice, .terms-content {
            page-break-inside: avoid;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Botão de impressão
        document.querySelector('.btn-print').addEventListener('click', function() {
            window.print();
        });
    });
</script>

<?php
// Inclusão do rodapé do site
include_once __DIR__ . '/../shared/footer.php';
?>