/**
 * MercadoPago Integration for Taverna da Impressão 3D
 *
 * This module provides secure integration with MercadoPago API for payment processing.
 * It follows all security best practices and handles token generation and error handling.
 *
 * @version 1.0.0
 * @author Taverna da Impressão
 */

/**
 * MercadoPago Integration Module
 * Uses IIFE pattern to encapsulate functionality and prevent global scope pollution
 */
const MercadoPagoIntegration = (function() {
    // Private module variables
    let mp = null;
    let cardForm = null;
    let config = {};
    let isInitialized = false;

    /**
     * Initialize MercadoPago SDK with public key
     * @param {Object} options Configuration options
     * @param {string} options.publicKey MercadoPago public key
     * @param {string} options.formId Payment form element ID
     * @param {string} options.cardTokenId Hidden input for card token
     * @param {string} options.cardBrandId Hidden input for card brand
     * @param {string} options.submitButtonId Submit button ID
     * @param {Function} options.onSuccessCallback Callback after successful tokenization
     * @param {Function} options.onErrorCallback Callback after tokenization error
     * @param {boolean} options.testMode Whether to enable test mode
     * @returns {Promise} Initialization promise
     */
    function initialize(options) {
        return new Promise((resolve, reject) => {
            // Validate required parameters
            if (!options.publicKey) {
                reject(new Error('MercadoPago public key is required'));
                return;
            }

            // Store configuration
            config = {
                formId: options.formId || 'payment-form',
                cardTokenId: options.cardTokenId || 'card_token',
                cardBrandId: options.cardBrandId || 'card_brand',
                submitButtonId: options.submitButtonId || 'pay-button',
                onSuccessCallback: options.onSuccessCallback || function() {},
                onErrorCallback: options.onErrorCallback || function(error) {
                    console.error('MercadoPago error:', error);
                },
                testMode: options.testMode || false
            };

            // Load MercadoPago script
            loadScript('https://sdk.mercadopago.com/js/v2')
                .then(() => {
                    if (!window.MercadoPago) {
                        reject(new Error('MercadoPago SDK failed to load'));
                        return;
                    }

                    try {
                        // Initialize SDK with public key
                        mp = new window.MercadoPago(options.publicKey, {
                            locale: 'pt-BR',
                            advancedFraudPrevention: true
                        });

                        isInitialized = true;
                        resolve(mp);
                    } catch (error) {
                        reject(error);
                    }
                })
                .catch(error => {
                    reject(new Error(`Failed to load MercadoPago SDK: ${error.message}`));
                });
        });
    }

    /**
     * Load external script
     * @param {string} src Script URL
     * @returns {Promise} Script loading promise
     */
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            // Check if script is already loaded
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.crossOrigin = 'anonymous';
            script.integrity = 'sha384-GX/8/KgvPgQ2sRuYxXoiBfZ2LtSY7nPgmnmSgQ1U+/OfMP/4JH5r/FL7nrj/mu1PkmnmSgQ1U';

            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load script'));

            document.head.appendChild(script);
        });
    }

    /**
     * Setup credit card form with secure tokenization
     * @returns {Promise} Setup promise
     */
    function setupCardForm() {
        return new Promise((resolve, reject) => {
            if (!isInitialized) {
                reject(new Error('MercadoPago SDK is not initialized. Call initialize() first.'));
                return;
            }

            try {
                const form = document.getElementById(config.formId);
                const cardTokenInput = document.getElementById(config.cardTokenId);
                const cardBrandInput = document.getElementById(config.cardBrandId);
                const submitButton = document.getElementById(config.submitButtonId);

                if (!form || !cardTokenInput || !submitButton) {
                    reject(new Error('Required form elements not found'));
                    return;
                }

                // Handle form submission
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    // Disable submit button
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...';

                    try {
                        // Collect form data
                        const cardData = collectCardData();
                        
                        // Create card token
                        const response = await mp.createCardToken(cardData);
                        
                        if (response.id) {
                            // Store token
                            cardTokenInput.value = response.id;
                            
                            // Store card brand
                            if (cardBrandInput && response.first_six_digits) {
                                const cardBrand = detectCardBrand(response.first_six_digits);
                                cardBrandInput.value = cardBrand;
                            }
                            
                            // Success callback
                            config.onSuccessCallback(response);
                            
                            // Submit form
                            form.submit();
                        } else {
                            throw new Error('Unable to create card token');
                        }
                    } catch (error) {
                        // Enable submit button
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Pagar';
                        
                        // Error callback
                        config.onErrorCallback(error);
                    }
                });

                resolve();
            } catch (error) {
                reject(error);
            }
        });
    }

    /**
     * Collect card data from form fields
     * @returns {Object} Card data object
     */
    function collectCardData() {
        // Get card number
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        
        // Get cardholder name
        const cardholderName = document.getElementById('card_holder').value;
        
        // Get expiry date
        const expiryValue = document.getElementById('card_expiry').value.split('/');
        const expiryMonth = expiryValue[0];
        const expiryYear = '20' + expiryValue[1];
        
        // Get security code
        const securityCode = document.getElementById('card_cvv').value;
        
        // Create identification object
        const identification = {
            type: 'CPF',
            number: '00000000000' // Placeholder (real value will be provided through backend)
        };
        
        // Return card data
        return {
            card_number: cardNumber,
            cardholder: {
                name: cardholderName,
                identification: identification
            },
            expiration_month: expiryMonth,
            expiration_year: expiryYear,
            security_code: securityCode
        };
    }

    /**
     * Detect card brand from first six digits
     * @param {string} firstSixDigits First six digits of card number
     * @returns {string} Card brand identifier
     */
    function detectCardBrand(firstSixDigits) {
        // Convert to string and get first digit
        const firstDigit = String(firstSixDigits).charAt(0);
        
        // Basic brand detection
        if (firstDigit === '4') {
            return 'visa';
        } else if (['51', '52', '53', '54', '55'].includes(firstSixDigits.substring(0, 2))) {
            return 'master';
        } else if (['34', '37'].includes(firstSixDigits.substring(0, 2))) {
            return 'amex';
        } else if (firstDigit === '6') {
            return 'elo';
        } else if (['60', '65'].includes(firstSixDigits.substring(0, 2))) {
            return 'hipercard';
        }
        
        return 'credit_card';
    }

    /**
     * Get card installment options
     * @param {number} amount Total payment amount
     * @param {string} bin First 6 digits of card number (BIN)
     * @returns {Promise<Array>} Installment options
     */
    function getInstallments(amount, bin) {
        return new Promise((resolve, reject) => {
            if (!isInitialized) {
                reject(new Error('MercadoPago SDK is not initialized. Call initialize() first.'));
                return;
            }

            mp.getInstallments({
                amount: String(amount),
                bin: bin
            })
            .then(response => {
                resolve(response[0].payer_costs);
            })
            .catch(error => {
                reject(error);
            });
        });
    }

    /**
     * Update installment options in select element
     * @param {string} selectId ID of select element
     * @param {Array} installments Installment options
     * @param {string} currencySymbol Currency symbol for display
     */
    function updateInstallmentOptions(selectId, installments, currencySymbol = 'R$') {
        const select = document.getElementById(selectId);
        
        if (!select) {
            console.error(`Select element with ID '${selectId}' not found`);
            return;
        }
        
        // Clear current options
        select.innerHTML = '';
        
        // Add new options
        installments.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.installments;
            
            const installmentAmount = option.installment_amount.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            const hasInterest = option.installment_rate > 0;
            const interestText = hasInterest ? 'com juros' : 'sem juros';
            
            optionElement.textContent = `${option.installments}x de ${currencySymbol} ${installmentAmount} ${interestText}`;
            
            select.appendChild(optionElement);
        });
    }

    // Public API
    return {
        initialize,
        setupCardForm,
        getInstallments,
        updateInstallmentOptions
    };
})();

// Export for use in CommonJS environment if needed
if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
    module.exports = MercadoPagoIntegration;
}
