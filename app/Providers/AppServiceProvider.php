<?php

namespace App\Providers;

use App\Models\Good;
use App\Models\Order;
use App\Models\Parameter;
use App\Models\Purchase;
use App\Models\RefundRequest;
use App\Models\SmService;
use App\Models\Supplier;
use App\Models\Type;
use App\Models\User;
use App\Models\VirtualNumber;
use App\Policies\GoodPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ParameterPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\RefundRequestPolicy;
use App\Policies\RolePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TypePolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Type::class, TypePolicy::class);
        Gate::policy(Good::class, GoodPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Parameter::class, ParameterPolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);
        Gate::policy(RefundRequest::class, RefundRequestPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);

        Relation::enforceMorphMap([
            'virtual_number' => VirtualNumber::class,
            'sm_service' => SmService::class,
            'user' => User::class,
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
