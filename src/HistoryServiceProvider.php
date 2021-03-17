<?php

namespace VDVT\History;

use Illuminate\Support\ServiceProvider;
use VDVT\History\Events\CreatedHistory;
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
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'vdvt/history');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->app->booted(function () {
            foreach ([
                CreatedHistory::class => config('vdvt.history.history.event_handler.created'),
                SaveLogHistory::class => config('vdvt.history.history.event_handler.store'),
            ] as $event => $eventHandler) {
                # code...
                if ($eventHandler && class_exists($eventHandler)) {
                    $this
                        ->app['events']
                        ->listen(
                            $event,
                            $eventHandler
                        );
                }
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
        $this->mergeConfigFrom(__DIR__ . '/../config/history.php', 'vdvt.history.history');

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
            __DIR__ . '/../config/history.php' => config_path('vdvt/history/history.php'),
        ], 'vdvt');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'vdvt');

        // Publishing the translation files.
        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/vdvt/history'),
        ], 'vdvt');

        // Registering package commands.
        $this->commands([]);
    }
}
