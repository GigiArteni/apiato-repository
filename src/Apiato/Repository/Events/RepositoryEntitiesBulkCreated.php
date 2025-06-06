<?php

namespace Apiato\Repository\Events;

use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Repository Entities Bulk Created Event
 * Fired after multiple entities are created in bulk
 */
class RepositoryEntitiesBulkCreated extends RepositoryEventBase
{
    protected string $action = "bulk_created";
    protected array $entities;

    public function __construct(RepositoryInterface $repository, array $entities)
    {
        $this->repository = $repository;
        $this->entities = $entities;
        $this->model = null; // No single model for bulk operations
    }

    /**
     * Get the created entities
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Get the count of created entities
     */
    public function getCount(): int
    {
        return count($this->entities);
    }
}
