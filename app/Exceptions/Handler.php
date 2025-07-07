<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        //
    }

    protected function unauthenticated($request, AuthenticationException $exception): \Symfony\Component\HttpFoundation\Response
    {
        // Return JSON if API request or Accept header prefers JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => false,
                'errNum' => 'E401',
                'msg' => 'Unauthenticated: Please log in or provide a valid token.',
            ], 401);
        }

        // Otherwise, fallback: redirect or customize as you wish (this prevents the RouteNotFoundException)
        abort(401, 'Unauthenticated: Login route not defined.');
    }

}
