/**
 * Script de Tracking do Facebook para Shopify
 * Integração com a API Laravel para Facebook Conversions API
 */

class ShopifyFacebookTracking {
    constructor(config) {
        this.apiUrl = config.apiUrl;
        this.contentId = config.contentId || 'shopify_store';
        this.external_id = this.generateExternalId();
        this.userData = {};
        
        this.init();
    }

    init() {
        // Inicializar tracking
        this.initializeTracking();
        
        // Configurar eventos
        this.setupEvents();
        
        // Enviar PageView inicial
        this.trackPageView();
    }

    generateExternalId() {
        // Tentar obter external_id de várias fontes
        const urlParams = new URLSearchParams(window.location.search);
        let externalId = urlParams.get('external_id') || 
                        urlParams.get('fbclid') || 
                        localStorage.getItem('fb_external_id');
        
        if (!externalId) {
            externalId = 'shopify_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('fb_external_id', externalId);
        }
        
        return externalId;
    }

    async initializeTracking() {
        try {
            // Coletar dados do usuário
            this.userData = {
                external_id: this.external_id,
                _fbc: this.getFbc(),
                _fbp: this.getFbp(),
                event_source_url: window.location.href,
                user_agent: navigator.userAgent,
                ip_address: await this.getClientIP()
            };

            // Enviar evento de inicialização para obter dados processados
            const response = await fetch(`${this.apiUrl}/events/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    eventType: 'Init',
                    contentId: this.contentId,
                    ...this.userData
                })
            });

            if (response.ok) {
                const data = await response.json();
                // Atualizar dados com informações processadas pela API
                this.userData = { ...this.userData, ...data };
                console.log('Shopify FB Tracking inicializado:', data);
            }
        } catch (error) {
            console.error('Erro ao inicializar tracking:', error);
        }
    }

    setupEvents() {
        // Event listener para adicionar ao carrinho
        document.addEventListener('click', (e) => {
            const button = e.target.closest('[name="add"], .btn-product-form, .product-form__cart-submit');
            if (button) {
                this.trackAddToCart();
            }
        });

        // Event listener para iniciar checkout
        document.addEventListener('click', (e) => {
            const checkoutButton = e.target.closest('.cart__checkout-button, [name="goto_checkout"]');
            if (checkoutButton) {
                this.trackInitiateCheckout();
            }
        });

        // Tracking de scroll
        this.setupScrollTracking();

        // Tracking de tempo na página
        this.setupTimeTracking();
    }

    setupScrollTracking() {
        let scrollTracker = {
            25: false,
            50: false,
            75: false,
            90: false
        };

        window.addEventListener('scroll', () => {
            const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            
            Object.keys(scrollTracker).forEach(threshold => {
                if (scrollPercent >= threshold && !scrollTracker[threshold]) {
                    scrollTracker[threshold] = true;
                    this.sendEvent(`Scroll_${threshold}`);
                }
            });
        });
    }

    setupTimeTracking() {
        setTimeout(() => {
            this.sendEvent('Timer_1min');
        }, 60000); // 1 minuto
    }

    async trackPageView() {
        await this.sendEvent('PageView');
    }

    async trackAddToCart() {
        const productId = this.getProductId();
        
        try {
            const response = await fetch(`${this.apiUrl}/shopify/add-to-cart`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    external_id: this.external_id,
                    product_id: productId,
                    _fbc: this.userData._fbc,
                    _fbp: this.userData._fbp,
                    event_source_url: window.location.href
                })
            });

            if (response.ok) {
                console.log('AddToCart enviado com sucesso');
            }
        } catch (error) {
            console.error('Erro ao enviar AddToCart:', error);
        }
    }

    async trackInitiateCheckout() {
        await this.sendEvent('InitiateCheckout');
    }

    async sendEvent(eventType, customData = {}) {
        try {
            const eventData = {
                eventType: eventType,
                contentId: this.contentId,
                event_source_url: window.location.href,
                ...this.userData,
                ...customData
            };

            const response = await fetch(`${this.apiUrl}/events/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(eventData)
            });

            if (response.ok) {
                console.log(`Evento ${eventType} enviado com sucesso`);
            }
        } catch (error) {
            console.error(`Erro ao enviar evento ${eventType}:`, error);
        }
    }

    getProductId() {
        // Tentar extrair ID do produto de várias fontes
        const productForm = document.querySelector('form[action*="/cart/add"]');
        if (productForm) {
            const variantInput = productForm.querySelector('[name="id"]');
            if (variantInput) {
                return variantInput.value;
            }
        }

        // Tentar extrair do meta
        const productMeta = document.querySelector('meta[property="product:retailer_item_id"]');
        if (productMeta) {
            return productMeta.content;
        }

        // Tentar extrair da URL
        const pathMatch = window.location.pathname.match(/\/products\/([^\/]+)/);
        if (pathMatch) {
            return pathMatch[1];
        }

        return 'unknown';
    }

    getFbc() {
        return this.getCookie('_fbc') || null;
    }

    getFbp() {
        return this.getCookie('_fbp') || null;
    }

    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    async getClientIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            return data.ip;
        } catch (error) {
            return null;
        }
    }

    // Método para coletar dados do usuário no checkout
    collectCheckoutData(customerData) {
        if (customerData.email) {
            this.userData.em = customerData.email.toLowerCase();
        }
        if (customerData.firstName) {
            this.userData.fn = customerData.firstName.toLowerCase();
        }
        if (customerData.lastName) {
            this.userData.ln = customerData.lastName.toLowerCase();
        }
        if (customerData.phone) {
            this.userData.ph = customerData.phone.replace(/\D/g, '');
        }

        // Salvar no localStorage para usar nos webhooks
        localStorage.setItem('fb_user_data', JSON.stringify(this.userData));
    }
}

// Função para inicializar o tracking
window.initShopifyFBTracking = function(config) {
    if (!config.apiUrl) {
        console.error('API URL é obrigatória para o tracking do Facebook');
        return;
    }
    
    window.shopifyFBTracking = new ShopifyFacebookTracking(config);
    
    // Adicionar external_id como atributo customizado nos formulários
    const forms = document.querySelectorAll('form[action*="/cart/add"]');
    forms.forEach(form => {
        if (!form.querySelector('[name="properties[_external_id]"]')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'properties[_external_id]';
            input.value = window.shopifyFBTracking.external_id;
            form.appendChild(input);
        }
    });
};

// Auto-inicializar se configuração estiver disponível
if (window.shopifyFBConfig) {
    window.initShopifyFBTracking(window.shopifyFBConfig);
}