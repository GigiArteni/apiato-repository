<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;

/**
 * Entity generator command for Apiato v.13
 * Creates the complete stack: Model, Repository, etc.
 */
class EntityMakeCommand extends Command
{
    protected $signature = 'make:entity {name} {--fillable=} {--rules=} {--validator=} {--force}';
    protected $description = 'Create a new entity (Model, Repository, etc.) for Apiato v.13';

    public function handle()
    {
        $name = $this->argument('name');
        
        $this->info("Creating entity: {$name}");

        // Generate model
        $this->call('make:model', ['name' => $name]);
        
        // Generate repository
        $this->call('make:repository', [
            'name' => $name . 'Repository',
            '--model' => $name,
            '--force' => $this->option('force')
        ]);

        // Generate validator if requested
        if ($this->option('validator')) {
            $this->call('make:validator', [
                'name' => $name . 'Validator',
                '--rules' => $this->option('rules'),
                '--force' => $this->option('force')
            ]);
        }

        $this->info('Entity created successfully!');
        $this->line("<info>Generated:</info> Model, Repository" .
                   ($this->option('validator') ? ', Validator' : ''));

        return true;
    }
}
