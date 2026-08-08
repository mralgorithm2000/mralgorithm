<?php

namespace App\Providers;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::viaRequest('order-session', function (Request $request): ?GenericUser {
            if (! $request->session()->has('phone_attempt_ids')) {
                return null;
            }

            return new GenericUser([
                'id' => $request->session()->getId(),
            ]);
        });
    }
}
