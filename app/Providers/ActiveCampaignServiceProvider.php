<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ActiveCampaign;

class ActiveCampaignServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ActiveCampaign::class, function ($app) {
            return new ActiveCampaign(
                $app['config']['activecampaign']['url'],
                $app['config']['activecampaign']['api_key']                
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}