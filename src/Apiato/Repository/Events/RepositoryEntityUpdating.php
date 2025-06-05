<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Updating Event
 * Fired before an entity is updated
 */
class RepositoryEntityUpdating extends RepositoryEventBase
{
    protected string $action = "updating";

    /**
     * Get the attributes being updated
     */
    public function getAttributes(): array
    {
        return is_array($this->model) ? $this->model : [];
    }
}
