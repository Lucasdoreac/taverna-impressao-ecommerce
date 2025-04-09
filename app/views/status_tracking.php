<?php
/**
 * Template de Interface de Usuário para Acompanhamento de Cotações em Andamento
 * 
 * @package App\Views
 * @category Frontend
 * @author Taverna da Impressão 3D Dev Team
 */

use App\Lib\Security\SecurityManager;
use App\Lib\Security\SecurityHeaders;

// Obter token CSRF para requisições AJAX
$csrfToken = SecurityManager::getCsrfToken();
// Obter nonce CSP para scripts inline
$nonce = SecurityHeaders::getNonce();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhamento de Cotação - Taverna da Impressão 3D</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/status-tracking.css">
    <!-- CSP nonce para scripts inline -->
    <meta name="csp-nonce" content="<?= htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>Acompanhamento de Cotação</h1>
            <p>Acompanhe o status do seu pedido de cotação em tempo real</p>
        </header>

        <main>
            <div class="status-card">
                <div class="status-header">
                    <h2>Cotação #<span id="quote-id"><?= htmlspecialchars($quoteId, ENT_QUOTES, 'UTF-8') ?></span></h2>
                    <span class="status-badge" id="status-badge">Carregando...</span>
                </div>

                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="progress-text">
                        <span id="progress-percentage">0%</span> concluído
                    </div>
                </div>

                <div class="status-details">
                    <div class="detail-row">
                        <span class="detail-label">Iniciado em:</span>
                        <span class="detail-value" id="created-at">Carregando...</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Última atualização:</span>
                        <span class="detail-value" id="updated-at">Carregando...</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Etapa atual:</span>
                        <span class="detail-value" id="current-step">Carregando...</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Conclusão estimada:</span>
                        <span class="detail-value" id="estimated-completion">Carregando...</span>
                    </div>
                </div>

                <div class="steps-container" id="steps-container">
                    <!-- Etapas serão inseridas aqui via JavaScript -->
                    <div class="loading-spinner">Carregando etapas...</div>
                </div>

                <div class="status-actions">
                    <button id="refresh-status" class="btn">Atualizar Status</button>
                    <div id="download-container" class="hidden">
                        <button id="download-quote" class="btn btn-primary">Baixar Cotação</button>
                    </div>
                </div>

                <div id="error-container" class="error-container hidden">
                    <p>Ocorreu um erro ao carregar as informações. Tente novamente mais tarde.</p>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> Taverna da Impressão 3D - Todos os direitos reservados</p>
        </footer>
    </div>

    <!-- Script para atualização em tempo real -->
    <script nonce="<?= htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') ?>">
        // Configuração do acompanhamento em tempo real
        const StatusTracking = {
            // Configurações
            config: {
                processToken: '<?= htmlspecialchars($processToken, ENT_QUOTES, 'UTF-8') ?>',
                csrfToken: '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>',
                apiEndpoint: '/api/status-check',
                pollingInterval: 5000, // 5 segundos
                maxErrorRetries: 3
            },
            
            // Estado
            state: {
                isPolling: false,
                pollingIntervalId: null,
                errorCount: 0,
                lastStatus: null
            },
            
            // Inicialização
            init: function() {
                // Vincular eventos
                document.getElementById('refresh-status').addEventListener('click', this.checkStatus.bind(this));
                
                // Carregar status inicial
                this.checkStatus();
                
                // Iniciar polling automático
                this.startPolling();
                
                // Adicionar listener para download quando disponível
                document.getElementById('download-quote').addEventListener('click', this.downloadQuote.bind(this));
            },
            
            // Iniciar polling automático
            startPolling: function() {
                if (this.state.isPolling) return;
                
                this.state.isPolling = true;
                this.state.pollingIntervalId = setInterval(() => {
                    this.checkStatus();
                }, this.config.pollingInterval);
                
                // Adicionar evento para pausar polling quando a página não estiver visível
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.pausePolling();
                    } else {
                        this.resumePolling();
                    }
                });
            },
            
            // Pausar polling
            pausePolling: function() {
                if (!this.state.isPolling) return;
                
                clearInterval(this.state.pollingIntervalId);
                this.state.isPolling = false;
            },
            
            // Retomar polling
            resumePolling: function() {
                if (this.state.isPolling) return;
                
                // Verificar status imediatamente
                this.checkStatus();
                // Reiniciar polling
                this.startPolling();
            },
            
            // Verificar status atual via API
            checkStatus: function() {
                // Mostrar indicador de carregamento
                this.setLoading(true);
                
                // Criar payload para a requisição
                const formData = new FormData();
                formData.append('process_token', this.config.processToken);
                formData.append('csrf_token', this.config.csrfToken);
                
                // Fazer requisição à API
                fetch(this.config.apiEndpoint, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => {
                    // Verificar se a resposta é JSON válido
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Resposta inválida do servidor');
                    }
                    
                    // Extrair dado JSON
                    return response.json();
                })
                .then(data => {
                    // Validar formato da resposta (proteção contra JSON Hijacking)
                    if (typeof data === 'string' && data.startsWith(")]}',\n")) {
                        data = JSON.parse(data.substring(5));
                    }
                    
                    // Verificar se a resposta foi bem-sucedida
                    if (!data.success) {
                        throw new Error(data.message || 'Erro desconhecido');
                    }
                    
                    // Resetar contador de erros
                    this.state.errorCount = 0;
                    
                    // Atualizar interface com os dados recebidos
                    this.updateUI(data.data);
                    
                    // Armazenar último status
                    this.state.lastStatus = data.data;
                    
                    // Parar polling se o processo foi concluído ou falhou
                    if (['completed', 'failed', 'cancelled'].includes(data.data.status)) {
                        this.pausePolling();
                    }
                })
                .catch(error => {
                    // Incrementar contador de erros
                    this.state.errorCount++;
                    
                    // Mostrar erro na interface
                    this.showError(error.message);
                    
                    // Parar polling se ultrapassou limite de erros
                    if (this.state.errorCount >= this.config.maxErrorRetries) {
                        this.pausePolling();
                    }
                })
                .finally(() => {
                    // Remover indicador de carregamento
                    this.setLoading(false);
                });
            },
            
            // Atualizar interface com dados do status
            updateUI: function(statusData) {
                // Ocultar mensagem de erro se estava visível
                document.getElementById('error-container').classList.add('hidden');
                
                // Atualizar badge de status
                const statusBadge = document.getElementById('status-badge');
                statusBadge.textContent = this.getStatusLabel(statusData.status);
                statusBadge.className = 'status-badge status-' + statusData.status;
                
                // Atualizar barra de progresso
                const progressPercentage = parseInt(statusData.progress_percentage) || 0;
                document.getElementById('progress-fill').style.width = progressPercentage + '%';
                document.getElementById('progress-percentage').textContent = progressPercentage;
                
                // Atualizar detalhes
                document.getElementById('created-at').textContent = this.formatDate(statusData.created_at);
                document.getElementById('updated-at').textContent = this.formatDate(statusData.updated_at);
                document.getElementById('current-step').textContent = statusData.current_step || 'Não iniciado';
                document.getElementById('estimated-completion').textContent = 
                    statusData.estimated_completion_time 
                    ? this.formatDate(statusData.estimated_completion_time) 
                    : 'Calculando...';
                
                // Renderizar etapas
                if (statusData.steps && statusData.steps.length > 0) {
                    this.renderSteps(statusData.steps);
                }
                
                // Mostrar botão de download se estiver concluído
                if (statusData.status === 'completed' && statusData.download_url) {
                    document.getElementById('download-container').classList.remove('hidden');
                    document.getElementById('download-quote').dataset.url = statusData.download_url;
                } else {
                    document.getElementById('download-container').classList.add('hidden');
                }
            },
            
            // Renderizar etapas do processo
            renderSteps: function(steps) {
                const container = document.getElementById('steps-container');
                container.innerHTML = '';
                
                const stepsHtml = document.createElement('ul');
                stepsHtml.className = 'steps-list';
                
                steps.forEach(step => {
                    const stepItem = document.createElement('li');
                    stepItem.className = 'step-item step-' + step.status;
                    
                    const stepIcon = document.createElement('span');
                    stepIcon.className = 'step-icon';
                    stepItem.appendChild(stepIcon);
                    
                    const stepContent = document.createElement('div');
                    stepContent.className = 'step-content';
                    
                    const stepName = document.createElement('div');
                    stepName.className = 'step-name';
                    stepName.textContent = step.step_name;
                    stepContent.appendChild(stepName);
                    
                    if (step.completed_at) {
                        const stepTime = document.createElement('div');
                        stepTime.className = 'step-time';
                        stepTime.textContent = this.formatDate(step.completed_at);
                        stepContent.appendChild(stepTime);
                    }
                    
                    stepItem.appendChild(stepContent);
                    stepsHtml.appendChild(stepItem);
                });
                
                container.appendChild(stepsHtml);
            },
            
            // Baixar cotação quando concluída
            downloadQuote: function(event) {
                const downloadUrl = event.target.dataset.url;
                if (!downloadUrl) return;
                
                // Adicionar token CSRF na URL para proteção
                const url = new URL(downloadUrl, window.location.origin);
                url.searchParams.append('csrf_token', this.config.csrfToken);
                
                // Abrir em nova aba
                window.open(url.toString(), '_blank');
            },
            
            // Utilitários
            getStatusLabel: function(status) {
                const labels = {
                    'pending': 'Pendente',
                    'processing': 'Processando',
                    'completed': 'Concluído',
                    'failed': 'Falhou',
                    'cancelled': 'Cancelado'
                };
                return labels[status] || status;
            },
            
            formatDate: function(dateString) {
                if (!dateString) return 'N/A';
                
                try {
                    const date = new Date(dateString);
                    return new Intl.DateTimeFormat('pt-BR', {
                        dateStyle: 'short',
                        timeStyle: 'short'
                    }).format(date);
                } catch (e) {
                    return dateString;
                }
            },
            
            setLoading: function(isLoading) {
                const refreshButton = document.getElementById('refresh-status');
                
                if (isLoading) {
                    refreshButton.classList.add('loading');
                    refreshButton.disabled = true;
                } else {
                    refreshButton.classList.remove('loading');
                    refreshButton.disabled = false;
                }
            },
            
            showError: function(message) {
                const errorContainer = document.getElementById('error-container');
                errorContainer.textContent = message || 'Ocorreu um erro ao carregar as informações. Tente novamente mais tarde.';
                errorContainer.classList.remove('hidden');
            }
        };
        
        // Inicializar quando o DOM estiver carregado
        document.addEventListener('DOMContentLoaded', function() {
            StatusTracking.init();
        });
    </script>
</body>
</html>
