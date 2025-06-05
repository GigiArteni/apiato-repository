<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Deleted Event
 * Fired after an entity is deleted
 */
class RepositoryEntityDeleted extends RepositoryEventBase
{
    protected string $action = "deleted";

    /**
     * Get the deleted model's ID
     */
    public function getModelId()
    {
        if ($this->model && method_exists($this->model, 'getKey')) {
            return $this->model->getKey();
        }

        return null;
    }

    /**
     * Get the deleted model's attributes
     */
    public function getDeletedAttributes(): array
    {
        if ($this->model && method_exists($this->model, 'getAttributes')) {
            return $this->model->getAttributes();
        }

        return [];
    }
}
