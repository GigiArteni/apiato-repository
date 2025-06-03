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
 * Class FractalPresenter - 100% l5-repository compatible + enhancements
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

    protected function setupSerializer(): static
    {
        $serializer = $this->serializer();

        if ($serializer instanceof SerializerAbstract) {
            $this->fractal->setSerializer($serializer);
        }

        return $this;
    }

    protected function parseIncludes(): static
    {
        $request = app('Illuminate\Http\Request');
        $paramIncludes = config('repository.fractal.params.include', 'include');

        if ($request->has($paramIncludes)) {
            $this->fractal->parseIncludes($request->get($paramIncludes));
        }

        return $this;
    }

    public function serializer(): SerializerAbstract
    {
        $serializer = config('repository.fractal.serializer', 'League\\Fractal\\Serializer\\DataArraySerializer');
        return new $serializer();
    }

    abstract public function getTransformer(): TransformerAbstract;

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

    protected function transformCollection($data)
    {
        return new Collection($data, $this->getTransformer(), $this->resourceKeyCollection);
    }

    protected function transformItem($data)
    {
        return new Item($data, $this->getTransformer(), $this->resourceKeyItem);
    }

    protected function transformPaginator($paginator)
    {
        $collection = $paginator->getCollection();
        $resource = new Collection($collection, $this->getTransformer(), $this->resourceKeyCollection);

        if ($paginator instanceof LengthAwarePaginator || $paginator instanceof Paginator) {
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        }

        return $resource;
    }
}
