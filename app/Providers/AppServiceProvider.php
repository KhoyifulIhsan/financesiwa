<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\PartnerPayment;
use App\Models\Trip;
use App\Observers\InvoiceObserver;
use App\Observers\PartnerPaymentObserver;
use App\Observers\TripObserver;
use Illuminate\Support\Facades\Gate;
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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Superadmin') ? true : null;
        });

        Invoice::observe(InvoiceObserver::class);
        Trip::observe(TripObserver::class);
        PartnerPayment::observe(PartnerPaymentObserver::class);
    }
}
