<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aggiunge header di sicurezza HTTP a tutte le risposte.
 * TODO(security): In produzione rivedere la CSP con nonce per script inline Vite.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content-Security-Policy — adattare in produzione per rimuovere unsafe-inline
        // TODO(security): Usare nonce per script e style in produzione
        $csp = "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "font-src 'self' https://fonts.gstatic.com; " .
            "img-src 'self' data: blob:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none'; " .
            "object-src 'none';";

        if (app()->environment('local', 'testing')) {
            $csp = "default-src 'self' http://localhost:* http://127.0.0.1:* ws://localhost:* ws://127.0.0.1:* 'unsafe-inline' 'unsafe-eval'; " .
                   "script-src 'self' http://localhost:* http://127.0.0.1:* 'unsafe-inline' 'unsafe-eval'; " .
                   "style-src 'self' https://fonts.googleapis.com http://localhost:* http://127.0.0.1:* 'unsafe-inline'; " .
                   "font-src 'self' https://fonts.gstatic.com; " .
                   "img-src 'self' data: blob:; " .
                   "connect-src 'self' http://localhost:* http://127.0.0.1:* ws://localhost:* ws://127.0.0.1:* wss://localhost:* wss://127.0.0.1:*; " .
                   "frame-ancestors 'none'; " .
                   "object-src 'none';";
        }

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
