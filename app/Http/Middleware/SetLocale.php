<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_keys(config('app.supported_locales', ['fr' => []]));
        $fallbackLocale = config('app.fallback_locale', 'fr');
        $locale = $fallbackLocale;

        if (Auth::check() && in_array(Auth::user()->locale, $supportedLocales, true)) {
            $locale = Auth::user()->locale;
        } elseif (in_array($request->session()->get('locale'), $supportedLocales, true)) {
            $locale = $request->session()->get('locale');
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        view()->share('currentLocale', $locale);
        view()->share('currentDirection', config("app.supported_locales.$locale.dir", 'ltr'));

        return $next($request);
    }
}
