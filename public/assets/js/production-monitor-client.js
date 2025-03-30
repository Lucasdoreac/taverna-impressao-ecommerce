/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Script para monitoramento contínuo de performance em ambiente de produção
 * Projetado para ser leve, não intrusivo e coletar dados de usuários reais
 * com impacto mínimo na experiência do usuário
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Namespace para monitoramento de produção
const ProductionMonitor = (function() {
    // Configurações privadas
    let _config = {
        // URL do endpoint para envio de métricas
        endpoint: 'api/performance/monitor',
        
        // Taxa de amostragem (1-100%)
        // Determina a porcentagem de sessões que coletarão dados
        samplingRate: 10,
        
        // Se deve usar o Storage para armazenar decisão de amostragem
        useStorage: true,
        
        // Tempo mínimo entre envios (em ms)
        minTimeBetweenSends: 3600000, // 1 hora
        
        // Caminhos a ignorar para coleta (admin, etc)
        ignorePaths: ['/admin', '/api', '/login'],
        
        // Se deve registrar no console (apenas para debugging)
        debug: false
    };
    
    // Controles privados
    let _isEnabled = false;
    let _hasBeenSampled = false;
    let _metrics = {};
    let _observers = [];
    
    /**
     * Inicializa o monitor de produção
     * 
     * @param {object} options Opções de configuração
     * @returns {object} API pública
     */
    function init(options = {}) {
        // Mesclar configurações personalizadas
        _config = { ..._config, ...options };
        
        // Verificar se está em um caminho ignorado
        if (_isIgnoredPath()) {
            _log('Caminho ignorado para monitoramento:', window.location.pathname);
            return {
                isEnabled: () => false,
                getMetrics: () => ({}),
                sendMetrics: () => Promise.reject('Monitor desabilitado')
            };
        }
        
        // Determinar se esta sessão deve ser amostrada
        _determineSampling();
        
        // Se for amostrado, iniciar coleta
        if (_isEnabled) {
            _setupMetricsCollection();
            
            // Registrar evento de envio quando a página estiver pronta para enviar métricas
            window.addEventListener('load', () => {
                // Aguardar página completamente carregada + 3 segundos para métricas finais
                setTimeout(() => {
                    _collectMetrics();
                    _sendMetrics();
                }, 3000);
            });
            
            _log('Monitor de produção inicializado com sucesso');
        } else {
            _log('Esta sessão não será monitorada (amostragem)');
        }
        
        // Retornar API pública
        return {
            isEnabled: () => _isEnabled,
            getMetrics: () => _metrics,
            sendMetrics: _sendMetrics
        };
    }
    
    /**
     * Determina se esta sessão será amostrada para coleta
     */
    function _determineSampling() {
        // Verificar Storage primeiro
        if (_config.useStorage) {
            try {
                const sampled = sessionStorage.getItem('taverna_monitor_sampled');
                if (sampled !== null) {
                    _isEnabled = sampled === 'true';
                    _hasBeenSampled = true;
                    
                    _log('Usando decisão de amostragem armazenada:', _isEnabled);
                    return;
                }
            } catch (e) {
                _log('Erro ao acessar sessionStorage:', e);
            }
        }
        
        // Se não tiver sido amostrado, aplicar taxa de amostragem
        if (!_hasBeenSampled) {
            // Gerar um valor aleatório entre 1 e 100
            const randomValue = Math.floor(Math.random() * 100) + 1;
            
            // Habilitar com base na taxa de amostragem
            _isEnabled = randomValue <= _config.samplingRate;
            _hasBeenSampled = true;
            
            // Armazenar decisão
            if (_config.useStorage) {
                try {
                    sessionStorage.setItem('taverna_monitor_sampled', _isEnabled.toString());
                } catch (e) {
                    _log('Erro ao armazenar decisão em sessionStorage:', e);
                }
            }
            
            _log('Nova decisão de amostragem:', _isEnabled, 'Valor aleatório:', randomValue);
        }
    }
    
    /**
     * Configura os coletores de métricas usando PerformanceObserver
     */
    function _setupMetricsCollection() {
        // Registrar observadores para diferentes tipos de métricas
        _registerObserver('navigation', (entries) => {
            const navigationEntries = entries.getEntries();
            if (navigationEntries.length > 0) {
                const nav = navigationEntries[0];
                _metrics.navigation = {
                    type: nav.type,
                    loadTime: nav.loadEventEnd - nav.startTime,
                    domContentLoaded: nav.domContentLoadedEventEnd - nav.startTime,
                    ttfb: nav.responseStart - nav.requestStart,
                    domInteractive: nav.domInteractive - nav.startTime
                };
            }
        });
        
        _registerObserver('largest-contentful-paint', (entries) => {
            const lcp = entries.getEntries().pop();
            if (lcp) {
                _metrics.lcp = {
                    value: lcp.startTime,
                    size: lcp.size,
                    id: lcp.id,
                    url: lcp.url
                };
            }
        });
        
        _registerObserver('paint', (entries) => {
            entries.getEntries().forEach(entry => {
                if (entry.name === 'first-paint') {
                    _metrics.fp = entry.startTime;
                } else if (entry.name === 'first-contentful-paint') {
                    _metrics.fcp = entry.startTime;
                }
            });
        });
        
        _registerObserver('layout-shift', (entries) => {
            let cls = 0;
            entries.getEntries().forEach(entry => {
                if (!entry.hadRecentInput) {
                    cls += entry.value;
                }
            });
            _metrics.cls = cls;
        });
        
        _registerObserver('first-input', (entries) => {
            const fid = entries.getEntries()[0];
            if (fid) {
                _metrics.fid = {
                    delay: fid.processingStart - fid.startTime,
                    startTime: fid.startTime
                };
            }
        });
        
        // Métricas de recursos (filtrar apenas os críticos)
        _registerObserver('resource', (entries) => {
            const resources = entries.getEntries();
            
            // Categorizar recursos
            const categorizedResources = {
                css: [],
                js: [],
                img: [],
                font: [],
                other: []
            };
            
            let totalTransferSize = 0;
            let totalSize = 0;
            let totalTime = 0;
            
            resources.forEach(resource => {
                // Determinar categoria
                let category = 'other';
                const url = resource.name;
                
                if (url.endsWith('.css') || url.includes('.css?')) {
                    category = 'css';
                } else if (url.endsWith('.js') || url.includes('.js?')) {
                    category = 'js';
                } else if (/\.(jpe?g|png|gif|svg|webp)($|\?)/i.test(url)) {
                    category = 'img';
                } else if (/\.(woff2?|ttf|otf|eot)($|\?)/i.test(url)) {
                    category = 'font';
                }
                
                // Dados do recurso
                const resourceData = {
                    url: url,
                    transferSize: resource.transferSize || 0,
                    duration: resource.duration,
                    decodedBodySize: resource.decodedBodySize || 0
                };
                
                // Adicionar à categoria
                categorizedResources[category].push(resourceData);
                
                // Atualizar totais
                totalTransferSize += resourceData.transferSize;
                totalSize += resourceData.decodedBodySize;
                totalTime += resourceData.duration;
            });
            
            // Salvar estatísticas gerais de recursos
            _metrics.resources = {
                count: resources.length,
                totalTransferSize,
                totalSize,
                totalTime,
                categories: {
                    css: categorizedResources.css.length,
                    js: categorizedResources.js.length,
                    img: categorizedResources.img.length,
                    font: categorizedResources.font.length,
                    other: categorizedResources.other.length
                }
            };
        });
        
        // Informações do dispositivo e conexão
        _metrics.device = {
            type: _detectDeviceType(),
            width: window.innerWidth,
            height: window.innerHeight,
            pixelRatio: window.devicePixelRatio || 1
        };
        
        // Informações da conexão (quando disponível)
        if (navigator.connection) {
            _metrics.connection = {
                effectiveType: navigator.connection.effectiveType,
                rtt: navigator.connection.rtt,
                downlink: navigator.connection.downlink,
                saveData: navigator.connection.saveData
            };
        }
    }
    
    /**
     * Registra um PerformanceObserver para coletar métricas específicas
     * 
     * @param {string} type Tipo de métrica a observar
     * @param {function} callback Função de callback para processar entradas
     */
    function _registerObserver(type, callback) {
        if (!window.PerformanceObserver) return;
        
        try {
            const observer = new PerformanceObserver(callback);
            observer.observe({ type: type, buffered: true });
            _observers.push(observer);
        } catch (e) {
            _log(`Erro ao registrar observer para ${type}:`, e);
        }
    }
    
    /**
     * Coleta métricas adicionais no final do carregamento
     */
    function _collectMetrics() {
        // Informações básicas da página
        _metrics.page = {
            url: window.location.href,
            title: document.title,
            referrer: document.referrer,
            timestamp: new Date().toISOString()
        };
        
        // Informações de memória (quando disponível)
        if (window.performance && window.performance.memory) {
            _metrics.memory = {
                usedJSHeapSize: window.performance.memory.usedJSHeapSize,
                totalJSHeapSize: window.performance.memory.totalJSHeapSize,
                jsHeapSizeLimit: window.performance.memory.jsHeapSizeLimit
            };
        }
        
        // Métricas de tempo (caso não tenham sido coletadas pelos observadores)
        if (!_metrics.loadTime && window.performance && window.performance.timing) {
            const timing = window.performance.timing;
            _metrics.timing = {
                loadTime: timing.loadEventEnd - timing.navigationStart,
                domContentLoaded: timing.domContentLoadedEventEnd - timing.navigationStart,
                ttfb: timing.responseStart - timing.requestStart,
                domInteractive: timing.domInteractive - timing.navigationStart
            };
        }
        
        // Detecção de performance issues
        _detectPerformanceIssues();
        
        _log('Métricas finais coletadas:', _metrics);
    }
    
    /**
     * Detecta possíveis problemas de performance
     */
    function _detectPerformanceIssues() {
        const issues = [];
        
        // Verificar LCP (Largest Contentful Paint)
        if (_metrics.lcp && _metrics.lcp.value > 2500) {
            issues.push({
                type: 'lcp',
                value: _metrics.lcp.value,
                threshold: 2500,
                message: 'Largest Contentful Paint acima do recomendado (2500ms)'
            });
        }
        
        // Verificar FID (First Input Delay)
        if (_metrics.fid && _metrics.fid.delay > 100) {
            issues.push({
                type: 'fid',
                value: _metrics.fid.delay,
                threshold: 100,
                message: 'First Input Delay acima do recomendado (100ms)'
            });
        }
        
        // Verificar CLS (Cumulative Layout Shift)
        if (_metrics.cls !== undefined && _metrics.cls > 0.1) {
            issues.push({
                type: 'cls',
                value: _metrics.cls,
                threshold: 0.1,
                message: 'Cumulative Layout Shift acima do recomendado (0.1)'
            });
        }
        
        // Verificar tempo de carregamento geral
        const loadTime = (_metrics.navigation && _metrics.navigation.loadTime) || 
                         (_metrics.timing && _metrics.timing.loadTime);
        
        if (loadTime && loadTime > 3000) {
            issues.push({
                type: 'load',
                value: loadTime,
                threshold: 3000,
                message: 'Tempo de carregamento acima do recomendado (3000ms)'
            });
        }
        
        // Adicionar issues às métricas
        if (issues.length > 0) {
            _metrics.issues = issues;
        }
    }
    
    /**
     * Envia as métricas coletadas para o servidor
     * 
     * @returns {Promise} Promessa com o resultado do envio
     */
    function _sendMetrics() {
        // Verificar se já enviou métricas recentemente
        if (_config.useStorage) {
            try {
                const lastSend = sessionStorage.getItem('taverna_last_metrics_send');
                if (lastSend) {
                    const lastSendTime = parseInt(lastSend, 10);
                    const now = Date.now();
                    
                    // Se enviou recentemente, não enviar novamente
                    if (now - lastSendTime < _config.minTimeBetweenSends) {
                        _log('Métricas enviadas recentemente, ignorando');
                        return Promise.resolve({ status: 'skipped', reason: 'recently_sent' });
                    }
                }
            } catch (e) {
                _log('Erro ao verificar último envio:', e);
            }
        }
        
        // Garantir que temos métricas para enviar
        if (!_metrics || Object.keys(_metrics).length === 0) {
            _log('Sem métricas para enviar');
            return Promise.resolve({ status: 'skipped', reason: 'no_metrics' });
        }
        
        // Criar payload
        const payload = {
            metrics: _metrics,
            userAgent: navigator.userAgent,
            path: window.location.pathname,
            timestamp: new Date().toISOString()
        };
        
        // Determinar URL completa do endpoint
        let url = _config.endpoint;
        if (!url.startsWith('http') && !url.startsWith('/')) {
            // Usar base URL do site quando disponível
            const baseElement = document.querySelector('base');
            const baseUrl = baseElement ? baseElement.href : window.location.origin;
            url = `${baseUrl}/${url}`.replace(/\/\//g, '/').replace(':/', '://');
        }
        
        _log('Enviando métricas para:', url);
        
        // Enviar usando beacon API quando disponível (mais confiável durante descarga de página)
        if (navigator.sendBeacon) {
            try {
                const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
                const success = navigator.sendBeacon(url, blob);
                
                if (success) {
                    _log('Métricas enviadas com sucesso via Beacon API');
                    
                    if (_config.useStorage) {
                        try {
                            sessionStorage.setItem('taverna_last_metrics_send', Date.now().toString());
                        } catch (e) {
                            _log('Erro ao armazenar timestamp de envio:', e);
                        }
                    }
                    
                    return Promise.resolve({ status: 'success', method: 'beacon' });
                }
            } catch (e) {
                _log('Erro ao enviar via Beacon API, tentando fetch:', e);
            }
        }
        
        // Fallback para fetch API
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload),
            // Usar keepalive para garantir que a requisição continue mesmo se a página for descarregada
            keepalive: true
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            
            _log('Métricas enviadas com sucesso via Fetch API');
            
            if (_config.useStorage) {
                try {
                    sessionStorage.setItem('taverna_last_metrics_send', Date.now().toString());
                } catch (e) {
                    _log('Erro ao armazenar timestamp de envio:', e);
                }
            }
            
            return { status: 'success', method: 'fetch' };
        })
        .catch(error => {
            _log('Erro ao enviar métricas:', error);
            return { status: 'error', error: error.message };
        });
    }
    
    /**
     * Verifica se o caminho atual está na lista de ignorados
     * 
     * @returns {boolean} True se o caminho deve ser ignorado
     */
    function _isIgnoredPath() {
        const currentPath = window.location.pathname;
        
        for (const ignorePath of _config.ignorePaths) {
            if (currentPath.startsWith(ignorePath)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detecta o tipo de dispositivo com base no User Agent e tamanho da tela
     * 
     * @returns {string} desktop, tablet ou mobile
     */
    function _detectDeviceType() {
        const ua = navigator.userAgent.toLowerCase();
        
        // Verificar tablets primeiro
        if (/(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/.test(ua)) {
            return 'tablet';
        }
        
        // Verificar mobile
        if (/(mobi|ipod|phone|blackberry|opera mini|fennec|minimo|symbian|psp|nintendo ds|archos|skyfire|puffin|blazer|bolt|gobrowser|iris|maemo|semc|teashark|uzard)/.test(ua)) {
            return 'mobile';
        }
        
        // Verificar baseado na largura da tela
        if (window.innerWidth <= 767) {
            return 'mobile';
        } else if (window.innerWidth <= 1024) {
            return 'tablet';
        }
        
        // Desktop por padrão
        return 'desktop';
    }
    
    /**
     * Registra mensagens de log quando o debug está ativado
     */
    function _log(...args) {
        if (_config.debug) {
            console.log('[ProductionMonitor]', ...args);
        }
    }
    
    // API pública
    return {
        init: init
    };
})();

// Auto-inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se deve inicializar automaticamente
    const autoInit = document.body.getAttribute('data-monitor-auto-init') !== 'false';
    
    if (autoInit) {
        // Inicializar com configurações padrão
        ProductionMonitor.init();
    }
});

// Exportar para uso global
window.ProductionMonitor = ProductionMonitor;
