<?php

namespace Apiato\Repository\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider - 100% Compatible + Auto-registration
 * This provider automatically makes your existing l5-repository code work
 */
class RepositoryServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

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
            ]);
        }
    }

    public function register()
    {
        // Register core services
        $this->app->register(\Apiato\Repository\Providers\EventServiceProvider::class);

        // CRITICAL: Create aliases so existing Apiato code works unchanged
        $this->createCompatibilityLayer();
    }

    /**
     * Create compatibility layer for existing l5-repository code
     * This makes your existing Apiato repositories work without any changes
     */
    protected function createCompatibilityLayer()
    {
        // Map old l5-repository classes to new Apiato classes
        $aliases = [
            // Core interfaces
            'Prettus\Repository\Contracts\RepositoryInterface' => 'Apiato\Repository\Contracts\RepositoryInterface',
            'Prettus\Repository\Contracts\CriteriaInterface' => 'Apiato\Repository\Contracts\CriteriaInterface',
            'Prettus\Repository\Contracts\PresenterInterface' => 'Apiato\Repository\Contracts\PresenterInterface',
            'Prettus\Repository\Contracts\Presentable' => 'Apiato\Repository\Contracts\Presentable',
            'Prettus\Repository\Contracts\CacheableInterface' => 'Apiato\Repository\Contracts\CacheableInterface',
            'Prettus\Repository\Contracts\RepositoryCriteriaInterface' => 'Apiato\Repository\Contracts\RepositoryCriteriaInterface',
            
            // Core classes
            'Prettus\Repository\Eloquent\BaseRepository' => 'Apiato\Repository\Eloquent\BaseRepository',
            'Prettus\Repository\Criteria\RequestCriteria' => 'Apiato\Repository\Criteria\RequestCriteria',
            'Prettus\Repository\Presenter\FractalPresenter' => 'Apiato\Repository\Presenters\FractalPresenter',
            
            // Traits
            'Prettus\Repository\Traits\CacheableRepository' => 'Apiato\Repository\Traits\CacheableRepository',
            'Prettus\Repository\Traits\PresentableTrait' => 'Apiato\Repository\Traits\PresentableTrait',
            
            // Events
            'Prettus\Repository\Events\RepositoryEntityCreating' => 'Apiato\Repository\Events\RepositoryEntityCreating',
            'Prettus\Repository\Events\RepositoryEntityCreated' => 'Apiato\Repository\Events\RepositoryEntityCreated',
            'Prettus\Repository\Events\RepositoryEntityUpdating' => 'Apiato\Repository\Events\RepositoryEntityUpdating',
            'Prettus\Repository\Events\RepositoryEntityUpdated' => 'Apiato\Repository\Events\RepositoryEntityUpdated',
            'Prettus\Repository\Events\RepositoryEntityDeleting' => 'Apiato\Repository\Events\RepositoryEntityDeleting',
            'Prettus\Repository\Events\RepositoryEntityDeleted' => 'Apiato\Repository\Events\RepositoryEntityDeleted',
            
            // Exceptions
            'Prettus\Repository\Exceptions\RepositoryException' => 'Apiato\Repository\Exceptions\RepositoryException',
        ];

        foreach ($aliases as $original => $new) {
            if (!class_exists($original) && class_exists($new)) {
                class_alias($new, $original);
            }
        }
    }

    public function provides()
    {
        return [];
    }
}
