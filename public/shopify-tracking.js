/**
 * Script de Tracking Duplo do Facebook para Shopify
 * Baseado no padrão otimizado para melhor match quality
 * Integração com API Laravel (server-side) + Pixel Facebook (client-side)
 */

// Função para obter cookies
function getCookie(name) {
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
        const [key, value] = cookie.trim().split('=');
        if (key === name) {
            return value;
        }
    }
    return null;
}

// Função para gerar UUID
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0,
              v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// Função principal para enviar eventos
async function sendEvent(eventType, data = {}) {
    const contentId = window.shopifyFBConfig?.contentId || 'shopify_store';
    const apiUrl = window.shopifyFBConfig?.apiUrl || 'https://traqueamentophp.onrender.com';
    const event_source_url = window.location.href;
    const _fbc = getCookie('_fbc') || '';
    const _fbp = getCookie('_fbp') || '';
    const userId = getCookie('userId') || '';
    let fn = getCookie("fn");
    let ln = getCookie("ln");
    let em = getCookie("em");
    let ph = getCookie("ph");

    // Coleta automática de dados de formulários para eventos específicos
    if (eventType === "Lead" || eventType === "InitiateCheckout") {
        // Detectar formulários Shopify automaticamente
        const nameField = document.querySelector('[name="contact[first_name]"], [name="customer[first_name]"], [name="first_name"], [name="nome"], [name="NOME"]');
        const lastNameField = document.querySelector('[name="contact[last_name]"], [name="customer[last_name]"], [name="last_name"], [name="sobrenome"], [name="SOBRENOME"]');
        const emailField = document.querySelector('[name="contact[email]"], [name="customer[email]"], [name="email"], [name="EMAIL"]');
        const phoneField = document.querySelector('[name="contact[phone]"], [name="customer[phone]"], [name="phone"], [name="telefone"], [name="TELEFONE"]');

        if (nameField && nameField.value) {
            fn = nameField.value.trim().toLowerCase();
        }
        
        if (lastNameField && lastNameField.value) {
            ln = lastNameField.value.trim().toLowerCase();
        } else if (nameField && nameField.value) {
            // Se não há campo de sobrenome, dividir o nome completo
            const nameParts = nameField.value.trim().split(' ');
            fn = nameParts[0].toLowerCase();
            if (nameParts.length > 1) {
                ln = nameParts[nameParts.length - 1].toLowerCase();
            }
        }

        if (emailField && emailField.value) {
            em = emailField.value.trim().toLowerCase();
        }

        if (phoneField && phoneField.value) {
            ph = phoneField.value.replace(/\s|-|\(|\)/g, '');
            // Adicionar código do país se não tiver
            if (ph && !ph.startsWith('55')) {
                ph = '55' + ph;
            }
        }

        // Armazenar os dados nos cookies
        const date = new Date();
        date.setFullYear(date.getFullYear() + 1); // Expira em 1 ano
        if (fn) document.cookie = `fn=${encodeURIComponent(fn)}; expires=${date.toUTCString()}; path=/`;
        if (ln) document.cookie = `ln=${encodeURIComponent(ln)}; expires=${date.toUTCString()}; path=/`;
        if (em) document.cookie = `em=${em}; expires=${date.toUTCString()}; path=/`;
        if (ph) document.cookie = `ph=${encodeURIComponent(ph)}; expires=${date.toUTCString()}; path=/`;
    }

    try {
        // Preparar parâmetros otimizados para ambos (API e Pixel)
        const optimizedParams = {
            app: 'Pixel Tracker',
            language: 'pt-BR',
            referrer_url: document.referrer || ''
        };

        // Adicionar parâmetros específicos por tipo de evento
        if (eventType === 'ViewContent' || eventType === 'AddToCart') {
            optimizedParams.content_type = 'product';
            optimizedParams.content_category = getProductCategory();
            optimizedParams.content_name = getProductName();
            optimizedParams.num_items = 1;
        } else if (eventType === 'PageView') {
            optimizedParams.content_type = getPageType();
            optimizedParams.content_category = getPageCategory();
            optimizedParams.content_name = getPageName();
            optimizedParams.num_items = 1;
        } else if (eventType === 'InitiateCheckout') {
            optimizedParams.content_type = 'checkout';
            optimizedParams.content_category = ['Checkout'];
            optimizedParams.content_name = ['Checkout Process'];
            optimizedParams.num_items = getCartItemsCount();
        } else if (eventType === 'Lead') {
            optimizedParams.content_type = 'lead_form';
            optimizedParams.content_category = ['Lead Generation'];
            optimizedParams.content_name = ['Contact Form'];
            optimizedParams.num_items = 1;
        }

        // Preparar payload para API (incluindo parâmetros otimizados)
        const payload = { 
            contentId, 
            eventType, 
            event_source_url, 
            _fbc, 
            _fbp, 
            userId,
            ...data,
            ...optimizedParams
        };
        
        // Adicionar dados pessoais se disponíveis
        if (fn) payload.fn = fn;
        if (ln) payload.ln = ln;
        if (em) payload.em = em;
        if (ph) payload.ph = ph;

        // Enviar para API (server-side)
        const response = await fetch(`${apiUrl}/events/send`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });

        const responseData = await response.json();
        console.log(`✅ Evento ${eventType} enviado para API (server-side)`);

        // Se for evento Init, apenas retornar os dados
        if (eventType === "Init") {
            return responseData;
        }

        // Enviar para Facebook Pixel (client-side) com eventID compartilhado
        if (typeof fbq !== 'undefined' && responseData.eventID) {
            const customEvents = ['Scroll_25', 'Scroll_50', 'Scroll_75', 'Scroll_90', 'Timer_1min', 'PlayVideo', 'ViewVideo_25', 'ViewVideo_50', 'ViewVideo_75', 'ViewVideo_90'];
            
            // Preparar dados para o pixel (reutilizando parâmetros otimizados)
            const pixelData = {
                ...optimizedParams
            };
            
            // Adicionar dados específicos do pixel
            if (data.content_ids) pixelData.content_ids = data.content_ids;
            if (data.value) pixelData.value = data.value;
            if (data.currency) pixelData.currency = data.currency;
            
            // Adicionar content_ids padrão se não fornecido
            if (!pixelData.content_ids) {
                pixelData.content_ids = [contentId];
            }

            if (customEvents.includes(eventType)) {
                fbq('trackCustom', eventType, pixelData, { eventID: responseData.eventID });
            } else {
                fbq('track', eventType, pixelData, { eventID: responseData.eventID });
            }
            
            console.log(`✅ Evento ${eventType} enviado para Pixel (client-side) com eventID: ${responseData.eventID}`);
        }

        return responseData;
    } catch (error) {
        console.error(`❌ Erro ao rastrear evento ${eventType}:`, error);
    }
}

// Função para inicializar o pixel
async function initPixel() {
    let userId = getCookie("userId");
    let fn = getCookie("fn");
    let ln = getCookie("ln");
    let em = getCookie("em");
    let ph = getCookie("ph");

    // Gerar userId se não existir
    if (!userId) {
        userId = generateUUID();
        const date = new Date();
        date.setFullYear(date.getFullYear() + 1);
        document.cookie = `userId=${userId}; expires=${date.toUTCString()}; path=/`;
    }

    // Obter dados de inicialização da API
    const init = await sendEvent('Init') || {};

    // Preparar dados do usuário para o Facebook Pixel
    let userData = {
        "ct": init.ct || '',
        "st": init.st || '',
        "zp": init.zp || '',
        "country": init.country || '',
        "client_ip_address": init.client_ip_address || '',
        "client_user_agent": init.client_user_agent || '',
        "fbc": init.fbc || '',
        "fbp": init.fbp || '',
        "external_id": userId || ''
    };

    // Adicionar dados pessoais se disponíveis
    if (fn) userData.fn = fn;
    if (ln) userData.ln = ln;
    if (em) userData.em = em;
    if (ph) userData.ph = ph;

    // Inicializar o Facebook Pixel com os dados
    const pixelId = window.shopifyFBConfig?.pixelId || '676999668497170';
    fbq('init', pixelId, userData);
    console.log('🚀 Facebook Pixel inicializado com dados:', userData);

    // Enviar PageView inicial
    sendEvent('PageView');
}

// Inicialização do Facebook Pixel
!function(f,b,e,v,n,t,s) {
    if(f.fbq) return;
    n=f.fbq=function(){n.callMethod ?
    n.callMethod.apply(n,arguments) : n.queue.push(arguments)};
    if(!f._fbq) f._fbq = n;
    n.push=n;
    n.loaded = !0;
    n.version='2.0';
    n.queue = [];
    t=b.createElement(e); 
    t.async = !0;
    t.src = v;
    s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)
}(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

// Configurar eventos automáticos do Shopify
function setupShopifyEvents() {
    // Detectar AddToCart
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[name="add"], .btn-product-form, .product-form__cart-submit, .product-form__buttons button[type="submit"]');
        if (button) {
            setTimeout(() => {
                const productData = getShopifyProductData();
                sendEvent('AddToCart', productData);
            }, 100);
        }
    });

    // Detectar InitiateCheckout
    document.addEventListener('click', (e) => {
        const checkoutButton = e.target.closest('.cart__checkout-button, [name="goto_checkout"], .btn--checkout, [href*="checkout"]');
        if (checkoutButton) {
            sendEvent('InitiateCheckout');
        }
    });

    // Detectar formulários de contato/newsletter (Lead)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.matches('.contact-form, .newsletter-form, form[action*="contact"], form[action*="newsletter"]')) {
            setTimeout(() => sendEvent('Lead'), 100);
        }
    });

    // ViewContent para páginas de produto
    if (window.location.pathname.includes('/products/')) {
        setTimeout(() => {
            const productData = getShopifyProductData();
            sendEvent('ViewContent', productData);
        }, 1000);
    }

    // Tracking de scroll
    setupScrollTracking();

    // Tracking de tempo
    setupTimeTracking();

    // Tracking de vídeos
    setupVideoTracking();
}

// Função para obter dados do produto Shopify
function getShopifyProductData() {
    const productData = {};
    
    // Tentar obter ID do produto
    const productForm = document.querySelector('form[action*="/cart/add"]');
    if (productForm) {
        const variantInput = productForm.querySelector('[name="id"]');
        if (variantInput) {
            productData.content_ids = [variantInput.value];
        }
    }

    // Tentar obter preço
    const priceElement = document.querySelector('.price, .product-price, [data-price], .money');
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

// Funções auxiliares para parâmetros otimizados
function getProductCategory() {
    // Tentar obter categoria do produto de várias fontes
    const breadcrumb = document.querySelector('.breadcrumb, .breadcrumbs');
    if (breadcrumb) {
        const links = breadcrumb.querySelectorAll('a');
        if (links.length > 1) {
            return [links[links.length - 2].textContent.trim()];
        }
    }
    
    // Tentar obter do meta tag
    const categoryMeta = document.querySelector('meta[property="product:category"]');
    if (categoryMeta) {
        return [categoryMeta.content];
    }
    
    // Tentar obter da URL
    const pathParts = window.location.pathname.split('/');
    if (pathParts.includes('collections') && pathParts.length > 2) {
        const collectionIndex = pathParts.indexOf('collections');
        if (pathParts[collectionIndex + 1]) {
            return [pathParts[collectionIndex + 1].replace(/-/g, ' ')];
        }
    }
    
    return ['General'];
}

function getProductName() {
    // Tentar obter nome do produto
    const productTitle = document.querySelector('h1.product-title, .product-title h1, h1[class*="product"], .product-meta h1');
    if (productTitle) {
        return [productTitle.textContent.trim()];
    }
    
    // Tentar obter do meta tag
    const titleMeta = document.querySelector('meta[property="og:title"]');
    if (titleMeta) {
        return [titleMeta.content];
    }
    
    return [document.title];
}

function getPageType() {
    const path = window.location.pathname;
    
    if (path.includes('/products/')) return 'product';
    if (path.includes('/collections/')) return 'category';
    if (path.includes('/cart')) return 'cart';
    if (path.includes('/checkout')) return 'checkout';
    if (path.includes('/contact')) return 'contact';
    if (path === '/' || path === '') return 'home';
    
    return 'page';
}

function getPageCategory() {
    const pageType = getPageType();
    
    switch (pageType) {
        case 'product': return ['Product'];
        case 'category': return ['Category'];
        case 'cart': return ['Cart'];
        case 'checkout': return ['Checkout'];
        case 'contact': return ['Contact'];
        case 'home': return ['Home'];
        default: return ['Page'];
    }
}

function getPageName() {
    const pageType = getPageType();
    
    if (pageType === 'product') {
        return getProductName();
    }
    
    if (pageType === 'category') {
        const pathParts = window.location.pathname.split('/');
        const collectionIndex = pathParts.indexOf('collections');
        if (collectionIndex !== -1 && pathParts[collectionIndex + 1]) {
            return [pathParts[collectionIndex + 1].replace(/-/g, ' ')];
        }
    }
    
    if (pageType === 'home') {
        return ['Home Page'];
    }
    
    return [document.title];
}

function getCartItemsCount() {
    // Tentar obter quantidade de itens no carrinho
    const cartCount = document.querySelector('.cart-count, [data-cart-count], .cart-item-count');
    if (cartCount) {
        const count = parseInt(cartCount.textContent || cartCount.dataset.cartCount);
        if (!isNaN(count)) return count;
    }
    
    // Tentar contar itens no carrinho
    const cartItems = document.querySelectorAll('.cart-item, [data-cart-item]');
    if (cartItems.length > 0) {
        return cartItems.length;
    }
    
    return 1; // Default
}

// Configurar tracking de scroll
function setupScrollTracking() {
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
                sendEvent(`Scroll_${threshold}`);
            }
        });
    });
}

// Configurar tracking de tempo
function setupTimeTracking() {
    setTimeout(() => {
        sendEvent('Timer_1min');
    }, 60000); // 1 minuto
}

// Configurar tracking de vídeos
function setupVideoTracking() {
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
                sendEvent('PlayVideo');
            });
            
            video.addEventListener('timeupdate', () => {
                const percent = Math.round((video.currentTime / video.duration) * 100);
                
                Object.keys(videoEvents).forEach(threshold => {
                    if (percent >= parseInt(threshold) && !videoEvents[threshold]) {
                        videoEvents[threshold] = true;
                        sendEvent(`ViewVideo_${threshold}`);
                    }
                });
            });
        });
    });
}

// Função pública para tracking manual de Purchase
window.trackShopifyPurchase = function(orderData) {
    const purchaseData = {
        value: orderData.total_price,
        currency: orderData.currency || 'BRL',
        content_ids: orderData.line_items?.map(item => item.variant_id) || [],
        order_id: orderData.order_id
    };
    
    sendEvent('Purchase', purchaseData);
};

// Inicialização automática
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se a configuração está disponível
    if (window.shopifyFBConfig) {
        console.log('🚀 Iniciando Shopify Facebook Tracking Duplo...');
        
        // Inicializar pixel
        initPixel();
        
        // Configurar eventos
        setupShopifyEvents();
        
        console.log('✅ Shopify Facebook Tracking Duplo inicializado!');
        console.log('📊 Server-side: API Laravel');
        console.log('🌐 Client-side: Facebook Pixel');
        console.log('🎯 Match Quality: Otimizado com dados pessoais');
    } else {
        console.warn('⚠️ shopifyFBConfig não encontrado. Configure window.shopifyFBConfig antes de carregar este script.');
    }
});

// Expor funções globalmente para uso manual
window.sendEvent = sendEvent;
window.initPixel = initPixel;