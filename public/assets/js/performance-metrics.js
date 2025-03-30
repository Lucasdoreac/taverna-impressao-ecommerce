/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Script para coleta de métricas de performance no cliente
 * Este script coleta uma variedade de métricas de performance da navegação
 * e as envia para o servidor para análise e geração de relatórios
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Função para inicializar a coleta de métricas
function initPerformanceMetrics() {
    // Verificar se a API de Performance está disponível
    if (!window.performance || !window.performance.timing) {
        console.warn('API de Performance não está disponível neste navegador.');
        return;
    }

    // Aguardar o carregamento completo da página
    if (document.readyState !== 'complete') {
        window.addEventListener('load', collectMetricsOnLoad);
    } else {
        collectMetricsOnLoad();
    }
}

// Coletar métricas após o carregamento da página
function collectMetricsOnLoad() {
    // Deixar um pequeno atraso para garantir que todas as métricas estejam disponíveis
    setTimeout(function() {
        // Coletar métricas
        const metrics = collectAllMetrics();
        
        // Enviar métricas para o servidor
        sendMetricsToServer(metrics);
        
        // Registrar no console em modo de desenvolvimento
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.log('Métricas de Performance:', metrics);
        }
    }, 300);
}

// Coletar todas as métricas disponíveis
function collectAllMetrics() {
    const timing = window.performance.timing;
    const navigation = window.performance.navigation;
    
    // Calcular métricas de tempo
    const metrics = {
        // Informações da navegação
        pageUrl: window.location.href,
        userAgent: navigator.userAgent,
        deviceType: getDeviceType(),
        screenSize: `${window.screen.width}x${window.screen.height}`,
        viewport: `${window.innerWidth}x${window.innerHeight}`,
        connectionType: getConnectionType(),
        
        // Tempos básicos
        navigationStart: timing.navigationStart,
        timestamp: new Date().toISOString(),
        
        // Métricas chave de performance
        pageLoadTime: timing.loadEventEnd - timing.navigationStart,
        domContentLoadedTime: timing.domContentLoadedEventEnd - timing.navigationStart,
        timeToFirstByte: timing.responseStart - timing.navigationStart,
        domProcessingTime: timing.domComplete - timing.domInteractive,
        redirectTime: timing.redirectEnd - timing.redirectStart,
        dnsLookupTime: timing.domainLookupEnd - timing.domainLookupStart,
        tcpConnectionTime: timing.connectEnd - timing.connectStart,
        serverResponseTime: timing.responseEnd - timing.requestStart,
        pageRenderTime: timing.loadEventEnd - timing.responseEnd,
        frontEndTime: timing.loadEventEnd - timing.responseEnd,
        backEndTime: timing.responseEnd - timing.navigationStart,
        
        // Tipo de navegação
        navigationType: getNavigationType(navigation.type),
        redirectCount: navigation.redirectCount,
        
        // Recursos
        resourceCount: getResourceCount(),
        resourceMetrics: collectResourceMetrics(),
        
        // Métricas adicionais (se disponíveis)
        fpTime: getFirstPaintTime(),
        fcpTime: getFirstContentfulPaintTime(),
        lcp: getLargestContentfulPaint(),
        cls: getCumulativeLayoutShift(),
        fid: getFirstInputDelay(),
        
        // Dados do navegador e sistema
        memory: getMemoryInfo()
    };
    
    return metrics;
}

// Obter métricas dos recursos (imagens, scripts, css, etc)
function collectResourceMetrics() {
    // Verificar se a API de Performance Timeline está disponível
    if (!window.performance || !window.performance.getEntriesByType) {
        return [];
    }
    
    // Obter todos os recursos carregados
    const resources = window.performance.getEntriesByType('resource');
    
    // Estrutura para agrupar os recursos por tipo
    const resourceMetrics = {
        summary: {
            total: resources.length,
            size: 0,
            totalDuration: 0,
            byType: {}
        },
        slowestResources: [],
        largestResources: []
    };
    
    // Processar cada recurso
    resources.forEach(resource => {
        const type = getResourceType(resource.name);
        const size = resource.transferSize || 0;
        const duration = resource.duration;
        
        // Adicionar ao tamanho total
        resourceMetrics.summary.size += size;
        resourceMetrics.summary.totalDuration += duration;
        
        // Agrupar por tipo
        if (!resourceMetrics.summary.byType[type]) {
            resourceMetrics.summary.byType[type] = {
                count: 0,
                size: 0,
                totalDuration: 0
            };
        }
        
        resourceMetrics.summary.byType[type].count++;
        resourceMetrics.summary.byType[type].size += size;
        resourceMetrics.summary.byType[type].totalDuration += duration;
        
        // Adicionar às listas de recursos mais lentos e maiores
        resourceMetrics.slowestResources.push({
            name: resource.name,
            type: type,
            duration: duration,
            size: size
        });
        
        resourceMetrics.largestResources.push({
            name: resource.name,
            type: type,
            duration: duration,
            size: size
        });
    });
    
    // Ordenar e limitar os recursos mais lentos e maiores
    resourceMetrics.slowestResources.sort((a, b) => b.duration - a.duration);
    resourceMetrics.largestResources.sort((a, b) => b.size - a.size);
    
    resourceMetrics.slowestResources = resourceMetrics.slowestResources.slice(0, 5);
    resourceMetrics.largestResources = resourceMetrics.largestResources.slice(0, 5);
    
    // Calcular médias para cada tipo
    Object.keys(resourceMetrics.summary.byType).forEach(type => {
        const typeData = resourceMetrics.summary.byType[type];
        typeData.avgDuration = typeData.totalDuration / typeData.count;
        typeData.avgSize = typeData.size / typeData.count;
    });
    
    return resourceMetrics;
}

// Enviar métricas para o servidor
function sendMetricsToServer(metrics) {
    // Verificar se o endpoint está definido
    const endpoint = '/admin/performance_test/collect_metrics';
    
    // Enviar via fetch API
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(metrics),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Falha ao enviar métricas: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('Métricas enviadas com sucesso');
        } else {
            console.warn('Falha ao processar métricas no servidor:', data.message);
        }
    })
    .catch(error => {
        console.error('Erro ao enviar métricas:', error);
    });
}

// Funções auxiliares para obter informações adicionais

// Obter o tipo de dispositivo
function getDeviceType() {
    const ua = navigator.userAgent;
    if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) {
        return 'tablet';
    }
    if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(ua)) {
        return 'mobile';
    }
    return 'desktop';
}

// Obter informações sobre a conexão
function getConnectionType() {
    if (navigator.connection) {
        return {
            effectiveType: navigator.connection.effectiveType || 'unknown',
            downlink: navigator.connection.downlink || 0,
            rtt: navigator.connection.rtt || 0,
            saveData: navigator.connection.saveData || false
        };
    }
    return 'not-available';
}

// Traduzir o tipo de navegação
function getNavigationType(type) {
    const types = {
        0: 'navigation',
        1: 'reload',
        2: 'back_forward',
        255: 'undefined'
    };
    return types[type] || 'unknown';
}

// Contar recursos carregados
function getResourceCount() {
    if (!window.performance || !window.performance.getEntriesByType) {
        return -1;
    }
    return window.performance.getEntriesByType('resource').length;
}

// Obter o tipo de recurso com base na URL
function getResourceType(url) {
    if (!url) return 'other';
    
    const extension = url.split('.').pop().split('?')[0].toLowerCase();
    
    if (/jpe?g|png|gif|svg|webp|bmp|ico/i.test(extension)) {
        return 'image';
    } else if (/css/i.test(extension)) {
        return 'css';
    } else if (/js/i.test(extension)) {
        return 'javascript';
    } else if (/html?/i.test(extension)) {
        return 'html';
    } else if (/woff2?|ttf|otf|eot/i.test(extension)) {
        return 'font';
    } else if (/json/i.test(extension)) {
        return 'json';
    } else if (/xml/i.test(extension)) {
        return 'xml';
    }
    
    // Verificar por padrões na URL
    if (url.includes('/api/') || url.includes('/ajax/')) {
        return 'api';
    } else if (url.includes('/fonts/')) {
        return 'font';
    } else if (url.includes('/images/') || url.includes('/img/')) {
        return 'image';
    } else if (url.includes('/css/') || url.includes('/styles/')) {
        return 'css';
    } else if (url.includes('/js/') || url.includes('/scripts/')) {
        return 'javascript';
    }
    
    return 'other';
}

// Obter tempo da primeira renderização (First Paint)
function getFirstPaintTime() {
    if (window.performance && window.performance.getEntriesByType) {
        const paintMetrics = window.performance.getEntriesByType('paint');
        const firstPaint = paintMetrics.find(entry => entry.name === 'first-paint');
        
        if (firstPaint) {
            return firstPaint.startTime;
        }
    }
    return null;
}

// Obter tempo da primeira renderização com conteúdo (First Contentful Paint)
function getFirstContentfulPaintTime() {
    if (window.performance && window.performance.getEntriesByType) {
        const paintMetrics = window.performance.getEntriesByType('paint');
        const firstContentfulPaint = paintMetrics.find(entry => entry.name === 'first-contentful-paint');
        
        if (firstContentfulPaint) {
            return firstContentfulPaint.startTime;
        }
    }
    return null;
}

// Obter informações sobre o maior conteúdo visível (Largest Contentful Paint)
function getLargestContentfulPaint() {
    if (!window.PerformanceObserver || !window.LargestContentfulPaint) {
        return null;
    }
    
    // Aqui usamos um valor armazenado pelo PerformanceObserver
    // que deveria ser inicializado no cabeçalho
    return window.largestContentfulPaint || null;
}

// Obter informações sobre mudanças de layout cumulativas (Cumulative Layout Shift)
function getCumulativeLayoutShift() {
    if (!window.PerformanceObserver || !window.CumulativeLayoutShift) {
        return null;
    }
    
    // Aqui usamos um valor armazenado pelo PerformanceObserver
    // que deveria ser inicializado no cabeçalho
    return window.cumulativeLayoutShift || null;
}

// Obter informações sobre o atraso da primeira interação (First Input Delay)
function getFirstInputDelay() {
    if (!window.PerformanceObserver || !window.FirstInputDelay) {
        return null;
    }
    
    // Aqui usamos um valor armazenado pelo PerformanceObserver
    // que deveria ser inicializado no cabeçalho
    return window.firstInputDelay || null;
}

// Obter informações sobre o uso de memória
function getMemoryInfo() {
    if (window.performance && window.performance.memory) {
        return {
            jsHeapSizeLimit: window.performance.memory.jsHeapSizeLimit,
            totalJSHeapSize: window.performance.memory.totalJSHeapSize,
            usedJSHeapSize: window.performance.memory.usedJSHeapSize
        };
    }
    return null;
}

// Inicializar a coleta de métricas de Web Vitals quando o documento estiver pronto
function initWebVitals() {
    // Verificar se o navegador suporta PerformanceObserver
    if (!window.PerformanceObserver) {
        return;
    }
    
    // Observador para Largest Contentful Paint (LCP)
    try {
        const lcpObserver = new PerformanceObserver((entryList) => {
            const entries = entryList.getEntries();
            const lastEntry = entries[entries.length - 1];
            window.largestContentfulPaint = lastEntry.startTime;
        });
        lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (e) {
        console.warn('LCP measurement not supported', e);
    }
    
    // Observador para Cumulative Layout Shift (CLS)
    try {
        let clsValue = 0;
        const clsObserver = new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
            window.cumulativeLayoutShift = clsValue;
        });
        clsObserver.observe({ type: 'layout-shift', buffered: true });
    } catch (e) {
        console.warn('CLS measurement not supported', e);
    }
    
    // Observador para First Input Delay (FID)
    try {
        const fidObserver = new PerformanceObserver((entryList) => {
            const firstInput = entryList.getEntries()[0];
            if (firstInput) {
                window.firstInputDelay = firstInput.processingStart - firstInput.startTime;
            }
        });
        fidObserver.observe({ type: 'first-input', buffered: true });
    } catch (e) {
        console.warn('FID measurement not supported', e);
    }
}

// Inicializar as métricas de Web Vitals
initWebVitals();

// Inicializar a coleta de métricas de performance
document.addEventListener('DOMContentLoaded', initPerformanceMetrics);
