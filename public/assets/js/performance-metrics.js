/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Script para coleta de métricas de performance no cliente
 * Captura dados sobre tempos de carregamento, renderização e recursos
 * para análise posterior da performance do site
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Namespace para métricas de performance
const PerformanceMetrics = {
    // Configurações
    config: {
        // URL para envio das métricas (relativo à base)
        endpoint: 'admin/performance_test/collect_metrics',
        // Se deve coletar automaticamente ao carregar a página
        autoCollect: true,
        // Intervalo mínimo entre coletas automáticas (ms)
        collectInterval: 3600000, // 1 hora
        // Se deve usar o localStorage para armazenar a última coleta
        useLocalStorage: true,
        // Quais métricas coletar
        metrics: {
            navigation: true,     // Navigation Timing API
            resource: true,       // Resource Timing API
            paint: true,          // Paint Timing API
            memory: true,         // Memory Info (quando disponível)
            layout: true,         // Layout Shifts
            firstInput: true,     // First Input Delay
            largestPaint: true    // Largest Contentful Paint
        }
    },
    
    // Dados coletados
    data: {},
    
    /**
     * Inicializa a coleta de métricas
     * 
     * @param {object} options Opções de configuração
     */
    init: function(options = {}) {
        // Mesclar configurações
        if (options) {
            this.config = { ...this.config, ...options };
            
            // Mesclar métricas se fornecidas
            if (options.metrics) {
                this.config.metrics = { ...this.config.metrics, ...options.metrics };
            }
        }
        
        // Registrar eventos para coleta de métricas
        this.registerEvents();
        
        // Coletar automaticamente se configurado
        if (this.config.autoCollect) {
            // Verificar se já coletou recentemente
            const lastCollect = this.getLastCollectTime();
            const now = Date.now();
            
            if (!lastCollect || (now - lastCollect) >= this.config.collectInterval) {
                // Aguardar o carregamento completo antes de coletar
                window.addEventListener('load', () => {
                    // Pequeno atraso para garantir que todas as métricas estejam disponíveis
                    setTimeout(() => this.collect(), 1000);
                });
            }
        }
        
        console.log('Taverna Impressão 3D: Sistema de métricas de performance inicializado');
    },
    
    /**
     * Registra eventos para coleta de métricas específicas
     */
    registerEvents: function() {
        // First Input Delay
        if (this.config.metrics.firstInput && window.PerformanceObserver) {
            try {
                new PerformanceObserver((entryList) => {
                    const entries = entryList.getEntries();
                    if (entries.length > 0) {
                        this.data.firstInput = entries[0].toJSON();
                    }
                }).observe({ type: 'first-input', buffered: true });
            } catch (e) {
                console.warn('Taverna Impressão 3D: Erro ao observar first-input', e);
            }
        }
        
        // Largest Contentful Paint
        if (this.config.metrics.largestPaint && window.PerformanceObserver) {
            try {
                new PerformanceObserver((entryList) => {
                    const entries = entryList.getEntries();
                    if (entries.length > 0) {
                        this.data.largestPaint = entries[entries.length - 1].toJSON();
                    }
                }).observe({ type: 'largest-contentful-paint', buffered: true });
            } catch (e) {
                console.warn('Taverna Impressão 3D: Erro ao observar largest-contentful-paint', e);
            }
        }
        
        // Layout Shifts
        if (this.config.metrics.layout && window.PerformanceObserver) {
            try {
                let cumulativeLayoutShift = 0;
                
                new PerformanceObserver((entryList) => {
                    for (const entry of entryList.getEntries()) {
                        // Apenas considerar se não foi causado por interação do usuário
                        if (!entry.hadRecentInput) {
                            cumulativeLayoutShift += entry.value;
                        }
                    }
                    
                    this.data.layoutShift = {
                        value: cumulativeLayoutShift
                    };
                }).observe({ type: 'layout-shift', buffered: true });
            } catch (e) {
                console.warn('Taverna Impressão 3D: Erro ao observar layout-shift', e);
            }
        }
    },
    
    /**
     * Coleta as métricas de performance
     * 
     * @returns {object} Objeto com as métricas coletadas
     */
    collect: function() {
        // Limpar dados anteriores
        this.data = {};
        
        // Navigation Timing
        if (this.config.metrics.navigation && window.performance && window.performance.timing) {
            this.collectNavigationTiming();
        }
        
        // Resource Timing
        if (this.config.metrics.resource && window.performance && window.performance.getEntriesByType) {
            this.collectResourceTiming();
        }
        
        // Paint Timing
        if (this.config.metrics.paint && window.performance && window.performance.getEntriesByType) {
            this.collectPaintTiming();
        }
        
        // Memory Info
        if (this.config.metrics.memory && window.performance && window.performance.memory) {
            this.collectMemoryInfo();
        }
        
        // Informações do dispositivo e navegador
        this.collectDeviceInfo();
        
        // Registrar timestamp da coleta
        this.data.timestamp = new Date().toISOString();
        this.data.url = window.location.href;
        
        // Salvar timestamp da última coleta
        if (this.config.useLocalStorage) {
            this.setLastCollectTime(Date.now());
        }
        
        // Retornar dados coletados
        return this.data;
    },
    
    /**
     * Coleta métricas de Navigation Timing
     */
    collectNavigationTiming: function() {
        const timing = window.performance.timing;
        const navigationStart = timing.navigationStart;
        
        this.data.navigation = {
            // DNS
            dnsLookup: timing.domainLookupEnd - timing.domainLookupStart,
            
            // TCP
            tcpConnection: timing.connectEnd - timing.connectStart,
            
            // Request/Response
            requestStart: timing.requestStart - navigationStart,
            responseStart: timing.responseStart - navigationStart,
            responseEnd: timing.responseEnd - navigationStart,
            
            // DOM
            domInteractive: timing.domInteractive - navigationStart,
            domContentLoaded: timing.domContentLoadedEventEnd - navigationStart,
            domComplete: timing.domComplete - navigationStart,
            
            // Page Load
            loadEvent: timing.loadEventEnd - navigationStart,
            
            // Total times
            backendTime: timing.responseStart - timing.navigationStart,
            frontendTime: timing.loadEventEnd - timing.responseStart,
            totalTime: timing.loadEventEnd - timing.navigationStart
        };
        
        // Navigation Timing 2 (quando disponível)
        if (window.PerformanceNavigationTiming) {
            try {
                const navigationEntries = performance.getEntriesByType('navigation');
                if (navigationEntries.length > 0) {
                    const navEntry = navigationEntries[0];
                    
                    this.data.navigation2 = {
                        // Tempo de resposta do servidor
                        serverTiming: navEntry.responseStart - navEntry.requestStart,
                        
                        // Tempo de redirecionamento
                        redirectTime: navEntry.redirectEnd - navEntry.redirectStart,
                        
                        // Tempo de cache
                        cacheTime: navEntry.domainLookupStart - navEntry.fetchStart,
                        
                        // Tempo para o primeiro byte
                        ttfb: navEntry.responseStart - navEntry.requestStart,
                        
                        // Tipo de navegação
                        type: navEntry.type
                    };
                }
            } catch (e) {
                console.warn('Taverna Impressão 3D: Erro ao coletar Navigation Timing 2', e);
            }
        }
    },
    
    /**
     * Coleta métricas de Resource Timing
     */
    collectResourceTiming: function() {
        try {
            const resources = performance.getEntriesByType('resource');
            
            // Agrupar recursos por tipo
            const resourcesByType = {
                script: [],
                css: [],
                img: [],
                font: [],
                xhr: [],
                fetch: [],
                other: []
            };
            
            let totalSize = 0;
            let totalTime = 0;
            
            // Filtragem e classificação de recursos
            resources.forEach(resource => {
                const url = resource.name;
                
                // Determinar tipo de recurso
                let type = 'other';
                
                if (url.match(/\.js(\?|$)/)) {
                    type = 'script';
                } else if (url.match(/\.css(\?|$)/)) {
                    type = 'css';
                } else if (url.match(/\.(png|jpg|jpeg|gif|webp|svg)(\?|$)/)) {
                    type = 'img';
                } else if (url.match(/\.(woff|woff2|ttf|otf|eot)(\?|$)/)) {
                    type = 'font';
                } else if (resource.initiatorType === 'xmlhttprequest') {
                    type = 'xhr';
                } else if (resource.initiatorType === 'fetch') {
                    type = 'fetch';
                }
                
                // Calcular tempos
                const duration = resource.duration;
                const size = resource.transferSize || 0;
                
                // Adicionar ao grupo apropriado
                resourcesByType[type].push({
                    url: url,
                    duration: duration,
                    size: size,
                    initiatorType: resource.initiatorType,
                    startTime: resource.startTime
                });
                
                // Somar aos totais
                totalSize += size;
                totalTime += duration;
            });
            
            // Calcular estatísticas por tipo
            const stats = {};
            
            for (const type in resourcesByType) {
                const typeResources = resourcesByType[type];
                
                if (typeResources.length > 0) {
                    // Calcular tempos médios, máximos e totais
                    const totalDuration = typeResources.reduce((sum, r) => sum + r.duration, 0);
                    const totalSize = typeResources.reduce((sum, r) => sum + r.size, 0);
                    const maxDuration = Math.max(...typeResources.map(r => r.duration));
                    
                    stats[type] = {
                        count: typeResources.length,
                        totalDuration: totalDuration,
                        avgDuration: totalDuration / typeResources.length,
                        maxDuration: maxDuration,
                        totalSize: totalSize,
                        avgSize: totalSize / typeResources.length
                    };
                }
            }
            
            // Recursos mais lentos
            const slowestResources = resources
                .filter(r => r.duration > 0)
                .sort((a, b) => b.duration - a.duration)
                .slice(0, 5)
                .map(r => ({
                    url: r.name,
                    duration: r.duration,
                    size: r.transferSize || 0,
                    type: r.initiatorType
                }));
            
            // Recursos maiores
            const largestResources = resources
                .filter(r => (r.transferSize || 0) > 0)
                .sort((a, b) => (b.transferSize || 0) - (a.transferSize || 0))
                .slice(0, 5)
                .map(r => ({
                    url: r.name,
                    duration: r.duration,
                    size: r.transferSize || 0,
                    type: r.initiatorType
                }));
            
            // Salvar resultados
            this.data.resources = {
                totalCount: resources.length,
                totalSize: totalSize,
                totalTime: totalTime,
                stats: stats,
                slowestResources: slowestResources,
                largestResources: largestResources
            };
        } catch (e) {
            console.warn('Taverna Impressão 3D: Erro ao coletar Resource Timing', e);
        }
    },
    
    /**
     * Coleta métricas de Paint Timing
     */
    collectPaintTiming: function() {
        try {
            const paintEntries = performance.getEntriesByType('paint');
            
            // Inicializar objeto de paint
            this.data.paint = {};
            
            // Processar entradas de pintura
            paintEntries.forEach(paint => {
                if (paint.name === 'first-paint') {
                    this.data.paint.firstPaint = paint.startTime;
                } else if (paint.name === 'first-contentful-paint') {
                    this.data.paint.firstContentfulPaint = paint.startTime;
                }
            });
        } catch (e) {
            console.warn('Taverna Impressão 3D: Erro ao coletar Paint Timing', e);
        }
    },
    
    /**
     * Coleta informações de memória (quando disponível)
     */
    collectMemoryInfo: function() {
        try {
            if (window.performance && window.performance.memory) {
                const memory = window.performance.memory;
                
                this.data.memory = {
                    totalJSHeapSize: memory.totalJSHeapSize,
                    usedJSHeapSize: memory.usedJSHeapSize,
                    jsHeapSizeLimit: memory.jsHeapSizeLimit
                };
            }
        } catch (e) {
            console.warn('Taverna Impressão 3D: Erro ao coletar Memory Info', e);
        }
    },
    
    /**
     * Coleta informações sobre o dispositivo e navegador
     */
    collectDeviceInfo: function() {
        this.data.device = {
            // Navegador
            userAgent: navigator.userAgent,
            language: navigator.language,
            
            // Viewport
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight,
            devicePixelRatio: window.devicePixelRatio || 1,
            
            // Hardware (quando disponível)
            hardwareConcurrency: navigator.hardwareConcurrency || 'N/A',
            maxTouchPoints: navigator.maxTouchPoints || 0,
            
            // Conexão (quando disponível)
            connection: (navigator.connection) ? {
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt,
                saveData: navigator.connection.saveData
            } : 'N/A'
        };
    },
    
    /**
     * Envia as métricas coletadas para o servidor
     * 
     * @param {object} data Dados a serem enviados (opcional, usa this.data se não fornecido)
     * @returns {Promise} Promessa com o resultado do envio
     */
    send: function(data = null) {
        // Usar dados fornecidos ou dados coletados
        const metricsData = data || this.data;
        
        // Verificar se há dados para enviar
        if (!metricsData || Object.keys(metricsData).length === 0) {
            console.warn('Taverna Impressão 3D: Nenhum dado para enviar');
            return Promise.reject(new Error('Nenhum dado para enviar'));
        }
        
        // Preparar dados para envio
        const payload = {
            pageUrl: window.location.href,
            metrics: metricsData
        };
        
        // Determinar URL base do site
        const baseUrl = (document.querySelector('meta[name="base-url"]')?.content || '') + this.config.endpoint;
        
        // Enviar dados
        return fetch(baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Taverna Impressão 3D: Métricas enviadas com sucesso', data);
            return data;
        })
        .catch(error => {
            console.error('Taverna Impressão 3D: Erro ao enviar métricas', error);
            throw error;
        });
    },
    
    /**
     * Obtém o timestamp da última coleta (de localStorage)
     * 
     * @returns {number|null} Timestamp ou null se não encontrado
     */
    getLastCollectTime: function() {
        if (this.config.useLocalStorage && window.localStorage) {
            try {
                const timestamp = localStorage.getItem('tavernaPerformanceLastCollect');
                return timestamp ? parseInt(timestamp, 10) : null;
            } catch (e) {
                console.warn('Taverna Impressão 3D: Erro ao acessar localStorage', e);
                return null;
            }
        }
        return null;
    },
    
    /**
     * Define o timestamp da última coleta (em localStorage)
     * 
     * @param {number} timestamp Timestamp a ser armazenado
     */
    setLastCollectTime: function(timestamp) {
        if (this.config.useLocalStorage && window.localStorage) {
            try {
                localStorage.setItem('tavernaPerformanceLastCollect', timestamp.toString());
            } catch (e) {
                console.warn('Taverna Impressão 3D: Erro ao acessar localStorage', e);
            }
        }
    },
    
    /**
     * Coleta e envia as métricas em uma única operação
     * 
     * @returns {Promise} Promessa com o resultado do envio
     */
    collectAndSend: function() {
        // Coletar métricas
        const data = this.collect();
        
        // Enviar para o servidor
        return this.send(data);
    }
};

// Inicializar automaticamente quando o script for carregado
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se deve inicializar automaticamente
    const autoInit = document.body.getAttribute('data-performance-auto-init') !== 'false';
    
    if (autoInit) {
        PerformanceMetrics.init();
    }
});

// Exportar para uso global
window.PerformanceMetrics = PerformanceMetrics;
