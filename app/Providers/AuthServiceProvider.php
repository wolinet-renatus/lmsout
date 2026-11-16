<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Auth Service Provider
 *
 * This service provider registers the authentication policies.
 *
 * @package App\Providers
 * @author Renatus Bernardo
 */
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    /**
     * Bootstrap services, policies, or model observers.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
