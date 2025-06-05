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
