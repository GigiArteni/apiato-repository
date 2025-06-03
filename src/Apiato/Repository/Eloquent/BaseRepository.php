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
use Apiato\Repository\Traits\CacheableRepository;

/**
 * Enhanced BaseRepository - 100% compatible with l5-repository
 * Includes ALL original features + performance improvements + Apiato enhancements
 */
abstract class BaseRepository implements RepositoryInterface, CacheableInterface, Presentable, RepositoryCriteriaInterface
{
    use CacheableRepository;

    protected Application $app;
    protected Model $model;
    protected ?PresenterInterface $presenter = null;
    protected ?ValidatorInterface $validator = null;
    protected ?Closure $scopeQuery = null;
    protected Collection $criteria;
    protected bool $skipCriteria = false;
    protected bool $skipPresenter = false;
    protected array $fieldSearchable = [];
    protected array $with = [];
    protected array $hidden = [];
    protected array $visible = [];

    // l5-repository validation support
    protected ?array $rules = null;

    // Apiato enhancements (auto-enabled, backward compatible)
    protected ?object $hashIds = null;
    protected bool $hashIdEnabled = true;

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

    public function boot()
    {
        // Can be overridden in child classes
    }

    abstract public function model();

    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    public function makePresenter()
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

    public function makeValidator()
    {
        // Support for l5-repository validation via $rules property
        if (isset($this->rules) && !is_null($this->rules) && is_array($this->rules) && !empty($this->rules)) {
            if (class_exists('Prettus\\Validator\\LaravelValidator')) {
                $validator = app('Prettus\\Validator\\LaravelValidator');
                if (!is_null($validator)) {
                    $validator->with($this->rules);
                    $this->validator = $validator;
                }
            }
        }

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

    public function setPresenter($presenter)
    {
        $this->makePresenter($presenter);
        return $this;
    }

    public function skipPresenter($status = true)
    {
        $this->skipPresenter = $status;
        return $this;
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

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    // Core l5-repository methods with Apiato enhancements
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
        // Enhanced: Auto-detect and decode HashIds (Apiato enhancement)
        $id = $this->processIdValue($id);
        
        $this->applyCriteria();
        $this->applyScope();
        
        $model = $this->model->find($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function findByField($field, $value, $columns = ['*'])
    {
        // Enhanced: Handle HashId fields (Apiato enhancement)
        if ($this->isHashIdField($field)) {
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
        // Enhanced: Handle HashId arrays (Apiato enhancement)
        if ($this->isHashIdField($field)) {
            $where = $this->decodeHashIds($where);
        }

        $this->applyCriteria();
        $this->applyScope();
        
        $model = $this->model->whereIn($field, $where)->get($columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    public function findWhereNotIn($field, array $where, $columns = ['*'])
    {
        if ($this->isHashIdField($field)) {
            $where = $this->decodeHashIds($where);
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
        // l5-repository event support
        event(new RepositoryEntityCreating($this, $attributes));

        // Validation support
        if (!is_null($this->validator)) {
            $this->validator->with($attributes);
            
            if (!$this->validator->passesCreate()) {
                throw new \Exception('Validation failed');
            }
        }

        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();

        // l5-repository event support
        event(new RepositoryEntityCreated($this, $model));

        return $this->parserResult($model);
    }

    public function update(array $attributes, $id)
    {
        $id = $this->processIdValue($id);
        
        $this->applyCriteria();
        $this->applyScope();

        // l5-repository event support
        event(new RepositoryEntityUpdating($this, $attributes));

        // Validation support
        if (!is_null($this->validator)) {
            $this->validator->with($attributes);
            
            if (!$this->validator->passesUpdate()) {
                throw new \Exception('Validation failed');
            }
        }

        $model = $this->model->findOrFail($id);
        $model->fill($attributes);
        $model->save();

        $this->resetModel();

        // l5-repository event support
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
        
        // l5-repository event support
        event(new RepositoryEntityDeleting($this, $id));
        
        $model = $this->model->findOrFail($id);
        $this->resetModel();
        
        $originalModel = clone $model;
        $deleted = $originalModel->delete();

        // l5-repository event support
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

    public function orderBy($column, $direction = 'asc')
    {
        $this->model = $this->model->orderBy($column, $direction);
        return $this;
    }

    public function with(array $relations)
    {
        $this->model = $this->model->with($relations);
        return $this;
    }

    public function has(string $relation)
    {
        $this->model = $this->model->has($relation);
        return $this;
    }

    public function whereHas(string $relation, Closure $closure)
    {
        $this->model = $this->model->whereHas($relation, $closure);
        return $this;
    }

    public function hidden(array $fields)
    {
        $this->hidden = $fields;
        return $this;
    }

    public function visible(array $fields)
    {
        $this->visible = $fields;
        return $this;
    }

    public function scopeQuery(Closure $scope)
    {
        $this->scopeQuery = $scope;
        return $this;
    }

    // RepositoryCriteriaInterface implementation
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
                
                // Enhanced: Handle HashId fields in conditions
                if ($this->isHashIdField($field)) {
                    $val = $this->processIdValue($val);
                }
                
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                if ($this->isHashIdField($field)) {
                    $value = $this->processIdValue($value);
                }
                
                $this->model = $this->model->where($field, '=', $value);
            }
        }
    }

    // Apiato HashId enhancements (auto-enabled, backward compatible)
    protected function initializeHashIds()
    {
        if (!$this->hashIdEnabled || !config('repository.apiato.hashid_enabled', true)) {
            return;
        }

        try {
            if (app()->bound('hashids')) {
                $this->hashIds = app('hashids');
            } elseif (class_exists('Hashids\Hashids')) {
                $this->hashIds = new \Hashids\Hashids(
                    config('app.key'),
                    config('hashid.length', 6)
                );
            }
        } catch (Exception $e) {
            $this->hashIds = null;
        }
    }

    protected function decodeHashId(string $hashId): ?int
    {
        if (!$this->hashIds) {
            return is_numeric($hashId) ? (int)$hashId : null;
        }

        try {
            if (method_exists($this->hashIds, 'decode')) {
                $decoded = $this->hashIds->decode($hashId);
                return !empty($decoded) ? $decoded[0] : null;
            }
        } catch (Exception $e) {
            // Invalid hash
        }

        return is_numeric($hashId) ? (int)$hashId : null;
    }

    protected function decodeHashIds(array $hashIds): array
    {
        return array_filter(array_map([$this, 'decodeHashId'], $hashIds));
    }

    protected function looksLikeHashId(string $value): bool
    {
        return !is_numeric($value) && 
               strlen($value) >= 4 && 
               strlen($value) <= 20 && 
               preg_match('/^[a-zA-Z0-9]+$/', $value);
    }

    protected function processIdValue($value)
    {
        if (is_string($value) && $this->looksLikeHashId($value)) {
            $decoded = $this->decodeHashId($value);
            return $decoded ?? $value;
        }

        return $value;
    }

    protected function isHashIdField(string $field): bool
    {
        return str_ends_with($field, '_id') || $field === 'id';
    }
}
