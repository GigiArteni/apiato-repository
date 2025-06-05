#!/bin/bash

# ========================================
# 06 - CREATE PROVIDERS AND COMMANDS
# Creates service providers and artisan commands
# ========================================

echo "📝 Creating service providers and artisan commands..."

# ========================================
# MAIN SERVICE PROVIDER
# ========================================

cat > src/Apiato/Repository/Providers/RepositoryServiceProvider.php << 'EOF'
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
EOF

# ========================================
# EVENT SERVICE PROVIDER
# ========================================

cat > src/Apiato/Repository/Providers/EventServiceProvider.php << 'EOF'
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
EOF

# ========================================
# REPOSITORY MAKE COMMAND
# ========================================

cat > src/Apiato/Repository/Generators/Commands/RepositoryMakeCommand.php << 'EOF'
<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Repository generator command for Apiato v.13
 */
class RepositoryMakeCommand extends Command
{
    protected $signature = 'make:repository {name} {--model=} {--fillable=} {--rules=} {--validator=} {--presenter=} {--force}';
    protected $description = 'Create a new repository class for Apiato v.13';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        
        if (!Str::endsWith($name, 'Repository')) {
            $name .= 'Repository';
        }

        $path = $this->getPath($name);

        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error('Repository already exists!');
            return false;
        }

        $this->makeDirectory($path);

        $stub = $this->buildClass($name);

        $this->files->put($path, $stub);

        $this->info('Repository created successfully.');
        $this->line("<info>Repository:</info> {$path}");

        return true;
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        return app_path(str_replace('\\', '/', $name) . '.php');
    }

    protected function rootNamespace()
    {
        return config('repository.generator.rootNamespace', 'App\\');
    }

    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    protected function buildClass($name)
    {
        $modelName = $this->option('model') ?: Str::replaceLast('Repository', '', class_basename($name));
        $modelClass = "App\\Models\\{$modelName}";

        $replacements = [
            '{{CLASS}}' => class_basename($name),
            '{{MODEL}}' => $modelName,
            '{{MODEL_CLASS}}' => $modelClass,
            '{{NAMESPACE}}' => $this->getNamespace($name),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->getStub()
        );
    }

    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    protected function getStub()
    {
        return '<?php

namespace {{NAMESPACE}};

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use {{MODEL_CLASS}};

/**
 * Class {{CLASS}}
 * Enhanced for Apiato v.13 with HashId support
 * @package {{NAMESPACE}}
 */
class {{CLASS}} extends BaseRepository
{
    /**
     * Specify Model class name
     */
    public function model()
    {
        return {{MODEL_CLASS}}::class;
    }

    /**
     * Specify fields that are searchable
     * HashId fields (id, *_id) are automatically processed
     */
    protected $fieldSearchable = [
        // Add your searchable fields here
        // \'name\' => \'like\',
        // \'email\' => \'=\',
        // \'id\' => \'=\', // Automatically supports HashIds
    ];

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
}';
    }
}
EOF

# ========================================
# CRITERIA MAKE COMMAND
# ========================================

cat > src/Apiato/Repository/Generators/Commands/CriteriaMakeCommand.php << 'EOF'
<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class CriteriaMakeCommand extends Command
{
    protected $signature = 'make:criteria {name} {--force}';
    protected $description = 'Create a new criteria class';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        
        if (!Str::endsWith($name, 'Criteria')) {
            $name .= 'Criteria';
        }

        $path = app_path('Criteria/' . $name . '.php');

        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error('Criteria already exists!');
            return false;
        }

        $this->makeDirectory($path);

        $stub = str_replace('{{CLASS}}', $name, $this->getStub());

        $this->files->put($path, $stub);

        $this->info('Criteria created successfully.');
        $this->line("<info>Criteria:</info> {$path}");

        return true;
    }

    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    protected function getStub()
    {
        return '<?php

namespace App\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Class {{CLASS}}
 * @package App\Criteria
 */
class {{CLASS}} implements CriteriaInterface
{
    /**
     * Apply criteria in query repository
     */
    public function apply($model, RepositoryInterface $repository)
    {
        // Add your criteria logic here
        
        return $model;
    }
}';
    }
}
EOF

# ========================================
# ENTITY MAKE COMMAND
# ========================================

cat > src/Apiato/Repository/Generators/Commands/EntityMakeCommand.php << 'EOF'
<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;

/**
 * Entity generator command for Apiato v.13
 * Creates the complete stack: Model, Repository, etc.
 */
class EntityMakeCommand extends Command
{
    protected $signature = 'make:entity {name} {--fillable=} {--rules=} {--validator=} {--presenter=} {--force}';
    protected $description = 'Create a new entity (Model, Repository, etc.) for Apiato v.13';

    public function handle()
    {
        $name = $this->argument('name');
        
        $this->info("Creating entity: {$name}");

        // Generate model
        $this->call('make:model', ['name' => $name]);
        
        // Generate repository
        $this->call('make:repository', [
            'name' => $name . 'Repository',
            '--model' => $name,
            '--force' => $this->option('force')
        ]);

        // Generate presenter if requested
        if ($this->option('presenter')) {
            $this->call('make:presenter', [
                'name' => $name . 'Presenter',
                '--force' => $this->option('force')
            ]);
        }

        // Generate validator if requested
        if ($this->option('validator')) {
            $this->call('make:validator', [
                'name' => $name . 'Validator',
                '--rules' => $this->option('rules'),
                '--force' => $this->option('force')
            ]);
        }

        $this->info('Entity created successfully!');
        $this->line("<info>Generated:</info> Model, Repository" . 
                   ($this->option('presenter') ? ', Presenter' : '') .
                   ($this->option('validator') ? ', Validator' : ''));

        return true;
    }
}
EOF

# ========================================
# PRESENTER MAKE COMMAND
# ========================================

cat > src/Apiato/Repository/Generators/Commands/PresenterMakeCommand.php << 'EOF'
<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class PresenterMakeCommand extends Command
{
    protected $signature = 'make:presenter {name} {--transformer=} {--force}';
    protected $description = 'Create a new presenter class';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        
        if (!Str::endsWith($name, 'Presenter')) {
            $name .= 'Presenter';
        }

        $path = app_path('Presenters/' . $name . '.php');

        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error('Presenter already exists!');
            return false;
        }

        $this->makeDirectory($path);

        $transformerName = $this->option('transformer') ?: Str::replaceLast('Presenter', 'Transformer', $name);
        
        $stub = str_replace(
            ['{{CLASS}}', '{{TRANSFORMER}}'],
            [$name, $transformerName],
            $this->getStub()
        );

        $this->files->put($path, $stub);

        $this->info('Presenter created successfully.');
        $this->line("<info>Presenter:</info> {$path}");

        return true;
    }

    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    protected function getStub()
    {
        return '<?php

namespace App\Presenters;

use Apiato\Repository\Presenters\FractalPresenter;
use App\Transformers\{{TRANSFORMER}};

/**
 * Class {{CLASS}}
 * @package App\Presenters
 */
class {{CLASS}} extends FractalPresenter
{
    /**
     * Transformer
     */
    public function getTransformer()
    {
        return new {{TRANSFORMER}}();
    }
}';
    }
}
EOF

# ========================================
# VALIDATOR MAKE COMMAND
# ========================================

cat > src/Apiato/Repository/Generators/Commands/ValidatorMakeCommand.php << 'EOF'
<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ValidatorMakeCommand extends Command
{
    protected $signature = 'make:validator {name} {--rules=} {--force}';
    protected $description = 'Create a new validator class';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        
        if (!Str::endsWith($name, 'Validator')) {
            $name .= 'Validator';
        }

        $path = app_path('Validators/' . $name . '.php');

        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error('Validator already exists!');
            return false;
        }

        $this->makeDirectory($path);

        $stub = str_replace('{{CLASS}}', $name, $this->getStub());

        $this->files->put($path, $stub);

        $this->info('Validator created successfully.');
        $this->line("<info>Validator:</info> {$path}");

        return true;
    }

    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    protected function getStub()
    {
        return '<?php

namespace App\Validators;

use Apiato\Repository\Validators\LaravelValidator;

/**
 * Class {{CLASS}}
 * @package App\Validators
 */
class {{CLASS}} extends LaravelValidator
{
    /**
     * Validation Rules
     */
    protected $rules = [
        \'create\' => [
            // Add your create validation rules here
        ],
        \'update\' => [
            // Add your update validation rules here
        ],
    ];
}';
    }
}
EOF

# ========================================
# TRANSFORMER MAKE COMMAND
# ========================================

cat > src/Apiato/Repository/Generators/Commands/TransformerMakeCommand.php << 'EOF'
<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class TransformerMakeCommand extends Command
{
    protected $signature = 'make:transformer {name} {--model=} {--force}';
    protected $description = 'Create a new transformer class';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        
        if (!Str::endsWith($name, 'Transformer')) {
            $name .= 'Transformer';
        }

        $path = app_path('Transformers/' . $name . '.php');

        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error('Transformer already exists!');
            return false;
        }

        $this->makeDirectory($path);

        $modelName = $this->option('model') ?: Str::replaceLast('Transformer', '', $name);
        
        $stub = str_replace(
            ['{{CLASS}}', '{{MODEL}}'],
            [$name, $modelName],
            $this->getStub()
        );

        $this->files->put($path, $stub);

        $this->info('Transformer created successfully.');
        $this->line("<info>Transformer:</info> {$path}");

        return true;
    }

    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    protected function getStub()
    {
        return '<?php

namespace App\Transformers;

use Apiato\Repository\Support\BaseTransformer;

/**
 * Class {{CLASS}}
 * @package App\Transformers
 */
class {{CLASS}} extends BaseTransformer
{
    /**
     * Transform the {{MODEL}}
     */
    public function transform($model)
    {
        return [
            \'id\' => $model->id,
            // Add your transformation logic here
            \'created_at\' => $this->transformDate($model->created_at),
            \'updated_at\' => $this->transformDate($model->updated_at),
        ];
    }
}';
    }
}
EOF

echo "✅ PROVIDERS AND COMMANDS CREATED!"
echo ""
echo "📝 Created provider files:"
echo "  - RepositoryServiceProvider.php (main service provider)"
echo "  - EventServiceProvider.php (event registration and cache clearing)"
echo ""
echo "📝 Created command files:"
echo "  - RepositoryMakeCommand.php (make:repository)"
echo "  - CriteriaMakeCommand.php (make:criteria)"
echo "  - EntityMakeCommand.php (make:entity)"
echo "  - PresenterMakeCommand.php (make:presenter)"
echo "  - ValidatorMakeCommand.php (make:validator)"
echo "  - TransformerMakeCommand.php (make:transformer)"
echo ""
echo "🚀 Key features implemented:"
echo "  - Auto-detection of Apiato v.13 projects"
echo "  - Automatic cache clearing on CRUD operations"
echo "  - Complete artisan command suite"
echo "  - Service registration and binding"
echo "  - Event listener auto-registration"
echo ""
echo "💡 Available commands after installation:"
echo "   php artisan make:repository UserRepository"
echo "   php artisan make:criteria ActiveUsersCriteria"
echo "   php artisan make:entity User --presenter --validator"
echo "   php artisan make:presenter UserPresenter"
echo "   php artisan make:validator UserValidator"
echo "   php artisan make:transformer UserTransformer"
echo ""
echo "🚀 Next: Run configuration generator"
echo "   ./07_create_config.sh"