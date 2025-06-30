# 🎯 Estratégia Inteligente de Content IDs

## **Problema Solucionado**
O PixelTracker estava enviando `content_ids: ["shopify_store"]` genérico para vários eventos, prejudicando a otimização do Facebook Ads.

## **Nova Solução**
Implementamos uma estratégia inteligente que gera `content_ids` específicos baseados no contexto de cada evento.

---

## **🔄 ANTES vs DEPOIS**

### **❌ ANTES (Genérico)**
```javascript
// Todos os eventos usavam o mesmo ID genérico
content_ids: ["shopify_store"]
```

### **✅ DEPOIS (Inteligente)**
```javascript
// Cada evento tem IDs específicos e contextuais
ViewContent: ["variant_123456"]
Search: ["search_tenis_nike"]
ViewHome: ["homepage"]
ViewList: ["collection_sapatos", "product_101", "product_102"]
```

---

## **📋 MAPEAMENTO POR TIPO DE EVENTO**

### **🛍️ Eventos de Produto**
- **ViewContent**: `["variant_123456"]` ou `["product_tenis-nike"]`
- **AddToCart**: `["variant_123456"]` ou `["product_tenis-nike"]`

### **📚 Eventos de Categoria**
- **ViewList**: `["product_101", "product_102", ...]` ou `["collection_sapatos"]`

### **🛒 Eventos de Carrinho**
- **ViewCart**: `["variant_123", "variant_456"]` ou `["empty_cart"]`
- **InitiateCheckout**: `["variant_123", "variant_456"]` ou `["empty_cart"]`

### **🏠 Eventos de Página**
- **ViewHome**: `["homepage"]`
- **PageView**: `["homepage"]`, `["contact_page"]`, `["page_sobre"]`

### **🔍 Eventos de Busca**
- **Search**: `["search_tenis_nike"]`, `["search_camiseta"]`

### **📝 Eventos de Lead**
- **Lead**: `["lead_contact"]`, `["lead_homepage"]`, `["variant_123"]` (se em página de produto)

### **📈 Eventos de Engajamento**
- **Scroll_25/50/75/90**: Usa contexto da página atual
- **Timer_1min**: Usa contexto da página atual

### **🎥 Eventos de Vídeo**
- **PlayVideo**: `["video_intro"]` ou `["product_tenis-nike_video"]`
- **ViewVideo_25/50/75/90**: `["video_intro"]` ou `["product_tenis-nike_video"]`

---

## **🎯 BENEFÍCIOS DA NOVA ESTRATÉGIA**

### **1. Otimização Melhorada**
- Facebook pode correlacionar eventos com produtos/páginas específicas
- Lookalike audiences baseadas em produtos reais
- Melhor targeting automático

### **2. Debugging Facilitado**
- Content IDs descritivos facilitam identificar origem dos eventos
- Logs mais claros e informativos
- Rastreamento de performance por produto/categoria

### **3. Segmentação Inteligente**
- Públicos baseados em produtos específicos
- Retargeting por categorias de interesse
- Exclusões mais precisas

### **4. Qualidade de Dados**
- Dados contextuais ricos para o Facebook
- Eliminação de IDs genéricos
- Correlação entre eventos e produtos

---

## **🔧 IMPLEMENTAÇÃO TÉCNICA**

### **Função Principal**
```javascript
function getIntelligentContentIds(eventType, data = {}) {
    // Se já tem content_ids específicos, usar eles
    if (data.content_ids && data.content_ids.length > 0) {
        return data.content_ids;
    }
    
    // Lógica específica por tipo de evento
    switch(eventType) {
        case 'ViewContent':
        case 'AddToCart':
            return getProductContentIds();
        // ... outros casos
    }
}
```

### **Hierarquia de Fallbacks**
Cada função tem múltiplos níveis de fallback:

1. **Dados específicos** (variant ID, product ID)
2. **Dados da URL** (product handle, collection handle)
3. **Dados da página** (títulos, categorias)
4. **Fallback descritivo** (`product_unknown`, `collection_unknown`)

---

## **📊 EXEMPLOS PRÁTICOS**

### **Página de Produto: `/products/tenis-nike-air`**
```javascript
ViewContent: ["variant_123456"] // Se variant disponível
AddToCart: ["variant_123456"]
Scroll_25: ["variant_123456"]
Lead: ["variant_123456"] // Formulário na página do produto
```

### **Página de Categoria: `/collections/sapatos`**
```javascript
ViewList: ["product_101", "product_102", "product_103"] // Produtos visíveis
PageView: ["collection_sapatos"]
Search: ["search_sapatos_masculinos"]
```

### **Página Inicial: `/`**
```javascript
ViewHome: ["homepage"]
PageView: ["homepage"]
Lead: ["lead_homepage"]
Timer_1min: ["homepage"]
```

### **Carrinho com Produtos**
```javascript
ViewCart: ["variant_123", "variant_456", "variant_789"]
InitiateCheckout: ["variant_123", "variant_456", "variant_789"]
```

### **Carrinho Vazio**
```javascript
ViewCart: ["empty_cart"]
InitiateCheckout: ["empty_cart"]
```

---

## **🚀 RESULTADOS ESPERADOS**

### **Melhor Performance**
- Aumento na qualidade do Match entre eventos
- Redução do CPC por melhor otimização
- Aumento do ROAS por targeting mais preciso

### **Insights Mais Ricos**
- Análise de performance por produto
- Identificação de produtos com melhor conversão
- Otimização de catálogo baseada em dados

### **Debugging Simplificado**
- Logs mais informativos
- Identificação rápida de problemas
- Rastreamento de jornada do usuário

---

## **📝 NOTAS IMPORTANTES**

1. **Compatibilidade**: Mantém compatibilidade com API backend através do `contentId`
2. **Performance**: Funções otimizadas para não impactar carregamento
3. **Flexibilidade**: Sistema de fallbacks garante que sempre haverá um content_id válido
4. **Escalabilidade**: Fácil adição de novos tipos de eventos

---

## **🔍 MONITORAMENTO**

Para verificar se a nova estratégia está funcionando:

1. **Console do navegador**: Verificar logs de eventos
2. **Facebook Pixel Helper**: Confirmar content_ids específicos
3. **Events Manager**: Monitorar qualidade dos dados
4. **Relatórios**: Acompanhar melhorias de performance

---

**Status**: ✅ Implementado e ativo
**Versão**: 2.0 - Content IDs Inteligentes
**Data**: 2024 