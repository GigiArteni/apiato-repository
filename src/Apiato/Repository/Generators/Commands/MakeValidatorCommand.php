<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * MakeValidatorCommand for Apiato v.13
 */
class MakeValidatorCommand extends Command
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
        return '<?php\n\nnamespace App\\Validators;\n\nuse Apiato\\Repository\\Validators\\LaravelValidator;\n\n/**\n * Class {{CLASS}}\n * @package App\\Validators\n */\nclass {{CLASS}} extends LaravelValidator\n{\n    /**\n     * Validation Rules\n     */\n    protected $rules = [\n        \'create\' => [\n            // Add your create validation rules here\n        ],\n        \'update\' => [\n            // Add your update validation rules here\n        ],\n    ];\n}\n';
    }
}
