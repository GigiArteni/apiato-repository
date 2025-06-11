<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * MakeTransformerCommand for Apiato v.13
 */
class MakeTransformerCommand extends Command
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
        return '<?php\n\nnamespace App\\Transformers;\n\nuse Apiato\\Repository\\Support\\BaseTransformer;\n\n/**\n * Class {{CLASS}}\n * @package App\\Transformers\n */\nclass {{CLASS}} extends BaseTransformer\n{\n    /**\n     * Transform the {{MODEL}}\n     */\n    public function transform($model)\n    {\n        return [\n            // Add your transformation logic here\n        ];\n    }\n}\n';
    }
}
