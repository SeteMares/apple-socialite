<?php

namespace SeteMares\Apple;

use Laravel\Socialite\SocialiteServiceProvider;
use Laravel\Socialite\Contracts\Factory;

use Socialite;

class ServiceProvider extends SocialiteServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

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
