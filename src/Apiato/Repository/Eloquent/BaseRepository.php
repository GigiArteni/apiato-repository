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
use Apiato\Repository\Events\RepositoryCreated;
use Apiato\Repository\Events\RepositoryCreating;
use Apiato\Repository\Events\RepositoryDeleted;
use Apiato\Repository\Events\RepositoryDeleting;
use Apiato\Repository\Events\RepositoryUpdated;
use Apiato\Repository\Events\RepositoryUpdating;
use Apiato\Repository\Exceptions\RepositoryException;
use Apiato\Repository\Traits\BulkOperations;
use Apiato\Repository\Traits\CacheableRepository;
use Apiato\Repository\Traits\TransactionalRepository;

/**
 * Lightweight BaseRepository for Apiato
 *
 * Removed: HashId, Sanitization, Presenter Layer
 */
abstract class BaseRepository implements RepositoryInterface, CacheableInterface, RepositoryCriteriaInterface
{
    use CacheableRepository;
    use TransactionalRepository;
    use BulkOperations;

    protected Application $app;
    protected Model $model;
    /**
     * @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|null
     */
    protected $query = null;
    protected ?ValidatorInterface $validator = null;
    protected ?Closure $scopeQuery = null;
    protected Collection $criteria;
    protected bool $skipCriteria = false;
    protected array $fieldSearchable = [];
    protected array $with = [];
    protected array $hidden = [];
    protected array $visible = [];
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
    public function boot()
    {
        // Can be overridden in child classes
    }

    /**
     * Specify Model class name
     * Must be implemented in child classes
     */
    abstract public function model();

    /**
     * Create model instance
     */
    public function makeModel()
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

    public function all($columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $results = $this->getQuery()->get($columns);
        $this->resetModel();
        $this->resetScope();
        return $results;
    }

    public function first($columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $results = $this->getQuery()->first($columns);
        $this->resetModel();
        $this->resetScope();
        return $results;
    }

    public function paginate($limit = null, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $limit = is_null($limit) ? config('repository.pagination.limit', 15) : $limit;
        $results = $this->getQuery()->paginate($limit, $columns);
        $this->resetModel();
        return $results;
    }

    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->find($id, $columns);
        $this->resetModel();
        return $model;
    }

    public function findByField($field, $value, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->where($field, '=', $value)->get($columns);
        $this->resetModel();
        return $model;
    }

    public function findWhere(array $where, $columns = ['*'])
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

    public function findWhereIn($field, array $where, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->whereIn($field, $where)->get($columns);
        $this->resetModel();
        return $model;
    }

    public function findWhereNotIn($field, array $where, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->whereNotIn($field, $where)->get($columns);
        $this->resetModel();
        return $model;
    }

    public function findWhereBetween($field, array $where, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->whereBetween($field, $where)->get($columns);
        $this->resetModel();
        return $model;
    }

    public function create(array $attributes)
    {
        event(new RepositoryCreating($this, $attributes));

        if (!is_null($this->validator)) {
            $this->validator->with($attributes);
            
            if (!$this->validator->passesCreate()) {
                throw new \Exception('Validation failed: ' . json_encode($this->validator->errors()));
            }
        }

        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();

        event(new RepositoryCreated($this, $model));

        return $model;
    }

    public function update(array $attributes, $id)
    {
        $this->applyCriteria();
        $this->applyScope();
        event(new RepositoryUpdating($this, $attributes));
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
        event(new RepositoryUpdated($this, $model));
        return $model;
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->getQuery()->updateOrCreate($attributes, $values);
        $this->resetModel();
        return $model;
    }

    public function delete($id)
    {
        $this->applyCriteria();
        $this->applyScope();
        event(new RepositoryDeleting($this, $id));
        $model = $this->getQuery()->findOrFail($id);
        $this->resetModel();
        $originalModel = clone $model;
        $deleted = $originalModel->delete();
        event(new RepositoryDeleted($this, $originalModel));
        return $deleted;
    }

    public function deleteWhere(array $where)
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
        return $deleted;
    }

    // ========================================
    // QUERY BUILDER METHODS
    // ========================================

    public function with(array $relations)
    {
        $this->query = $this->getQuery()->with($relations);
        return $this;
    }

    public function has(string $relation)
    {
        $this->query = $this->getQuery()->has($relation);
        return $this;
    }

    public function whereHas(string $relation, Closure $closure)
    {
        $this->query = $this->getQuery()->whereHas($relation, $closure);
        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->query = $this->getQuery()->orderBy($column, $direction);
        return $this;
    }

    // ========================================
    // IMPLEMENTATION METHODS
    // ========================================

    public function makeValidator()
    {
        $validator = $this->validator();

        if (!is_null($validator)) {
            $this->validator = is_string($validator) ? $this->app->make($validator) : $validator;
        }

        return null;
    }

    public function validator()
    {
        return null;
    }

    public function resetModel()
    {
        $this->makeModel();
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    // ========================================
    // CRITERIA METHODS
    // ========================================

    public function pushCriteria($criteria)
    {
        if (is_string($criteria)) {
            $criteria = new $criteria;
        }
        if (!$criteria instanceof CriteriaInterface) {
            throw new RepositoryException("Class " . get_class($criteria) . " must be an instance of CriteriaInterface");
        }
        $this->criteria->push($criteria);
        return $this;
    }

    public function popCriteria($criteria)
    {
        $this->criteria = $this->criteria->reject(function ($item) use ($criteria) {
            if (is_object($item) && is_string($criteria)) {
                return get_class($item) === $criteria;
            }

            if (is_string($item) && is_object($criteria)) {
                return $item === get_class($criteria);
            }

            return get_class($item) === get_class($criteria);
        });

        return $this;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }

    public function getByCriteria(CriteriaInterface $criteria)
    {
        $this->model = $criteria->apply($this->model, $this);
        $results = $this->model->get();
        $this->resetModel();

        return $results;
    }

    public function skipCriteria($status = true)
    {
        $this->skipCriteria = $status;
        return $this;
    }

    public function clearCriteria()
    {
        $this->criteria = new Collection();
        return $this;
    }

    public function applyCriteria()
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

    protected function applyScope()
    {
        if (is_callable($this->scopeQuery)) {
            $callback = $this->scopeQuery;
            $this->model = $callback($this->model);
        }
        return $this;
    }

    protected function resetScope()
    {
        $this->scopeQuery = null;
        return $this;
    }

    protected function applyConditions(array $where)
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
