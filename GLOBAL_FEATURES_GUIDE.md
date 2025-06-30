# 🌍 Guia de Funcionalidades Globais - PixelTracker

## ✅ Funcionalidades Implementadas

O **PixelTracker** agora possui suporte completo para uso global, com detecção automática de país, moeda, idioma e campos de formulário internacionais.

---

## 🚀 **1. Detecção Automática de Telefone por País**

### ❌ Antes (Limitado ao Brasil):
```javascript
// Forçava código +55 sempre
if (ph && !ph.startsWith('55')) {
    ph = '55' + ph;
}
```

### ✅ Agora (Global):
```javascript
// Detecta automaticamente baseado no país
ph = normalizePhoneByCountry(phoneField.value);
```

### 📋 **Países Suportados:**
- **Brasil**: +55
- **Estados Unidos/Canadá**: +1  
- **Reino Unido**: +44
- **França**: +33
- **Alemanha**: +49
- **Espanha**: +34
- **Portugal**: +351
- **E mais 25+ países**

---

## 💰 **2. Detecção Automática de Moeda**

### ❌ Antes (Hardcoded BRL):
```javascript
productData.currency = 'BRL';
cartData.currency = 'BRL';
```

### ✅ Agora (Baseado no País):
```javascript
productData.currency = getCurrencyByCountry();
cartData.currency = getCurrencyByCountry();
```

### 💱 **Moedas Suportadas:**
- **Brasil**: BRL
- **Estados Unidos**: USD
- **Eurozona**: EUR (França, Alemanha, Espanha, etc.)
- **Reino Unido**: GBP
- **Japão**: JPY
- **Canadá**: CAD
- **E mais 30+ moedas**

---

## 🗣️ **3. Detecção Automática de Idioma**

### ❌ Antes (Hardcoded pt-BR):
```javascript
pixelData.language = 'pt-BR';
```

### ✅ Agora (Baseado no Navegador):
```javascript
pixelData.language = detectUserLanguage();
```

### 🌐 **Idiomas Suportados:**
- **Português**: pt-BR, pt-PT
- **Inglês**: en-US, en-GB
- **Espanhol**: es-ES, es-MX, es-AR
- **Francês**: fr-FR, fr-CA
- **Alemão**: de-DE
- **E mais 15+ idiomas**

---

## 📝 **4. Campos de Formulário Internacionais**

### ❌ Antes (Apenas Português):
```javascript
const nameField = document.querySelector('[name="nome"], [name="NOME"]');
const lastNameField = document.querySelector('[name="sobrenome"]');
```

### ✅ Agora (Multilíngue):
```javascript
const nameField = document.querySelector(INTERNATIONAL_FIELD_SELECTORS.firstName);
const lastNameField = document.querySelector(INTERNATIONAL_FIELD_SELECTORS.lastName);
```

### 📋 **Campos Suportados:**

#### **Nome:**
- Português: `nome`, `primeiro_nome`
- Inglês: `first_name`, `fname`, `given_name`
- Espanhol: `nombre`, `primer_nombre`
- Francês: `prenom`
- Alemão: `vorname`

#### **Sobrenome:**
- Português: `sobrenome`, `ultimo_nome`
- Inglês: `last_name`, `surname`, `family_name`
- Espanhol: `apellido`, `apellidos`
- Francês: `nom_famille`
- Alemão: `nachname`

#### **Email (Universal):**
- `email`, `e-mail`, `mail`, `correo`

#### **Telefone:**
- Português: `telefone`, `celular`
- Inglês: `phone`, `mobile`, `cell`
- Espanhol: `telefono`, `movil`
- Francês: `telephone`, `mobile`
- Alemão: `telefon`, `handy`

---

## 🛠️ **Como Configurar para Seu País**

### **Opção 1: Detecção Automática (Recomendado)**
O sistema detecta automaticamente baseado no IP do usuário. Nenhuma configuração necessária.

### **Opção 2: Configuração Manual**
```javascript
// Definir país manualmente (antes de carregar o script)
window.shopifyFBUserCountry = 'fr'; // França
window.shopifyFBConfig = {
    pixelId: 'SEU_PIXEL_ID',
    language: 'fr-FR', // Sobrescrever idioma
    apiUrl: 'https://sua-api.com'
};
```

---

## 📊 **Como Verificar se Está Funcionando**

### **1. Console do Navegador:**
```
🌍 País detectado pelo backend: br
✅ Idioma detectado: pt-BR
💰 Moeda detectada: BRL
📱 Telefone normalizado: 5511999999999
```

### **2. Logs do Servidor:**
- Verificar `storage/logs/PixelTracker.log`
- Procurar por `detected_country` nos eventos

### **3. Facebook Events Manager:**
- Verificar se eventos chegam com moeda correta
- Verificar se dados de localização estão corretos

---

## 🔧 **Troubleshooting**

### **Problema: País não detectado corretamente**
```javascript
// Força país manualmente
window.shopifyFBUserCountry = 'us';
```

### **Problema: Moeda incorreta**
```javascript
// Sobrescrever em orderData
window.trackShopifyPurchase({
    total_price: 100,
    currency: 'EUR', // Força EUR
    line_items: [...]
});
```

### **Problema: Campos não detectados**
Adicionar seletores customizados:
```javascript
// Antes de carregar o script
INTERNATIONAL_FIELD_SELECTORS.firstName += ', [name="seu_campo_custom"]';
```

---

## 🆕 **Funcionalidades Futuras Planejadas**

- [ ] Compliance automático GDPR/CCPA por país
- [ ] Formatação de endereços por país
- [ ] Fusos horários automáticos
- [ ] Validação de campos por padrões nacionais
- [ ] Suporte a mais 50+ países

---

## 📞 **Suporte**

Para dúvidas sobre configuração global:
1. Verificar logs em `storage/logs/PixelTraacker.log`
2. Testar com diferentes países via VPN
3. Verificar console do navegador para mensagens de debug

**O PixelTracker agora está 100% preparado para uso global! 🚀** 