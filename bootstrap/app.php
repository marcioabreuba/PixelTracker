<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Habilita middleware para requisições frontend
        $middleware->web();
        
        // Middleware do Sanctum para requisições stateful
        $middleware->append(EnsureFrontendRequestsAreStateful::class);
        
        // Configurar proxies confiáveis (importante para Render/Cloudflare)
        $middleware->trustProxies(at: '*');
        
        $middleware->validateCsrfTokens(except: [
            '/events/send',
            '/webhook/hotmart',
            '/webhook/shopify',
            '/webhook/yampi',
            '/webhook/digital',
            '/shopify/add-to-cart',
        ]);
        $middleware->EncryptCookies(except: [
            '_fbp',
            '_fbc',
            'userId',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
