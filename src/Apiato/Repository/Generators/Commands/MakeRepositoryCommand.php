<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * MakeRepositoryCommand for Apiato v.13
 */
class MakeRepositoryCommand extends Command
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
        return '<?php\n\nnamespace {{NAMESPACE}};\n\nuse Apiato\\Repository\\Eloquent\\BaseRepository;\nuse Apiato\\Repository\\Criteria\\RequestCriteria;\nuse {{MODEL_CLASS}};\n\n/**\n * Class {{CLASS}}\n * Enhanced for Apiato v.13 with HashId support\n * @package {{NAMESPACE}}\n */\nclass {{CLASS}} extends BaseRepository\n{\n    /**\n     * Specify Model class name\n     */\n    public function model()\n    {\n        return {{MODEL_CLASS}}::class;\n    }\n\n    /**\n     * Specify fields that are searchable\n     * HashId fields (id, *_id) are automatically processed\n     */\n    protected $fieldSearchable = [\n        // Add your searchable fields here\n        // \'name\' => \'like\',\n        // \'email\' => \'=\',\n        // \'id\' => \'=\', // Automatically supports HashIds\n    ];\n\n    /**\n     * Boot up the repository, pushing criteria\n     */\n    public function boot()\n    {\n        $this->pushCriteria(app(RequestCriteria::class));\n    }\n}\n';
    }
}
