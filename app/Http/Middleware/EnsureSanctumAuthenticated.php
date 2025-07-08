<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSanctumAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return response()->json([
                'status' => false,
                'errNum' => 'E401',
                'msg' => 'Not Authorized',
            ], 401);
        }

        return $next($request);
    }
}
