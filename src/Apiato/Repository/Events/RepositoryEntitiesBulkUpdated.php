<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entities Bulk Updated Event
 * Fired after multiple entities are updated in bulk
 */
class RepositoryEntitiesBulkUpdated extends RepositoryEventBase
{
    protected string $action = "bulk_updated";
    protected array $conditions;
    protected array $attributes;
    protected int $affectedRows;

    public function __construct(RepositoryInterface $repository, array $conditions, array $attributes, int $affectedRows)
    {
        $this->repository = $repository;
        $this->conditions = $conditions;
        $this->attributes = $attributes;
        $this->affectedRows = $affectedRows;
        $this->model = null; // No single model for bulk operations
    }

    /**
     * Get the update conditions
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Get the updated attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get the number of affected rows
     */
    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
