<?php

namespace Apiato\Repository\Events;

use Illuminate\Database\Eloquent\Model;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Class RepositoryEventBase
 */
abstract class RepositoryEventBase
{
    protected $model;
    protected RepositoryInterface $repository;
    protected string $action;

    public function __construct(RepositoryInterface $repository, $model)
    {
        $this->repository = $repository;
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
