<?php

declare(strict_types=1);

namespace Apiato\Repository\Presenters;

use Apiato\Repository\Contracts\PresenterInterface;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\SerializerAbstract;
use League\Fractal\TransformerAbstract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Professional Fractal Presenter for data transformation
 */
class FractalPresenter implements PresenterInterface
{
    protected Manager $fractal;
    protected ?TransformerAbstract $transformer = null;
    protected ?Request $request = null;

    public function __construct(Manager $fractal, ?Request $request = null)
    {
        $this->fractal = $fractal;
        $this->request = $request ?? request();
        $this->setupFractal();
    }

    protected function setupFractal(): void
    {
        if ($serializer = $this->getSerializer()) {
            $this->fractal->setSerializer($serializer);
        }

        $this->parseIncludes();
        $this->parseExcludes();
        $this->parseFieldsets();
    }

    public function present(mixed $data): mixed
    {
        if (!$this->transformer) {
            return $data;
        }

        if ($data instanceof LengthAwarePaginator) {
            return $this->presentPaginated($data);
        }

        if ($data instanceof \Illuminate\Database\Eloquent\Collection) {
            return $this->presentCollection($data);
        }

        if ($data instanceof Model) {
            return $this->presentItem($data);
        }

        return $data;
    }

    protected function presentPaginated(LengthAwarePaginator $paginator): array
    {
        $resource = new Collection($paginator->getCollection(), $this->transformer);
        $data = $this->fractal->createData($resource)->toArray();

        return array_merge($data, [
            'meta' => [
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'path' => $paginator->path(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ]
            ]
        ]);
    }

    protected function presentCollection(\Illuminate\Database\Eloquent\Collection $collection): array
    {
        $resource = new Collection($collection, $this->transformer);
        return $this->fractal->createData($resource)->toArray();
    }

    protected function presentItem(Model $model): array
    {
        $resource = new Item($model, $this->transformer);
        return $this->fractal->createData($resource)->toArray();
    }

    public function setTransformer(TransformerAbstract $transformer): static
    {
        $this->transformer = $transformer;
        return $this;
    }

    protected function getSerializer(): ?SerializerAbstract
    {
        $serializer = config('repository.fractal.serializer');
        
        if ($serializer && class_exists($serializer)) {
            return app($serializer);
        }

        return null;
    }

    protected function parseIncludes(): void
    {
        $includes = $this->request->get(config('repository.fractal.params.include', 'include'));
        
        if ($includes) {
            $this->fractal->parseIncludes($includes);
        }
    }

    protected function parseExcludes(): void
    {
        $excludes = $this->request->get(config('repository.fractal.params.exclude', 'exclude'));
        
        if ($excludes) {
            $this->fractal->parseExcludes($excludes);
        }
    }

    protected function parseFieldsets(): void
    {
        $fieldsets = $this->request->get(config('repository.fractal.params.fields', 'fields'));
        
        if ($fieldsets) {
            $this->fractal->parseFieldsets($fieldsets);
        }
    }
}
