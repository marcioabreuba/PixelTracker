<?php

namespace App\Http\Controllers;

use App\Events\Purchase;
use App\Events\AddToCart;
use App\Events\InitiateCheckout;
use App\Models\User;
use Esign\ConversionsApi\Facades\ConversionsApi;
use FacebookAds\Object\ServerSide\CustomData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Services\PixelLogger;

class ShopifyController extends Controller
{
    public function purchase(Request $request)
    {
        try {
            $json = $request->all();
            
            // Log do início do processamento Shopify
            PixelLogger::logEventStart('Purchase', $json, 'SHOPIFY');

            // Extrair dados do cliente
            $customer = $json['customer'] ?? [];
            $billing_address = $json['billing_address'] ?? [];
            
            $fullName = strtolower(trim($customer['first_name'] . ' ' . $customer['last_name']));
            $names = explode(' ', $fullName);
            $fn = $names[0] ?? '';
            $ln = count($names) > 1 ? $names[count($names) - 1] : '';
            $em = strtolower($customer['email'] ?? '');
            $ph = preg_replace('/\D/', '', $billing_address['phone'] ?? '');
            
            // Dados da compra
            $order_id = $json['id'] ?? '';
            $order_number = $json['order_number'] ?? '';
            $currency = $json['currency'] ?? 'BRL';
            $total_price = $json['total_price'] ?? 0;
            
            // Extrair external_id das propriedades customizadas ou referrer
            $external_id = $this->extractExternalId($json);

            // Buscar ou criar usuário
            $user = User::where('external_id', $external_id)->first();
            if ($user) {
                $user->update([
                    'fn' => $fn,
                    'ln' => $ln,
                    'em' => $em,
                    'ph' => $ph,
                ]);
            } else {
                // Se não encontrar, criar um novo usuário básico
                $user = User::create([
                    'fn' => $fn,
                    'ln' => $ln,
                    'em' => $em,
                    'ph' => $ph,
                    'external_id' => $external_id ?: 'shopify_' . $order_id,
                    'content_id' => 'shopify_store', // Você pode personalizar isso
                ]);
            }

            $contentId = $user->content_id ?? 'shopify_store';
            $external_id = $user->external_id ?? '';
            $client_ip_address = $user->client_ip_address ?? '';
            $client_user_agent = $user->client_user_agent ?? '';
            $fbp = $user->fbp ?? '';
            $fbc = $user->fbc ?? '';
            $country = $user->country ?? strtolower($billing_address['country_code'] ?? '');
            $st = $user->st ?? strtolower($billing_address['province_code'] ?? '');
            $ct = $user->ct ?? strtolower($billing_address['city'] ?? '');
            $zp = $user->zp ?? ''; // Sempre usar apenas o zip do geoIP

            // Configurar credenciais do Facebook
            $domains = config('conversions.domains');
            if (isset($domains[$contentId])) {
                $config = $domains[$contentId];
                Config::set('conversions-api.pixel_id', $config['pixel_id']);
                Config::set('conversions-api.access_token', $config['access_token']);
                Config::set('conversions-api.test_code', $config['test_code']);
                
                // Log da configuração do pixel
                PixelLogger::logPixelConfig($contentId, $config);
            } else {
                PixelLogger::logError('Configuração Pixel Shopify', 'Content ID não encontrado: ' . $contentId);
            }

            // Criar evento de Purchase
            $event = Purchase::create();
            $advancedMatching = $event->getUserData()
                ->setExternalId($external_id)
                ->setClientIpAddress($client_ip_address)
                ->setClientUserAgent($client_user_agent)
                ->setFbp($fbp)
                ->setFbc($fbc)
                ->setCountryCode($country)
                ->setState($st)
                ->setCity($ct)
                ->setZipCode($zp)
                ->setFirstName($fn)
                ->setLastName($ln)
                ->setEmail($em)
                ->setPhone($ph);
            
            $event->setUserData($advancedMatching);
            $event->setCustomData(
                (new CustomData())
                    ->setContentIds([$contentId])
                    ->setCurrency($currency)
                    ->setValue($total_price)
                    ->setOrderId($order_id)
            );

            // Log específico do evento Shopify
            PixelLogger::logShopifyEvent('Purchase', [
                'order_id' => $order_id,
                'order_number' => $order_number,
                'total_price' => $total_price,
                'currency' => $currency,
                'customer_email' => $em
            ], [
                'external_id' => $external_id,
                'fn' => $fn,
                'ln' => $ln,
                'em' => $em,
                'ph' => $ph
            ], [
                'event_id' => $event->getEventId(),
                'pixel_id' => config('conversions-api.pixel_id'),
                'success' => true
            ]);

            ConversionsApi::addEvent($event)->sendEvents();

            // Log de sucesso
            PixelLogger::logEventSuccess('Purchase', $event->getEventId(), $external_id);

            return response()->json(['message' => 'Webhook do Shopify processado com sucesso']);
        } catch (\Exception $e) {
            PixelLogger::logError('Webhook Shopify', $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => 'Erro interno no servidor'], 500);
        }
    }

    public function addToCart(Request $request)
    {
        try {
            // Processar evento de AddToCart do Shopify
            // Este endpoint será chamado via JavaScript do tema
            $validatedData = $request->validate([
                'external_id' => 'required|string',
                'product_id' => 'required|string',
                '_fbc' => 'nullable|string',
                '_fbp' => 'nullable|string',
                'event_source_url' => 'nullable|string',
            ]);

            $user = User::where('external_id', $validatedData['external_id'])->first();
            if (!$user) {
                return response()->json(['error' => 'Usuário não encontrado'], 404);
            }

            $contentId = $user->content_id ?? 'shopify_store';
            
            // Configurar credenciais
            $domains = config('conversions.domains');
            if (isset($domains[$contentId])) {
                $config = $domains[$contentId];
                Config::set('conversions-api.pixel_id', $config['pixel_id']);
                Config::set('conversions-api.access_token', $config['access_token']);
                Config::set('conversions-api.test_code', $config['test_code']);
            }

            $event = AddToCart::create()
                ->setEventSourceUrl($validatedData['event_source_url'])
                ->setCustomData(
                    (new CustomData())->setContentIds([$validatedData['product_id']])
                );

            $advancedMatching = $event->getUserData()
                ->setFbc($validatedData['_fbc'] ?? $user->fbc)
                ->setFbp($validatedData['_fbp'] ?? $user->fbp)
                ->setExternalId($user->external_id)
                ->setClientIpAddress($user->client_ip_address)
                ->setClientUserAgent($user->client_user_agent)
                ->setCountryCode($user->country)
                ->setState($user->st)
                ->setCity($user->ct)
                ->setZipCode($user->zp)
                ->setFirstName($user->fn)
                ->setLastName($user->ln)
                ->setEmail($user->em)
                ->setPhone($user->ph);

            $event->setUserData($advancedMatching);

            ConversionsApi::addEvent($event)->sendEvents();

            return response()->json(['success' => true, 'event_id' => $event->getEventId()]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar AddToCart do Shopify:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno'], 500);
        }
    }

    private function extractExternalId($orderData)
    {
        // Tentar extrair external_id de várias fontes possíveis
        
        // 1. De propriedades customizadas do pedido
        if (isset($orderData['note_attributes'])) {
            foreach ($orderData['note_attributes'] as $attribute) {
                if ($attribute['name'] === 'external_id' || $attribute['name'] === '_external_id') {
                    return $attribute['value'];
                }
            }
        }
        
        // 2. Do referrer ou landing_site
        if (isset($orderData['landing_site'])) {
            $parsed = parse_url($orderData['landing_site']);
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $params);
                if (isset($params['external_id'])) {
                    return $params['external_id'];
                }
            }
        }
        
        // 3. De tags do cliente
        if (isset($orderData['customer']['tags'])) {
            $tags = explode(',', $orderData['customer']['tags']);
            foreach ($tags as $tag) {
                if (strpos($tag, 'external_id:') === 0) {
                    return str_replace('external_id:', '', trim($tag));
                }
            }
        }
        
        return null;
    }
} 