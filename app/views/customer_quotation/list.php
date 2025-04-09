<?php
/**
 * View para listagem de cotações do cliente
 * 
 * Esta view exibe a lista de cotações do usuário logado
 * com opções de filtro e paginação
 * 
 * @var array $quotations Lista de cotações
 * @var array $filters Filtros aplicados
 * @var int $currentPage Página atual
 * @var int $totalPages Total de páginas
 * @var array $materials Lista de materiais disponíveis
 * @var string $csrfToken Token CSRF para operações seguras
 */

// Definir o título da página
$pageTitle = 'Minhas Cotações';

// Inclusão do cabeçalho do site
include_once __DIR__ . '/../shared/header.php';

// Funções auxiliares
function getStatusLabel($status) {
    $labels = [
        'draft' => '<span class="status-badge status-draft">Rascunho</span>',
        'pending' => '<span class="status-badge status-pending">Aguardando</span>',
        'approved' => '<span class="status-badge status-approved">Aprovada</span>',
        'rejected' => '<span class="status-badge status-rejected">Rejeitada</span>',
        'expired' => '<span class="status-badge status-expired">Expirada</span>',
        'converted' => '<span class="status-badge status-converted">Convertida</span>'
    ];
    
    return $labels[$status] ?? '<span class="status-badge status-unknown">Desconhecido</span>';
}

function getMaterialIcon($material_id) {
    return '<span class="material-icon material-' . $material_id . '"></span>';
}

function getMaterialName($material_id) {
    $names = [
        'pla' => 'PLA',
        'abs' => 'ABS',
        'petg' => 'PETG',
        'tpu' => 'TPU',
        'resin' => 'Resina'
    ];
    
    return $names[$material_id] ?? 'Desconhecido';
}

function isQuotationExpired($expiryDate) {
    $expiry = new DateTime($expiryDate);
    $now = new DateTime();
    return $expiry < $now;
}
?>

<main class="quotation-list-container">
    <div class="page-header">
        <h1>Minhas Cotações</h1>
        
        <div class="header-actions">
            <a href="/customer-models/list" class="btn btn-secondary">
                <i class="fas fa-cube"></i> Meus Modelos
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-panel">
        <form action="/customer-quotation/my" method="get" class="filter-form">
            <div class="filter-group">
                <label for="material">Material:</label>
                <select name="material" id="material">
                    <option value="">Todos</option>
                    <?php foreach ($materials as $code => $material): ?>
                    <option value="<?= $code ?>" <?= (isset($filters['material']) && $filters['material'] === $code) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($material['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date_from">De:</label>
                <input type="date" name="date_from" id="date_from" value="<?= $filters['date_from'] ?? '' ?>">
            </div>
            
            <div class="filter-group">
                <label for="date_to">Até:</label>
                <input type="date" name="date_to" id="date_to" value="<?= $filters['date_to'] ?? '' ?>">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="/customer-quotation/my" class="btn btn-outline">Limpar</a>
            </div>
        </form>
    </div>

    <!-- Listagem de cotações -->
    <div class="quotation-list">
        <?php if (empty($quotations)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h2>Nenhuma cotação encontrada</h2>
            <p>Você ainda não solicitou cotações ou nenhuma cotação corresponde aos filtros aplicados.</p>
            <a href="/customer-models/list" class="btn btn-primary">Ver Meus Modelos</a>
        </div>
        <?php else: ?>
            <!-- Tabela de cotações -->
            <div class="table-responsive">
                <table class="quotation-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Modelo</th>
                            <th>Material</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotations as $quotation): ?>
                        <tr class="<?= isQuotationExpired($quotation['expires_at']) && $quotation['status'] !== 'converted' ? 'expired-row' : '' ?>">
                            <td><?= substr($quotation['id'], 0, 8) ?>...</td>
                            <td><?= (new DateTime($quotation['created_at']))->format('d/m/Y') ?></td>
                            <td><?= htmlspecialchars($quotation['model_name']) ?></td>
                            <td class="material-cell">
                                <?= getMaterialIcon($quotation['material_type']) ?>
                                <?= getMaterialName($quotation['material_type']) ?>
                            </td>
                            <td class="price-cell">R$ <?= number_format($quotation['total_price'], 2, ',', '.') ?></td>
                            <td><?= getStatusLabel($quotation['status']) ?></td>
                            <td class="actions-cell">
                                <a href="/customer-quotation/view/<?= $quotation['id'] ?>" class="action-link view-link" title="Ver Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if (!isQuotationExpired($quotation['expires_at']) && $quotation['status'] === 'approved'): ?>
                                <a href="/customer-quotation/confirm/<?= $quotation['id'] ?>" class="action-link confirm-link" title="Confirmar Pedido">
                                    <i class="fas fa-shopping-cart"></i>
                                </a>
                                <?php endif; ?>
                                
                                <a href="/customer-models/details/<?= $quotation['model_id'] ?>" class="action-link model-link" title="Ver Modelo">
                                    <i class="fas fa-cube"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                <a href="/customer-quotation/my?page=<?= $currentPage - 1 ?><?= !empty($filters['material']) ? '&material=' . $filters['material'] : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . $filters['date_from'] : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . $filters['date_to'] : '' ?>" class="pagination-link prev-link">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
                <?php endif; ?>
                
                <div class="pagination-status">
                    Página <?= $currentPage ?> de <?= $totalPages ?>
                </div>
                
                <?php if ($currentPage < $totalPages): ?>
                <a href="/customer-quotation/my?page=<?= $currentPage + 1 ?><?= !empty($filters['material']) ? '&material=' . $filters['material'] : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . $filters['date_from'] : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . $filters['date_to'] : '' ?>" class="pagination-link next-link">
                    Próxima <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Informações sobre cotações -->
    <div class="quotation-info-panel">
        <div class="info-header">
            <h2>Informações sobre cotações</h2>
        </div>
        <div class="info-content">
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="info-text">
                    <h3>Validade</h3>
                    <p>Todas as cotações são válidas por 7 dias a partir da data de emissão.</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="info-text">
                    <h3>Status</h3>
                    <p>Cotações recebem aprovação automática após análise técnica. Você será notificado quando sua cotação for aprovada.</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="info-text">
                    <h3>Confirmação</h3>
                    <p>Para confirmar um pedido, clique no ícone de carrinho ao lado da cotação aprovada e dentro do prazo de validade.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    .quotation-list-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .page-header h1 {
        margin: 0;
        font-size: 1.8rem;
        color: #333;
    }
    
    .header-actions {
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
    
    .btn-secondary {
        background-color: #2ecc71;
        color: white;
        border: none;
    }
    
    .btn-secondary:hover {
        background-color: #27ae60;
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
    
    .filter-panel {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .filter-group label {
        font-size: 0.9rem;
        color: #555;
    }
    
    .filter-group select,
    .filter-group input {
        height: 36px;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 0 10px;
        min-width: 150px;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
        margin-left: auto;
    }
    
    .quotation-list {
        margin-bottom: 30px;
    }
    
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
    }
    
    .empty-icon {
        font-size: 48px;
        color: #bdc3c7;
        margin-bottom: 20px;
    }
    
    .empty-state h2 {
        margin: 0 0 15px 0;
        font-size: 1.5rem;
        color: #555;
    }
    
    .empty-state p {
        margin: 0 0 20px 0;
        color: #777;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .quotation-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .quotation-table th {
        background-color: #f5f5f5;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #ddd;
    }
    
    .quotation-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        color: #555;
    }
    
    .quotation-table tr:hover {
        background-color: #f9f9f9;
    }
    
    .material-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .material-icon {
        width: 18px;
        height: 18px;
        border-radius: 50%;
    }
    
    .material-pla {
        background-color: #2ecc71;
    }
    
    .material-abs {
        background-color: #e74c3c;
    }
    
    .material-petg {
        background-color: #3498db;
    }
    
    .material-tpu {
        background-color: #9b59b6;
    }
    
    .material-resin {
        background-color: #f1c40f;
    }
    
    .price-cell {
        font-weight: 500;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
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
    
    .actions-cell {
        display: flex;
        gap: 10px;
    }
    
    .action-link {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        width: 30px;
        height: 30px;
        border-radius: 4px;
        color: #555;
        transition: all 0.2s;
    }
    
    .view-link:hover {
        background-color: #e8f4fd;
        color: #3498db;
    }
    
    .confirm-link:hover {
        background-color: #eafaf1;
        color: #27ae60;
    }
    
    .model-link:hover {
        background-color: #fff4de;
        color: #f39c12;
    }
    
    .expired-row {
        opacity: 0.6;
    }
    
    .pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding: 10px 0;
    }
    
    .pagination-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 16px;
        border-radius: 4px;
        color: #3498db;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .pagination-link:hover {
        background-color: #e8f4fd;
    }
    
    .pagination-status {
        color: #777;
    }
    
    .quotation-info-panel {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .info-header {
        padding: 15px;
        background-color: #f5f5f5;
        border-bottom: 1px solid #eee;
    }
    
    .info-header h2 {
        margin: 0;
        font-size: 1.2rem;
        color: #333;
    }
    
    .info-content {
        padding: 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        display: flex;
        gap: 15px;
    }
    
    .info-icon {
        flex: 0 0 40px;
        height: 40px;
        background-color: #f1f9fe;
        color: #3498db;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.2rem;
    }
    
    .info-text h3 {
        margin: 0 0 5px 0;
        font-size: 1rem;
        color: #333;
    }
    
    .info-text p {
        margin: 0;
        color: #777;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-actions {
            margin-left: 0;
        }
        
        .info-content {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
// Inclusão do rodapé do site
include_once __DIR__ . '/../shared/footer.php';
?>