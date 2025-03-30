/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Script client-side para monitoramento de performance em ambiente de produção.
 * Este script coleta métricas de carregamento de página e recursos
 * com base em Performance API e Navigation Timing API.
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Namespace para evitar conflitos
window.ProductionMonitor = (function() {
    // Cache de configurações
    let config = {
        endpoint: '/api/performance/collect',
        debug: false,
        pageInfo: {}
    };
    
    // Cache de métricas coletadas
    let metrics = {
        timing: {},
        resources: [],
        paint: {},
        memory: {},
        viewport: {},
        errors: []
    };
    
    /**
     * Inicializa o monitor com configurações
     * 
     * @param {Object} options Opções de configuração
     */
    function init(options) {
        // Mesclar configurações
        config = Object.assign(config, window.tavernaMonitorConfig || {}, options || {});
        
        // Registrar handlers para coleta de eventos
        if (document.readyState === 'complete') {
            collectMetrics();
        } else {
            window.addEventListener('load', collectMetrics);
        }
        
        // Registrar handler para erros
        window.addEventListener('error', function(event) {
            metrics.errors.push({
                message: event.message,
                source: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                timestamp: new Date().toISOString()
            });
        });
        
        log('Monitor de performance inicializado');
    }
    
    /**
     * Coleta métricas básicas de performance
     */
    function collectMetrics() {
        // Métricas de timing
        collectTimingMetrics();
        
        // Métricas de recursos
        collectResourceMetrics();
        
        // Métricas de paint
        collectPaintMetrics();
        
        // Informações de dispositivo
        collectDeviceInfo();
        
        // Log em modo debug
        log('Métricas coletadas', metrics);
    }
    
    /**
     * Coleta métricas de timing
     */
    function collectTimingMetrics() {
        if (window.performance && window.performance.timing) {
            const timing = window.performance.timing;
            
            metrics.timing = {
                navigationStart: timing.navigationStart,
                redirectTime: timing.redirectEnd - timing.redirectStart,
                dnsTime: timing.domainLookupEnd - timing.domainLookupStart,
                connectTime: timing.connectEnd - timing.connectStart,
                requestTime: timing.responseStart - timing.requestStart,
                responseTime: timing.responseEnd - timing.responseStart,
                domProcessingTime: timing.domComplete - timing.domLoading,
                domContentLoadedTime: timing.domContentLoadedEventEnd - timing.navigationStart,
                loadTime: timing.loadEventEnd - timing.navigationStart
            };
        }
    }
    
    /**
     * Coleta métricas de recursos
     */
    function collectResourceMetrics() {
        if (window.performance && window.performance.getEntriesByType) {
            const resources = window.performance.getEntriesByType('resource');
            
            // Agrupar por tipo e tamanho
            let totalSize = 0;
            const resourceTypes = {};
            
            resources.forEach(resource => {
                const type = getResourceType(resource.name);
                
                if (!resourceTypes[type]) {
                    resourceTypes[type] = {
                        count: 0,
                        size: 0,
                        time: 0
                    };
                }
                
                resourceTypes[type].count++;
                
                // Tentar estimar tamanho (não disponível em todos navegadores)
                if (resource.transferSize) {
                    resourceTypes[type].size += resource.transferSize;
                    totalSize += resource.transferSize;
                }
                
                // Tempo de carregamento
                resourceTypes[type].time += resource.duration;
                
                // Se estiver no modo de debug, armazenar mais informações
                if (config.debug) {
                    metrics.resources.push({
                        name: resource.name,
                        type: type,
                        duration: resource.duration,
                        transferSize: resource.transferSize,
                        initiatorType: resource.initiatorType
                    });
                }
            });
            
            metrics.resourceSummary = {
                totalResources: resources.length,
                totalSize: totalSize,
                byType: resourceTypes
            };
        }
    }
    
    /**
     * Coleta métricas de paint
     */
    function collectPaintMetrics() {
        if (window.performance && window.performance.getEntriesByType) {
            const paintMetrics = window.performance.getEntriesByType('paint');
            
            paintMetrics.forEach(paint => {
                if (paint.name === 'first-paint') {
                    metrics.paint.firstPaint = paint.startTime;
                } else if (paint.name === 'first-contentful-paint') {
                    metrics.paint.firstContentfulPaint = paint.startTime;
                }
            });
        }
    }
    
    /**
     * Coleta informações do dispositivo
     */
    function collectDeviceInfo() {
        // Dimensões da viewport
        metrics.viewport = {
            width: window.innerWidth,
            height: window.innerHeight,
            devicePixelRatio: window.devicePixelRatio || 1
        };
        
        // Informações de memória (Chrome only)
        if (window.performance && window.performance.memory) {
            metrics.memory = {
                jsHeapSizeLimit: window.performance.memory.jsHeapSizeLimit,
                totalJSHeapSize: window.performance.memory.totalJSHeapSize,
                usedJSHeapSize: window.performance.memory.usedJSHeapSize
            };
        }
        
        // Informações de conexão
        if (navigator.connection) {
            metrics.connection = {
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt,
                saveData: navigator.connection.saveData
            };
        }
    }
    
    /**
     * Envia métricas para o servidor
     */
    function sendMetrics() {
        // Adicionar informações da página
        const data = {
            metrics: metrics,
            pageInfo: config.pageInfo,
            userAgent: navigator.userAgent,
            url: window.location.href,
            referrer: document.referrer,
            timestamp: new Date().toISOString()
        };
        
        // Tentar enviar usando fetch
        try {
            fetch(config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data),
                // Não esperar por resposta se estiver fechando a página
                keepalive: true
            }).then(response => {
                if (config.debug && response.ok) {
                    log('Métricas enviadas com sucesso');
                }
            }).catch(error => {
                if (config.debug) {
                    console.error('Erro ao enviar métricas:', error);
                }
            });
        } catch (e) {
            if (config.debug) {
                console.error('Erro ao enviar métricas:', e);
            }
            
            // Fallback para Beacon API
            const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
            navigator.sendBeacon(config.endpoint, blob);
        }
    }
    
    /**
     * Obtém o tipo de recurso com base na URL
     * 
     * @param {string} url URL do recurso
     * @return {string} Tipo do recurso
     */
    function getResourceType(url) {
        const extension = url.split('.').pop().split('?')[0].toLowerCase();
        
        const types = {
            'js': 'script',
            'css': 'style',
            'jpg': 'image',
            'jpeg': 'image',
            'png': 'image',
            'gif': 'image',
            'svg': 'image',
            'webp': 'image',
            'woff': 'font',
            'woff2': 'font',
            'ttf': 'font',
            'eot': 'font',
            'otf': 'font'
        };
        
        return types[extension] || 'other';
    }
    
    /**
     * Registra mensagens de log no console (apenas em modo debug)
     */
    function log() {
        if (config.debug && console && console.log) {
            console.log('[Performance Monitor]', ...arguments);
        }
    }
    
    // Inicializar automaticamente se a configuração estiver disponível
    if (window.tavernaMonitorConfig) {
        init();
    }
    
    // API pública
    return {
        init: init,
        collectMetrics: collectMetrics,
        sendMetrics: sendMetrics
    };
})();
