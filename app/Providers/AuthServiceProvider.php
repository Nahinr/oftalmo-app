<?php

namespace App\Providers;


use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
        protected $policies = [
        // Policies internas (Laravel ya autodetecta User y Patient si siguen convención)
        \App\Models\User::class    => \App\Policies\UserPolicy::class,
        \App\Models\Patient::class => \App\Policies\PatientPolicy::class,

        // Policies externas (Spatie)
        \Spatie\Permission\Models\Role::class       => \App\Policies\RolePolicy::class,
        
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
