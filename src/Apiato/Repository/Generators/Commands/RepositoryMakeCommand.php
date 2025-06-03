<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Repository generator command - 100% compatible with l5-repository
 */
class RepositoryMakeCommand extends Command
{
    protected $signature = 'make:repository {name} {--fillable=} {--rules=} {--validator=} {--force}';
    protected $description = 'Create a new repository class';

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
        $modelName = Str::replaceLast('Repository', '', class_basename($name));
        $modelClass = "App\\Models\\{$modelName}";

        return str_replace(
            ['{{CLASS}}', '{{MODEL}}', '{{MODEL_CLASS}}'],
            [class_basename($name), $modelName, $modelClass],
            $this->getStub()
        );
    }

    protected function getStub()
    {
        return '<?php

namespace App\Repositories;

use Apiato\Repository\Eloquent\BaseRepository;
use Apiato\Repository\Criteria\RequestCriteria;
use {{MODEL_CLASS}};

/**
 * Class {{CLASS}}
 * @package App\Repositories
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
     */
    protected $fieldSearchable = [
        // Add your searchable fields here
        // \'name\' => \'like\',
        // \'email\' => \'=\',
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
