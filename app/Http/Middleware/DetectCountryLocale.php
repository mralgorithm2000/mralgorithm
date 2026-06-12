<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Stevebauman\Location\Facades\Location;

class DetectCountryLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = 'en';

        try {
            $position = Location::get($request->ip());

            if ($position && strtoupper($position->countryCode) === 'RU') {
                $locale = 'ru';
            }
        } catch (\Throwable $e) {
        }

        App::setLocale($locale);

        return $next($request);
    }
}
