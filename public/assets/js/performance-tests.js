/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Script para interação com a interface de testes de performance
 * Gerencia a execução de testes, visualização de resultados e interações da UI
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Configurações Globais
const PerformanceTests = {
    baseUrl: document.querySelector('meta[name="base-url"]')?.content || '',
    testTypes: {
        page_load: {
            icon: 'file-earmark-text',
            label: 'Carregamento de Página',
            color: 'primary'
        },
        resource_load: {
            icon: 'file-earmark-image',
            label: 'Carregamento de Recursos',
            color: 'success'
        },
        api_response: {
            icon: 'hdd-network',
            label: 'Resposta API',
            color: 'info'
        },
        db_query: {
            icon: 'database',
            label: 'Consulta BD',
            color: 'warning'
        },
        render_time: {
            icon: 'display',
            label: 'Tempo de Renderização',
            color: 'danger'
        },
        memory_usage: {
            icon: 'memory',
            label: 'Uso de Memória',
            color: 'secondary'
        },
        network: {
            icon: 'wifi',
            label: 'Rede',
            color: 'dark'
        },
        full_page: {
            icon: 'window-fullscreen',
            label: 'Página Completa',
            color: 'primary'
        }
    },
    thresholds: {
        page_load: {
            excellent: 500,
            good: 1000,
            regular: 2000
        },
        api_response: {
            excellent: 100,
            good: 300,
            regular: 500
        },
        db_query: {
            excellent: 50,
            good: 100,
            regular: 200
        }
    }
};

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    initPerformanceTestsUI();
});

/**
 * Inicializa a interface de usuário para testes de performance
 */
function initPerformanceTestsUI() {
    // Inicializar formulários de teste
    initTestForms();
    
    // Inicializar tabela de testes
    initTestsTable();
    
    // Inicializar seleção de testes para comparação
    initTestSelectionForComparison();
    
    // Inicializar detalhes específicos por tipo de teste
    initTestTypeSpecifics();
}

/**
 * Inicializa os formulários de teste
 */
function initTestForms() {
    // Obter todos os formulários de teste
    const forms = [
        document.getElementById('pageLoadForm'),
        document.getElementById('apiResponseForm'),
        document.getElementById('dbQueryForm'),
        document.getElementById('fullPageForm')
    ];
    
    // Adicionar validação e envio para cada formulário
    forms.forEach(form => {
        if (form) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Validar o formulário
                if (!form.checkValidity()) {
                    event.stopPropagation();
                    form.classList.add('was-validated');
                    return;
                }
                
                // Executar o teste
                executeTest(form);
            });
        }
    });
}

/**
 * Inicializa a tabela de testes com DataTables
 */
function initTestsTable() {
    const table = document.getElementById('testsTable');
    if (table) {
        $(table).DataTable({
            order: [[1, 'desc']], // Ordenar por ID (descendente)
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json'
            },
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            responsive: true
        });
    }
}

/**
 * Inicializa a seleção de testes para comparação
 */
function initTestSelectionForComparison() {
    const selectAllCheckbox = document.getElementById('selectAllTests');
    const testCheckboxes = document.querySelectorAll('.test-checkbox');
    const compareButton = document.getElementById('compareSelectedTests');
    
    // Selecionar/desselecionar todos
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            testCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedTests();
        });
    }
    
    // Atualizar seleção individual
    testCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedTests);
    });
    
    // Botão para comparar testes selecionados
    if (compareButton) {
        compareButton.addEventListener('click', function() {
            const form = document.getElementById('compareTestsForm');
            if (form) {
                form.submit();
            }
        });
    }
}

/**
 * Inicializa comportamentos específicos para cada tipo de teste
 */
function initTestTypeSpecifics() {
    // Seletor de tipo de consulta para teste de banco de dados
    const queryTypeSelect = document.getElementById('query_type');
    if (queryTypeSelect) {
        queryTypeSelect.addEventListener('change', function() {
            updateDbQueryParams();
        });
    }
    
    // Seletor de método para teste de API
    const apiMethodSelect = document.getElementById('api_method');
    if (apiMethodSelect) {
        apiMethodSelect.addEventListener('change', function() {
            const apiDataContainer = document.getElementById('api_data_container');
            if (apiDataContainer) {
                if (this.value === 'GET' || this.value === 'DELETE') {
                    apiDataContainer.classList.add('d-none');
                } else {
                    apiDataContainer.classList.remove('d-none');
                }
            }
        });
    }
}

/**
 * Atualiza os parâmetros disponíveis para o teste de banco de dados
 * com base no tipo de consulta selecionado
 */
function updateDbQueryParams() {
    const queryTypeSelect = document.getElementById('query_type');
    if (!queryTypeSelect) return;
    
    const queryParams = document.getElementById('queryParams');
    const categoryParam = document.querySelector('.category-param');
    const searchParam = document.querySelector('.search-param');
    const orderParam = document.querySelector('.order-param');
    
    // Ocultar todos os parâmetros
    if (queryParams) queryParams.classList.add('d-none');
    if (categoryParam) categoryParam.classList.add('d-none');
    if (searchParam) searchParam.classList.add('d-none');
    if (orderParam) orderParam.classList.add('d-none');
    
    // Mostrar parâmetros relevantes
    if (queryTypeSelect.value) {
        if (queryParams) queryParams.classList.remove('d-none');
        
        switch (queryTypeSelect.value) {
            case 'products_category':
                if (categoryParam) categoryParam.classList.remove('d-none');
                break;
            case 'products_search':
                if (searchParam) searchParam.classList.remove('d-none');
                break;
            case 'order_details':
                if (orderParam) orderParam.classList.remove('d-none');
                break;
        }
    }
}

/**
 * Atualiza a lista de testes selecionados para comparação
 */
function updateSelectedTests() {
    const testCheckboxes = document.querySelectorAll('.test-checkbox:checked');
    const selectedTestsDiv = document.getElementById('selectedTests');
    const compareButton = document.getElementById('compareSelectedTests');
    const compareForm = document.getElementById('compareTestsForm');
    
    if (!selectedTestsDiv || !compareButton || !compareForm) return;
    
    // Limpar inputs ocultos existentes
    const existingInputs = compareForm.querySelectorAll('input[name="test_ids[]"]');
    existingInputs.forEach(input => input.remove());
    
    // Verificar se há testes selecionados
    if (testCheckboxes.length === 0) {
        selectedTestsDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i> Nenhum teste selecionado
            </div>
        `;
        compareButton.disabled = true;
        return;
    }
    
    // Verificar se todos os testes são do mesmo tipo
    let firstType = null;
    let sameType = true;
    
    testCheckboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const typeCell = row.cells[2];
        const currentType = typeCell.textContent.trim();
        
        if (!firstType) {
            firstType = currentType;
        } else if (currentType !== firstType) {
            sameType = false;
        }
        
        // Adicionar input oculto para o ID do teste
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'test_ids[]';
        input.value = checkbox.value;
        compareForm.appendChild(input);
    });
    
    // Construir lista de testes selecionados
    let html = '<ul class="list-group">';
    
    testCheckboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const testId = row.cells[1].textContent;
        const testType = row.cells[2].textContent.trim();
        const testDate = row.cells[3].textContent.trim();
        
        html += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>Teste #${testId}</strong>
                    <span class="text-muted"> - ${testType}</span>
                    <small class="d-block text-muted">${testDate}</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-test" data-test-id="${checkbox.value}">
                    <i class="bi bi-x"></i>
                </button>
            </li>
        `;
    });
    
    html += '</ul>';
    
    if (!sameType) {
        html += `
            <div class="alert alert-danger mt-3">
                <i class="bi bi-exclamation-triangle me-2"></i> Os testes selecionados são de tipos diferentes. Apenas testes do mesmo tipo podem ser comparados.
            </div>
        `;
    }
    
    selectedTestsDiv.innerHTML = html;
    compareButton.disabled = !sameType || testCheckboxes.length < 2;
    
    // Adicionar evento para remover testes da seleção
    const removeButtons = selectedTestsDiv.querySelectorAll('.remove-test');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const testId = this.getAttribute('data-test-id');
            const checkbox = document.querySelector(`.test-checkbox[value="${testId}"]`);
            if (checkbox) {
                checkbox.checked = false;
                updateSelectedTests();
            }
        });
    });
}

/**
 * Executa um teste de performance
 * 
 * @param {HTMLFormElement} form Formulário com os parâmetros do teste
 */
function executeTest(form) {
    // Obter dados do formulário
    const formData = new FormData(form);
    const params = {};
    
    for (const [key, value] of formData.entries()) {
        params[key] = value;
    }
    
    // Mostrar indicador de carregamento
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Executando...';
    
    // URL para envio do teste
    const url = `${PerformanceTests.baseUrl}admin/performance_test/run_test`;
    
    // Fazer a requisição AJAX
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(params)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        // Restaurar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = btnText;
        
        if (data.error) {
            showAlert('danger', `Erro ao executar o teste: ${data.error}`);
        } else {
            // Fechar o modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newTestModal'));
            if (modal) {
                modal.hide();
            }
            
            // Redirecionar para a página de relatório
            const reportUrl = `${PerformanceTests.baseUrl}admin/performance_test/view_report/${data.test_id}`;
            window.location.href = reportUrl;
        }
    })
    .catch(error => {
        // Restaurar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = btnText;
        
        console.error('Erro na execução do teste:', error);
        showAlert('danger', `Erro ao executar o teste: ${error.message}`);
    });
}

/**
 * Exibe um alerta na tela
 * 
 * @param {string} type Tipo do alerta (success, danger, warning, info)
 * @param {string} message Mensagem a ser exibida
 * @param {number} duration Duração em ms (0 para não desaparecer)
 */
function showAlert(type, message, duration = 5000) {
    const alertsContainer = document.getElementById('alerts-container');
    
    // Criar container de alertas se não existir
    if (!alertsContainer) {
        const container = document.createElement('div');
        container.id = 'alerts-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1050';
        document.body.appendChild(container);
    }
    
    // Criar o alerta
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <div>${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    `;
    
    // Adicionar ao container
    const container = document.getElementById('alerts-container');
    container.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-remover após duração especificada
    if (duration > 0) {
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        }, duration);
    }
}

/**
 * Formata um valor de tempo para exibição
 * 
 * @param {number} value Valor em milissegundos
 * @param {boolean} showUnit Se deve mostrar a unidade (ms)
 * @returns {string} Valor formatado
 */
function formatTime(value, showUnit = true) {
    if (value === null || value === undefined) {
        return 'N/A';
    }
    
    const unit = showUnit ? ' ms' : '';
    
    if (value < 0.1) {
        return '< 0.1' + unit;
    }
    
    return value.toFixed(1) + unit;
}

/**
 * Formata um valor de tamanho para exibição
 * 
 * @param {number} bytes Tamanho em bytes
 * @returns {string} Tamanho formatado
 */
function formatSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Determina a classe CSS para um valor com base nos limiares
 * 
 * @param {number} value Valor a ser avaliado
 * @param {object} thresholds Objeto com limiares excellent, good e regular
 * @returns {string} Classe CSS (success, warning, danger)
 */
function getPerformanceClass(value, thresholds) {
    if (value <= thresholds.excellent) {
        return 'success';
    } else if (value <= thresholds.good) {
        return 'success';
    } else if (value <= thresholds.regular) {
        return 'warning';
    } else {
        return 'danger';
    }
}

/**
 * Determina o ícone para um valor com base nos limiares
 * 
 * @param {number} value Valor a ser avaliado
 * @param {object} thresholds Objeto com limiares excellent, good e regular
 * @returns {string} Nome do ícone do Bootstrap
 */
function getPerformanceIcon(value, thresholds) {
    if (value <= thresholds.excellent) {
        return 'check-circle-fill';
    } else if (value <= thresholds.good) {
        return 'check-circle';
    } else if (value <= thresholds.regular) {
        return 'exclamation-triangle';
    } else {
        return 'exclamation-circle';
    }
}
