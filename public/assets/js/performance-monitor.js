/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Script para monitoramento de performance em ambiente de produção
 * Coleta métricas de desempenho de maneira não intrusiva e as envia ao servidor
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

(function() {
    'use strict';
    
    /**
     * Classe principal para monitoramento de performance em produção
     */
    class PerformanceMonitor {
        /**
         * Construtor
         * @param {Object} options Opções de configuração
         */
        constructor(options = {}) {
            // Configurações padrão
            this.config = {
                endpoint: '/api/performance-monitor/collect',
                minTimeBetweenReports: 24 * 60 * 60 * 1000, // 24 horas por padrão
                enabled: true,
                debug: false,
                collectCoreWebVitals: true,
                collectResourceTiming: true,
                collectErrorData: true,
                maxEntries: 50,
                reportAfterLoad: true
            };
            
            // Mesclar opções fornecidas
            Object.assign(this.config, options);
            
            // Verificar suporte a Performance API
            this.isSupported = window.performance && window.performance.now;
            
            if (!this.isSupported) {
                this.log('Performance API não suportada neste navegador.');
                return;
            }
            
            // Inicializar métricas
            this.metrics = {
                navigationStart: window.performance.timing?.navigationStart || 0,
                loadTime: 0,
                domComplete: 0,
                ttfb: 0,
                fcp: 0,
                lcp: 0,
                cls: 0,
                fid: 0,
                tbt: 0,
                resources: [],
                errors: []
            };
            
            // Inicializar armazenamento local
            this.storage = {
                key: 'tavernaPerformanceMonitor',
                lastReport: this.getLastReportTime()
            };
            
            // Flag para verificar se já reportamos nesta sessão
            this.hasReported = false;
            
            // Verificar se devemos coletar métricas
            if (this.shouldCollectMetrics()) {
                this.init();
            }
        }
        
        /**
         * Inicializa o monitoramento de performance
         */
        init() {
            // Registrar handlers para diferentes eventos
            if (document.readyState === 'complete') {
                this.onLoad();
            } else {
                window.addEventListener('load', () => this.onLoad());
            }
            
            // Observar Core Web Vitals se suportado
            if (this.config.collectCoreWebVitals) {
                this.observeCoreWebVitals();
            }
            
            // Observar erros se habilitado
            if (this.config.collectErrorData) {
                window.addEventListener('error', (e) => this.captureError(e));
                window.addEventListener('unhandledrejection', (e) => this.capturePromiseError(e));
            }
            
            this.log('Monitoramento de performance iniciado.');
        }
        
        /**
         * Handler para evento 'load'
         */
        onLoad() {
            // Metrics disponíveis após load
            this.collectNavigationTiming();
            this.collectPaintTiming();
            
            // Coletar informações de recursos se habilitado
            if (this.config.collectResourceTiming) {
                this.collectResourceTiming();
            }
            
            // Reportar após carga se habilitado
            if (this.config.reportAfterLoad && !this.hasReported) {
                // Aguardar um pouco para garantir que CWV sejam coletadas
                setTimeout(() => {
                    this.reportMetrics();
                }, 3000);
            }
        }
        
        /**
         * Coleta métricas de Navigation Timing API
         */
        collectNavigationTiming() {
            const perf = window.performance;
            
            if (perf.timing) {
                const timing = perf.timing;
                const navigationStart = timing.navigationStart;
                
                this.metrics.ttfb = timing.responseStart - navigationStart;
                this.metrics.domComplete = timing.domComplete - navigationStart;
                this.metrics.loadTime = timing.loadEventEnd - navigationStart;
            } else if (perf.getEntriesByType && perf.getEntriesByType('navigation').length) {
                // Navigation Timing API v2
                const navigation = perf.getEntriesByType('navigation')[0];
                
                this.metrics.ttfb = navigation.responseStart;
                this.metrics.domComplete = navigation.domComplete;
                this.metrics.loadTime = navigation.loadEventEnd;
            }
        }
        
        /**
         * Coleta métricas de Paint Timing API
         */
        collectPaintTiming() {
            if (!window.performance || !window.performance.getEntriesByType) {
                return;
            }
            
            const paintEntries = performance.getEntriesByType('paint');
            
            paintEntries.forEach(entry => {
                if (entry.name === 'first-contentful-paint') {
                    this.metrics.fcp = entry.startTime;
                }
            });
        }
        
        /**
         * Observa métricas Core Web Vitals usando PerformanceObserver
         */
        observeCoreWebVitals() {
            // Largest Contentful Paint
            if (window.PerformanceObserver && PerformanceObserver.supportedEntryTypes && PerformanceObserver.supportedEntryTypes.includes('largest-contentful-paint')) {
                const lcpObserver = new PerformanceObserver(entries => {
                    const lcpEntries = entries.getEntries();
                    if (lcpEntries.length > 0) {
                        // Usar apenas o último LCP
                        const lastEntry = lcpEntries[lcpEntries.length - 1];
                        this.metrics.lcp = lastEntry.startTime;
                    }
                });
                lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });
            }
            
            // Cumulative Layout Shift
            if (window.PerformanceObserver && PerformanceObserver.supportedEntryTypes && PerformanceObserver.supportedEntryTypes.includes('layout-shift')) {
                let cumulativeLayoutShift = 0;
                
                const clsObserver = new PerformanceObserver(entries => {
                    for (const entry of entries.getEntries()) {
                        // Apenas considerar mudanças que não são causadas por interação do usuário
                        if (!entry.hadRecentInput) {
                            cumulativeLayoutShift += entry.value;
                        }
                    }
                    
                    this.metrics.cls = cumulativeLayoutShift;
                });
                clsObserver.observe({ type: 'layout-shift', buffered: true });
            }
            
            // First Input Delay
            if (window.PerformanceObserver && PerformanceObserver.supportedEntryTypes && PerformanceObserver.supportedEntryTypes.includes('first-input')) {
                const fidObserver = new PerformanceObserver(entries => {
                    const firstInput = entries.getEntries()[0];
                    if (firstInput) {
                        this.metrics.fid = firstInput.processingStart - firstInput.startTime;
                    }
                });
                fidObserver.observe({ type: 'first-input', buffered: true });
            }
            
            // Total Blocking Time
            if (window.PerformanceObserver && PerformanceObserver.supportedEntryTypes && PerformanceObserver.supportedEntryTypes.includes('longtask')) {
                let totalBlockingTime = 0;
                
                const tbtObserver = new PerformanceObserver(entries => {
                    for (const entry of entries.getEntries()) {
                        const blockingTime = entry.duration - 50; // Threshold de 50ms
                        if (blockingTime > 0) {
                            totalBlockingTime += blockingTime;
                        }
                    }
                    
                    this.metrics.tbt = totalBlockingTime;
                });
                tbtObserver.observe({ type: 'longtask', buffered: true });
            }
        }
        
        /**
         * Coleta informações de Resource Timing API
         */
        collectResourceTiming() {
            if (!window.performance || !window.performance.getEntriesByType) {
                return;
            }
            
            const resourceEntries = performance.getEntriesByType('resource');
            const filteredEntries = resourceEntries.slice(0, this.config.maxEntries);
            
            // Estruturas de recursos comuns
            const resourceTypes = {
                script: { count: 0, size: 0, time: 0 },
                css: { count: 0, size: 0, time: 0 },
                img: { count: 0, size: 0, time: 0 },
                font: { count: 0, size: 0, time: 0 },
                other: { count: 0, size: 0, time: 0 }
            };
            
            filteredEntries.forEach(entry => {
                // Identificar tipo de recurso
                let type = 'other';
                const url = entry.name || '';
                
                if (url.match(/\.js(\?|$)/)) {
                    type = 'script';
                } else if (url.match(/\.css(\?|$)/)) {
                    type = 'css';
                } else if (url.match(/\.(png|jpg|jpeg|gif|webp|svg)(\?|$)/)) {
                    type = 'img';
                } else if (url.match(/\.(woff|woff2|ttf|otf|eot)(\?|$)/)) {
                    type = 'font';
                }
                
                // Incrementar contadores
                resourceTypes[type].count++;
                resourceTypes[type].time += entry.responseEnd;
                
                // Tentar estimar tamanho se disponível
                if (entry.transferSize && entry.transferSize > 0) {
                    resourceTypes[type].size += entry.transferSize;
                }
            });
            
            this.metrics.resources = resourceTypes;
        }
        
        /**
         * Captura erros JavaScript
         * @param {Event} event Evento de erro
         */
        captureError(event) {
            if (!this.config.collectErrorData || !event) {
                return;
            }
            
            const error = {
                message: event.message || 'Unknown error',
                source: event.filename || 'Unknown source',
                lineno: event.lineno || 0,
                colno: event.colno || 0,
                timestamp: new Date().toISOString()
            };
            
            this.metrics.errors.push(error);
            
            // Limitar número de erros
            if (this.metrics.errors.length > 10) {
                this.metrics.errors = this.metrics.errors.slice(0, 10);
            }
        }
        
        /**
         * Captura erros de Promise não tratados
         * @param {PromiseRejectionEvent} event Evento de rejeição
         */
        capturePromiseError(event) {
            if (!this.config.collectErrorData || !event) {
                return;
            }
            
            const error = {
                message: 'Unhandled Promise Rejection',
                details: event.reason ? (event.reason.message || String(event.reason)) : 'Unknown reason',
                timestamp: new Date().toISOString()
            };
            
            this.metrics.errors.push(error);
            
            // Limitar número de erros
            if (this.metrics.errors.length > 10) {
                this.metrics.errors = this.metrics.errors.slice(0, 10);
            }
        }
        
        /**
         * Reporta métricas para o servidor
         * @returns {Promise} Promessa do envio
         */
        reportMetrics() {
            if (!this.config.enabled || this.hasReported) {
                return Promise.resolve();
            }
            
            // Coletar informações básicas sobre a visita
            const data = {
                pageUrl: window.location.pathname,
                metrics: { ...this.metrics },
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                viewportWidth: window.innerWidth,
                viewportHeight: window.innerHeight,
                connection: this.getConnectionInfo()
            };
            
            // Registrar que já reportamos nesta sessão
            this.hasReported = true;
            this.updateLastReportTime();
            
            // Enviar os dados
            this.log('Enviando métricas de performance:', data);
            
            return fetch(this.config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data),
                // Usar keepalive para garantir que a requisição seja enviada mesmo se a página for fechada
                keepalive: true
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro ao enviar métricas: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                this.log('Métricas enviadas com sucesso:', result);
                return result;
            })
            .catch(error => {
                console.error('Erro ao enviar métricas de performance:', error);
                return null;
            });
        }
        
        /**
         * Verifica se devemos coletar métricas
         * @returns {boolean} True se devemos coletar métricas
         */
        shouldCollectMetrics() {
            if (!this.config.enabled || !this.isSupported) {
                return false;
            }
            
            // Verificar tempo desde o último relatório
            const lastReportTime = this.storage.lastReport;
            const now = Date.now();
            
            if (lastReportTime && (now - lastReportTime) < this.config.minTimeBetweenReports) {
                this.log('Relatório recente encontrado, pulando coleta.');
                return false;
            }
            
            return true;
        }
        
        /**
         * Obtém o tempo do último relatório de performance
         * @returns {number} Timestamp do último relatório ou 0
         */
        getLastReportTime() {
            try {
                const stored = localStorage.getItem(this.storage.key);
                if (stored) {
                    const data = JSON.parse(stored);
                    return data.lastReport || 0;
                }
            } catch (e) {
                console.error('Erro ao ler último tempo de relatório:', e);
            }
            
            return 0;
        }
        
        /**
         * Atualiza o tempo do último relatório
         */
        updateLastReportTime() {
            try {
                localStorage.setItem(this.storage.key, JSON.stringify({
                    lastReport: Date.now()
                }));
            } catch (e) {
                console.error('Erro ao atualizar tempo de relatório:', e);
            }
        }
        
        /**
         * Obtém informações de conexão, se disponíveis
         * @returns {Object} Informações de conexão
         */
        getConnectionInfo() {
            const connection = navigator.connection || 
                               navigator.mozConnection || 
                               navigator.webkitConnection;
            
            if (!connection) {
                return {
                    type: 'unknown',
                    effectiveType: 'unknown',
                    rtt: null,
                    downlink: null
                };
            }
            
            return {
                type: connection.type || 'unknown',
                effectiveType: connection.effectiveType || 'unknown',
                rtt: connection.rtt,
                downlink: connection.downlink
            };
        }
        
        /**
         * Função de log para depuração
         * @param {...any} args Argumentos para log
         */
        log(...args) {
            if (this.config.debug) {
                console.log('[PerformanceMonitor]', ...args);
            }
        }
    }
    
    // Exportar para uso global
    window.PerformanceMonitor = PerformanceMonitor;
    
    // Auto inicialização se o atributo estiver presente
    document.addEventListener('DOMContentLoaded', () => {
        if (document.body.hasAttribute('data-performance-monitor-auto-init')) {
            const autoInit = document.body.getAttribute('data-performance-monitor-auto-init') === 'true';
            
            if (autoInit) {
                window.tavernaPerformanceMonitor = new PerformanceMonitor();
            }
        }
    });
})();
