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
use App\Services\PixelLogger;
use App\Services\FacebookGeoNormalizer;

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
                    return $ip;
                }
            }
        }

        // PRIORIDADE 3: Fallback para request IP
        return $request->ip();
    }

    public function send(Request $request)
    {
        // Log estruturado do início do evento
        $eventType = $request->input('eventType', 'Unknown');
        PixelLogger::logEventStart($eventType, $request->all());
        
        // Detectar IP do cliente e logar
        $detectedIp = $this->getRealClientIP($request);
        PixelLogger::logIPDetection($request, $detectedIp);
        
        try {
            // Executar o processamento GeoIP
            $geoipPath = storage_path('app/geoip/GeoLite2-City.mmdb');
            if (!file_exists($geoipPath) || filesize($geoipPath) < 100) {
                throw new \Exception('GeoIP database not available');
            }
            $reader = new Reader($geoipPath);
            $ip = $detectedIp;
            $record = $reader->city($ip);
            
            // Obter todos os dados com o GeoLite
            $country = strtolower($record->country->isoCode);
            $state = strtolower($record->mostSpecificSubdivision->isoCode);
            $city = strtolower($record->city->name);
            $postalCode = $record->postal->code;

            // Substitui acentos manualmente
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
            $hashedCountry = hash('sha256', $country);
            $hashedState = hash('sha256', $state);
            $hashedCity = hash('sha256', $city);
            $hashedPostalCode = $postalCode ? hash('sha256', $postalCode) : null;
            
            // Log estruturado dos dados GeoIP
            PixelLogger::logGeoIP($ip, [
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'postal_code' => $postalCode,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'country_hash' => $hashedCountry,
                'state_hash' => $hashedState,
                'city_hash' => $hashedCity,
                'postal_hash' => $hashedPostalCode
            ]);
        } catch (\Exception $e) {
            $country = null;
            $state = null;
            $city = null;
            $postalCode = null;
            $hashedCountry = null;
            $hashedState = null;
            $hashedCity = null;
            $hashedPostalCode = null;
            PixelLogger::logError('GeoIP', $e->getMessage());
        }

        // FALLBACK: Se GeoIP não retornou CEP, tentar Nominatim
        if (empty($postalCode) && !empty($record) && isset($record->location->latitude) && isset($record->location->longitude)) {
            try {
                $nominatimData = FacebookGeoNormalizer::getLocationFromCoordinates(
                    $record->location->latitude, 
                    $record->location->longitude
                );
                
                if ($nominatimData && !empty($nominatimData['postal_code'])) {
                    // Usar dados do Nominatim apenas para complementar dados ausentes
                    $postalCode = $nominatimData['postal_code'];
                    $hashedPostalCode = hash('sha256', $postalCode);
                    
                    // Se outros dados estiverem vazios, usar do Nominatim também
                    if (empty($country)) {
                        $country = $nominatimData['country'];
                        $hashedCountry = $country ? hash('sha256', $country) : null;
                    }
                    if (empty($state)) {
                        $state = $nominatimData['state'];
                        $hashedState = $state ? hash('sha256', $state) : null;
                    }
                    if (empty($city)) {
                        $city = $nominatimData['city'];
                        $hashedCity = $city ? hash('sha256', $city) : null;
                    }
                    
                    PixelLogger::logNominatimFallback('CEP ausente no GeoIP', $nominatimData);
                }
            } catch (\Exception $e) {
                PixelLogger::logError('Nominatim Fallback', $e->getMessage());
            }
        }
        try {
            // Configurar pixel usando configuração padrão do .env (single tenant)
            $contentId = $request->post('contentId');
            
            // Usar sempre configuração do .env para sistema single-tenant
            Config::set('conversions-api.pixel_id', env('CONVERSIONS_API_PIXEL_ID'));
            Config::set('conversions-api.access_token', env('CONVERSIONS_API_ACCESS_TOKEN'));
            Config::set('conversions-api.test_code', env('CONVERSIONS_API_TEST_CODE', 'TEST57660'));
            
            // Log da configuração do pixel (sucesso)
            PixelLogger::logPixelConfig($contentId, [
                'pixel_id' => env('CONVERSIONS_API_PIXEL_ID'),
                'access_token' => '***HIDDEN***',
                'test_code' => env('CONVERSIONS_API_TEST_CODE', 'TEST57660'),
                'source' => 'env_default'
            ]);
            
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
                    'external_id' => $userId,
                    // 🌍 GLOBAL: Adicionar país para funções de globalização no frontend
                    'detected_country' => $country
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

            $event->setUserData($advancedMatching);
            
            // Log antes de enviar para o Facebook
            PixelLogger::logFacebookSend($eventType, $eventID, [
                'external_id' => $userId,
                'client_ip_address' => $event->getUserData()->getClientIpAddress(),
                'client_user_agent' => $event->getUserData()->getClientUserAgent(),
                'fbc' => $_fbc,
                'fbp' => $_fbp,
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'postal_code' => $postalCode,
                'fn' => $validatedData['fn'] ?? '',
                'ln' => $validatedData['ln'] ?? '',
                'em' => $validatedData['em'] ?? '',
                'ph' => $validatedData['ph'] ?? '',
            ], [
                'content_ids' => [$contentId]
            ]);
            
            ConversionsApi::addEvent($event)->sendEvents();

            // Log de sucesso
            PixelLogger::logEventSuccess($eventType, $eventID, $userId);

            return response()->json(['eventID' => $eventID, 'external_id' => $userId]);
        } catch (\Exception $e) {
            PixelLogger::logError('Envio Evento', $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json(['error' => 'Erro interno no servidor.'], 500);
        }
    }
}