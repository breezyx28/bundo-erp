<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected array $supported = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = Session::get('locale');

        if (! is_string($locale) || $locale === '') {
            $locale = data_get(Auth::user()?->settings, 'locale');
        }

        if (! is_string($locale) || ! in_array($locale, $this->supported, true)) {
            $locale = (string) config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
