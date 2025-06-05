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
