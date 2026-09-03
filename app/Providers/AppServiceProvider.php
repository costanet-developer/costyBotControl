<?php

namespace App\Providers;

use App\Models\Comprobante;
use App\Models\ObservacionInteraccion;
use App\Models\User;
use App\Observers\AuditoriaObserver;
use App\Policies\ComprobantePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Comprobante::class, ComprobantePolicy::class);

        Comprobante::observe(AuditoriaObserver::class);
        User::observe(AuditoriaObserver::class);
        ObservacionInteraccion::observe(AuditoriaObserver::class);

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
