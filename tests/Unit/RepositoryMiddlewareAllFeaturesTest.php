<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Apiato\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container as IlluminateContainer;

class RepositoryMiddlewareAllFeaturesTest extends TestCase
{
    protected $repository;
    protected $model;
    protected $app;

    public static function setUpBeforeClass(): void
    {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setEventDispatcher(new Dispatcher(new IlluminateContainer));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        Capsule::schema()->create('test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        global $__test_container;
        $this->app = new \Illuminate\Container\Container();
        $__test_container = $this->app;
        \Illuminate\Container\Container::setInstance($this->app);
        $this->app->singleton('config', function () {
            return new class implements \ArrayAccess {
                private $items = [
                    'repository.apiato.hashids.enabled' => false,
                    'repository.apiato.hashids.auto_decode' => false,
                    'repository.cache.enabled' => false,
                    'repository.apiato.hashids.decode_search' => false,
                    'repository.apiato.hashids.decode_filters' => false,
                    'repository.apiato.hashids.fields' => ['id', '*_id'],
                    'repository.apiato.hashids.auto_encode' => false,
                ];
                public function get($key, $default = null) { return $this->items[$key] ?? $default; }
                public function offsetExists($offset): bool { return isset($this->items[$offset]); }
                public function offsetGet($offset): mixed { return $this->items[$offset] ?? null; }
                public function offsetSet($offset, $value): void { $this->items[$offset] = $value; }
                public function offsetUnset($offset): void { unset($this->items[$offset]); }
            };
        });
        $this->app->singleton('events', function () {
            return new class($this->app) extends \Illuminate\Events\Dispatcher {
                public function __construct($app) { parent::__construct($app); }
                public function refreshEventDispatcher() { return $this; }
            };
        });
        \Illuminate\Support\Facades\Facade::setFacadeApplication($this->app);
        \Illuminate\Support\Facades\Event::swap($this->app['events']);
        $this->app->singleton('cache', function () {
            return new class {
                public function get($key) { return null; }
                public function put($key, $value, $minutes = null) { return true; }
                public function forget($key) { return true; }
                public function tags($names) { return $this; }
            };
        });
        $this->app->singleton('db', function () {
            return new class {
                public function transaction($callback, $attempts = 1) { return $callback(); }
                public function table($table) { return \Illuminate\Database\Capsule\Manager::table($table); }
            };
        });
        $this->app->bind(MiddlewareTestModel::class, function () {
            return new MiddlewareTestModel();
        });
        $this->model = new MiddlewareTestModel();
        $this->repository = new MiddlewareTestRepository($this->app);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_applies_repository_middleware_with_all_features()
    {
        $this->repository->middleware([
            'audit',
            'cache:10',
            'rate-limit:2',
            'performance:500',
            'tenant-scope:42',
        ]);
        $user = $this->repository->create(['name' => 'Middleware', 'email' => 'middleware@example.com']);
        $this->assertInstanceOf(MiddlewareTestModel::class, $user);
        $found = $this->repository->find($user->id);
        $this->assertEquals($user->id, $found->id);
        $this->repository->update(['name' => 'Middleware2'], $user->id);
        $this->assertEquals('Middleware2', $this->repository->find($user->id)->name);
        $this->repository->delete($user->id);
        $this->assertNull($this->repository->find($user->id));
    }
}

class MiddlewareTestRepository extends BaseRepository
{
    public function model()
    {
        return MiddlewareTestModel::class;
    }
    public function hidden(array $fields) {
        $this->hidden = $fields;
        $this->model->setHidden($fields);
        return $this;
    }
    public function visible(array $fields) {
        $this->visible = $fields;
        $this->model->setVisible($fields);
        return $this;
    }
    public function scopeQuery(\Closure $scope) { return $this; }
    public function getFieldsSearchable() { return []; }
    public function setPresenter($presenter) { return $this; }
    public function skipPresenter($status = true) { return $this; }
    public function all($columns = ['*']) { return $this->getQuery()->get($columns); }
    public function first($columns = ['*']) { return $this->getQuery()->first($columns); }
    public function paginate($limit = null, $columns = ['*']) { return $this->getQuery()->paginate($limit, $columns); }
    public function find($id, $columns = ['*']) { return $this->getQuery()->find($id, $columns); }
    public function findByField($field, $value, $columns = ['*']) { return $this->getQuery()->where($field, $value)->get($columns); }
    public function findWhere(array $where, $columns = ['*']) { $query = $this->getQuery(); foreach ($where as $k => $v) { $query->where($k, $v); } return $query->get($columns); }
    public function findWhereIn($field, array $where, $columns = ['*']) { return $this->getQuery()->whereIn($field, $where)->get($columns); }
    public function findWhereNotIn($field, array $where, $columns = ['*']) { return $this->getQuery()->whereNotIn($field, $where)->get($columns); }
    public function findWhereBetween($field, array $where, $columns = ['*']) { return $this->getQuery()->whereBetween($field, $where)->get($columns); }
    public function create(array $attributes) { return $this->model->create($attributes); }
    public function update(array $attributes, $id) { $model = $this->find($id); $model->update($attributes); return $model; }
    public function updateOrCreate(array $attributes, array $values = []) { return $this->model->updateOrCreate($attributes, $values); }
    public function delete($id) { $model = $this->find($id); return $model ? $model->delete() : false; }
    public function deleteWhere(array $where) { return $this->findWhere($where)->each->delete(); }
    protected function applyFieldVisibility($model)
    {
        if (!empty($this->hidden)) {
            $model->setHidden($this->hidden);
        }
        if (!empty($this->visible)) {
            $model->setVisible($this->visible);
        }
    }
}

class MiddlewareTestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name', 'email'];
    public function refreshEventDispatcher() { return $this; }
    public function table() { return $this->getTable(); }
    public static function factory()
    {
        return new class extends Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'email'];
            private $count = 1;
            public function count($count) { $this->count = $count; return $this; }
            public function create($attributes = []) {
                $created = [];
                for ($i = 0; $i < ($this->count ?? 1); $i++) {
                    $data = [
                        'name' => $attributes['name'] ?? 'User' . uniqid(),
                        'email' => $attributes['email'] ?? uniqid().'@example.com',
                    ];
                    $created[] = MiddlewareTestModel::create($data);
                }
                return collect($created);
            }
            public function pluck($field) {
                return MiddlewareTestModel::all()->pluck($field);
            }
        };
    }
}
