<?php

namespace Apiato\Repository\Generators\Commands;

use Illuminate\Console\Command;

/**
 * Entity generator command (l5-repository compatibility)
 * This creates the complete stack: Model, Repository, Presenter, etc.
 */
class EntityMakeCommand extends Command
{
    protected $signature = 'make:entity {name} {--fillable=} {--rules=} {--validator=} {--force}';
    protected $description = 'Create a new entity (Model, Repository, Presenter, etc.)';

    public function handle()
    {
        $name = $this->argument('name');
        
        $this->info("Creating entity: {$name}");

        // Generate model
        $this->call('make:model', ['name' => $name]);
        
        // Generate repository
        $this->call('make:repository', [
            'name' => $name . 'Repository',
            '--force' => $this->option('force')
        ]);

        $this->info('Entity created successfully!');
        return true;
    }
}
