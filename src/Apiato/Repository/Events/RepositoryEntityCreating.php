<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Creating Event
 * Fired before a new entity is created
 */
class RepositoryEntityCreating extends RepositoryEventBase
{
    protected string $action = "creating";

    /**
     * Get the attributes being created
     */
    public function getAttributes(): array
    {
        return is_array($this->model) ? $this->model : [];
    }
}
