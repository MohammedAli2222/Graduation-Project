<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeploymentSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedSecret = (string) $request->input('secret', '');
        $expectedSecret = config('app.deployment_secret', 'fdd1e7fc37037945b199ba383023275f0142831c');

        if (!hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid secret key.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
