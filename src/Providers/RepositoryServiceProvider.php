<?php

declare(strict_types=1);

namespace Apiato\Repository\Providers;

use Apiato\Repository\Console\Commands\ClearCacheCommand;
use Apiato\Repository\Console\Commands\MakeCriteriaCommand;
use Apiato\Repository\Console\Commands\MakeRepositoryCommand;
use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager;

/**
 * Repository Service Provider
 */
class RepositoryServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/repository.php' => config_path('repository.php'),
        ], 'repository-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRepositoryCommand::class,
                MakeCriteriaCommand::class,
                ClearCacheCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/repository.php', 'repository');
        
        $this->app->singleton('repository.cache', function ($app) {
            return $app['cache.store'];
        });

        $this->app->singleton(Manager::class, function ($app) {
            $manager = new Manager();
            
            if ($serializer = config('repository.fractal.serializer')) {
                $manager->setSerializer(app($serializer));
            }
            
            return $manager;
        });
    }

    public function provides(): array
    {
        return ['repository.cache', Manager::class];
    }
}
