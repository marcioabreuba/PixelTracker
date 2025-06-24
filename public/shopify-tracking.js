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

// Função para definir cookie
function setCookie(name, value, days = 365) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = `expires=${date.toUTCString()}`;
    document.cookie = `${name}=${value}; ${expires}; path=/; SameSite=Lax`;
}

// Função para obter parâmetro da URL
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Função para gerar _fbc a partir de fbclid
function generateFbcFromFbclid(fbclid) {
    if (!fbclid) return null;
    
    // Formato: fb.{subdominio}.{timestamp}.{fbclid}
    const subdomain = 1; // Subdomínio padrão
    const timestamp = Math.floor(Date.now() / 1000); // Timestamp atual em segundos
    
    return `fb.${subdomain}.${timestamp}.${fbclid}`;
}

// Função avançada para capturar _fbc com múltiplas fontes
function getAdvancedFbc() {
    // 1. Tentar obter _fbc do cookie
    let fbc = getCookie('_fbc');
    if (fbc) {
        console.log('✅ _fbc encontrado no cookie:', fbc);
        return fbc;
    }

    // 2. Tentar obter _fbc do localStorage
    try {
        fbc = localStorage.getItem('_fbc');
        if (fbc) {
            console.log('✅ _fbc encontrado no localStorage:', fbc);
            // Recriar cookie se encontrado no localStorage
            setCookie('_fbc', fbc);
            return fbc;
        }
    } catch (e) {
        console.warn('⚠️ Erro ao acessar localStorage:', e);
    }

    // 3. Tentar capturar fbclid da URL atual
    let fbclid = getUrlParameter('fbclid');
    if (fbclid) {
        fbc = generateFbcFromFbclid(fbclid);
        console.log('✅ fbclid capturado da URL:', fbclid, '-> _fbc gerado:', fbc);
        
        // Armazenar _fbc em cookie e localStorage
        setCookie('_fbc', fbc);
        try {
            localStorage.setItem('_fbc', fbc);
        } catch (e) {
            console.warn('⚠️ Erro ao salvar no localStorage:', e);
        }
        return fbc;
    }

    // 4. Tentar extrair fbclid de URLs anteriores (referrer)
    if (document.referrer) {
        try {
            const referrerUrl = new URL(document.referrer);
            fbclid = referrerUrl.searchParams.get('fbclid');
            if (fbclid) {
                fbc = generateFbcFromFbclid(fbclid);
                console.log('✅ fbclid capturado do referrer:', fbclid, '-> _fbc gerado:', fbc);
                
                // Armazenar _fbc em cookie e localStorage
                setCookie('_fbc', fbc);
                try {
                    localStorage.setItem('_fbc', fbc);
                } catch (e) {
                    console.warn('⚠️ Erro ao salvar no localStorage:', e);
                }
                return fbc;
            }
        } catch (e) {
            console.warn('⚠️ Erro ao processar referrer:', e);
        }
    }

    // 5. Verificar se há fbclid no histórico do navegador (sessionStorage)
    try {
        fbclid = sessionStorage.getItem('fbclid');
        if (fbclid) {
            fbc = generateFbcFromFbclid(fbclid);
            console.log('✅ fbclid encontrado no sessionStorage:', fbclid, '-> _fbc gerado:', fbc);
            
            // Armazenar _fbc em cookie e localStorage
            setCookie('_fbc', fbc);
            try {
                localStorage.setItem('_fbc', fbc);
            } catch (e) {
                console.warn('⚠️ Erro ao salvar no localStorage:', e);
            }
            return fbc;
        }
    } catch (e) {
        console.warn('⚠️ Erro ao acessar sessionStorage:', e);
    }

    console.log('ℹ️ Nenhum _fbc ou fbclid encontrado');
    return '';
}

// Função para gerar UUID
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0,
              v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// Função para capturar fbclid automaticamente quando a página carrega
function captureAndStoreFbclid() {
    const fbclid = getUrlParameter('fbclid');
    if (fbclid) {
        console.log('🔥 fbclid detectado na URL:', fbclid);
        
        // Armazenar fbclid no sessionStorage para uso futuro
        try {
            sessionStorage.setItem('fbclid', fbclid);
            console.log('✅ fbclid armazenado no sessionStorage');
        } catch (e) {
            console.warn('⚠️ Erro ao salvar fbclid no sessionStorage:', e);
        }
        
        // Gerar e armazenar _fbc imediatamente
        const fbc = generateFbcFromFbclid(fbclid);
        if (fbc) {
            setCookie('_fbc', fbc);
            try {
                localStorage.setItem('_fbc', fbc);
                console.log('✅ _fbc gerado e armazenado:', fbc);
            } catch (e) {
                console.warn('⚠️ Erro ao salvar _fbc no localStorage:', e);
            }
        }
        
        // Limpar fbclid da URL para manter URLs limpas (opcional)
        if (window.history && window.history.replaceState) {
            try {
                const url = new URL(window.location);
                url.searchParams.delete('fbclid');
                window.history.replaceState(null, null, url.toString());
                console.log('✅ fbclid removido da URL para manter limpeza');
            } catch (e) {
                console.warn('⚠️ Erro ao limpar URL:', e);
            }
        }
    }
}

// Função principal para enviar eventos
async function sendEvent(eventType, data = {}) {
    const contentId = window.shopifyFBConfig?.contentId || 'shopify_store';
    const apiUrl = window.shopifyFBConfig?.apiUrl || 'https://traqueamentophp.onrender.com';
    const event_source_url = window.location.href;
    const _fbc = getAdvancedFbc(); // Usar função avançada de captura do _fbc
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
        // Preparar payload para API
        const payload = { 
            contentId, 
            eventType, 
            event_source_url, 
            _fbc, 
            _fbp, 
            userId,
            ...data
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
            const customEvents = ['ViewList', 'ViewHome', 'ViewCart', 'Scroll_25', 'Scroll_50', 'Scroll_75', 'Scroll_90', 'Timer_1min', 'PlayVideo', 'ViewVideo_25', 'ViewVideo_50', 'ViewVideo_75', 'ViewVideo_90'];
            
            // Preparar dados para o pixel
            const pixelData = {};
            if (data.content_ids) pixelData.content_ids = data.content_ids;
            if (data.value) pixelData.value = data.value;
            if (data.currency) pixelData.currency = data.currency;
            
            // Adicionar content_ids padrão se não fornecido
            if (!pixelData.content_ids) {
                pixelData.content_ids = [contentId];
            }

            // Adicionar parâmetros otimizados para melhor segmentação
            pixelData.language = 'pt-BR';
            pixelData.referrer_url = document.referrer || '';
            
            // Parâmetros específicos por tipo de evento
            if (eventType === 'ViewContent' || eventType === 'AddToCart') {
                pixelData.content_type = 'product';
                pixelData.content_category = getProductCategory();
                pixelData.content_name = getProductName();
                pixelData.num_items = 1;
            } else if (eventType === 'PageView') {
                pixelData.content_type = getPageType();
                pixelData.content_category = getPageCategory();
                pixelData.content_name = getPageName();
                pixelData.num_items = 1;
            } else if (eventType === 'ViewList') {
                pixelData.content_type = 'product_group';
                pixelData.content_category = ['Category'];
                pixelData.content_name = ['Product List'];
                if (data.num_items) pixelData.num_items = data.num_items;
            } else if (eventType === 'ViewHome') {
                pixelData.content_type = 'website';
                pixelData.content_category = ['Home'];
                pixelData.content_name = ['Home Page'];
                pixelData.num_items = 1;
            } else if (eventType === 'ViewCart') {
                pixelData.content_type = 'product';
                pixelData.content_category = ['Cart'];
                pixelData.content_name = ['Shopping Cart'];
                if (data.num_items) pixelData.num_items = data.num_items;
            } else if (eventType === 'Search') {
                pixelData.content_type = 'search';
                pixelData.content_category = ['Search'];
                pixelData.content_name = ['Search Results'];
                if (data.search_string) pixelData.search_string = data.search_string;
                pixelData.num_items = 1;
            } else if (eventType === 'InitiateCheckout') {
                pixelData.content_type = 'checkout';
                pixelData.content_category = ['Checkout'];
                pixelData.content_name = ['Checkout Process'];
                pixelData.num_items = getCartItemsCount();
            } else if (eventType === 'Lead') {
                pixelData.content_type = 'lead_form';
                pixelData.content_category = ['Lead Generation'];
                pixelData.content_name = ['Contact Form'];
                pixelData.num_items = 1;
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

    // Gerar userId se não existir (normalmente já foi gerado antecipadamente)
    if (!userId) {
        userId = generateUUID();
        setCookie("userId", userId);
        console.log("⚠️ userId gerado em initPixel (fallback):", userId);
    } else {
        console.log("✅ userId encontrado em initPixel:", userId);
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

    // ViewHome para página inicial
    if (window.location.pathname === '/' || window.location.pathname === '') {
        setTimeout(() => {
            sendEvent('ViewHome');
        }, 1000);
    }

    // ViewList para páginas de categoria/coleção
    if (window.location.pathname.includes('/collections/')) {
        setTimeout(() => {
            const listData = getCollectionData();
            sendEvent('ViewList', listData);
        }, 1000);
    }

    // ViewCart para página do carrinho
    if (window.location.pathname.includes('/cart')) {
        setTimeout(() => {
            const cartData = getCartData();
            sendEvent('ViewCart', cartData);
        }, 1000);
    }

    // Search - detectar pesquisas
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.matches('form[action*="search"], .search-form, [data-search-form]')) {
            setTimeout(() => {
                const searchTerm = getSearchTerm(form);
                sendEvent('Search', { search_string: searchTerm });
            }, 100);
        }
    });

    // Search - detectar também quando usuário navega para página de resultados
    if (window.location.pathname.includes('/search') || window.location.search.includes('q=')) {
        setTimeout(() => {
            const searchTerm = getSearchTermFromUrl();
            if (searchTerm) {
                sendEvent('Search', { search_string: searchTerm });
            }
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

// Função para obter dados da coleção/categoria
function getCollectionData() {
    const collectionData = {};
    
    // Tentar obter nome da coleção
    const collectionTitle = document.querySelector('h1.collection-title, .collection-header h1, .page-title');
    if (collectionTitle) {
        collectionData.content_category = [collectionTitle.textContent.trim()];
        collectionData.content_name = [collectionTitle.textContent.trim()];
    }
    
    // Contar produtos na página
    const products = document.querySelectorAll('.product-item, .grid__item, [data-product-id]');
    if (products.length > 0) {
        collectionData.num_items = products.length;
    }
    
    collectionData.content_type = 'product_group';
    
    return collectionData;
}

// Função para obter dados do carrinho
function getCartData() {
    const cartData = {};
    
    // Tentar obter valor total do carrinho
    const totalElement = document.querySelector('.cart__total, .cart-total, [data-cart-total]');
    if (totalElement) {
        const totalText = totalElement.textContent || totalElement.dataset.cartTotal;
        const total = parseFloat(totalText.replace(/[^\d.,]/g, '').replace(',', '.'));
        if (!isNaN(total)) {
            cartData.value = total;
            cartData.currency = 'BRL';
        }
    }
    
    // Contar itens no carrinho
    const cartItems = document.querySelectorAll('.cart-item, [data-cart-item]');
    if (cartItems.length > 0) {
        cartData.num_items = cartItems.length;
        
        // Coletar IDs dos produtos no carrinho
        const contentIds = [];
        cartItems.forEach(item => {
            const productId = item.dataset.productId || item.dataset.variantId;
            if (productId) {
                contentIds.push(productId);
            }
        });
        if (contentIds.length > 0) {
            cartData.content_ids = contentIds;
        }
    }
    
    cartData.content_type = 'product';
    cartData.content_category = ['Cart'];
    cartData.content_name = ['Shopping Cart'];
    
    return cartData;
}

// Função para obter termo de pesquisa de formulário
function getSearchTerm(form) {
    const searchInput = form.querySelector('[name="q"], [name="query"], [name="search"], input[type="search"]');
    if (searchInput && searchInput.value) {
        return searchInput.value.trim();
    }
    return '';
}

// Função para obter termo de pesquisa da URL
function getSearchTermFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('q') || urlParams.get('query') || urlParams.get('search') || '';
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
    // Capturar fbclid imediatamente se presente na URL
    captureAndStoreFbclid();
    
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
        console.log('🔗 fbc Coverage: Melhorado com captura de fbclid');
    } else {
        console.warn('⚠️ shopifyFBConfig não encontrado. Configure window.shopifyFBConfig antes de carregar este script.');
    }
});

// Capturar fbclid também quando script é carregado (para casos onde DOM já está pronto)
captureAndStoreFbclid();

// Garantir userId no início (ANTES do DOMContentLoaded)
(function ensureUserIdEarly() {
    let userId = getCookie("userId");
    if (!userId) {
        userId = generateUUID();
        setCookie("userId", userId);
        console.log("✅ userId inicial gerado antecipadamente:", userId);
    } else {
        console.log("✅ userId já existe:", userId);
    }
})();

// Garantir captura antecipada de fbclid/_fbc (redundante mas seguro)
captureAndStoreFbclid();

// Expor funções globalmente para uso manual
window.sendEvent = sendEvent;
window.initPixel = initPixel;
window.getAdvancedFbc = getAdvancedFbc;
window.captureAndStoreFbclid = captureAndStoreFbclid;