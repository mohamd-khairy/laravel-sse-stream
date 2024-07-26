<?php

namespace Khairy\LaravelSSEStream;

use Illuminate\Support\ServiceProvider;
use Khairy\LaravelSSEStream\Classes\SSE;

class SSEServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Views', 'sse');
        $this->mergeConfigFrom(__DIR__ . '/Config/config.php', 'sse');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            // Publish the configuration file.
            $this->publishes([
                __DIR__ . '/Config/config.php' => config_path('sse.php'),
            ], 'sse.config');

            // Publish the views.
            $this->publishes([
                __DIR__ . '/Views' => base_path('resources/views/vendor/sse'),
            ], 'sse.views');

            // Publish the migrations.
            $this->publishes([
                __DIR__ . '/Migrations' => database_path('migrations')
            ]);
        }
    }

    /**
     * Register package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('SSE', function () {
            return $this->app->make(SSE::class);
        });
    }
}
