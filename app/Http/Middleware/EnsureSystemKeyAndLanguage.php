<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemKeyAndLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Validate Language header
        $language = $request->header('Language');
        if (!in_array($language, ['en', 'ar'])) {
            return response()->json(['message' => 'Invalid or missing Language header'], 400);
        }

        // You can set the locale here if needed:
        app()->setLocale($language);

        // 2) Validate System-Key header
        $systemKey = $request->header('System-Key');
        $validKey = config('app.system_key'); // We'll add it in .env below

        if ($systemKey !== $validKey) {
            return response()->json(['message' => 'Invalid System-Key'], 401);
        }

        // Proceed with the request
        return $next($request);
    }
}
