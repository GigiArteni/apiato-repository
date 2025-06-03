<?php

declare(strict_types=1);

namespace Apiato\Repository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Clear repository cache
 */
class ClearCacheCommand extends Command
{
    protected $signature = 'repository:clear-cache {--tags=}';
    protected $description = 'Clear repository cache';

    public function handle(): int
    {
        $tags = $this->option('tags');
        
        if ($tags) {
            $tagArray = explode(',', $tags);
            Cache::tags($tagArray)->flush();
            $this->info("Cache cleared for tags: " . implode(', ', $tagArray));
        } else {
            Cache::flush();
            $this->info('All repository cache cleared!');
        }

        return 0;
    }
}
