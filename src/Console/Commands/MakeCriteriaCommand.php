<?php

declare(strict_types=1);

namespace Apiato\Repository\Console\Commands;

use Illuminate\Console\GeneratorCommand;

/**
 * Generate criteria classes
 */
class MakeCriteriaCommand extends GeneratorCommand
{
    protected $signature = 'make:criteria {name} {--force}';
    protected $description = 'Create a new criteria class';
    protected $type = 'Criteria';

    protected function getStub(): string
    {
        return __DIR__ . '/../../Stubs/criteria.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\Criteria';
    }
}
