<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Deleting Event
 * Fired before an entity is deleted
 */
class RepositoryEntityDeleting extends RepositoryEventBase
{
    protected string $action = "deleting";

    /**
     * Get the ID of the entity being deleted
     */
    public function getEntityId()
    {
        return $this->model;
    }
}
