<?php

namespace Apiato\Repository\Eloquent;

use Closure;
use Exception;
use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Apiato\Repository\Contracts\CacheableInterface;
use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryCriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;
use Apiato\Repository\Contracts\ValidatorInterface;
use Apiato\Repository\Exceptions\RepositoryException;
use Apiato\Repository\Traits\BulkOperations;
use Apiato\Repository\Traits\CacheableRepository;
use Apiato\Repository\Traits\TransactionalRepository;

/**
 * Lightweight BaseRepository for Apiato
 */
abstract class BaseRepository implements RepositoryInterface, CacheableInterface, RepositoryCriteriaInterface
{
    use CacheableRepository;
    use TransactionalRepository;
    use BulkOperations;

    protected Application $app;
    protected Model $model;
    /**
     * @var Builder|null
     */
    protected ?Builder $query = null;
    protected ?ValidatorInterface $validator = null;
    protected ?Closure $scopeQuery = null;
    /**
     * @var Collection<int, CriteriaInterface>
     */
    protected Collection $criteria;
    protected bool $skipCriteria = false;
    /**
     * @var array<string, string>
     */
    protected array $fieldSearchable = [];
    /**
     * @var array<int, string>
     */
    protected array $with = [];
    /**
     * @var array<int, string>
     */
    protected array $hidden = [];
    /**
     * @var array<int, string>
     */
    protected array $visible = [];
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $rules = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->criteria = new Collection();
        $this->makeModel();
        $this->makeValidator();
        $this->boot();
    }

    /**
     * Boot method for repository initialization
     * Override in child classes for custom setup
     */
    public function boot(): void
    {
        // Can be overridden in child classes
    }

    /**
     * Specify Model class name
     * Must be implemented in child classes
     */
    abstract public function model(): string;

    /**
     * Create model instance
     */
    public function makeModel(): Model
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }
        $this->query = null; // Reset query builder
        return $this->model = $model;
    }

    // ========================================
    // CORE REPOSITORY METHODS
    // ========================================

    public function all(array $columns = ['*']): \Illuminate\Support\Collection
    {
        $this->applyCriteria();
        $this->applyScope();
        $results = $this->getQuery()->get($columns);
        $this->resetModel();
        $this->resetScope();
        return $results;
    }

    public function first(array $columns = ['*']): mixed
    {
        $this->applyCriteria();
        $this->applyScope();
        $results = $this->getQuery()->first($columns);
        $this->resetModel();
        $this->resetScope();
        return $results;
    }

    public function paginate(int $limit = null, array $columns = ['*']): mixed
    {
        $this->applyCriteria();
        $this->applyScope();
        $limit = is_null($limit) ? config('repository.pagination.limit', 15) : $limit;
        $results = $this->getQuery()->paginate($limit, $columns);
        $this->resetModel();
        return $results;
    }

    public function find(mixed $id, array $columns = ['*']): mixed
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->find($id, $columns);
        $this->resetModel();
        return $model;
    }

    public function findByField(string $field, mixed $value, array $columns = ['*']): \Illuminate\Support\Collection
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->where($field, '=', $value)->get($columns);
        $this->resetModel();
        return $model;
    }

    public function findWhere(array $where, array $columns = ['*']): \Illuminate\Support\Collection
    {
        $this->applyCriteria();
        $this->applyScope();
        $query = $this->getQuery();
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$f, $condition, $val] = $value;
                $query = $query->where($f, $condition, $val);
            } else {
                $query = $query->where($field, '=', $value);
            }
        }
        $model = $query->get($columns);
        $this->resetModel();
        return $model;
    }

    public function findWhereIn(string $field, array $where, array $columns = ['*']): \Illuminate\Support\Collection
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->whereIn($field, $where)->get($columns);
        $this->resetModel();
        return $model;
    }

    public function findWhereNotIn(string $field, array $where, array $columns = ['*']): \Illuminate\Support\Collection
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->whereNotIn($field, $where)->get($columns);
        $this->resetModel();
        return $model;
    }

    public function findWhereBetween(string $field, array $where, array $columns = ['*']): \Illuminate\Support\Collection
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->whereBetween($field, $where)->get($columns);
        $this->resetModel();
        return $model;
    }

    public function create(array $attributes): mixed
    {
        if (!is_null($this->validator)) {
            $this->validator->with($attributes);
            if (!$this->validator->passesCreate()) {
                throw new \Exception('Validation failed: ' . json_encode($this->validator->errors()));
            }
        }
        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();
        return $model;
    }

    public function update(array $attributes, mixed $id): mixed
    {
        $this->applyCriteria();
        $this->applyScope();
        if (!is_null($this->validator)) {
            $this->validator->with($attributes);
            if (!$this->validator->passesUpdate()) {
                throw new \Exception('Validation failed: ' . json_encode($this->validator->errors()));
            }
        }
        $model = $this->getQuery()->findOrFail($id);
        $model->fill($attributes);
        $model->save();
        $this->resetModel();
        return $model;
    }

    public function updateOrCreate(array $attributes, array $values = []): mixed
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->updateOrCreate($attributes, $values);
        $this->resetModel();
        return $model;
    }

    public function delete(mixed $id): bool
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->findOrFail($id);
        $this->resetModel();
        $originalModel = clone $model;
        $deleted = $originalModel->delete();
        return (bool)$deleted;
    }

    public function deleteWhere(array $where): int
    {
        $this->applyCriteria();
        $this->applyScope();
        $query = $this->getQuery();
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$f, $condition, $val] = $value;
                $query = $query->where($f, $condition, $val);
            } else {
                $query = $query->where($field, '=', $value);
            }
        }
        $deleted = $query->delete();
        $this->resetModel();
        return (int)$deleted;
    }

    // ========================================
    // QUERY BUILDER METHODS
    // ========================================

    public function with(array $relations): static
    {
        $this->query = $this->getQuery()->with($relations);
        return $this;
    }

    public function has(string $relation): static
    {
        $this->query = $this->getQuery()->has($relation);
        return $this;
    }

    public function whereHas(string $relation, Closure $closure): static
    {
        $this->query = $this->getQuery()->whereHas($relation, $closure);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->query = $this->getQuery()->orderBy($column, $direction);
        return $this;
    }

    // ========================================
    // IMPLEMENTATION METHODS
    // ========================================

    public function makeValidator(): void
    {
        $validator = $this->validator();

        if (!is_null($validator)) {
            $this->validator = is_string($validator) ? $this->app->make($validator) : $validator;
        }
    }

    public function validator(): ValidatorInterface|null
    {
        return null;
    }

    public function resetModel(): static
    {
        $this->makeModel();
        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    // ========================================
    // CRITERIA METHODS
    // ========================================

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

    /**
     * @return Collection<int, CriteriaInterface>
     */
    public function getCriteria(): Collection
    {
        return $this->criteria;
    }

    public function getByCriteria(CriteriaInterface $criteria): array
    {
        $this->model = $criteria->apply($this->model, $this);
        $results = $this->model->get()->all();
        $this->resetModel();
        return $results;
    }

    public function skipCriteria(bool $status = true): static
    {
        $this->skipCriteria = $status;
        return $this;
    }

    public function clearCriteria(): static
    {
        $this->criteria = new Collection();
        return $this;
    }

    public function applyCriteria(): static
    {
        if ($this->skipCriteria) {
            return $this;
        }
        $criteria = $this->getCriteria();
        foreach ($criteria as $c) {
            if ($c instanceof CriteriaInterface) {
                $this->model = $c->apply($this->model, $this);
            }
        }
        return $this;
    }

    protected function applyScope(): static
    {
        if (is_callable($this->scopeQuery)) {
            $callback = $this->scopeQuery;
            $this->model = $callback($this->model);
        }
        return $this;
    }

    protected function resetScope(): static
    {
        $this->scopeQuery = null;
        return $this;
    }

    protected function applyConditions(array $where): void
    {
        $query = $this->getQuery();
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$f, $condition, $val] = $value;
                $query = $query->where($f, $condition, $val);
            } else {
                $query = $query->where($field, '=', $value);
            }
        }
        $this->query = $query;
    }

    // Helper to get the current query builder (protected for test access)
    protected function getQuery(): Builder
    {
        return $this->query ?? $this->model->newQuery();
    }
}
