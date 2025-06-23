<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class PixelLogger
{
    /**
     * Log do início do processamento de um evento
     */
    public static function logEventStart(string $eventType, array $requestData, string $source = 'WEB')
    {
        Log::channel('PixelTracker')->info("🚀 EVENTO INICIADO", [
            'tipo' => $eventType,
            'origem' => $source,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'dados_recebidos' => [
                'contentId' => $requestData['contentId'] ?? null,
                'userId' => $requestData['userId'] ?? null,
                'event_source_url' => $requestData['event_source_url'] ?? null,
                '_fbc' => $requestData['_fbc'] ?? null,
                '_fbp' => $requestData['_fbp'] ?? null,
                'dados_usuario' => [
                    'fn' => $requestData['fn'] ?? null,
                    'ln' => $requestData['ln'] ?? null,
                    'em' => $requestData['em'] ?? null,
                    'ph' => $requestData['ph'] ?? null,
                ]
            ]
        ]);
    }

    /**
     * Log dos dados obtidos do GeoIP
     */
    public static function logGeoIP(string $ip, array $geoData)
    {
        Log::channel('PixelTracker')->info("🌍 GEOIP PROCESSADO", [
            'ip_cliente' => $ip,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'dados_geograficos' => [
                'pais' => $geoData['country'] ?? null,
                'estado' => $geoData['state'] ?? null,
                'cidade' => $geoData['city'] ?? null,
                'cep' => $geoData['postal_code'] ?? null,
                'latitude' => $geoData['latitude'] ?? null,
                'longitude' => $geoData['longitude'] ?? null,
            ],
            'dados_hash' => [
                'country_hash' => $geoData['country_hash'] ?? null,
                'state_hash' => $geoData['state_hash'] ?? null,
                'city_hash' => $geoData['city_hash'] ?? null,
                'postal_hash' => $geoData['postal_hash'] ?? null,
            ]
        ]);
    }

    /**
     * Log dos dados enviados para o Facebook
     */
    public static function logFacebookSend(string $eventType, string $eventId, array $userData, array $customData = [])
    {
        Log::channel('PixelTracker')->info("📤 ENVIADO PARA FACEBOOK", [
            'evento' => $eventType,
            'event_id' => $eventId,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'dados_usuario_enviados' => [
                'external_id' => $userData['external_id'] ?? null,
                'client_ip_address' => $userData['client_ip_address'] ?? null,
                'client_user_agent' => $userData['client_user_agent'] ?? null,
                'fbc' => $userData['fbc'] ?? null,
                'fbp' => $userData['fbp'] ?? null,
                'dados_geograficos' => [
                    'country' => $userData['country'] ?? null,
                    'state' => $userData['state'] ?? null,
                    'city' => $userData['city'] ?? null,
                    'postal_code' => $userData['postal_code'] ?? null,
                ],
                'dados_pessoais' => [
                    'fn' => $userData['fn'] ?? null,
                    'ln' => $userData['ln'] ?? null,
                    'em' => $userData['em'] ?? null,
                    'ph' => $userData['ph'] ?? null,
                ]
            ],
            'dados_customizados' => $customData
        ]);
    }

    /**
     * Log específico para eventos do Shopify
     */
    public static function logShopifyEvent(string $eventType, array $orderData, array $userData, array $facebookData)
    {
        Log::channel('PixelTracker')->info("🛒 EVENTO SHOPIFY", [
            'tipo_evento' => $eventType,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'dados_shopify' => [
                'order_id' => $orderData['order_id'] ?? null,
                'order_number' => $orderData['order_number'] ?? null,
                'total_price' => $orderData['total_price'] ?? null,
                'currency' => $orderData['currency'] ?? null,
                'customer_email' => $orderData['customer_email'] ?? null,
            ],
            'dados_usuario_processados' => [
                'external_id' => $userData['external_id'] ?? null,
                'fn' => $userData['fn'] ?? null,
                'ln' => $userData['ln'] ?? null,
                'em' => $userData['em'] ?? null,
                'ph' => $userData['ph'] ?? null,
            ],
            'dados_facebook' => [
                'event_id' => $facebookData['event_id'] ?? null,
                'pixel_id' => $facebookData['pixel_id'] ?? null,
                'success' => $facebookData['success'] ?? null,
            ]
        ]);
    }

    /**
     * Log de erro estruturado
     */
    public static function logError(string $context, string $error, array $additionalData = [])
    {
        Log::channel('PixelTracker')->error("❌ ERRO", [
            'contexto' => $context,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'erro' => $error,
            'dados_adicionais' => $additionalData
        ]);
    }

    /**
     * Log de sucesso do evento
     */
    public static function logEventSuccess(string $eventType, string $eventId, string $externalId)
    {
        Log::channel('PixelTracker')->info("✅ EVENTO CONCLUÍDO", [
            'tipo' => $eventType,
            'event_id' => $eventId,
            'external_id' => $externalId,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'status' => 'SUCCESS'
        ]);
    }

    /**
     * Log de configuração do pixel
     */
    public static function logPixelConfig(string $contentId, array $config)
    {
        Log::channel('PixelTracker')->info("⚙️ CONFIGURAÇÃO PIXEL", [
            'content_id' => $contentId,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'configuracao' => [
                'pixel_id' => $config['pixel_id'] ?? null,
                'tem_access_token' => !empty($config['access_token']),
                'tem_test_code' => !empty($config['test_code']),
            ]
        ]);
    }

    /**
     * Log de IP detection
     */
    public static function logIPDetection(Request $request, string $detectedIp)
    {
        Log::channel('PixelTracker')->info("🔍 DETECÇÃO DE IP", [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'ip_detectado' => $detectedIp,
            'headers_relevantes' => [
                'X-Forwarded-For' => $request->header('X-Forwarded-For'),
                'CF-Connecting-IP' => $request->header('CF-Connecting-IP'),
                'X-Real-IP' => $request->header('X-Real-IP'),
                'X-Client-IP' => $request->header('X-Client-IP'),
            ],
            'request_ip' => $request->ip()
        ]);
    }

    /**
     * Log de validação de dados
     */
    public static function logValidation(array $validatedData, array $errors = [])
    {
        if (empty($errors)) {
            Log::channel('PixelTracker')->info("✅ VALIDAÇÃO SUCESSO", [
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'dados_validados' => array_keys($validatedData)
            ]);
        } else {
            Log::channel('PixelTracker')->warning("⚠️ VALIDAÇÃO COM ERROS", [
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'erros' => $errors
            ]);
        }
    }

    /**
     * Log específico para HERE Geocoding API
     */
    public static function logHereApi(string $message, array $data = [])
    {
        Log::channel('PixelTracker')->info("🗺️ HERE API: {$message}", [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'here_data' => $data
        ]);
    }

    /**
     * Log de resposta da API do Facebook
     */
    public static function logFacebookResponse(array $response)
    {
        Log::channel('PixelTracker')->info("📥 RESPOSTA FACEBOOK", [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'resposta' => $response
        ]);
    }
} 