<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\HotmartController;
use App\Http\Controllers\YampiController;
use App\Http\Controllers\DigitalController;
use App\Http\Controllers\ShopifyController;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/events/send', [EventsController::class, 'send']);
Route::post('/webhook/hotmart', [HotmartController::class, 'Hotmart']);
Route::post('/webhook/yampi', [YampiController::class, 'Yampi']);
Route::post('/webhook/digital', [DigitalController::class, 'Digital']);
Route::post('/webhook/shopify', [ShopifyController::class, 'purchase']);
Route::post('/shopify/add-to-cart', [ShopifyController::class, 'addToCart']);

// Health check simples
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'app_name' => config('app.name', 'NOT SET'),
    ]);
});

// Rota de teste para debug
Route::get('/test-debug', function () {
    Log::info('=== TESTE DE DEBUG ===');
    Log::info('Esta é uma mensagem de teste');
    return response()->json(['status' => 'debug test sent']);
});

// Rota de teste para verificar configurações
Route::get('/test-config', function () {
    return response()->json([
        'app_key' => config('app.key') ? 'SET' : 'NOT SET',
        'conversions_domains' => config('conversions.domains'),
        'facebook_pixel_id' => config('conversions-api.pixel_id'),
        'facebook_access_token' => config('conversions-api.access_token') ? 'SET' : 'NOT SET',
    ]);
});

// Rota super simples - só para testar se Laravel funciona
Route::get('/simple', function () {
    return response()->json(['status' => 'LARAVEL_WORKING', 'time' => now()]);
});

// Rota para testar variáveis de ambiente
Route::get('/env-test', function () {
    return response()->json([
        'app_key_set' => !empty(config('app.key')),
        'app_env' => config('app.env'),
        'db_connection' => config('database.default'),
        'all_env_vars' => [
            'APP_KEY' => env('APP_KEY') ? 'SET' : 'NOT_SET',
            'APP_ENV' => env('APP_ENV', 'NOT_SET'),
            'DB_CONNECTION' => env('DB_CONNECTION', 'NOT_SET'),
            'DB_HOST' => env('DB_HOST', 'NOT_SET'),
            'DB_DATABASE' => env('DB_DATABASE', 'NOT_SET'),
        ]
    ]);
});

// Rota ULTRA simples - só para testar se Laravel carrega
Route::get('/ultra-simple', function () {
    return 'LARAVEL_WORKING_BASIC';
});

// Rota para testar sem usar facades
Route::get('/no-facades', function () {
    return response('NO_FACADES_TEST', 200, ['Content-Type' => 'text/plain']);
});

// Rota para testar conexão com banco
Route::get('/db-test', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json(['db_status' => 'CONNECTED']);
    } catch (\Exception $e) {
        return response()->json(['db_status' => 'ERROR', 'message' => $e->getMessage()]);
    }
});

Route::middleware(['auth:sanctum'])->group(function () {
});

Route::middleware('auth:sanctum')->prefix('api')->group(function () {
    
});