<?php

namespace SeteMares\Apple;

use Laravel\Socialite\SocialiteServiceProvider;
use Laravel\Socialite\Contracts\Factory;

use Socialite;

class ServiceProvider extends SocialiteServiceProvider
{
    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        return false;
    }

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        Socialite::extend('apple', function ($app) {
            $config = $this->app['config']['services.apple'];

            return Socialite::buildProvider(SocialiteProvider::class, $config);
        });
    }
}
