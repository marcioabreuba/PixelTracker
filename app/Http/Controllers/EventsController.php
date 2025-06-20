<?php

namespace App\Http\Controllers;

use App\Events\PageView;
use App\Events\ViewContent;
use App\Events\ViewList;
use App\Events\ViewHome;
use App\Events\ViewCart;
use App\Events\Search;
use App\Events\Lead;
use App\Events\AddToWishlist;
use App\Events\AddToCart;
use App\Events\InitiateCheckout;
use App\Events\Purchase;
use App\Events\Scroll_25;
use App\Events\Scroll_50;
use App\Events\Scroll_75;
use App\Events\Scroll_90;
use App\Events\Timer_1min;
use App\Events\PlayVideo;
use App\Events\ViewVideo_25;
use App\Events\ViewVideo_50;
use App\Events\ViewVideo_75;
use App\Events\ViewVideo_90;
use Esign\ConversionsApi\Facades\ConversionsApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GeoIp2\WebService\Client;
use GeoIp2\Database\Reader;
use Esign\ConversionsApi\Objects\DefaultUserData;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\Content;
use Illuminate\Support\Facades\Config;
use App\Models\User;

class EventsController extends Controller
{
    /**
     * Obter o IP real do cliente considerando proxies/load balancers
     * Baseado na lógica eficaz: primeiro IP do X-Forwarded-For é sempre o cliente real
     */
    private function getRealClientIP($request)
    {
        // PRIORIDADE 1: X-Forwarded-For (cliente real + histórico de proxies)
        $xForwardedFor = $request->header('X-Forwarded-For');
        if (!empty($xForwardedFor)) {
            // Split para separar os IPs e pegar o primeiro (cliente real)
            $ips = explode(',', $xForwardedFor);
            $clientIp = trim($ips[0]); // Primeiro IP é sempre o cliente real
            
            // Validar se é um IP válido
            if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                Log::error('IP Real do Cliente (X-Forwarded-For):', [
                    'client_ip' => $clientIp,
                    'full_header' => $xForwardedFor,
                    'all_ips' => array_map('trim', $ips)
                ]);
                return $clientIp;
            }
        }

        // PRIORIDADE 2: Outros headers de proxy
        $headers = [
            'CF-Connecting-IP',     // Cloudflare
            'True-Client-IP',       // Cloudflare Enterprise
            'X-Real-IP',            // Nginx
            'X-Client-IP',          // Proxy
            'X-Forwarded',          // Proxy
            'Forwarded-For',        // Forwarded
            'Forwarded'             // Forwarded
        ];

        foreach ($headers as $headerName) {
            $ip = $request->header($headerName);
            if (!empty($ip)) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    Log::error('IP Encontrado em Header Alternativo:', [
                        'header' => $headerName,
                        'ip' => $ip
                    ]);
                    return $ip;
                }
            }
        }

        // PRIORIDADE 3: Fallback para request IP
        $fallbackIp = $request->ip();
        Log::error('Usando IP Request como Fallback:', [
            'fallback_ip' => $fallbackIp
        ]);
        return $fallbackIp;
    }

    public function send(Request $request)
    {
        // Debug logs para identificar o problema
        Log::error('=== DEBUG EVENTS CONTROLLER ===');
        Log::error('Request data:', ['data' => $request->all()]);
        Log::error('Content ID:', ['contentId' => $request->post('contentId')]);
        Log::error('Config domains:', ['domains' => config('conversions.domains')]);
        
        // Debug completo de headers para entender o que está disponível
        Log::error('=== HEADERS DEBUG ===', [
            'all_headers' => $request->headers->all(),
            'server_vars' => array_filter($_SERVER, function($key) {
                return strpos($key, 'HTTP_') === 0 || in_array($key, ['REMOTE_ADDR', 'SERVER_ADDR']);
            }, ARRAY_FILTER_USE_KEY)
        ]);
        
        // Debug específico de IP
        $detectedIp = $this->getRealClientIP($request);
        Log::error('=== IP DETECTION RESULT ===', [
            'detected_ip' => $detectedIp,
            'request_ip' => $request->ip(),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
            'x_real_ip' => $request->header('X-Real-IP')
        ]);
        
        try {
            // Executar o login no GeoLite
            // ==================================================
            $geoipPath = storage_path('app/geoip/GeoLite2-City.mmdb');
            if (!file_exists($geoipPath) || filesize($geoipPath) < 100) {
                throw new \Exception('GeoIP database not available');
            }
            $reader = new Reader($geoipPath);
            $ip = $this->getRealClientIP($request);
            $record = $reader->city($ip);
            
            // Obter todos os dados com o GeoLite
            // ==================================================
            $country = strtolower($record->country->isoCode);
            $state = strtolower($record->mostSpecificSubdivision->isoCode);
            $city = strtolower($record->city->name);
            $postalCode = $record->postal->code;
            
            // Debug da geolocalização
            Log::error('=== GEOIP RESULT ===', [
                'ip_used' => $ip,
                'country' => $country,
                'state' => $state,
                'city_original' => $record->city->name,
                'city_processed' => $city,
                'postal_code' => $postalCode,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude
            ]);

            // Substitui acentos manualmente
            // ==================================================
            $city = strtr($city, [
                'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
                'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o',
                'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
                'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a',
                'É' => 'e', 'Ê' => 'e', 'Í' => 'i', 'Ó' => 'o',
                'Ô' => 'o', 'Õ' => 'o', 'Ú' => 'u', 'Ç' => 'c'
            ]);
            $city = preg_replace('/[^a-z]/', '', $city); 

            // Colocar hash nos dados
            // ==================================================
            $hashedCountry = hash('sha256', $country);
            $hashedState = hash('sha256', $state);
            $hashedCity = hash('sha256', $city);
            $hashedPostalCode = hash('sha256', $postalCode);
        } catch (\Exception $e) {
            $country = null;
            $state = null;
            $city = null;
            $postalCode = null;
            $hashedCountry = null;
            $hashedState = null;
            $hashedCity = null;
            $hashedPostalCode = null;
            logger()->error('Erro ao consultar o GeoIP: ' . $e->getMessage());
        }
        try {
            // Apenas para quem usa a minha Api
            $contentId = $request->post('contentId');
            $domains = config('conversions.domains');
            if (isset($domains[$contentId])) {
                $config = $domains[$contentId];
                Config::set('conversions-api.pixel_id', $config['pixel_id']);
                Config::set('conversions-api.access_token', $config['access_token']);
                Config::set('conversions-api.test_code', $config['test_code']);
            } else {
                Log::info('[ERROR][EVENTS] Não achou o produto no banco de dados: ' . $contentId);
            }
            
            $request->merge([
                'ph' => preg_replace('/\D/', '', $request->input('ph'))
            ]);

            $validatedData = $request->validate([
                'eventType' => 'required|string|in:Init,PageView,ViewContent,ViewList,ViewHome,ViewCart,Search,Lead,AddToWishlist,AddToCart,InitiateCheckout,Purchase,Scroll_25,Scroll_50,Scroll_75,Scroll_90,Timer_1min,PlayVideo,ViewVideo_25,ViewVideo_50,ViewVideo_75,ViewVideo_90',
                'event_source_url' => 'nullable|string',
                '_fbc' => 'nullable|string', 
                '_fbp' => 'nullable|string',
                'userId' => 'nullable|string',
                'fn' => 'nullable|string|max:255',
                'ln' => 'nullable|string|max:255',
                'em' => 'nullable|email|max:255',
                'ph' => 'nullable|string|max:15',
            ]);

            $eventType = $validatedData['eventType'];
            $event_source_url = $validatedData['event_source_url'] ?? '';
            $_fbc = $validatedData['_fbc'] ?? '';
            $_fbp = $validatedData['_fbp'] ?? '';
            $userId = $validatedData['userId'] ?? '';
            
            $initData = ConversionsApi::getUserData();
            
            if ($eventType == "Init") {
                return response()->json([
                    'ct' => $city,
                    'st' => $state,
                    'zp' => $postalCode,
                    'country' => $country,
                    'client_ip_address' => $initData->getClientIpAddress(),
                    'client_user_agent' => $initData->getClientUserAgent(),
                    'fbc' => $_fbc,
                    'fbp' => $_fbp,
                    'external_id' => $userId
                ]);
            } elseif ($eventType == "PageView") {
                $user = User::where('external_id', $userId)->first();
                if (!$user) {
                    User::create([
                        'content_id' => $contentId,
                        'external_id' => $userId,
                        'client_ip_address' => $initData->getClientIpAddress(),
                        'client_user_agent' => $initData->getClientUserAgent(),
                        'fbp' => $_fbp,
                        'fbc' => $_fbc,
                        'country' => $country,
                        'st' => $state,
                        'ct' => $city,
                        'zp' => $postalCode,
                        'fn' => $validatedData['fn'] ?? '',
                        'ln' => $validatedData['ln'] ?? '',
                        'em' => $validatedData['em'] ?? '',
                        'ph' => $validatedData['ph'] ?? '',
                    ]);
                }
            }

            // Cria dinamicamente o evento com base no tipo
            $eventClass = "App\\Events\\{$eventType}";
            if (!class_exists($eventClass)) {
                return response()->json(['error' => 'Tipo de evento inválido.'], 400);
            }

            $event = $eventClass::create()
                ->setEventSourceUrl($event_source_url)
                ->setCustomData(
                    (new CustomData())->setContentIds([$contentId])
                );
            $eventID = $event->getEventId();

            $advancedMatching = $event->getUserData()
                ->setFbc($_fbc)
                ->setFbp($_fbp)
                ->setState($state)
                ->setCountryCode($country)
                ->setCity($city)
                ->setZipCode($postalCode)
                ->setExternalId($userId);

            if (isset($validatedData['fn']) && !empty($validatedData['fn'])) {
                $advancedMatching->setFirstName($validatedData['fn']);
            }

            if (isset($validatedData['ln']) && !empty($validatedData['ln'])) {
                $advancedMatching->setLastName($validatedData['ln']);
            }

            if (isset($validatedData['em']) && !empty($validatedData['em'])) {
                $advancedMatching->setEmail($validatedData['em']);
            }

            if (isset($validatedData['ph']) && !empty($validatedData['ph'])) {
                $advancedMatching->setPhone($validatedData['ph']);
            }

            $log = [
                'event_id' => $event->getEventId(),
                'event_name' => $event->getEventName(),
                'event_time' => $event->getEventTime(),
                'event_source_url' => $event->getEventSourceUrl(),
                'user_data' => [
                    'client_user_agent' => $event->getUserData()->getClientUserAgent(),
                    'client_ip_address' => $event->getUserData()->getClientIpAddress(),
                    'fbc' => $event->getUserData()->getFbc(),
                    'fbp' => $event->getUserData()->getFbp(),
                    'external_id' => $userId,
                    'country' => $advancedMatching->getCountryCode(),
                    'state' => $advancedMatching->getState(),
                    'city' => $advancedMatching->getCity(),
                    'postal_code' => $advancedMatching->getZipCode(),
                    'fn' => $validatedData['fn'] ?? '',
                    'ln' => $validatedData['ln'] ?? '',
                    'em' => $validatedData['em'] ?? '',
                    'ph' => $validatedData['ph'] ?? '',
                ],
            ];

            $event->setUserData($advancedMatching);
            ConversionsApi::addEvent($event)->sendEvents();

            Log::channel('Events')->info(json_encode($log, JSON_PRETTY_PRINT));

            return response()->json(['eventID' => $eventID, 'external_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('Erro no envio do evento:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Erro interno no servidor.'], 500);
        }
    }
}