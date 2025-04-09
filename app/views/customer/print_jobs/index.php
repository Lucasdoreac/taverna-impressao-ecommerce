<?php
/**
 * View - Lista de Trabalhos de Impressão do Cliente
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views/Customer
 * @version    1.0.0
 */

// Garantir que este arquivo não seja acessado diretamente
defined('BASEPATH') or exit('Acesso direto ao script não é permitido');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            
            <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Filtros Simples -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filtrar por Status</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group mb-3 w-100">
                        <a href="/user/print-jobs" class="btn <?= !isset($currentStatus) ? 'btn-primary' : 'btn-outline-primary' ?>">Todos</a>
                        <a href="/user/print-jobs?status=pending" class="btn <?= isset($currentStatus) && $currentStatus === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">Pendentes</a>
                        <a href="/user/print-jobs?status=preparing" class="btn <?= isset($currentStatus) && $currentStatus === 'preparing' ? 'btn-primary' : 'btn-outline-primary' ?>">Preparando</a>
                        <a href="/user/print-jobs?status=printing" class="btn <?= isset($currentStatus) && $currentStatus === 'printing' ? 'btn-primary' : 'btn-outline-primary' ?>">Em Impressão</a>
                        <a href="/user/print-jobs?status=completed" class="btn <?= isset($currentStatus) && $currentStatus === 'completed' ? 'btn-primary' : 'btn-outline-primary' ?>">Concluídos</a>
                        <a href="/user/print-jobs?status=failed" class="btn <?= isset($currentStatus) && $currentStatus === 'failed' ? 'btn-primary' : 'btn-outline-primary' ?>">Falhas</a>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Trabalhos de Impressão -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Meus Trabalhos de Impressão</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($jobs)): ?>
                        <div class="alert alert-info">
                            Você não tem nenhum trabalho de impressão <?= isset($currentStatus) ? "com o status '$currentStatus'" : "" ?>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Modelo</th>
                                        <th>Impressora</th>
                                        <th>Status</th>
                                        <th>Progresso</th>
                                        <th>Data de Início</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($job['printer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                
                                                switch($job['status']) {
                                                    case 'pending':
                                                        $statusClass = 'badge-secondary';
                                                        $statusText = 'Pendente';
                                                        break;
                                                    case 'preparing':
                                                        $statusClass = 'badge-warning';
                                                        $statusText = 'Preparando';
                                                        break;
                                                    case 'printing':
                                                        $statusClass = 'badge-primary';
                                                        $statusText = 'Imprimindo';
                                                        break;
                                                    case 'post-processing':
                                                        $statusClass = 'badge-info';
                                                        $statusText = 'Pós-processamento';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'badge-success';
                                                        $statusText = 'Concluído';
                                                        break;
                                                    case 'failed':
                                                        $statusClass = 'badge-danger';
                                                        $statusText = 'Falha';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge-light';
                                                        $statusText = $job['status'];
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td>
                                                <?php if ($job['status'] === 'printing'): ?>
                                                    <div class="progress">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                            role="progressbar" 
                                                            style="width: <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%"
                                                            aria-valuenow="<?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                            <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%
                                                        </div>
                                                    </div>
                                                <?php elseif ($job['status'] === 'completed'): ?>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-success" 
                                                            role="progressbar" 
                                                            style="width: 100%"
                                                            aria-valuenow="100" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                            100%
                                                        </div>
                                                    </div>
                                                <?php elseif ($job['status'] === 'failed'): ?>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-danger" 
                                                            role="progressbar" 
                                                            style="width: <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%"
                                                            aria-valuenow="<?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                            <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($job['started_at']) && $job['started_at']): ?>
                                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['started_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                <?php elseif (isset($job['scheduled_start_time']) && $job['scheduled_start_time']): ?>
                                                    <span class="text-muted">Agendado para:</span><br>
                                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['scheduled_start_time'])), ENT_QUOTES, 'UTF-8') ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="/user/print-jobs/details/<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i> Detalhes
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Instruções e Informações -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informações Sobre Trabalhos de Impressão</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Status dos Trabalhos</h6>
                            <ul>
                                <li><span class="badge badge-secondary">Pendente</span> - O trabalho foi criado mas ainda não foi iniciado</li>
                                <li><span class="badge badge-warning">Preparando</span> - O operador está configurando a impressora</li>
                                <li><span class="badge badge-primary">Imprimindo</span> - O seu modelo está sendo impresso neste momento</li>
                                <li><span class="badge badge-info">Pós-processamento</span> - O modelo está sendo finalizado</li>
                                <li><span class="badge badge-success">Concluído</span> - A impressão foi finalizada com sucesso</li>
                                <li><span class="badge badge-danger">Falha</span> - Ocorreu um problema durante a impressão</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Informações Adicionais</h6>
                            <ul>
                                <li>Você receberá notificações por e-mail quando o status do seu trabalho mudar.</li>
                                <li>Trabalhos concluídos ficam disponíveis para retirada por até 30 dias.</li>
                                <li>Em caso de falha, entraremos em contato para discutir os próximos passos.</li>
                                <li>Você pode ver detalhes completos do trabalho, incluindo fotos (quando disponíveis), clicando em "Detalhes".</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Recarregar a página a cada 5 minutos para atualizar o progresso dos trabalhos em andamento
    if (document.querySelector('.progress-bar-animated')) {
        setTimeout(function() {
            location.reload();
        }, 5 * 60 * 1000);
    }
});
</script>
