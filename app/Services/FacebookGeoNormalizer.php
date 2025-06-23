<?php

namespace App\Services;

class FacebookGeoNormalizer
{
    /**
     * Mapeamento de estados brasileiros para códigos de 2 letras
     */
    private static array $brazilianStates = [
        'acre' => 'ac',
        'alagoas' => 'al',
        'amapá' => 'ap',
        'amazonas' => 'am',
        'bahia' => 'ba',
        'ceará' => 'ce',
        'distrito federal' => 'df',
        'espírito santo' => 'es',
        'goiás' => 'go',
        'maranhão' => 'ma',
        'mato grosso' => 'mt',
        'mato grosso do sul' => 'ms',
        'minas gerais' => 'mg',
        'pará' => 'pa',
        'paraíba' => 'pb',
        'paraná' => 'pr',
        'pernambuco' => 'pe',
        'piauí' => 'pi',
        'rio de janeiro' => 'rj',
        'rio grande do norte' => 'rn',
        'rio grande do sul' => 'rs',
        'rondônia' => 'ro',
        'roraima' => 'rr',
        'santa catarina' => 'sc',
        'são paulo' => 'sp',
        'sergipe' => 'se',
        'tocantins' => 'to'
    ];

    /**
     * Mapeamento de estados americanos (exemplos comuns)
     */
    private static array $usStates = [
        'california' => 'ca',
        'new york' => 'ny',
        'texas' => 'tx',
        'florida' => 'fl',
        'illinois' => 'il',
        'pennsylvania' => 'pa',
        'ohio' => 'oh',
        'georgia' => 'ga',
        'north carolina' => 'nc',
        'michigan' => 'mi'
    ];

    /**
     * Normaliza dados do Nominatim para padrão Facebook
     */
    public static function normalize(array $nominatimData): array
    {
        $address = $nominatimData['address'] ?? [];
        
        return [
            'country' => self::normalizeCountry($address['country_code'] ?? ''),
            'state' => self::normalizeState(
                $address['state'] ?? '', 
                $address['country_code'] ?? ''
            ),
            'city' => self::normalizeCity(
                $address['city'] ?? $address['town'] ?? $address['village'] ?? ''
            ),
            'postal_code' => self::normalizePostalCode($address['postcode'] ?? ''),
            'latitude' => $nominatimData['lat'] ?? null,
            'longitude' => $nominatimData['lon'] ?? null
        ];
    }

    /**
     * Normaliza country code para padrão Facebook
     * Formato: 2 letras minúsculas (ISO 3166-1 alpha-2)
     */
    private static function normalizeCountry(string $country): ?string
    {
        if (empty($country)) {
            return null;
        }

        // Remove caracteres especiais e converte para minúsculo
        $normalized = strtolower(preg_replace('/[^a-zA-Z]/', '', $country));
        
        // Deve ter exatamente 2 letras
        return strlen($normalized) === 2 ? $normalized : null;
    }

    /**
     * Normaliza state para padrão Facebook
     * Formato: código de 2 letras minúsculo
     */
    private static function normalizeState(string $state, string $countryCode): ?string
    {
        if (empty($state)) {
            return null;
        }

        $stateLower = strtolower(trim($state));
        $countryLower = strtolower($countryCode);

        // Mapeamento específico por país
        switch ($countryLower) {
            case 'br':
                return self::$brazilianStates[$stateLower] ?? self::extractStateCode($state);
                
            case 'us':
                return self::$usStates[$stateLower] ?? self::extractStateCode($state);
                
            default:
                return self::extractStateCode($state);
        }
    }

    /**
     * Extrai código do estado (fallback)
     */
    private static function extractStateCode(string $state): ?string
    {
        // Remove tudo exceto letras (padrão Facebook)
        $cleaned = strtolower(preg_replace('/[^a-zA-Z]/', '', $state));
        
        // Se já tem 2 letras, retorna
        if (strlen($cleaned) === 2) {
            return $cleaned;
        }
        
        // Se tem mais de 2, pega as 2 primeiras
        if (strlen($cleaned) > 2) {
            return substr($cleaned, 0, 2);
        }
        
        return null;
    }

    /**
     * Normaliza city para padrão Facebook
     * Formato: minúsculo, sem espaços ou pontuação
     */
    private static function normalizeCity(string $city): ?string
    {
        if (empty($city)) {
            return null;
        }

        // Remove números, espaços, hífens, parênteses e pontuação
        $normalized = strtolower(preg_replace('/[0-9.\s\-()]/', '', $city));
        
        // Remove acentos
        $normalized = self::removeAccents($normalized);
        
        return !empty($normalized) ? $normalized : null;
    }

    /**
     * Normaliza postal code para padrão Facebook
     * Formato: sem hífen, só números
     */
    private static function normalizePostalCode(string $postalCode): ?string
    {
        if (empty($postalCode)) {
            return null;
        }

        // Remove espaços
        $normalized = preg_replace('/\s/', '', $postalCode);
        
        // Se tem hífen, pega só a primeira parte
        $parts = explode('-', $normalized);
        $normalized = $parts[0];
        
        // Remove tudo exceto números
        $normalized = preg_replace('/[^0-9]/', '', $normalized);
        
        return !empty($normalized) ? $normalized : null;
    }

    /**
     * Remove acentos de strings
     */
    private static function removeAccents(string $string): string
    {
        $accents = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n'
        ];
        
        return strtr($string, $accents);
    }

    /**
     * Faz requisição para Nominatim com rate limiting
     */
    public static function getLocationFromCoordinates(float $lat, float $lon): ?array
    {
        // Rate limiting: máximo 1 request por segundo
        $cacheKey = "nominatim_rate_limit";
        $lastRequest = cache($cacheKey);
        
        if ($lastRequest && (time() - $lastRequest) < 1) {
            sleep(1);
        }
        
        cache([$cacheKey => time()], 60);

        // URL da API Nominatim
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&addressdetails=1";

        // Headers obrigatórios
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PixelTracker/1.0 (contato@pixeltracker.com)',
                ],
                'timeout' => 10
            ]
        ]);

        try {
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                PixelLogger::logError('Nominatim API', 'Falha na requisição HTTP');
                return null;
            }

            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                PixelLogger::logError('Nominatim API', 'Resposta JSON inválida');
                return null;
            }

            // Log da resposta do Nominatim
            PixelLogger::logNominatimResponse($lat, $lon, $data);
            
            return self::normalize($data);
            
        } catch (\Exception $e) {
            PixelLogger::logError('Nominatim API', $e->getMessage());
            return null;
        }
    }
} 