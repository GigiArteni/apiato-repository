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
