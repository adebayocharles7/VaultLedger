<?php

namespace App\Providers;

//use Illuminate\Support\ServiceProvider;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Folio;
use App\Models\Remittance;
use App\Policies\FolioPolicy;
use App\Policies\RemittancePolicy;


class AuthServiceProvider extends ServiceProvider
{
     protected $policies = [
        Folio::class      => FolioPolicy::class,
        Remittance::class => RemittancePolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
