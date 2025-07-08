<?php
use App\Http\Middleware\EnsureSystemKeyAndLanguage;
use App\Http\Middleware\EnsureSanctumAuthenticated;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

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
            'sanctum.auth.json' => EnsureSanctumAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request): JsonResponse {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'errNum' => 'E401',
                    'msg' => 'Unauthenticated: Invalid or missing token.',
                ], 401);
            }

            // Fallback if it's a web request
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        });
    })->create();
