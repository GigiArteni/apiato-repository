<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Created Event
 * Fired after a new entity is created
 */
class RepositoryEntityCreated extends RepositoryEventBase
{
    protected string $action = "created";

    /**
     * Get the created model's ID
     */
    public function getModelId()
    {
        if ($this->model && method_exists($this->model, 'getKey')) {
            return $this->model->getKey();
        }

        return null;
    }
}
