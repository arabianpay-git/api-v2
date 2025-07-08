<?php
use App\Http\Middleware\EnsureSystemKeyAndLanguage;
use App\Http\Middleware\EnsureSanctumAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware عالمي مخصص للنظام واللغة (يبقى مفعلًا دائمًا)
        $middleware->append(EnsureSystemKeyAndLanguage::class);

        // Middleware المصادقة لا يُطبق إلا عندما يُطلب بـ auth:sanctum
        $middleware->alias([
            'auth:sanctum' => EnsureSanctumAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
