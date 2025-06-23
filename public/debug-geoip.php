<?php
// Debug específico do GeoIP para o IP de Fortaleza
require_once __DIR__ . '/../vendor/autoload.php';

use GeoIp2\Database\Reader;

header('Content-Type: application/json');

try {
    // IP específico de Fortaleza que mostrou CEP no MaxMind
    $testIP = '186.222.168.201';
    
    $geoipPath = __DIR__ . '/../storage/app/geoip/GeoLite2-City.mmdb';
    
    echo json_encode([
        'debug_info' => [
            'ip_testado' => $testIP,
            'arquivo_geoip' => $geoipPath,
            'arquivo_existe' => file_exists($geoipPath),
            'tamanho_arquivo' => file_exists($geoipPath) ? filesize($geoipPath) : 0,
            'data_modificacao' => file_exists($geoipPath) ? date('Y-m-d H:i:s', filemtime($geoipPath)) : null,
        ]
    ], JSON_PRETTY_PRINT);
    
    if (!file_exists($geoipPath)) {
        throw new \Exception('Arquivo GeoIP não encontrado');
    }
    
    $reader = new Reader($geoipPath);
    $record = $reader->city($testIP);
    
    // Capturar TODOS os dados disponíveis
    $allData = [
        'ip_consultado' => $testIP,
        'dados_raw' => [
            // Country
            'country' => [
                'isoCode' => $record->country->isoCode,
                'name' => $record->country->name,
                'names' => $record->country->names,
            ],
            // Subdivisions (Estados)
            'subdivisions' => [
                'count' => count($record->subdivisions),
                'mostSpecific' => [
                    'isoCode' => $record->mostSpecificSubdivision->isoCode,
                    'name' => $record->mostSpecificSubdivision->name,
                    'names' => $record->mostSpecificSubdivision->names,
                ],
                'all' => []
            ],
            // City
            'city' => [
                'name' => $record->city->name,
                'names' => $record->city->names,
            ],
            // Postal (CEP) - FOCO AQUI
            'postal' => [
                'code' => $record->postal->code,
                'confidence' => $record->postal->confidence ?? null,
            ],
            // Location
            'location' => [
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'accuracyRadius' => $record->location->accuracyRadius,
                'timeZone' => $record->location->timeZone,
            ],
            // Traits
            'traits' => [
                'isAnonymousProxy' => $record->traits->isAnonymousProxy,
                'isSatelliteProvider' => $record->traits->isSatelliteProvider,
            ]
        ],
        'dados_processados' => [
            'country' => strtolower($record->country->isoCode),
            'state' => strtolower($record->mostSpecificSubdivision->isoCode),
            'city' => strtolower($record->city->name),
            'postal_code' => $record->postal->code,
            'latitude' => $record->location->latitude,
            'longitude' => $record->location->longitude,
        ]
    ];
    
    // Adicionar todas as subdivisões
    foreach ($record->subdivisions as $subdivision) {
        $allData['dados_raw']['subdivisions']['all'][] = [
            'isoCode' => $subdivision->isoCode,
            'name' => $subdivision->name,
            'names' => $subdivision->names,
        ];
    }
    
    echo "\n\n" . json_encode($allData, JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    echo json_encode([
        'erro' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
} 