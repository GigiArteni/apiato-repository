<?php

namespace Apiato\Repository\Eloquent;

use Closure;
use Exception;
use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Apiato\Repository\Contracts\CacheableInterface;
use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\Presentable;
use Apiato\Repository\Contracts\PresenterInterface;
use Apiato\Repository\Contracts\RepositoryCriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;
use Apiato\Repository\Contracts\ValidatorInterface;
use Apiato\Repository\Events\RepositoryEntityCreated;
use Apiato\Repository\Events\RepositoryEntityCreating;
use Apiato\Repository\Events\RepositoryEntityDeleted;
use Apiato\Repository\Events\RepositoryEntityDeleting;
use Apiato\Repository\Events\RepositoryEntityUpdated;
use Apiato\Repository\Events\RepositoryEntityUpdating;
use Apiato\Repository\Exceptions\RepositoryException;
use Apiato\Repository\Traits\BulkOperations;
use Apiato\Repository\Traits\CacheableRepository;
use Apiato\Repository\Traits\HasMiddleware;
use Apiato\Repository\Traits\SanitizableRepository;
use Apiato\Repository\Traits\TransactionalRepository;

/**
 * Enhanced BaseRepository for Apiato v.13
 * Includes HashId support using vinkla/hashids + performance improvements
 */
abstract class BaseRepository implements RepositoryInterface, CacheableInterface, Presentable, RepositoryCriteriaInterface
{
    use CacheableRepository;
    use SanitizableRepository;
    use TransactionalRepository;
    use BulkOperations;
    use HasMiddleware {
        HasMiddleware::all as middlewareAll;
        HasMiddleware::find as middlewareFind;
        HasMiddleware::create as middlewareCreate;
        HasMiddleware::update as middlewareUpdate;
        HasMiddleware::delete as middlewareDelete;
    } 

    protected Application $app;
    protected Model $model;
    protected ?Builder $query = null; // Store Builder instance for queries
    protected ?PresenterInterface $presenter = null;
    protected ?ValidatorInterface $validator = null; // Ensure this property is always defined
    protected ?Closure $scopeQuery = null;
    protected Collection $criteria;
    protected bool $skipCriteria = false;
    protected bool $skipPresenter = false;
    protected array $fieldSearchable = [];
    protected array $with = [];
    protected array $hidden = [];
    protected array $visible = [];

    // Validation support
    protected ?array $rules = null;

    // Apiato v.13 HashIds integration (vinkla/hashids)
    protected $hashIds = null;
    protected bool $hashIdsEnabled = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->criteria = new Collection();
        $this->makeModel();
        $this->makePresenter();
        $this->makeValidator();
        $this->initializeHashIds();
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

    /**
     * Initialize Apiato v.13 HashIds service (vinkla/hashids)
     */
    protected function initializeHashIds()
    {
        if (!config('repository.apiato.hashids.enabled', true)) {
            $this->hashIdsEnabled = false;
            return;
        }

        try {
            // Try to use Apiato's HashIds service (vinkla/hashids)
            if (app()->bound('hashids')) {
                $this->hashIds = app('hashids');
                $this->hashIdsEnabled = true;
            }
        } catch (Exception $e) {
            // No HashIds service available
            $this->hashIdsEnabled = false;
        }
    }

    /**
     * Decode HashId using Apiato's vinkla/hashids service
     */
    protected function decodeHashId($hashId)
    {
        if (!$this->hashIdsEnabled || !$this->hashIds) {
            return is_numeric($hashId) ? (int)$hashId : $hashId;
        }

        try {
            if (method_exists($this->hashIds, 'decode')) {
                $decoded = $this->hashIds->decode($hashId);
                return !empty($decoded) ? $decoded[0] : $hashId;
            }
        } catch (Exception $e) {
            // Return original if decoding fails
        }

        return is_numeric($hashId) ? (int)$hashId : $hashId;
    }

    /**
     * Process ID value - auto-decode HashIds
     */
    protected function processIdValue($value)
    {
        if (!$this->hashIdsEnabled || !config('repository.apiato.hashids.auto_decode', true)) {
            return $value;
        }

        if (is_string($value) && !is_numeric($value) && strlen($value) > 3) {
            return $this->decodeHashId($value);
        }

        return $value;
    }

    /**
     * Process array of IDs
     */
    protected function processIdArray(array $ids): array
    {
        return array_map([$this, 'processIdValue'], $ids);
    }

    /**
     * Check if field is an ID field
     */
    protected function isIdField(string $field): bool
    {
        return $field === 'id' || str_ends_with($field, '_id');
    }

    // ========================================
    // CORE REPOSITORY METHODS
    // ========================================

    public function all($columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        if ($this->model instanceof Builder) {
            $results = $this->model->get($columns);
        } else {
            $results = $this->model->all($columns);
        }

        $this->resetModel();
        $this->resetScope();

        return $this->parserResult($results);
    }

    public function first($columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $results = $this->model->first($columns);

        $this->resetModel();
        $this->resetScope();

        return $this->parserResult($results);
    }

    public function paginate($limit = null, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $limit = is_null($limit) ? config('repository.pagination.limit', 15) : $limit;
        $results = $this->model->paginate($limit, $columns);
        
        $results->getCollection()->transform(function ($model) {
            return $this->parserResult($model);
        });

        $this->resetModel();

        return $results;
    }

    public function find($id, $columns = ['*'])
    {
        $id = $this->processIdValue($id);
        
        $this->applyCriteria();
        $this->applyScope();
        
        $model = $this->model->find($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function findByField($field, $value, $columns = ['*'])
    {
        if ($this->isIdField($field)) {
            $value = $this->processIdValue($value);
        }

        $this->applyCriteria();
        $this->applyScope();
        
        $model = $this->model->where($field, '=', $value)->get($columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function findWhere(array $where, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $this->applyConditions($where);

        $model = $this->model->get($columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function findWhereIn($field, array $where, $columns = ['*'])
    {
        if ($this->isIdField($field)) {
            $where = $this->processIdArray($where);
        }

        $this->applyCriteria();
        $this->applyScope();
        
        $model = $this->model->whereIn($field, $where)->get($columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function findWhereNotIn($field, array $where, $columns = ['*'])
    {
        if ($this->isIdField($field)) {
            $where = $this->processIdArray($where);
        }

        $this->applyCriteria();
        $this->applyScope();
        
        $model = $this->model->whereNotIn($field, $where)->get($columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function findWhereBetween($field, array $where, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        
        $model = $this->model->whereBetween($field, $where)->get($columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function create(array $attributes)
    {
        event(new RepositoryEntityCreating($this, $attributes));

        if (!is_null($this->validator)) {
            $this->validator->with($attributes);
            
            if (!$this->validator->passesCreate()) {
                throw new \Exception('Validation failed: ' . json_encode($this->validator->errors()));
            }
        }

        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();

        event(new RepositoryEntityCreated($this, $model));

        return $this->parserResult($model);
    }

    public function update(array $attributes, $id)
    {
        $id = $this->processIdValue($id);
        
        $this->applyCriteria();
        $this->applyScope();

        event(new RepositoryEntityUpdating($this, $attributes));

        if (!is_null($this->validator)) {
            $this->validator->with($attributes);
            
            if (!$this->validator->passesUpdate()) {
                throw new \Exception('Validation failed: ' . json_encode($this->validator->errors()));
            }
        }

        $model = $this->model->findOrFail($id);
        $model->fill($attributes);
        $model->save();

        $this->resetModel();

        event(new RepositoryEntityUpdated($this, $model));

        return $this->parserResult($model);
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->updateOrCreate($attributes, $values);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function delete($id)
    {
        $id = $this->processIdValue($id);
        
        $this->applyCriteria();
        $this->applyScope();
        
        event(new RepositoryEntityDeleting($this, $id));
        
        $model = $this->model->findOrFail($id);
        $this->resetModel();
        
        $originalModel = clone $model;
        $deleted = $originalModel->delete();

        event(new RepositoryEntityDeleted($this, $originalModel));

        return $deleted;
    }

    public function deleteWhere(array $where)
    {
        $this->applyCriteria();
        $this->applyScope();

        $this->applyConditions($where);

        $deleted = $this->model->delete();

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

    public function makePresenter($presenter = null)
    {
        $presenter = $presenter ?: $this->presenter();

        if (!is_null($presenter)) {
            $this->presenter = is_string($presenter) ? $this->app->make($presenter) : $presenter;

            if (!$this->presenter instanceof PresenterInterface) {
                throw new RepositoryException("Class must be an instance of PresenterInterface");
            }

            return $this->presenter;
        }

        return null;
    }

    public function makeValidator()
    {
        $validator = $this->validator();

        if (!is_null($validator)) {
            $this->validator = is_string($validator) ? $this->app->make($validator) : $validator;
        }

        return null;
    }

    public function presenter()
    {
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

        return $this->parserResult($results);
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
        if ($this->skipCriteria === true) {
            return $this;
        }

        $criteria = $this->getCriteria();

        if ($criteria) {
            foreach ($criteria as $c) {
                if ($c instanceof CriteriaInterface) {
                    $this->model = $c->apply($this->model, $this);
                }
            }
        }

        return $this;
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    protected function applyScope()
    {
        if (isset($this->scopeQuery) && is_callable($this->scopeQuery)) {
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

    protected function parserResult($result)
    {
        if ($result instanceof \Illuminate\Database\Eloquent\Collection || $result instanceof LengthAwarePaginator) {  
            if ($result instanceof LengthAwarePaginator) {
                $result->getCollection()->each(function ($model) {
                    if ($model instanceof Model) {
                        $this->applyFieldVisibility($model);
                    }
                });
            } else {
                $result->each(function ($model) {
                    if ($model instanceof Model) {
                        $this->applyFieldVisibility($model);
                    }
                });
            }        
        } elseif ($result instanceof Model) {
            $this->applyFieldVisibility($result);
        }

        if ($this->presenter instanceof PresenterInterface && !$this->skipPresenter) {
            return $this->presenter->present($result);
        }

        return $result;
    }

    protected function applyFieldVisibility($model)
    {
        if (!empty($this->hidden)) {
            $model->makeHidden($this->hidden);
        }

        if (!empty($this->visible)) {
            $model->makeVisible($this->visible);
        }
    }

    protected function applyConditions(array $where)
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                [$field, $condition, $val] = $value;
                
                if ($this->isIdField($field)) {
                    $val = $this->processIdValue($val);
                }
                
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                if ($this->isIdField($field)) {
                    $value = $this->processIdValue($value);
                }
                
                $this->model = $this->model->where($field, '=', $value);
            }
        }
    }

    // Helper to get the current query builder (protected for test access)
    protected function getQuery(): Builder
    {
        return $this->query ?? $this->model->newQuery();
    }
}
