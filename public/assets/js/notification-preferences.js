/**
 * JavaScript para manipulação das preferências de notificação
 */

document.addEventListener('DOMContentLoaded', function() {
    /**
     * Inicialização e configuração
     */
    const API_BASE_URL = typeof BASE_URL !== 'undefined' ? BASE_URL : '/';
    const UPDATE_ENDPOINT = `${API_BASE_URL}preferencias-notificacao/atualizar`;
    const INITIALIZE_ENDPOINT = `${API_BASE_URL}preferencias-notificacao/inicializar`;
    
    // Elementos DOM
    const resetButton = document.getElementById('reset-preferences');
    const preferencesForm = document.getElementById('notification-preferences-form');
    const notificationToggles = document.querySelectorAll('.notification-toggle');
    const frequencySelects = document.querySelectorAll('.frequency-select');
    
    /**
     * Eventos
     */
    
    // Botão de restauração de padrões
    if (resetButton) {
        resetButton.addEventListener('click', showResetConfirmation);
    }
    
    // Toggles de notificação
    notificationToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            handleToggleChange(toggle);
        });
    });
    
    // Seletores de frequência
    frequencySelects.forEach(select => {
        select.addEventListener('change', function() {
            handleFrequencyChange(select);
        });
    });
    
    // Formulário principal
    if (preferencesForm) {
        preferencesForm.addEventListener('submit', function(event) {
            // A submissão normal do formulário é permitida
            // mas podemos adicionar validação aqui se necessário
        });
    }
    
    /**
     * Funções de manipulação
     */
    
    // Mostrar modal de confirmação para restaurar padrões
    function showResetConfirmation() {
        const resetModal = new bootstrap.Modal(document.getElementById('resetConfirmModal'));
        resetModal.show();
    }
    
    // Manipular mudança em toggle de notificação
    function handleToggleChange(toggle) {
        const typeId = toggle.dataset.typeId;
        const channelId = toggle.dataset.channelId;
        const isEnabled = toggle.checked;
        
        // Atualizar estado do select de frequência correspondente
        const frequencySelect = document.querySelector(`.frequency-select[data-type-id="${typeId}"][data-channel-id="${channelId}"]`);
        if (frequencySelect) {
            frequencySelect.disabled = !isEnabled;
        }
        
        // Enviar atualização via AJAX
        updatePreference(
            typeId, 
            channelId, 
            isEnabled, 
            frequencySelect ? frequencySelect.value : 'realtime'
        );
    }
    
    // Manipular mudança em select de frequência
    function handleFrequencyChange(select) {
        const typeId = select.dataset.typeId;
        const channelId = select.dataset.channelId;
        const toggle = document.querySelector(`.notification-toggle[data-type-id="${typeId}"][data-channel-id="${channelId}"]`);
        
        if (toggle && toggle.checked) {
            updatePreference(typeId, channelId, true, select.value);
        }
    }
    
    // Atualizar preferência via AJAX
    function updatePreference(typeId, channelId, isEnabled, frequency) {
        const data = {
            typeId: typeId,
            channelId: channelId,
            isEnabled: isEnabled,
            frequency: frequency
        };
        
        // Adicionar indicador de carregamento
        const toggleElement = document.querySelector(`.notification-toggle[data-type-id="${typeId}"][data-channel-id="${channelId}"]`);
        const cardElement = toggleElement.closest('.card');
        cardElement.classList.add('opacity-50');
        
        fetch(UPDATE_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            // Remover indicador de carregamento
            cardElement.classList.remove('opacity-50');
            
            if (result.success) {
                showToast('Sucesso', 'Preferência atualizada com sucesso!', 'success');
            } else {
                showToast('Erro', result.message || 'Erro ao atualizar preferência.', 'danger');
                // Reverter alteração visual em caso de erro
                if (toggleElement) {
                    toggleElement.checked = !isEnabled;
                }
                // Também reverter o estado de desabilitação do select
                const frequencySelect = document.querySelector(`.frequency-select[data-type-id="${typeId}"][data-channel-id="${channelId}"]`);
                if (frequencySelect) {
                    frequencySelect.disabled = isEnabled;
                }
            }
        })
        .catch(error => {
            console.error('Erro na solicitação AJAX:', error);
            cardElement.classList.remove('opacity-50');
            showToast('Erro', 'Falha na comunicação com o servidor.', 'danger');
            
            // Reverter alteração visual em caso de erro
            if (toggleElement) {
                toggleElement.checked = !isEnabled;
            }
            // Também reverter o estado de desabilitação do select
            const frequencySelect = document.querySelector(`.frequency-select[data-type-id="${typeId}"][data-channel-id="${channelId}"]`);
            if (frequencySelect) {
                frequencySelect.disabled = isEnabled;
            }
        });
    }
    
    // Mostrar uma notificação toast
    function showToast(title, message, type = 'info') {
        // Criar elementos do toast
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-notification';
        
        const toastElement = document.createElement('div');
        toastElement.className = 'toast show';
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        const toastHeader = document.createElement('div');
        toastHeader.className = `toast-header bg-${type} text-white`;
        
        const titleElement = document.createElement('strong');
        titleElement.className = 'me-auto';
        titleElement.textContent = title;
        
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close';
        closeButton.setAttribute('data-bs-dismiss', 'toast');
        closeButton.setAttribute('aria-label', 'Close');
        
        const toastBody = document.createElement('div');
        toastBody.className = 'toast-body';
        toastBody.textContent = message;
        
        // Montar estrutura do toast
        toastHeader.appendChild(titleElement);
        toastHeader.appendChild(closeButton);
        toastElement.appendChild(toastHeader);
        toastElement.appendChild(toastBody);
        toastContainer.appendChild(toastElement);
        
        // Adicionar ao corpo do documento
        document.body.appendChild(toastContainer);
        
        // Configurar evento de clique para botão fechar
        closeButton.addEventListener('click', function() {
            toastContainer.remove();
        });
        
        // Remover automaticamente após 3 segundos
        setTimeout(() => {
            if (document.body.contains(toastContainer)) {
                toastContainer.remove();
            }
        }, 3000);
    }
    
    // Inicializar tooltips se o Bootstrap estiver disponível
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
