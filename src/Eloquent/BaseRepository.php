<?php

declare(strict_types=1);

namespace Apiato\Repository\Eloquent;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\PresenterInterface;
use Apiato\Repository\Contracts\RepositoryInterface;
use Apiato\Repository\Contracts\ValidatorInterface;
use Apiato\Repository\Exceptions\RepositoryException;
use Illuminate\Container\Container as App;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @template TModel of Model
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected App $app;
    protected Model $model;
    protected BaseCollection $criteria;
    protected bool $skipCriteria = false;
    protected bool $skipPresenter = false;
    protected ?PresenterInterface $presenter = null;
    protected ?ValidatorInterface $validator = null;
    protected array $fieldSearchable = [];

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->criteria = new BaseCollection();
        $this->makeModel();
        $this->makePresenter();
        $this->makeValidator();
        $this->boot();
    }

    protected function boot(): void
    {
        // Override in subclasses if needed
    }

    abstract public function model(): string;

    public function presenter(): ?string
    {
        return null;
    }

    public function validator(): ?string
    {
        return null;
    }

    public function makeModel(): Model
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    public function makePresenter(): ?PresenterInterface
    {
        $presenter = $this->presenter();

        if (!is_null($presenter)) {
            $this->presenter = is_string($presenter) ? $this->app->make($presenter) : $presenter;

            if (!$this->presenter instanceof PresenterInterface) {
                throw new RepositoryException("Class {$presenter} must be an instance of PresenterInterface");
            }

            return $this->presenter;
        }

        return null;
    }

    public function makeValidator(): ?ValidatorInterface
    {
        $validator = $this->validator();

        if (!is_null($validator)) {
            $this->validator = is_string($validator) ? $this->app->make($validator) : $validator;

            if (!$this->validator instanceof ValidatorInterface) {
                throw new RepositoryException("Class {$validator} must be an instance of ValidatorInterface");
            }

            return $this->validator;
        }

        return null;
    }

    public function resetModel(): static
    {
        $this->makeModel();
        return $this;
    }

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function all(array $columns = ['*']): Collection
    {
        $this->applyCriteria();

        if ($this->model instanceof Builder) {
            $results = $this->model->get($columns);
        } else {
            $results = $this->model->all($columns);
        }

        $this->resetModel();
        return $this->presentResult($results);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        $this->applyCriteria();
        $results = $this->model->paginate($perPage, $columns, $pageName, $page);
        $results->getCollection()->transform(function ($model) {
            return $this->presentResult($model);
        });

        $this->resetModel();
        return $results;
    }

    public function find(mixed $id, array $columns = ['*']): ?Model
    {
        $this->applyCriteria();
        $model = $this->model->find($id, $columns);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function findOrFail(mixed $id, array $columns = ['*']): Model
    {
        $this->applyCriteria();
        $model = $this->model->findOrFail($id, $columns);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function findByField(string $field, mixed $value, array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        $model = $this->model->where($field, '=', $value)->get($columns);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function findWhere(array $where, array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        $this->applyConditions($where);
        $model = $this->model->get($columns);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function findWhereFirst(array $where, array $columns = ['*']): ?Model
    {
        $this->applyCriteria();
        $this->applyConditions($where);
        $model = $this->model->first($columns);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function findWhereIn(string $field, array $values, array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        $model = $this->model->whereIn($field, $values)->get($columns);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function findWhereNotIn(string $field, array $values, array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        $model = $this->model->whereNotIn($field, $values)->get($columns);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function findWhereBetween(string $field, array $values, array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        $model = $this->model->whereBetween($field, $values)->get($columns);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function create(array $attributes): Model
    {
        if (!is_null($this->validator)) {
            $attributes = $this->validator->validate($attributes, 'create');
        }

        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function update(array $attributes, mixed $id): Model
    {
        if (!is_null($this->validator)) {
            $attributes = $this->validator->validate($attributes, 'update');
        }

        $this->applyCriteria();
        $model = $this->model->findOrFail($id);
        $model->fill($attributes);
        $model->save();
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        if (!is_null($this->validator)) {
            $attributes = $this->validator->validate($attributes, 'create');
            $values = $this->validator->validate($values, 'update');
        }

        $this->applyCriteria();
        $model = $this->model->updateOrCreate($attributes, $values);
        $this->resetModel();

        return $this->presentResult($model);
    }

    public function delete(mixed $id): int
    {
        $this->applyCriteria();
        $model = $this->model->findOrFail($id);
        $this->resetModel();
        $originalModel = clone $model;

        return $originalModel->delete();
    }

    public function deleteMultiple(array $ids): int
    {
        $this->applyCriteria();
        $deleted = $this->model->destroy($ids);
        $this->resetModel();

        return $deleted;
    }

    public function deleteWhere(array $where): int
    {
        $this->applyCriteria();
        $this->applyConditions($where);
        $deleted = $this->model->delete();
        $this->resetModel();

        return $deleted;
    }

    public function pushCriteria(CriteriaInterface $criteria): static
    {
        $this->criteria->push($criteria);
        return $this;
    }

    public function popCriteria(CriteriaInterface $criteria): static
    {
        $this->criteria = $this->criteria->reject(function ($item) use ($criteria) {
            return get_class($item) === get_class($criteria);
        });

        return $this;
    }

    public function getCriteria(): BaseCollection
    {
        return $this->criteria;
    }

    public function applyCriteria(): static
    {
        if ($this->skipCriteria === true) {
            return $this;
        }

        foreach ($this->getCriteria() as $criteria) {
            if ($criteria instanceof CriteriaInterface) {
                $this->model = $criteria->apply($this->model, $this);
            }
        }

        return $this;
    }

    public function skipCriteria(bool $status = true): static
    {
        $this->skipCriteria = $status;
        return $this;
    }

    public function clearCriteria(): static
    {
        $this->criteria = new BaseCollection();
        return $this;
    }

    public function skipPresenter(bool $status = true): static
    {
        $this->skipPresenter = $status;
        return $this;
    }

    protected function presentResult(mixed $result): mixed
    {
        if ($this->skipPresenter === true) {
            return $result;
        }

        if (isset($this->presenter) && $this->presenter instanceof PresenterInterface) {
            return $this->presenter->present($result);
        }

        return $result;
    }

    protected function applyConditions(array $where): void
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$field, $condition, $val] = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }
    }
}
