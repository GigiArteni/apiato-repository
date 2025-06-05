<?php

namespace Apiato\Repository\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Event Service Provider
 * Registers repository events and listeners
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application
     */
    protected $listen = [
        \Apiato\Repository\Events\RepositoryEntityCreated::class => [
            // Add your listeners here
        ],
        \Apiato\Repository\Events\RepositoryEntityUpdated::class => [
            // Add your listeners here
        ],
        \Apiato\Repository\Events\RepositoryEntityDeleted::class => [
            // Add your listeners here
        ],
    ];

    /**
     * Boot the application services
     */
    public function boot()
    {
        parent::boot();

        // Auto-register cache clearing listeners if enabled
        if (config('repository.cache.clean.enabled', true)) {
            $this->registerCacheClearingListeners();
        }
    }

    /**
     * Register cache clearing listeners
     */
    protected function registerCacheClearingListeners()
    {
        // Clear cache on entity creation
        if (config('repository.cache.clean.on.create', true)) {
            $this->app['events']->listen(
                \Apiato\Repository\Events\RepositoryEntityCreated::class,
                function ($event) {
                    if (method_exists($event->getRepository(), 'clearCache')) {
                        $event->getRepository()->clearCache();
                    }
                }
            );
        }

        // Clear cache on entity update
        if (config('repository.cache.clean.on.update', true)) {
            $this->app['events']->listen(
                \Apiato\Repository\Events\RepositoryEntityUpdated::class,
                function ($event) {
                    if (method_exists($event->getRepository(), 'clearCache')) {
                        $event->getRepository()->clearCache();
                    }
                }
            );
        }

        // Clear cache on entity deletion
        if (config('repository.cache.clean.on.delete', true)) {
            $this->app['events']->listen(
                \Apiato\Repository\Events\RepositoryEntityDeleted::class,
                function ($event) {
                    if (method_exists($event->getRepository(), 'clearCache')) {
                        $event->getRepository()->clearCache();
                    }
                }
            );
        }
    }
}
