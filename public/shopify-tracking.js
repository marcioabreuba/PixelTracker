/**
 * Script de Tracking Duplo do Facebook para Shopify
 * Integração com API Laravel (server-side) + Pixel Facebook (client-side)
 */

class ShopifyFacebookTracking {
    constructor(config) {
        this.apiUrl = config.apiUrl;
        this.contentId = config.contentId || 'shopify_store';
        this.pixelId = config.pixelId || '676999668497170';
        this.external_id = this.generateExternalId();
        this.userData = {};
        this.pixelLoaded = false;
        
        this.init();
    }

    init() {
        // Carregar Pixel do Facebook
        this.loadFacebookPixel();
        
        // Inicializar tracking
        this.initializeTracking();
        
        // Configurar eventos
        this.setupEvents();
        
        // Enviar PageView inicial
        this.trackPageView();
    }

    loadFacebookPixel() {
        // Carregar o Pixel do Facebook se não estiver carregado
        if (!window.fbq) {
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');

            fbq('init', this.pixelId);
            this.pixelLoaded = true;
            console.log('Facebook Pixel carregado:', this.pixelId);
        }
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
            const button = e.target.closest('[name="add"], .btn-product-form, .product-form__cart-submit, .product-form__buttons button[type="submit"]');
            if (button) {
                this.trackAddToCart();
            }
        });

        // Event listener para iniciar checkout
        document.addEventListener('click', (e) => {
            const checkoutButton = e.target.closest('.cart__checkout-button, [name="goto_checkout"], .btn--checkout, [href*="checkout"]');
            if (checkoutButton) {
                this.trackInitiateCheckout();
            }
        });

        // Event listener para formulários de lead
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.matches('.contact-form, .newsletter-form, form[action*="contact"]')) {
                this.trackLead();
            }
        });

        // Detectar página de produto para ViewContent
        if (window.location.pathname.includes('/products/')) {
            setTimeout(() => this.trackViewContent(), 1000);
        }

        // Tracking de scroll
        this.setupScrollTracking();

        // Tracking de tempo na página
        this.setupTimeTracking();

        // Tracking de vídeos
        this.setupVideoTracking();
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
                    this.sendDualEvent(`Scroll_${threshold}`);
                }
            });
        });
    }

    setupTimeTracking() {
        setTimeout(() => {
            this.sendDualEvent('Timer_1min');
        }, 60000); // 1 minuto
    }

    setupVideoTracking() {
        document.addEventListener('DOMContentLoaded', () => {
            const videos = document.querySelectorAll('video');
            videos.forEach(video => {
                let videoEvents = {
                    25: false,
                    50: false,
                    75: false,
                    90: false
                };
                
                video.addEventListener('play', () => {
                    this.sendDualEvent('PlayVideo');
                });
                
                video.addEventListener('timeupdate', () => {
                    const percent = Math.round((video.currentTime / video.duration) * 100);
                    
                    Object.keys(videoEvents).forEach(threshold => {
                        if (percent >= parseInt(threshold) && !videoEvents[threshold]) {
                            videoEvents[threshold] = true;
                            this.sendDualEvent(`ViewVideo_${threshold}`);
                        }
                    });
                });
            });
        });
    }

    async trackPageView() {
        await this.sendDualEvent('PageView');
    }

    async trackViewContent() {
        const productData = this.getProductData();
        await this.sendDualEvent('ViewContent', productData);
    }

    async trackAddToCart() {
        const productData = this.getProductData();
        
        // Enviar para API personalizada
        try {
            const response = await fetch(`${this.apiUrl}/shopify/add-to-cart`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    external_id: this.external_id,
                    product_id: productData.content_ids?.[0] || this.getProductId(),
                    _fbc: this.userData._fbc,
                    _fbp: this.userData._fbp,
                    event_source_url: window.location.href
                })
            });

            if (response.ok) {
                console.log('AddToCart enviado para API com sucesso');
            }
        } catch (error) {
            console.error('Erro ao enviar AddToCart para API:', error);
        }

        // Enviar para Pixel do Facebook
        this.sendPixelEvent('AddToCart', productData);
    }

    async trackInitiateCheckout() {
        await this.sendDualEvent('InitiateCheckout');
    }

    async trackLead() {
        await this.sendDualEvent('Lead');
    }

    async trackPurchase(purchaseData) {
        await this.sendDualEvent('Purchase', purchaseData);
    }

    // Método principal para envio duplo (API + Pixel)
    async sendDualEvent(eventType, customData = {}) {
        // Enviar para API (server-side)
        await this.sendServerEvent(eventType, customData);
        
        // Enviar para Pixel (client-side)
        this.sendPixelEvent(eventType, customData);
    }

    // Envio para API (server-side)
    async sendServerEvent(eventType, customData = {}) {
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
                console.log(`✅ Evento ${eventType} enviado para API (server-side)`);
            }
        } catch (error) {
            console.error(`❌ Erro ao enviar evento ${eventType} para API:`, error);
        }
    }

    // Envio para Pixel do Facebook (client-side)
    sendPixelEvent(eventType, customData = {}) {
        if (typeof fbq !== 'undefined') {
            try {
                // Mapear eventos customizados para eventos padrão do Facebook
                const eventMapping = {
                    'PageView': 'PageView',
                    'ViewContent': 'ViewContent',
                    'AddToCart': 'AddToCart',
                    'InitiateCheckout': 'InitiateCheckout',
                    'Purchase': 'Purchase',
                    'Lead': 'Lead',
                    'Scroll_25': 'Scroll_25',
                    'Scroll_50': 'Scroll_50',
                    'Scroll_75': 'Scroll_75',
                    'Scroll_90': 'Scroll_90',
                    'Timer_1min': 'Timer_1min',
                    'PlayVideo': 'PlayVideo',
                    'ViewVideo_25': 'ViewVideo_25',
                    'ViewVideo_50': 'ViewVideo_50',
                    'ViewVideo_75': 'ViewVideo_75',
                    'ViewVideo_90': 'ViewVideo_90'
                };

                const fbEventName = eventMapping[eventType] || eventType;
                
                // Preparar dados para o pixel
                const pixelData = this.preparePixelData(customData);
                
                if (['PageView', 'ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase', 'Lead'].includes(fbEventName)) {
                    // Eventos padrão do Facebook
                    fbq('track', fbEventName, pixelData);
                } else {
                    // Eventos customizados
                    fbq('trackCustom', fbEventName, pixelData);
                }
                
                console.log(`✅ Evento ${eventType} enviado para Pixel (client-side)`);
            } catch (error) {
                console.error(`❌ Erro ao enviar evento ${eventType} para Pixel:`, error);
            }
        } else {
            console.warn('Facebook Pixel não carregado');
        }
    }

    preparePixelData(customData) {
        const pixelData = {};
        
        // Adicionar dados de produto se disponível
        if (customData.content_ids) {
            pixelData.content_ids = customData.content_ids;
        }
        
        if (customData.value) {
            pixelData.value = customData.value;
        }
        
        if (customData.currency) {
            pixelData.currency = customData.currency;
        }
        
        // Adicionar external_id para melhor matching
        if (this.external_id) {
            pixelData.external_id = this.external_id;
        }
        
        return pixelData;
    }

    getProductData() {
        const productData = {};
        
        // Tentar obter dados do produto
        const productId = this.getProductId();
        if (productId) {
            productData.content_ids = [productId];
        }
        
        // Tentar obter preço
        const priceElement = document.querySelector('.price, .product-price, [data-price]');
        if (priceElement) {
            const priceText = priceElement.textContent || priceElement.dataset.price;
            const price = parseFloat(priceText.replace(/[^\d.,]/g, '').replace(',', '.'));
            if (!isNaN(price)) {
                productData.value = price;
                productData.currency = 'BRL';
            }
        }
        
        return productData;
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

    // Método público para tracking manual de Purchase
    trackManualPurchase(orderData) {
        const purchaseData = {
            value: orderData.total_price,
            currency: orderData.currency || 'BRL',
            content_ids: orderData.line_items?.map(item => item.variant_id) || [],
            order_id: orderData.order_id
        };
        
        this.trackPurchase(purchaseData);
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
    
    console.log('🚀 Shopify Facebook Tracking Duplo inicializado!');
    console.log('📊 Server-side: API Laravel');
    console.log('🌐 Client-side: Facebook Pixel');
};

// Auto-inicializar se configuração estiver disponível
if (window.shopifyFBConfig) {
    window.initShopifyFBTracking(window.shopifyFBConfig);
} 