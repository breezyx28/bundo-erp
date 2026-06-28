<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds defence-in-depth HTTP security headers to every web response.
 *
 * The Content-Security-Policy is intentionally permissive about inline scripts
 * and styles because Livewire/Alpine and the FlyonUI stack rely on them;
 * it still constrains object/base/frame-ancestors and forces same-origin
 * defaults, which closes the most common injection vectors.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-XSS-Protection' => '1; mode=block',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
            'Content-Security-Policy' => $this->contentSecurityPolicy(),
        ];

        // Only advertise HSTS over HTTPS so local http development is unaffected.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }

    protected function contentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
        ]);
    }
}
