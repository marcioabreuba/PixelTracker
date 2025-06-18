/**
 * Script de Tracking Duplo do Facebook para Shopify - VERSÃO MELHORADA
 * Melhorias: padronização de parâmetros, performance, validações
 * Integração com API Laravel (server-side) + Pixel Facebook (client-side)
 */

// ===== UTILITÁRIOS DE TRACKING =====
const TrackingUtils = {
    // Debug log
    log(message, data = null) {
        const debug = window.shopifyFBConfig?.debug || false;
        if (debug) {
            console.log(`🔍 FB Tracking: ${message}`, data || '');
        }
    },

    // Validar email
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    // Validar telefone brasileiro
    isValidBrazilianPhone(phone) {
        return /^55\d{10,11}$/.test(phone);
    },

    // Debounce para performance
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Safe DOM query
    safeQuery(selector) {
        try {
            return document.querySelector(selector);
        } catch (e) {
            return null;
        }
    },

    // Safe DOM query all
    safeQueryAll(selector) {
        try {
            return document.querySelectorAll(selector);
        } catch (e) {
            return [];
        }
    },

    // Função para definir cookies
    setCookie(name, value, days = 365) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${encodeURIComponent(value)}; expires=${date.toUTCString()}; path=/`;
    },

    // Função para normalizar valores de preço (corrigir imprecisões do parseFloat)
    normalizePrice(priceText) {
        if (!priceText) return null;
        
        // Extrair apenas números, pontos e vírgulas
        const cleanText = priceText.toString().replace(/[^\d.,]/g, '');
        if (!cleanText) return null;
        
        // Converter vírgula para ponto (formato brasileiro)
        const normalizedText = cleanText.replace(',', '.');
        
        // Converter para número
        const price = parseFloat(normalizedText);
        if (isNaN(price)) return null;
        
        // Arredondar para 2 casas decimais para evitar imprecisões
        const roundedPrice = Math.round(price * 100) / 100;
        
        // Se for um número inteiro, retornar como inteiro
        if (roundedPrice % 1 === 0) {
            return Math.floor(roundedPrice);
        }
        
        // Caso contrário, retornar com até 2 casas decimais
        return roundedPrice;
    }
};

// ===== COLETA PADRONIZADA DE DADOS PESSOAIS =====
const PersonalDataCollector = {
    // Coletar dados de formulários em TODOS os eventos
    collectFromForms() {
        const data = {};
        
        // Detectar formulários Shopify
        const nameField = TrackingUtils.safeQuery('[name="contact[first_name]"], [name="customer[first_name]"], [name="first_name"], [name="nome"], [name="NOME"]');
        const lastNameField = TrackingUtils.safeQuery('[name="contact[last_name]"], [name="customer[last_name]"], [name="last_name"], [name="sobrenome"], [name="SOBRENOME"]');
        const emailField = TrackingUtils.safeQuery('[name="contact[email]"], [name="customer[email]"], [name="email"], [name="EMAIL"]');
        const phoneField = TrackingUtils.safeQuery('[name="contact[phone]"], [name="customer[phone]"], [name="phone"], [name="telefone"], [name="TELEFONE"]');

        if (nameField?.value) {
            data.fn = nameField.value.trim().toLowerCase();
        }
        
        if (lastNameField?.value) {
            data.ln = lastNameField.value.trim().toLowerCase();
        } else if (nameField?.value) {
            const nameParts = nameField.value.trim().split(' ');
            data.fn = nameParts[0].toLowerCase();
            if (nameParts.length > 1) {
                data.ln = nameParts[nameParts.length - 1].toLowerCase();
            }
        }

        if (emailField?.value && TrackingUtils.isValidEmail(emailField.value)) {
            data.em = emailField.value.trim().toLowerCase();
        }

        if (phoneField?.value) {
            let phone = phoneField.value.replace(/\s|-|\(|\)/g, '');
            if (phone && !phone.startsWith('55')) {
                phone = '55' + phone;
            }
            if (TrackingUtils.isValidBrazilianPhone(phone)) {
                data.ph = phone;
            }
        }

        // Salvar nos cookies para próximos eventos
        Object.keys(data).forEach(key => {
            if (data[key]) {
                TrackingUtils.setCookie(key, data[key]);
            }
        });

        return data;
    },

    // Obter dados salvos dos cookies
    getSavedData() {
        const data = {};
        ['fn', 'ln', 'em', 'ph'].forEach(key => {
            const value = getCookie(key);
            if (value) data[key] = decodeURIComponent(value);
        });
        return data;
    },

    // Merge de dados atuais + salvos
    getAllPersonalData() {
        const savedData = this.getSavedData();
        const formData = this.collectFromForms();
        return { ...savedData, ...formData };
    }
};

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

// Função principal para enviar eventos - VERSÃO MELHORADA
async function sendEvent(eventType, data = {}) {
    const contentId = window.shopifyFBConfig?.contentId || 'shopify_store';
    const apiUrl = window.shopifyFBConfig?.apiUrl || 'https://traqueamentophp.onrender.com';
    const event_source_url = window.location.href;
    const _fbc = getCookie('_fbc') || '';
    const _fbp = getCookie('_fbp') || '';
    let userId = getCookie('userId');
    
    // Gerar userId se não existir
    if (!userId) {
        userId = generateUUID();
        TrackingUtils.setCookie('userId', userId);
    }

    // COLETA PADRONIZADA DE DADOS PESSOAIS PARA TODOS OS EVENTOS
    const personalData = PersonalDataCollector.getAllPersonalData();
    
    TrackingUtils.log(`Coletando evento ${eventType}`, { eventType, personalData, data });

    try {
        // Obter dados do produto/página atual para todos os eventos
        const currentPageData = getCurrentPageData();
        
        // DETERMINAR CONTENT_IDS CORRETOS baseado no tipo de evento e página
        let finalContentIds = data.content_ids || [];
        
        // Se não há content_ids específicos, determinar baseado no contexto
        if (!finalContentIds || finalContentIds.length === 0) {
            // Para páginas de produto, usar apenas o variant ID específico
            if (window.location.pathname.includes('/products/')) {
                const specificVariantId = getCurrentProductVariantId();
                if (specificVariantId) {
                    finalContentIds = [specificVariantId];
                    TrackingUtils.log(`Content ID específico da variante para ${eventType}`, specificVariantId);
                } else {
                    // Fallback para product handle da URL
                    const pathMatch = window.location.pathname.match(/\/products\/([^\/\?]+)/);
                    if (pathMatch && pathMatch[1]) {
                        finalContentIds = [pathMatch[1]];
                        TrackingUtils.log(`Content ID do product handle para ${eventType}`, pathMatch[1]);
                    } else {
                        finalContentIds = [contentId];
                    }
                }
            }
            // Para outras páginas, usar múltiplos IDs conforme necessário
            else {
                const realProductIds = getRealProductIds();
                if (realProductIds.length > 0) {
                    finalContentIds = realProductIds;
                    TrackingUtils.log(`Content IDs múltiplos para ${eventType}`, realProductIds);
                } else {
                    finalContentIds = [contentId]; // Fallback para contentId do domínio
                }
            }
        } else {
            TrackingUtils.log(`Content IDs específicos para ${eventType}`, finalContentIds);
        }
        
        // PARÂMETROS PADRONIZADOS PARA TODOS OS EVENTOS
        const standardParams = {
            // Identidade
            contentId, 
            eventType, 
            event_source_url, 
            _fbc, 
            _fbp, 
            userId,
            // Dados pessoais (sempre incluídos quando disponíveis)
            ...personalData,
            // Parâmetros otimizados
            app: 'Pixel Tracker Enhanced',
            language: 'pt-BR',
            referrer_url: document.referrer || '',
            // Dados da página/produto
            content_type: currentPageData.content_type,
            content_category: currentPageData.content_category,
            content_name: currentPageData.content_name,
            num_items: currentPageData.num_items,
            // CONTENT_IDS CORRETOS (enviados para backend)
            content_ids: finalContentIds,
            // Dados de valor/moeda se disponíveis
            value: data.value || null,
            currency: data.currency || null,
            // Dados adicionais padronizados
            timestamp: Date.now(),
            page_url: window.location.href,
            page_title: document.title,
            device_type: /Mobile|Android|iPhone|iPad/.test(navigator.userAgent) ? 'mobile' : 'desktop',
            // Outros dados específicos do evento (exceto content_ids que já tratamos)
            ...Object.fromEntries(Object.entries(data).filter(([key]) => !['content_ids', 'value', 'currency'].includes(key)))
        };

        TrackingUtils.log(`Parâmetros padronizados para ${eventType}`, standardParams);

        // Enviar para API (server-side)
        const response = await fetch(`${apiUrl}/events/send`, {
                method: 'POST',
            credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
            body: JSON.stringify(standardParams)
        });

        if (!response.ok) {
            throw new Error(`API Error: ${response.status} ${response.statusText}`);
        }

        const responseData = await response.json();
        TrackingUtils.log(`Evento ${eventType} enviado para API`, responseData);
        console.log(`✅ Evento ${eventType} enviado para API (server-side) - EventID: ${responseData.eventID}`);

        // Se for evento Init, apenas retornar os dados
        if (eventType === "Init") {
            return responseData;
        }

        // DELAY ESTRATÉGICO: Aguardar para garantir que server-side chegue primeiro
        setTimeout(() => {
            // Enviar para Facebook Pixel (client-side) com MESMOS DADOS e eventID compartilhado
            if (typeof fbq !== 'undefined' && responseData.eventID) {
                const customEvents = ['Scroll_25', 'Scroll_50', 'Scroll_75', 'Scroll_90', 'Timer_1min', 'PlayVideo', 'ViewVideo_25', 'ViewVideo_50', 'ViewVideo_75', 'ViewVideo_90'];
                
                // USAR OS MESMOS DADOS PADRONIZADOS para garantir consistência
                const pixelData = {
                    content_type: standardParams.content_type,
                    content_category: standardParams.content_category,
                    content_name: standardParams.content_name,
                    num_items: standardParams.num_items,
                    language: standardParams.language,
                    device_type: standardParams.device_type,
                    // USAR OS MESMOS CONTENT_IDS do backend
                    content_ids: standardParams.content_ids
                };
                
                // Adicionar outros dados específicos do pixel
                if (standardParams.value) pixelData.value = standardParams.value;
                if (standardParams.currency) pixelData.currency = standardParams.currency;
                if (data.search_string) pixelData.search_string = data.search_string;

                // Enviar para o pixel com dados padronizados
                if (customEvents.includes(eventType)) {
                    fbq('trackCustom', eventType, pixelData, { eventID: responseData.eventID });
                } else {
                    fbq('track', eventType, pixelData, { eventID: responseData.eventID });
                }
                
                TrackingUtils.log(`Pixel ${eventType}`, pixelData);
                console.log(`✅ Evento ${eventType} enviado para Pixel (client-side) com eventID: ${responseData.eventID}`);
                console.log(`📦 Content IDs: ${JSON.stringify(pixelData.content_ids)}`);
            }
        }, 200); // 200ms delay para server-side processar primeiro

        return responseData;
        } catch (error) {
        console.error(`❌ Erro ao rastrear evento ${eventType}:`, error);
        TrackingUtils.log(`Erro ${eventType}`, error);
        
        // Fallback: tentar enviar apenas para o pixel em caso de erro
        if (typeof fbq !== 'undefined' && eventType !== 'Init') {
            const fallbackData = { 
                error_fallback: true,
                content_type: currentPageData.content_type || 'page'
            };
            
            const customEvents = ['Scroll_25', 'Scroll_50', 'Scroll_75', 'Scroll_90', 'Timer_1min', 'PlayVideo', 'ViewVideo_25', 'ViewVideo_50', 'ViewVideo_75', 'ViewVideo_90'];
            
            if (customEvents.includes(eventType)) {
                fbq('trackCustom', eventType, fallbackData);
            } else {
                fbq('track', eventType, fallbackData);
            }
            console.log(`🔄 Evento ${eventType} enviado apenas para Pixel (fallback)`);
        }
        
        throw error; // Re-throw para debugging
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
    TrackingUtils.log('Configurando detectores de eventos Shopify...');
    
    // Detectar e enviar eventos automaticamente baseado na página atual
    detectAndSendPageEvents();
    
    // Detectar AddToCart com debouncing
    const addToCartHandler = TrackingUtils.debounce((e) => {
        const button = e.target.closest('[name="add"], .btn-product-form, .product-form__cart-submit, .product-form__buttons button[type="submit"], .btn--add-to-cart');
            if (button) {
            TrackingUtils.log('AddToCart detectado');
            setTimeout(() => {
                const productData = getShopifyProductData();
                sendEvent('AddToCart', productData);
            }, 100);
        }
    }, 100);

    // Detectar ViewCart com debouncing
    const cartHandler = TrackingUtils.debounce((e) => {
        const cartButton = e.target.closest('[href*="/cart"], .cart-link, .header-cart, .cart-icon');
        if (cartButton) {
            TrackingUtils.log('ViewCart detectado');
            setTimeout(() => sendEvent('ViewCart'), 500);
        }
    }, 200);

    // Detectar InitiateCheckout
    const checkoutHandler = (e) => {
        const checkoutButton = e.target.closest('.cart__checkout-button, [name="goto_checkout"], .btn--checkout, [href*="checkout"]');
        if (checkoutButton) {
            TrackingUtils.log('InitiateCheckout detectado');
            sendEvent('InitiateCheckout');
        }
    };

    // Event listeners com melhor performance
    if (document.body) {
        document.addEventListener('click', addToCartHandler, { passive: true });
        document.addEventListener('click', cartHandler, { passive: true }); 
        document.addEventListener('click', checkoutHandler, { passive: true });
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('click', addToCartHandler, { passive: true });
            document.addEventListener('click', cartHandler, { passive: true });
            document.addEventListener('click', checkoutHandler, { passive: true });
        });
    }

    // Detectar Search (formulários de busca)
    const searchHandler = (e) => {
        const searchForm = e.target;
        if (searchForm.matches('form[action*="/search"], .search-form, form[role="search"]')) {
            setTimeout(() => {
                const searchQuery = searchForm.querySelector('input[name="q"], input[name="query"], input[type="search"]');
                const searchData = searchQuery ? { search_string: searchQuery.value } : {};
                TrackingUtils.log('Search detectado', searchData);
                sendEvent('Search', searchData);
            }, 100);
        }
    };

    // Detectar formulários de contato/newsletter (Lead)
    const formHandler = (e) => {
        const form = e.target;
        if (form.matches('.contact-form, .newsletter-form, form[action*="contact"], form[action*="newsletter"]')) {
            TrackingUtils.log('Lead detectado');
            setTimeout(() => sendEvent('Lead'), 100);
        }
    };

    // Form listeners
    if (document.body) {
        document.addEventListener('submit', searchHandler);
        document.addEventListener('submit', formHandler);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('submit', searchHandler);
            document.addEventListener('submit', formHandler);
        });
    }

    // Tracking de scroll, tempo e vídeos com melhorias
    setupScrollTracking();
    setupTimeTracking();
    setupVideoTracking();
    
    TrackingUtils.log('✅ Todos os detectores de eventos configurados');
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
            const homeData = getHomePageData();
            sendEvent('ViewHome', homeData);
        }
        
        // 3. ViewList - Páginas de coleção/categoria
        else if (path.includes('/collections/') && !path.includes('/products/')) {
            const collectionData = getCollectionPageData();
            sendEvent('ViewList', collectionData);
        }
        
        // 4. ViewContent - Páginas de produto
        else if (path.includes('/products/')) {
            const productData = getShopifyProductData();
            sendEvent('ViewContent', productData);
        }
        
        // 5. ViewCart - Página do carrinho
        else if (path.includes('/cart')) {
            const cartData = getCartPageData();
            sendEvent('ViewCart', cartData);
        }
        
        // 6. Search - Página de resultados de busca
        else if (path.includes('/search') || search.includes('q=') || search.includes('query=')) {
            const urlParams = new URLSearchParams(search);
            const searchQuery = urlParams.get('q') || urlParams.get('query') || '';
            const searchData = getSearchPageData(searchQuery);
            sendEvent('Search', searchData);
        }
        
    }, 1000); // Aguardar 1 segundo para garantir que a página carregou
}

// Função para obter dados do produto Shopify
function getShopifyProductData() {
    const productData = {};
    
    // Tentar obter ID do produto
    const productForm = TrackingUtils.safeQuery('form[action*="/cart/add"]');
    if (productForm) {
        const variantInput = productForm.querySelector('[name="id"]');
        if (variantInput && variantInput.value) {
            productData.content_ids = [variantInput.value];
            TrackingUtils.log('Product variant ID encontrado', variantInput.value);
        }
    }

    // Se não encontrou via form, usar getRealProductIds
    if (!productData.content_ids) {
        const realIds = getRealProductIds();
        if (realIds.length > 0) {
            productData.content_ids = realIds;
            TrackingUtils.log('Product IDs via getRealProductIds', realIds);
        }
    }

    // Tentar obter preço
    const priceElement = TrackingUtils.safeQuery('.price, .product-price, [data-price], .money');
    if (priceElement) {
        const priceText = priceElement.textContent || priceElement.dataset.price;
        const price = TrackingUtils.normalizePrice(priceText);
        if (price !== null) {
            productData.value = price;
            productData.currency = 'BRL';
        }
    }

    return productData;
}

// Função para obter dados da página inicial (ViewHome)
function getHomePageData() {
    const homeData = {};
    
    // Obter produtos em destaque na home
    const featuredProductIds = getFeaturedProductIds();
    if (featuredProductIds.length > 0) {
        homeData.content_ids = featuredProductIds;
        TrackingUtils.log('Home featured products', featuredProductIds);
    } else {
        // Fallback: usar qualquer produto visível na home
        const productElements = TrackingUtils.safeQueryAll('[data-product-id], [href*="/products/"]');
        const visibleProductIds = [];
        
        productElements.forEach(element => {
            if (element.dataset.productId) {
                visibleProductIds.push(element.dataset.productId);
            } else if (element.href) {
                const productMatch = element.href.match(/\/products\/([^\/\?]+)/);
                if (productMatch) visibleProductIds.push(productMatch[1]);
            }
        });
        
        if (visibleProductIds.length > 0) {
            homeData.content_ids = [...new Set(visibleProductIds)].slice(0, 10); // Max 10 produtos
            TrackingUtils.log('Home visible products', homeData.content_ids);
        }
    }
    
    return homeData;
}

// Função para obter dados da página de coleção (ViewList)
function getCollectionPageData() {
    const collectionData = {};
    
    // Obter IDs dos produtos da coleção
    const productElements = TrackingUtils.safeQueryAll('.product-item [data-product-id], .product-card [data-product-id], .grid-product [data-product-id], [href*="/products/"]');
    const productIds = [];
    
    productElements.forEach(element => {
        if (element.dataset.productId) {
            productIds.push(element.dataset.productId);
        } else if (element.href) {
            const productMatch = element.href.match(/\/products\/([^\/\?]+)/);
            if (productMatch) productIds.push(productMatch[1]);
        }
    });
    
    // Tentar obter variant IDs dos produtos visíveis
    const variantElements = TrackingUtils.safeQueryAll('[data-variant-id]');
    variantElements.forEach(element => {
        if (element.dataset.variantId) {
            productIds.push(element.dataset.variantId);
        }
    });
    
    if (productIds.length > 0) {
        collectionData.content_ids = [...new Set(productIds)]; // Remover duplicatas
        TrackingUtils.log('Collection products', collectionData.content_ids);
    } else {
        // Fallback: extrair da URL da coleção
        const pathMatch = window.location.pathname.match(/\/collections\/([^\/\?]+)/);
        if (pathMatch) {
            collectionData.content_ids = [pathMatch[1]]; // Nome da coleção como fallback
        }
    }
    
    return collectionData;
}

// Função para obter dados da página do carrinho (ViewCart)
function getCartPageData() {
    const cartData = {};
    
    // Obter IDs dos produtos no carrinho
    const cartItems = TrackingUtils.safeQueryAll('[data-variant-id], [data-product-id], .cart-item');
    const productIds = [];
    let totalValue = 0;
    
    cartItems.forEach(item => {
        // Variant ID (prioritário)
        if (item.dataset.variantId) {
            productIds.push(item.dataset.variantId);
        }
        // Product ID
        else if (item.dataset.productId) {
            productIds.push(item.dataset.productId);
        }
        // Tentar extrair de links dentro do item
        else {
            const productLink = item.querySelector('[href*="/products/"]');
            if (productLink) {
                const productMatch = productLink.href.match(/\/products\/([^\/\?]+)/);
                if (productMatch) productIds.push(productMatch[1]);
            }
        }
        
        // Tentar obter preço do item
        const priceElement = item.querySelector('.cart-item-price, .price, [data-price]');
        if (priceElement) {
            const priceText = priceElement.textContent || priceElement.dataset.price;
            const price = TrackingUtils.normalizePrice(priceText);
            if (price !== null) totalValue += price;
        }
    });
    
    if (productIds.length > 0) {
        cartData.content_ids = [...new Set(productIds)]; // Remover duplicatas
        TrackingUtils.log('Cart products', cartData.content_ids);
    }
    
    // Tentar obter valor total do carrinho
    if (totalValue === 0) {
        const totalElement = TrackingUtils.safeQuery('.cart-total, .total-price, [data-cart-total]');
        if (totalElement) {
            const totalText = totalElement.textContent;
            const total = TrackingUtils.normalizePrice(totalText);
            if (total !== null) totalValue = total;
        }
    }
    
    if (totalValue > 0) {
        cartData.value = totalValue;
        cartData.currency = 'BRL';
    }
    
    return cartData;
}

// Função para obter dados da página de busca (Search)
function getSearchPageData(searchQuery) {
    const searchData = {};
    
    // Adicionar termo de busca
    if (searchQuery) {
        searchData.search_string = searchQuery;
        TrackingUtils.log('Search query', searchQuery);
    }
    
    // Obter IDs dos produtos nos resultados
    const resultElements = TrackingUtils.safeQueryAll('.search-result [data-product-id], .product-item [data-product-id], .product-card [data-product-id], [href*="/products/"]');
    const productIds = [];
    
    resultElements.forEach(element => {
        if (element.dataset.productId) {
            productIds.push(element.dataset.productId);
        } else if (element.href) {
            const productMatch = element.href.match(/\/products\/([^\/\?]+)/);
            if (productMatch) productIds.push(productMatch[1]);
        }
    });
    
    if (productIds.length > 0) {
        searchData.content_ids = [...new Set(productIds)]; // Remover duplicatas
        TrackingUtils.log('Search results products', searchData.content_ids);
    }
    
    return searchData;
}

// Função melhorada para obter produtos em destaque
function getFeaturedProductIds() {
    // Buscar produtos em destaque na home com seletores mais específicos
    const featuredSelectors = [
        '.featured-product [data-product-id]',
        '.featured-products [data-product-id]', 
        '.hero-product [data-product-id]',
        '.recommended-products [data-product-id]',
        '.trending-products [data-product-id]',
        '.best-sellers [data-product-id]',
        '.product-recommendations [data-product-id]',
        '.homepage-products [data-product-id]'
    ];
    
    const productIds = [];
    
    featuredSelectors.forEach(selector => {
        const elements = TrackingUtils.safeQueryAll(selector);
        elements.forEach(element => {
            if (element.dataset.productId) {
                productIds.push(element.dataset.productId);
            }
        });
    });
    
    // Se não encontrou produtos em destaque, pegar primeiros produtos visíveis
    if (productIds.length === 0) {
        const allProducts = TrackingUtils.safeQueryAll('.product-card [data-product-id], .product-item [data-product-id]');
        allProducts.forEach(element => {
            if (element.dataset.productId) {
                productIds.push(element.dataset.productId);
            }
        });
    }
    
    return [...new Set(productIds)].slice(0, 10); // Máximo 10 produtos, sem duplicatas
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

// Função para obter apenas o variant ID específico da página de produto atual
function getCurrentProductVariantId() {
    // 1. Prioridade: Variant ID do form de adicionar ao carrinho
    const productForm = TrackingUtils.safeQuery('form[action*="/cart/add"]');
    if (productForm) {
        const variantInput = productForm.querySelector('[name="id"]');
        if (variantInput && variantInput.value) {
            TrackingUtils.log('Variant ID atual do form', variantInput.value);
            return variantInput.value;
        }
    }
    
    // 2. Variant ID de elementos com data-variant-id visíveis
    const variantElement = TrackingUtils.safeQuery('[data-variant-id]:not([style*="display: none"])');
    if (variantElement && variantElement.dataset.variantId) {
        TrackingUtils.log('Variant ID atual de elemento', variantElement.dataset.variantId);
        return variantElement.dataset.variantId;
    }
    
    // 3. Tentar obter de variáveis Shopify globais
    if (typeof window.ShopifyAnalytics !== 'undefined' && window.ShopifyAnalytics.meta) {
        const meta = window.ShopifyAnalytics.meta;
        if (meta.product && meta.product.variants && meta.product.variants.length > 0) {
            // Pegar primeira variante como padrão
            const variantId = meta.product.variants[0].id.toString();
            TrackingUtils.log('Variant ID de ShopifyAnalytics', variantId);
            return variantId;
        }
    }
    
    // 4. Tentar obter de meta tags específicas
    const variantMeta = TrackingUtils.safeQuery('meta[name="shopify-variant-id"]');
    if (variantMeta && variantMeta.content) {
        TrackingUtils.log('Variant ID de meta tag', variantMeta.content);
        return variantMeta.content;
    }
    
    // 5. Fallback: tentar extrair de dados estruturados
    const jsonLdScripts = TrackingUtils.safeQueryAll('script[type="application/ld+json"]');
    for (const script of jsonLdScripts) {
        try {
            const data = JSON.parse(script.textContent);
            if (data['@type'] === 'Product' && data.sku) {
                TrackingUtils.log('Variant ID de JSON-LD', data.sku);
                return data.sku;
            }
        } catch (e) {
            // Continuar tentando outros scripts
        }
    }
    
    TrackingUtils.log('Nenhum variant ID específico encontrado');
    return null;
}

// Função melhorada para obter IDs reais dos produtos (otimização para catálogo)
function getRealProductIds() {
    const productIds = [];
    const path = window.location.pathname;
    
    // 1. Para páginas de produto - Priorizar variant ID
    if (path.includes('/products/')) {
        // Variant ID do form (mais importante para tracking)
        const productForm = TrackingUtils.safeQuery('form[action*="/cart/add"]');
        if (productForm) {
            const variantInput = productForm.querySelector('[name="id"]');
            if (variantInput && variantInput.value) {
                productIds.push(variantInput.value);
                TrackingUtils.log('Variant ID from form', variantInput.value);
            }
        }
        
        // Product ID de elementos Shopify
        const productDataElements = TrackingUtils.safeQueryAll('[data-product-id]');
        productDataElements.forEach(element => {
            if (element.dataset.productId) {
                productIds.push(element.dataset.productId);
            }
        });
        
        // Extrair do handle da URL como fallback
        const pathMatch = path.match(/\/products\/([^\/\?]+)/);
        if (pathMatch && pathMatch[1]) {
            productIds.push(pathMatch[1]);
        }
    }
    
    // 2. Para páginas de coleção
    else if (path.includes('/collections/')) {
        const productElements = TrackingUtils.safeQueryAll('[data-product-id], [data-variant-id]');
        productElements.forEach(element => {
            if (element.dataset.variantId) productIds.push(element.dataset.variantId);
            if (element.dataset.productId) productIds.push(element.dataset.productId);
        });
    }
    
    // 3. Para páginas de carrinho
    else if (path.includes('/cart')) {
        const cartItems = TrackingUtils.safeQueryAll('[data-variant-id], [data-product-id], .cart-item');
        cartItems.forEach(item => {
            if (item.dataset.variantId) productIds.push(item.dataset.variantId);
            if (item.dataset.productId) productIds.push(item.dataset.productId);
        });
    }
    
    // 4. Para qualquer página - buscar produtos visíveis
    else {
        const visibleProducts = TrackingUtils.safeQueryAll('[data-product-id], [data-variant-id], [href*="/products/"]');
        visibleProducts.forEach(element => {
            if (element.dataset.variantId) productIds.push(element.dataset.variantId);
            if (element.dataset.productId) productIds.push(element.dataset.productId);
            if (element.href) {
                const productMatch = element.href.match(/\/products\/([^\/\?]+)/);
                if (productMatch) productIds.push(productMatch[1]);
            }
        });
    }
    
    // 5. Tentar obter de meta tags (sempre útil)
    const metaSelectors = [
        'meta[property="product:retailer_item_id"]',
        'meta[name="shopify-product-id"]',
        'meta[name="shopify-variant-id"]'
    ];
    
    metaSelectors.forEach(selector => {
        const meta = TrackingUtils.safeQuery(selector);
        if (meta && meta.content) {
            productIds.push(meta.content);
        }
    });
    
    // 6. Tentar obter de dados estruturados JSON-LD
    const jsonLdScripts = TrackingUtils.safeQueryAll('script[type="application/ld+json"]');
    jsonLdScripts.forEach(script => {
        try {
            const data = JSON.parse(script.textContent);
            
            // Produto único
            if (data['@type'] === 'Product') {
                if (data.sku) productIds.push(data.sku);
                if (data.productID) productIds.push(data.productID);
                if (data.identifier) productIds.push(data.identifier);
            }
            
            // Array de produtos
            if (Array.isArray(data) || data['@graph']) {
                const products = data['@graph'] || data;
                products.forEach(item => {
                    if (item['@type'] === 'Product') {
                        if (item.sku) productIds.push(item.sku);
                        if (item.productID) productIds.push(item.productID);
                        if (item.identifier) productIds.push(item.identifier);
                    }
                });
            }
        } catch (e) {
            // Ignorar erros de parsing JSON
        }
    });
    
    // 7. Tentar obter de variáveis Shopify globais
    if (typeof window.ShopifyAnalytics !== 'undefined' && window.ShopifyAnalytics.meta) {
        const meta = window.ShopifyAnalytics.meta;
        if (meta.product && meta.product.id) productIds.push(meta.product.id.toString());
        if (meta.product && meta.product.variants) {
            meta.product.variants.forEach(variant => {
                if (variant.id) productIds.push(variant.id.toString());
            });
        }
    }
    
    // Remover duplicatas, valores vazios e manter apenas valores válidos
    const validIds = [...new Set(productIds.filter(id => {
        return id && id.toString().trim() !== '' && id.toString().length > 0;
    }))];
    
    TrackingUtils.log('All found product IDs', validIds);
    return validIds;
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

// Configurar tracking de scroll - VERSÃO MELHORADA
function setupScrollTracking() {
    let scrollTracker = { 25: false, 50: false, 75: false, 90: false };
    
    const handleScroll = TrackingUtils.debounce(() => {
        const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
        
        Object.keys(scrollTracker).forEach(threshold => {
            if (scrollPercent >= threshold && !scrollTracker[threshold]) {
                scrollTracker[threshold] = true;
                TrackingUtils.log(`Scroll ${threshold}% detectado`);
                sendEvent(`Scroll_${threshold}`);
            }
        });
    }, 100);

    if (window) {
        window.addEventListener('scroll', handleScroll, { passive: true });
        TrackingUtils.log('✅ Scroll tracking configurado');
    }
}

// Configurar tracking de tempo - VERSÃO MELHORADA
function setupTimeTracking() {
    const timeTracker = {
        '1min': 60000
    };

    Object.entries(timeTracker).forEach(([label, time]) => {
        setTimeout(() => {
            TrackingUtils.log(`Timer ${label} atingido`);
            sendEvent(`Timer_${label}`);
        }, time);
    });
    
    TrackingUtils.log('✅ Time tracking configurado');
}

// Configurar tracking de vídeos - VERSÃO MELHORADA
function setupVideoTracking() {
    const setupVideoEvents = () => {
        const videos = TrackingUtils.safeQueryAll('video, iframe[src*="youtube"], iframe[src*="vimeo"]');
        
        videos.forEach(video => {
            if (video.tagName === 'VIDEO') {
                setupNativeVideoEvents(video);
            } else {
                // Para iframes de YouTube/Vimeo
                video.addEventListener('load', () => {
                    TrackingUtils.log('Video iframe carregado');
                    sendEvent('PlayVideo');
                });
            }
        });
        
        TrackingUtils.log(`✅ Video tracking configurado para ${videos.length} vídeos`);
    };

    const setupNativeVideoEvents = (video) => {
        let videoEvents = { 25: false, 50: false, 75: false, 90: false };
        
        video.addEventListener('play', () => {
            TrackingUtils.log('Video play detectado');
            sendEvent('PlayVideo');
        });
        
        video.addEventListener('timeupdate', TrackingUtils.debounce(() => {
            if (video.duration > 0) {
                const percent = Math.round((video.currentTime / video.duration) * 100);
                
                Object.keys(videoEvents).forEach(threshold => {
                    if (percent >= parseInt(threshold) && !videoEvents[threshold]) {
                        videoEvents[threshold] = true;
                        TrackingUtils.log(`Video ${threshold}% detectado`);
                        sendEvent(`ViewVideo_${threshold}`);
                    }
                });
            }
        }, 200));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupVideoEvents);
    } else {
        setupVideoEvents();
    }
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

// Inicialização automática - VERSÃO MELHORADA
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se a configuração está disponível
if (window.shopifyFBConfig) {
        console.log('🚀 Iniciando Shopify Facebook Tracking Duplo - VERSÃO MELHORADA...');
        TrackingUtils.log('Configuração detectada', window.shopifyFBConfig);
        
        // Inicializar pixel
        initPixel();
        
        // Configurar eventos
        setupShopifyEvents();
        
        console.log('✅ Shopify Facebook Tracking Duplo inicializado!');
        console.log('📊 Server-side: API Laravel');
        console.log('🌐 Client-side: Facebook Pixel');
        console.log('🎯 Match Quality: MAXIMIZADO com dados padronizados');
        console.log('⚡ Performance: Otimizada com debouncing e passive listeners');
        console.log('🛠️ Debug: Ative debug=true na config para logs detalhados');
        
        TrackingUtils.log('✅ Sistema completamente inicializado');
    } else {
        console.warn('⚠️ shopifyFBConfig não encontrado. Configure window.shopifyFBConfig antes de carregar este script.');
    }
});

// Expor funções globalmente para uso manual e debugging
window.sendEvent = sendEvent;
window.initPixel = initPixel;
window.TrackingUtils = TrackingUtils;
window.PersonalDataCollector = PersonalDataCollector;

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