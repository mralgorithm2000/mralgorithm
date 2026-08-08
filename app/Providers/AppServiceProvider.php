<?php

namespace App\Providers;

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        Relation::enforceMorphMap([
            'virtual_number' => \App\Models\VirtualNumber::class,
            'sm_service' => \App\Models\SmService::class,
        ]);

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
