# Guia de Integração: API Laravel + Shopify para Facebook Conversions API

Este guia explica como integrar sua loja Shopify com esta API Laravel para tracking avançado de eventos do Facebook através da Conversions API.

## 🎯 Funcionalidades

- ✅ Tracking completo de eventos do Facebook (PageView, AddToCart, Purchase, etc.)
- ✅ Integração via webhooks do Shopify
- ✅ Matching avançado de usuários com dados geográficos
- ✅ Suporte a múltiplos pixels do Facebook
- ✅ Tracking de scroll, tempo na página e outras interações
- ✅ Compatible com temas do Shopify

## 📋 Pré-requisitos

1. **API Laravel funcionando** em um servidor/hospedagem
2. **Acesso ao Admin do Shopify** da sua loja
3. **Facebook Pixel ID** e **Access Token** da Conversions API
4. **Conhecimento básico** de edição de temas do Shopify

## 🚀 Configuração da API

### 1. Configure suas credenciais do Facebook

Edite o arquivo `config/conversions.php`:

```php
<?php

return [
    'domains' => [
        'shopify_store' => [
            'pixel_id' => 'SEU_PIXEL_ID_AQUI',
            'access_token' => 'SEU_ACCESS_TOKEN_AQUI',
            'test_code' => 'TEST_CODE_OPCIONAL',
        ],
        
        // Você pode ter múltiplas configurações
        'minha_loja_shopify' => [
            'pixel_id' => 'OUTRO_PIXEL_ID',
            'access_token' => 'OUTRO_ACCESS_TOKEN',
            'test_code' => 'OUTRO_TEST_CODE',
        ],
    ]
];
```

### 2. Execute as migrações (se necessário)

```bash
php artisan migrate
```

### 3. Configure os logs

Certifique-se de que os logs estão configurados em `config/logging.php`:

```php
'channels' => [
    // ... outros channels
    'Events' => [
        'driver' => 'single',
        'path' => storage_path('logs/facebook-events.log'),
        'level' => 'info',
    ],
],
```

## 🛍️ Configuração no Shopify

### 1. Configurar Webhooks

No admin do Shopify, vá em **Settings > Notifications** e adicione um webhook:

- **Event**: `Order payment`
- **Format**: `JSON`
- **URL**: `https://sua-api.com/webhook/shopify`
- **API Version**: Latest

### 2. Adicionar Script de Tracking no Tema

#### Opção A: Via Editor de Código

1. No admin do Shopify, vá em **Online Store > Themes**
2. Clique em **Actions > Edit code**
3. Abra o arquivo `theme.liquid`
4. Adicione o seguinte código antes da tag `</head>`:

```html
<!-- Facebook Conversions API Tracking -->
<script>
  window.shopifyFBConfig = {
    apiUrl: 'https://sua-api.com', // Substitua pela URL da sua API
    contentId: 'shopify_store' // Deve corresponder à chave em config/conversions.php
  };
</script>
<script src="https://sua-api.com/shopify-tracking.js" defer></script>
```

#### Opção B: Upload do Script

1. Faça upload do arquivo `shopify-tracking.js` para a pasta `assets` do seu tema
2. Adicione no `theme.liquid`:

```html
<script>
  window.shopifyFBConfig = {
    apiUrl: 'https://sua-api.com',
    contentId: 'shopify_store'
  };
</script>
{{ 'shopify-tracking.js' | asset_url | script_tag }}
```

### 3. Configuração Adicional no Checkout (Opcional)

Para melhor tracking no checkout, adicione no arquivo `checkout.liquid` (se disponível):

```html
<script>
  // Coletar dados do usuário no checkout
  if (window.Shopify && Shopify.checkout && window.shopifyFBTracking) {
    const checkout = Shopify.checkout;
    window.shopifyFBTracking.collectCheckoutData({
      email: checkout.email,
      firstName: checkout.billing_address?.first_name,
      lastName: checkout.billing_address?.last_name,
      phone: checkout.billing_address?.phone
    });
  }
</script>
```

## 🔧 Configurações Avançadas

### Múltiplas Lojas/Pixels

Se você tem múltiplas lojas ou quer usar pixels diferentes por produto:

```javascript
// Configuração diferente por página/produto
window.shopifyFBConfig = {
  apiUrl: 'https://sua-api.com',
  contentId: window.location.pathname.includes('/collections/premium') ? 'loja_premium' : 'shopify_store'
};
```

### Customizar Content ID por Produto

Você pode usar diferentes content_ids baseados em produtos ou coleções:

```javascript
// No produto específico
{% if product.handle == 'produto-especial' %}
  window.shopifyFBConfig.contentId = 'produto_especial';
{% endif %}
```

### Adicionar Dados Customizados

Para adicionar propriedades customizadas aos eventos:

```javascript
// Depois que o tracking for inicializado
window.shopifyFBTracking.sendEvent('CustomEvent', {
  custom_parameter: 'valor_customizado',
  product_category: '{{ product.type }}'
});
```

## 📊 Eventos Tracking Automático

O script automaticamente rastreia:

- **PageView**: Visualização de página
- **AddToCart**: Adição ao carrinho
- **InitiateCheckout**: Início do checkout
- **Purchase**: Através de webhooks
- **Scroll_25/50/75/90**: Porcentagem de scroll
- **Timer_1min**: Tempo na página

## 🔍 Debug e Monitoramento

### 1. Logs da API

Monitore os logs em:
- `storage/logs/facebook-events.log`
- `storage/logs/laravel.log`

### 2. Console do Navegador

Abra o Developer Tools e verifique:
- Mensagens de sucesso/erro no console
- Requisições na aba Network

### 3. Facebook Events Manager

Verifique se os eventos estão chegando no Facebook Events Manager:
1. Acesse o Facebook Business Manager
2. Vá em Events Manager
3. Selecione seu pixel
4. Verifique a aba "Test Events" e "Events"

## 🚨 Troubleshooting

### Problema: Eventos não aparecem no Facebook

**Soluções:**
1. Verifique se o Pixel ID e Access Token estão corretos
2. Confirme se o webhook está funcionando (teste manual)
3. Verifique os logs da API
4. Teste em modo de desenvolvimento primeiro

### Problema: Webhook não recebe dados

**Soluções:**
1. Verifique se a URL do webhook está acessível
2. Confirme se o SSL está configurado
3. Teste a URL manualmente com curl
4. Verifique se não há firewall bloqueando

### Problema: External ID não está sendo capturado

**Soluções:**
1. Adicione parâmetro `external_id` na URL dos anúncios do Facebook
2. Configure UTM parameters
3. Use o localStorage como fallback
4. Verifique se os formulários estão sendo interceptados corretamente

## 🧪 Teste de Integração

### 1. Teste Manual da API

```bash
curl -X POST https://sua-api.com/events/send \
  -H "Content-Type: application/json" \
  -d '{
    "eventType": "PageView",
    "contentId": "shopify_store",
    "external_id": "test_user_123",
    "event_source_url": "https://sua-loja.myshopify.com/products/test"
  }'
```

### 2. Teste do Webhook

```bash
curl -X POST https://sua-api.com/webhook/shopify \
  -H "Content-Type: application/json" \
  -d '{
    "id": 12345,
    "order_number": 1001,
    "total_price": "99.99",
    "currency": "BRL",
    "customer": {
      "first_name": "João",
      "last_name": "Silva",
      "email": "joao@email.com"
    },
    "billing_address": {
      "phone": "+5511999999999",
      "country_code": "BR",
      "province_code": "SP",
      "city": "São Paulo",
      "zip": "01234-567"
    }
  }'
```

## 📈 Otimizações de Performance

1. **Cache de configurações**: A API já faz cache das configurações do Facebook
2. **Requests assíncronos**: Todos os eventos são enviados de forma assíncrona
3. **Fallbacks**: O script tem fallbacks para diferentes cenários
4. **Debouncing**: Eventos repetitivos são controlados

## 🔐 Segurança

1. **Validação de webhooks**: Considere adicionar verificação de assinatura do Shopify
2. **Rate limiting**: Implemente rate limiting se necessário
3. **HTTPS obrigatório**: Use sempre HTTPS
4. **Logs sensíveis**: Não logue dados sensíveis como tokens

## 📞 Suporte

Se você encontrar problemas:

1. Verifique os logs da API
2. Teste os endpoints manualmente
3. Confirme as configurações do Facebook
4. Verifique se os webhooks estão configurados corretamente

---

**Importante**: Este é um sistema profissional de tracking. Certifique-se de estar em compliance com as políticas do Facebook e leis de privacidade (LGPD, GDPR, etc.). 