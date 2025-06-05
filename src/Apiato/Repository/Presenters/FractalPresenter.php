<?php

namespace Apiato\Repository\Presenters;

use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\SerializerAbstract;
use League\Fractal\TransformerAbstract;
use Apiato\Repository\Contracts\PresenterInterface;

/**
 * Enhanced FractalPresenter for Apiato v.13
 */
abstract class FractalPresenter implements PresenterInterface
{
    protected ?string $resourceKeyItem = null;
    protected ?string $resourceKeyCollection = null;
    protected Manager $fractal;
    protected $resource = null;

    public function __construct()
    {
        if (!class_exists('League\Fractal\Manager')) {
            throw new Exception('Package required. Please install: league/fractal');
        }

        $this->fractal = new Manager();
        $this->parseIncludes();
        $this->setupSerializer();
    }

    /**
     * Setup serializer
     */
    protected function setupSerializer(): static
    {
        $serializer = $this->serializer();

        if ($serializer instanceof SerializerAbstract) {
            $this->fractal->setSerializer($serializer);
        }

        return $this;
    }

    /**
     * Parse includes from request
     */
    protected function parseIncludes(): static
    {
        $request = app('Illuminate\Http\Request');
        $paramIncludes = config('repository.fractal.params.include', 'include');

        if ($request->has($paramIncludes)) {
            $this->fractal->parseIncludes($request->get($paramIncludes));
        }

        return $this;
    }

    /**
     * Get serializer instance
     */
    public function serializer(): SerializerAbstract
    {
        $serializer = config('repository.fractal.serializer', 'League\\Fractal\\Serializer\\DataArraySerializer');
        return new $serializer();
    }

    /**
     * Get transformer instance (must be implemented by child classes)
     */
    abstract public function getTransformer(): TransformerAbstract;

    /**
     * Present data
     */
    public function present($data)
    {
        if (!class_exists('League\Fractal\Manager')) {
            throw new Exception('Package required. Please install: league/fractal');
        }

        if ($data instanceof EloquentCollection) {
            $this->resource = $this->transformCollection($data);
        } elseif ($data instanceof AbstractPaginator) {
            $this->resource = $this->transformPaginator($data);
        } else {
            $this->resource = $this->transformItem($data);
        }

        return $this->fractal->createData($this->resource)->toArray();
    }

    /**
     * Transform collection
     */
    protected function transformCollection($data)
    {
        return new Collection($data, $this->getTransformer(), $this->resourceKeyCollection);
    }

    /**
     * Transform single item
     */
    protected function transformItem($data)
    {
        return new Item($data, $this->getTransformer(), $this->resourceKeyItem);
    }

    /**
     * Transform paginated data
     */
    protected function transformPaginator($paginator)
    {
        $collection = $paginator->getCollection();
        $resource = new Collection($collection, $this->getTransformer(), $this->resourceKeyCollection);

        if ($paginator instanceof LengthAwarePaginator || $paginator instanceof Paginator) {
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        }

        return $resource;
    }

    /**
     * Set resource key for items
     */
    public function setResourceKeyItem(string $key)
    {
        $this->resourceKeyItem = $key;
        return $this;
    }

    /**
     * Set resource key for collections
     */
    public function setResourceKeyCollection(string $key)
    {
        $this->resourceKeyCollection = $key;
        return $this;
    }
}
