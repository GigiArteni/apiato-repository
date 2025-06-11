<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * MakeCriteriaCommand for Apiato v.13
 */
class MakeCriteriaCommand extends Command
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
        return '<?php\n\nnamespace App\\Criteria;\n\nuse Apiato\\Repository\\Contracts\\CriteriaInterface;\nuse Apiato\\Repository\\Contracts\\RepositoryInterface;\n\n/**\n * Class {{CLASS}}\n * @package App\\Criteria\n */\nclass {{CLASS}} implements CriteriaInterface\n{\n    /**\n     * Apply criteria in query repository\n     */\n    public function apply($model, RepositoryInterface $repository)\n    {\n        // Add your criteria logic here\n        \n        return $model;\n    }\n}\n';
    }
}
