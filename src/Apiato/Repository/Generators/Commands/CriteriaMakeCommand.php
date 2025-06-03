<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class CriteriaMakeCommand extends Command
{
    protected $signature = 'make:criteria {name}';
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

        if ($this->files->exists($path)) {
            $this->error('Criteria already exists!');
            return false;
        }

        $this->makeDirectory($path);

        $stub = str_replace('{{CLASS}}', $name, $this->getStub());

        $this->files->put($path, $stub);

        $this->info('Criteria created successfully.');

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
