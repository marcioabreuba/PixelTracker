<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HereGeocodingService
{
    private $apiKey;
    private $baseUrl;
    private $dailyLimit;
    private $pixelLogger;

    public function __construct(PixelLogger $pixelLogger)
    {
        $this->apiKey = config('services.here.api_key');
        $this->baseUrl = config('services.here.base_url');
        $this->dailyLimit = config('services.here.daily_limit');
        $this->pixelLogger = $pixelLogger;
    }

    /**
     * Busca CEP usando coordenadas via HERE API
     */
    public function reverseGeocode($latitude, $longitude)
    {
        try {
            // Verificar se API está configurada
            if (empty($this->apiKey)) {
                $this->pixelLogger->logHereApi('⚠️ HERE API KEY não configurada', [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
                return null;
            }

            // Verificar rate limit
            if (!$this->checkRateLimit()) {
                $this->pixelLogger->logHereApi('🚫 Rate limit HERE API atingido', [
                    'daily_usage' => $this->getDailyUsage(),
                    'daily_limit' => $this->dailyLimit
                ]);
                return null;
            }

            // Verificar cache primeiro
            $cacheKey = $this->getCacheKey($latitude, $longitude);
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                $this->pixelLogger->logHereApi('💾 HERE Cache HIT', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'cached_postal_code' => $cached
                ]);
                return $cached;
            }

            // Fazer request para HERE API
            $this->pixelLogger->logHereApi('🌐 HERE API Request iniciado', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'daily_usage_before' => $this->getDailyUsage()
            ]);

            $response = Http::timeout(5)->get($this->baseUrl . '/revgeocode', [
                'at' => $latitude . ',' . $longitude,
                'apikey' => $this->apiKey,
                'lang' => 'pt-BR'
            ]);

            // Incrementar contador de uso
            $this->incrementDailyUsage();

            if ($response->successful()) {
                $data = $response->json();
                $postalCode = $this->extractPostalCode($data);

                // Cache por 30 dias (CEPs não mudam frequentemente)
                Cache::put($cacheKey, $postalCode, now()->addDays(30));

                $this->pixelLogger->logHereApi('✅ HERE API Response sucesso', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'postal_code' => $postalCode,
                    'daily_usage_after' => $this->getDailyUsage(),
                    'cached_until' => now()->addDays(30)->toDateTimeString()
                ]);

                return $postalCode;
            } else {
                $this->pixelLogger->logHereApi('❌ HERE API Error', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'status_code' => $response->status(),
                    'error' => $response->body()
                ]);
                return null;
            }

        } catch (\Exception $e) {
            $this->pixelLogger->logHereApi('💥 HERE API Exception', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Extrai código postal da resposta HERE
     */
    private function extractPostalCode($data)
    {
        if (isset($data['items'][0]['address']['postalCode'])) {
            return $data['items'][0]['address']['postalCode'];
        }
        return null;
    }

    /**
     * Gera chave de cache baseada nas coordenadas
     */
    private function getCacheKey($latitude, $longitude)
    {
        // Arredondar para 4 casas decimais (precisão ~11 metros)
        $lat = round($latitude, 4);
        $lng = round($longitude, 4);
        return 'here_geocode_' . md5($lat . '_' . $lng);
    }

    /**
     * Verifica se ainda há requests disponíveis hoje
     */
    private function checkRateLimit()
    {
        $dailyUsage = $this->getDailyUsage();
        return $dailyUsage < $this->dailyLimit;
    }

    /**
     * Obtém uso diário atual
     */
    private function getDailyUsage()
    {
        $today = now()->format('Y-m-d');
        return Cache::get("here_daily_usage_{$today}", 0);
    }

    /**
     * Incrementa contador de uso diário
     */
    private function incrementDailyUsage()
    {
        $today = now()->format('Y-m-d');
        $key = "here_daily_usage_{$today}";
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->endOfDay());
    }

    /**
     * Obtém estatísticas de uso
     */
    public function getUsageStats()
    {
        $today = now()->format('Y-m-d');
        $dailyUsage = $this->getDailyUsage();
        
        return [
            'daily_usage' => $dailyUsage,
            'daily_limit' => $this->dailyLimit,
            'daily_remaining' => max(0, $this->dailyLimit - $dailyUsage),
            'daily_percentage' => round(($dailyUsage / $this->dailyLimit) * 100, 2),
            'date' => $today
        ];
    }
} 