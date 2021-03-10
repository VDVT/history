<?php

namespace VDVT\History;

use Illuminate\Support\ServiceProvider;
use VDVT\History\Events\SaveLogHistory;

class HistoryServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->app->booted(function () {
            $saveLogHistoryHandler = config('history.event_handler');
            if (class_exists($saveLogHistoryHandler)) {
                $this
                    ->app['events']
                    ->listen(
                        SaveLogHistory::class,
                        $saveLogHistoryHandler
                    );

            }
        });
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/history.php', 'history');

        // Register the service the package provides.
        $this->app->singleton('history', function ($app) {
            return new History;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['history'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/history.php' => config_path('history.php'),
        ], 'history.config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'history.migrations');

        // Publishing the translation files.
        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/vdvt'),
        ], 'history.views');

        // Registering package commands.
        $this->commands([]);
    }
}
