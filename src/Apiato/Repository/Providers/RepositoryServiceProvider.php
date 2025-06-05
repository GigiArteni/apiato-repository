<?php

namespace Apiato\Repository\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider for Apiato v.13
 * Registers all repository services and commands
 */
class RepositoryServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

    /**
     * Boot the application services
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../config/repository.php' => config_path('repository.php'),
        ], 'repository');

        $this->mergeConfigFrom(__DIR__ . '/../../../config/repository.php', 'repository');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Apiato\Repository\Generators\Commands\RepositoryMakeCommand::class,
                \Apiato\Repository\Generators\Commands\CriteriaMakeCommand::class,
                \Apiato\Repository\Generators\Commands\EntityMakeCommand::class,
                \Apiato\Repository\Generators\Commands\PresenterMakeCommand::class,
                \Apiato\Repository\Generators\Commands\ValidatorMakeCommand::class,
                \Apiato\Repository\Generators\Commands\TransformerMakeCommand::class,
            ]);
        }

        // Auto-detect Apiato v.13 environment
        $this->detectApiatoEnvironment();
    }

    /**
     * Register the application services
     */
    public function register()
    {
        $this->app->register(\Apiato\Repository\Providers\EventServiceProvider::class);
        
        // Register core services
        $this->registerRepositoryServices();
        
        // Register validators
        $this->registerValidators();
    }

    /**
     * Detect Apiato v.13 environment and configure accordingly
     */
    protected function detectApiatoEnvironment()
    {
        // Check if we're in an Apiato project
        if ($this->isApiatoProject()) {
            // Ensure HashIds are enabled by default in Apiato projects
            if (!config()->has('repository.apiato.hashids.enabled')) {
                config(['repository.apiato.hashids.enabled' => true]);
            }

            // Log successful integration
            if (config('app.debug')) {
                logger('Apiato Repository: Successfully integrated with Apiato v.13 project');
            }
        }
    }

    /**
     * Check if this is an Apiato project
     */
    protected function isApiatoProject(): bool
    {
        return class_exists('App\Ship\Engine\Foundation\Facades\Apiato') || 
               file_exists(base_path('app/Ship')) ||
               file_exists(base_path('app/Containers'));
    }

    /**
     * Register repository services
     */
    protected function registerRepositoryServices()
    {
        // Register default validator
        $this->app->bind(
            \Apiato\Repository\Contracts\ValidatorInterface::class,
            \Apiato\Repository\Validators\LaravelValidator::class
        );

        // Register cache manager
        $this->app->singleton('repository.cache', function ($app) {
            return $app['cache'];
        });
    }

    /**
     * Register validators
     */
    protected function registerValidators()
    {
        $this->app->bind('validator.repository', function ($app) {
            return new \Apiato\Repository\Validators\LaravelValidator();
        });
    }

    /**
     * Get the services provided by the provider
     */
    public function provides()
    {
        return [
            'repository.cache',
            'validator.repository',
        ];
    }
}
