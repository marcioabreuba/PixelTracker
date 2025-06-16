# 🚀 Guia de Deploy no Render

## 📋 Pré-requisitos

1. ✅ Conta no [Render](https://render.com) (gratuita)
2. ✅ Código no GitHub
3. ✅ Facebook Pixel ID e Access Token
4. ✅ Loja Shopify

## 🔧 Passo a Passo

### 1. **Preparar o Repositório**

1. Faça fork ou clone deste projeto
2. Suba para seu GitHub
3. Certifique-se que o `.env.example` está atualizado

### 2. **Criar Web Service no Render**

1. Acesse [Render Dashboard](https://dashboard.render.com)
2. Clique em "New +" → "Web Service"
3. Conecte seu repositório GitHub
4. Configure:
   - **Name**: `facebook-conversions-api`
   - **Branch**: `main`
   - **Runtime**: `PHP`
   - **Build Command**: `composer install --no-dev`
   - **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`

### 3. **Configurar Banco PostgreSQL**

1. No dashboard, clique em "New +" → "PostgreSQL"
2. Configure:
   - **Name**: `conversions-db`
   - **Plan**: Free
3. Anote as credenciais que serão geradas

### 4. **Configurar Variáveis de Ambiente**

No seu Web Service, vá em **Environment** e adicione:

```bash
# === APLICAÇÃO ===
APP_NAME=Facebook Conversions API
APP_ENV=production
APP_KEY=base64:SERÁ_GERADA_AUTOMATICAMENTE
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
APP_URL=https://seu-app.onrender.com

# === IDIOMA ===
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

# === LOGS ===
LOG_LEVEL=info

# === BANCO (PostgreSQL) ===
DB_CONNECTION=pgsql
DB_HOST=seu-host-postgres
DB_PORT=5432
DB_DATABASE=seu_banco
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

# === SESSÃO ===
SESSION_DOMAIN=seu-app.onrender.com
SESSION_SECURE_COOKIE=true

# === FACEBOOK (SUBSTITUA PELOS SEUS) ===
CONVERSIONS_API_ACCESS_TOKEN=EAAxxxxxxxxxxxxx
CONVERSIONS_API_PIXEL_ID=1234567890123456
CONVERSIONS_API_TEST_CODE=TEST12345

# === SHOPIFY ===
SHOPIFY_STORE_DOMAIN=sua-loja.myshopify.com

# === RENDER ===
PORT=10000
```

### 5. **Comandos de Build**

Adicione um arquivo `build.sh` na raiz do projeto:

```bash
#!/bin/bash

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Gerar chave da aplicação
php artisan key:generate --force

# Executar migrações
php artisan migrate --force

# Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Baixar GeoIP database
php artisan geoip:update

echo "Build completed successfully!"
```

E configure no Render:
- **Build Command**: `bash build.sh`

### 6. **Configurar Domínio Personalizado (Opcional)**

1. No Web Service, vá em **Settings**
2. Em **Custom Domains**, adicione seu domínio
3. Configure o DNS do seu domínio para apontar para o Render

## 🔍 **Como Obter as Credenciais**

### **Facebook Pixel ID e Access Token:**

1. Acesse [Facebook Business Manager](https://business.facebook.com)
2. Vá em **Events Manager**
3. Selecione seu Pixel
4. Em **Settings** → **Conversions API**:
   - **Pixel ID**: Copie o ID do pixel
   - **Access Token**: Gere um novo token

### **Banco PostgreSQL (Render):**

Após criar o banco PostgreSQL no Render, você receberá:
```
Host: xxxxx.render.com
Port: 5432
Database: nome_do_banco
Username: usuario
Password: senha_gerada
```

### **MaxMind GeoIP (Opcional):**

1. Crie conta em [MaxMind](https://www.maxmind.com)
2. Gere License Key
3. Anote Account ID e License Key

## 🧪 **Testando a Instalação**

### 1. **Verificar se a API está funcionando:**
```bash
curl https://seu-app.onrender.com/
```

### 2. **Testar webhook do Shopify:**
```bash
curl -X POST https://seu-app.onrender.com/webhook/shopify \
  -H "Content-Type: application/json" \
  -d '{
    "id": 12345,
    "total_price": "99.99",
    "customer": {
      "email": "test@example.com"
    }
  }'
```

### 3. **Testar eventos:**
```bash
curl -X POST https://seu-app.onrender.com/events/send \
  -H "Content-Type: application/json" \
  -d '{
    "eventType": "PageView",
    "contentId": "shopify_store",
    "external_id": "test_123"
  }'
```

## 📊 **Monitoramento**

### **Logs do Render:**
1. No dashboard, clique em seu Web Service
2. Vá na aba **Logs**
3. Monitore erros e sucessos

### **Facebook Events Manager:**
1. Acesse Facebook Business Manager
2. Vá em Events Manager
3. Verifique se os eventos estão chegando

## 🚨 **Problemas Comuns**

### **1. Build Failed**
- Verifique se `composer.json` está correto
- Certifique-se que o PHP 8.2+ está sendo usado

### **2. Database Connection Failed**
- Verifique as credenciais do PostgreSQL
- Certifique-se que o banco foi criado

### **3. Facebook Events Not Working**
- Verifique Pixel ID e Access Token
- Confirme se o domínio está verificado no Facebook

### **4. Shopify Webhook Not Receiving**
- Verifique se a URL está acessível
- Confirme se o SSL está funcionando
- Teste a URL manualmente

## 💰 **Custos no Render**

### **Plano Gratuito:**
- Web Service: Gratuito (limitações)
- PostgreSQL: Gratuito (512MB)
- **Total**: R$ 0/mês

### **Plano Pago:**
- Web Service: $7/mês
- PostgreSQL: $7/mês  
- **Total**: ~R$ 75/mês

## 🔐 **Segurança**

### **Variáveis Sensíveis:**
- Nunca commite tokens/senhas no Git
- Use sempre HTTPS em produção
- Configure CORS adequadamente

### **Webhook Security:**
- Configure `SHOPIFY_WEBHOOK_SECRET`
- Implemente verificação de assinatura se necessário

## 📞 **URLs Importantes**

Após o deploy, você terá:
- **API Base**: `https://seu-app.onrender.com`
- **Webhook Shopify**: `https://seu-app.onrender.com/webhook/shopify`
- **Script JS**: `https://seu-app.onrender.com/shopify-tracking.js`
- **Logs**: Dashboard do Render

---

**🎉 Pronto! Sua API está funcionando no Render e pronta para integrar com Shopify!** 