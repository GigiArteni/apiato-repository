<?php

declare(strict_types=1);

namespace Apiato\Repository\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

/**
 * Generate repository classes with Apiato structure
 */
class MakeRepositoryCommand extends GeneratorCommand
{
    protected $signature = 'make:repository {name} {--model=} {--cache} {--interface} {--force}';
    protected $description = 'Create a new repository class';
    protected $type = 'Repository';

    protected function getStub(): string
    {
        if ($this->option('cache')) {
            return __DIR__ . '/../../Stubs/repository.cacheable.stub';
        }

        return __DIR__ . '/../../Stubs/repository.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\Repositories';
    }

    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $this->replaceNamespace($stub, $name)
             ->replaceClass($stub, $name)
             ->replaceModel($stub);

        return $stub;
    }

    protected function replaceModel(string &$stub): static
    {
        $model = $this->option('model') ?: $this->guessModelName();
        $modelClass = $this->qualifyModel($model);
        
        $stub = str_replace('{{MODEL}}', class_basename($modelClass), $stub);
        $stub = str_replace('{{MODEL_NAMESPACE}}', $modelClass, $stub);
        $stub = str_replace('{{MODEL_LOWER}}', Str::snake(class_basename($modelClass)), $stub);

        return $this;
    }

    protected function guessModelName(): string
    {
        $name = class_basename($this->getNameInput());
        return Str::replaceLast('Repository', '', $name);
    }
}
