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
    protected $signature = 'make:repository {name} {--model=} {--fillable=} {--rules=} {--validator=} {--force}';
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

class {{CLASS}} extends BaseRepository
{
    public function model()
    {
        return {{MODEL_CLASS}}::class;
    }

    protected $fieldSearchable = [
    ];

    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
}';
    }
}
