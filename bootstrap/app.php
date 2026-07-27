<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // In produzione/UAT il container non è mai raggiungibile direttamente: solo Apache
        // (reverse proxy sullo stesso host, docker-compose.uat.yml lega la porta a
        // 127.0.0.1) può contattarlo. Senza questa configurazione Laravel ignora
        // X-Forwarded-Proto/Port e genera sempre URL http:// anche dietro HTTPS reale,
        // causando errori "mixed content" sugli asset (bug reale scoperto in UAT, v0.3.0).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
