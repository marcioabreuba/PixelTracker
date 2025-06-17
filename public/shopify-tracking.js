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
        // Obter dados do produto/página atual para todos os eventos
        const currentPageData = getCurrentPageData();
        
        // Preparar parâmetros otimizados para ambos (API e Pixel) - TODOS OS EVENTOS
        const optimizedParams = {
            app: 'Pixel Tracker',
            language: 'pt-BR',
            referrer_url: document.referrer || '',
            content_type: currentPageData.content_type,
            content_category: currentPageData.content_category,
            content_name: currentPageData.content_name,
            num_items: currentPageData.num_items
        };

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
            
            // OTIMIZAÇÃO PARA CATÁLOGO: Usar IDs reais dos produtos
            if (!pixelData.content_ids) {
                // Tentar obter content_ids reais dos produtos
                const realProductIds = getRealProductIds();
                if (realProductIds.length > 0) {
                    pixelData.content_ids = realProductIds;
                } else {
                    pixelData.content_ids = [contentId]; // Fallback
                }
            }

            if (customEvents.includes(eventType)) {
                fbq('trackCustom', eventType, pixelData, { eventID: responseData.eventID });
            } else {
                fbq('track', eventType, pixelData, { eventID: responseData.eventID });
            }
            
            console.log(`✅ Evento ${eventType} enviado para Pixel (client-side) com eventID: ${responseData.eventID}`);
            console.log(`📦 Content IDs: ${JSON.stringify(pixelData.content_ids)}`);
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
    // Detectar e enviar eventos automaticamente baseado na página atual
    detectAndSendPageEvents();
    
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

    // Detectar ViewCart (quando vai para página do carrinho)
    document.addEventListener('click', (e) => {
        const cartButton = e.target.closest('[href*="/cart"], .cart-link, .header-cart');
        if (cartButton) {
            setTimeout(() => {
                sendEvent('ViewCart');
            }, 500);
        }
    });

    // Detectar Search (formulários de busca)
    document.addEventListener('submit', (e) => {
        const searchForm = e.target;
        if (searchForm.matches('form[action*="/search"], .search-form, form[role="search"]')) {
            setTimeout(() => {
                const searchQuery = searchForm.querySelector('input[name="q"], input[name="query"], input[type="search"]');
                const searchData = searchQuery ? { search_string: searchQuery.value } : {};
                sendEvent('Search', searchData);
            }, 100);
        }
    });

    // Detectar formulários de contato/newsletter (Lead)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.matches('.contact-form, .newsletter-form, form[action*="contact"], form[action*="newsletter"]')) {
            setTimeout(() => sendEvent('Lead'), 100);
        }
    });

    // Detectar InitiateCheckout
    document.addEventListener('click', (e) => {
        const checkoutButton = e.target.closest('.cart__checkout-button, [name="goto_checkout"], .btn--checkout, [href*="checkout"]');
        if (checkoutButton) {
            sendEvent('InitiateCheckout');
        }
    });

    // Tracking de scroll, tempo e vídeos
    setupScrollTracking();
    setupTimeTracking();
    setupVideoTracking();
}

// Função para detectar e enviar eventos baseados na página atual
function detectAndSendPageEvents() {
    const path = window.location.pathname;
    const search = window.location.search;
    
    // Aguardar carregamento completo da página
    setTimeout(() => {
        // 1. PageView sempre é enviado primeiro (já enviado no initPixel)
        
        // 2. ViewHome - Página inicial
        if (path === '/' || path === '' || path === '/index' || path.includes('/pages/home')) {
            sendEvent('ViewHome');
        }
        
        // 3. ViewList - Páginas de coleção/categoria
        else if (path.includes('/collections/') && !path.includes('/products/')) {
            sendEvent('ViewList');
        }
        
        // 4. ViewContent - Páginas de produto
        else if (path.includes('/products/')) {
            const productData = getShopifyProductData();
            sendEvent('ViewContent', productData);
        }
        
        // 5. ViewCart - Página do carrinho
        else if (path.includes('/cart')) {
            sendEvent('ViewCart');
        }
        
        // 6. Search - Página de resultados de busca
        else if (path.includes('/search') || search.includes('q=') || search.includes('query=')) {
            const urlParams = new URLSearchParams(search);
            const searchQuery = urlParams.get('q') || urlParams.get('query') || '';
            const searchData = searchQuery ? { search_string: searchQuery } : {};
            sendEvent('Search', searchData);
        }
        
    }, 1000); // Aguardar 1 segundo para garantir que a página carregou
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

// Função para obter dados da página atual (para todos os eventos)
function getCurrentPageData() {
    const pageType = getPageType();
    const path = window.location.pathname;
    const search = window.location.search;
    
    if (pageType === 'product') {
        return {
            content_type: 'product',
            content_category: getProductCategory(),
            content_name: getProductName(),
            num_items: 1
        };
    } else if (pageType === 'home' || path === '/' || path === '') {
        return {
            content_type: 'home',
            content_category: ['Home'],
            content_name: ['Home Page'],
            num_items: 1
        };
    } else if (path.includes('/collections/') && !path.includes('/products/')) {
        // ViewList - Páginas de coleção
        const collectionName = getCollectionName();
        return {
            content_type: 'product_group',
            content_category: ['Collection', collectionName],
            content_name: [collectionName + ' Collection'],
            num_items: getCollectionProductsCount()
        };
    } else if (path.includes('/cart')) {
        // ViewCart - Página do carrinho
        return {
            content_type: 'cart',
            content_category: ['Cart'],
            content_name: ['Shopping Cart'],
            num_items: getCartItemsCount()
        };
    } else if (path.includes('/search') || search.includes('q=')) {
        // Search - Página de busca
        const urlParams = new URLSearchParams(search);
        const searchQuery = urlParams.get('q') || urlParams.get('query') || 'search';
        return {
            content_type: 'search_results',
            content_category: ['Search'],
            content_name: ['Search: ' + searchQuery],
            num_items: getSearchResultsCount()
        };
    } else if (pageType === 'checkout') {
        return {
            content_type: 'checkout',
            content_category: ['Checkout'],
            content_name: ['Checkout Process'],
            num_items: getCartItemsCount()
        };
    } else {
        return {
            content_type: pageType,
            content_category: getPageCategory(),
            content_name: getPageName(),
            num_items: 1
        };
    }
}

// Função para obter IDs reais dos produtos (otimização para catálogo)
function getRealProductIds() {
    const productIds = [];
    
    // 1. Tentar obter de página de produto
    if (window.location.pathname.includes('/products/')) {
        const productForm = document.querySelector('form[action*="/cart/add"]');
        if (productForm) {
            const variantInput = productForm.querySelector('[name="id"]');
            if (variantInput && variantInput.value) {
                productIds.push(variantInput.value);
            }
        }
        
        // Tentar extrair product ID da URL também
        const pathMatch = window.location.pathname.match(/\/products\/([^\/\?]+)/);
        if (pathMatch && pathMatch[1]) {
            productIds.push(pathMatch[1]);
        }
    }
    
    // 2. Tentar obter de carrinho
    const cartItems = document.querySelectorAll('[data-variant-id], [data-product-id]');
    cartItems.forEach(item => {
        const variantId = item.dataset.variantId;
        const productId = item.dataset.productId;
        if (variantId) productIds.push(variantId);
        if (productId) productIds.push(productId);
    });
    
    // 3. Tentar obter de meta tags
    const productMeta = document.querySelector('meta[property="product:retailer_item_id"]');
    if (productMeta && productMeta.content) {
        productIds.push(productMeta.content);
    }
    
    // 4. Tentar obter de dados estruturados JSON-LD
    const jsonLdScripts = document.querySelectorAll('script[type="application/ld+json"]');
    jsonLdScripts.forEach(script => {
        try {
            const data = JSON.parse(script.textContent);
            if (data['@type'] === 'Product' && data.sku) {
                productIds.push(data.sku);
            }
            if (data['@type'] === 'Product' && data.productID) {
                productIds.push(data.productID);
            }
        } catch (e) {
            // Ignorar erros de parsing
        }
    });
    
    // Remover duplicatas e valores vazios
    return [...new Set(productIds.filter(id => id && id.trim() !== ''))];
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

// Função para obter nome da coleção atual
function getCollectionName() {
    // Tentar obter do breadcrumb
    const breadcrumb = document.querySelector('.breadcrumb, .breadcrumbs');
    if (breadcrumb) {
        const links = breadcrumb.querySelectorAll('a');
        if (links.length > 0) {
            return links[links.length - 1].textContent.trim();
        }
    }
    
    // Tentar obter do título da página
    const collectionTitle = document.querySelector('h1.collection-title, .collection-header h1, h1[class*="collection"]');
    if (collectionTitle) {
        return collectionTitle.textContent.trim();
    }
    
    // Tentar extrair da URL
    const pathParts = window.location.pathname.split('/');
    const collectionIndex = pathParts.indexOf('collections');
    if (collectionIndex !== -1 && pathParts[collectionIndex + 1]) {
        return pathParts[collectionIndex + 1].replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    // Tentar obter do meta tag
    const titleMeta = document.querySelector('meta[property="og:title"]');
    if (titleMeta) {
        return titleMeta.content;
    }
    
    return 'Collection';
}

// Função para contar produtos em uma coleção
function getCollectionProductsCount() {
    // Tentar contar produtos visíveis na página
    const productItems = document.querySelectorAll('.product-item, .product-card, [data-product-id], .grid-product, .product');
    if (productItems.length > 0) {
        return productItems.length;
    }
    
    // Tentar obter do elemento de contagem
    const countElement = document.querySelector('.collection-count, .products-count, [data-products-count]');
    if (countElement) {
        const count = parseInt(countElement.textContent.match(/\d+/)?.[0]);
        if (!isNaN(count)) return count;
    }
    
    return 1; // Default
}

// Função para contar resultados de busca
function getSearchResultsCount() {
    // Tentar contar resultados visíveis
    const searchResults = document.querySelectorAll('.search-result, .product-item, .product-card, [data-product-id]');
    if (searchResults.length > 0) {
        return searchResults.length;
    }
    
    // Tentar obter do elemento de contagem de resultados
    const resultsCount = document.querySelector('.search-results-count, .results-count, [data-results-count]');
    if (resultsCount) {
        const count = parseInt(resultsCount.textContent.match(/\d+/)?.[0]);
        if (!isNaN(count)) return count;
    }
    
    // Verificar se há mensagem de "sem resultados"
    const noResults = document.querySelector('.no-results, .search-no-results');
    if (noResults) return 0;
    
    return 1; // Default
}