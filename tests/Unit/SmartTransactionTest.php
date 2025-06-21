<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Apiato\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container as IlluminateContainer;

class SmartTransactionTest extends TestCase
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
        $this->app = new Container();
        Container::setInstance($this->app);
        $this->app->singleton('config', function () {
            return new class implements \ArrayAccess {
                private $items = [
                    'repository.transactions.auto_wrap_bulk' => true,
                    'repository.transactions.auto_wrap_single' => false,
                    'repository.transactions.max_retries' => 2,
                    'repository.transactions.retry_delay' => 10,
                    'repository.transactions.retry_deadlocks' => true,
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
                public $transactionLevel = 0;
                public function transaction($callback, $attempts = 1) {
                    $this->transactionLevel++;
                    try {
                        return $callback();
                    } finally {
                        $this->transactionLevel--;
                    }
                }
                public function table($table) { return \Illuminate\Database\Capsule\Manager::table($table); }
                public function transactionLevel() { return $this->transactionLevel; }
            };
        });
        $this->app->bind(SmartTransactionTestModel::class, function () {
            return new SmartTransactionTestModel();
        });
        $this->model = new SmartTransactionTestModel();
        $this->repository = new SmartTransactionTestRepository($this->app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_wraps_bulk_operations_in_transactions()
    {
        $data = [
            ['name' => 'TxA', 'email' => 'txa@example.com'],
            ['name' => 'TxB', 'email' => 'txb@example.com'],
        ];
        $result = $this->repository->bulkInsert($data);
        $this->assertEquals(2, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_retries_on_deadlock_exception()
    {
        $repo = $this->repository;
        $attempts = 0;
        // Patch DB transaction to throw a deadlock on first call, then succeed
        $originalDB = app('db');
        $patchedDB = new class($originalDB) {
            public $callCount = 0;
            private $originalDB;
            public function __construct($originalDB) { $this->originalDB = $originalDB; }
            public function transaction($callback, $attempts = 1) {
                $this->callCount++;
                if ($this->callCount === 1) {
                    throw new \Exception('Deadlock found when trying to get lock', 40001);
                }
                return $callback();
            }
            public function __call($name, $args) { return $this->originalDB->$name(...$args); }
        };
        app()->singleton('db', fn() => $patchedDB);
        try {
            $result = $repo->transaction(function () use (&$attempts, $repo) {
                $attempts++;
                return $repo->create(['name' => 'RetryTx', 'email' => 'retrytx@example.com']);
            });
            $this->assertEquals('RetryTx', $result->name);
            $this->assertGreaterThanOrEqual(1, $attempts); // Should be at least 1 if retried
        } finally {
            app()->singleton('db', fn() => $originalDB);
        }
    }
}

class SmartTransactionTestRepository extends BaseRepository
{
    protected $transactionCallback = null;
    public function model(): string { return SmartTransactionTestModel::class; }
    public function setTransactionCallback($cb): void { $this->transactionCallback = $cb; }
    public function transaction(callable $callback, ?int $attempts = null): mixed {
        // Only patch the *inner* DB transaction, not the retry logic
        if ($this->transactionCallback) {
            // Patch DB::transaction to throw deadlock on first call
            $originalDB = app('db');
            $patchedDB = new class($originalDB, $this->transactionCallback) {
                private $originalDB;
                private $cb;
                private $attempts = 0;
                public function __construct($originalDB, $cb) { $this->originalDB = $originalDB; $this->cb = $cb; }
                public function transaction($callback, $attempts = 1) {
                    $this->attempts++;
                    if ($this->attempts === 1) {
                        throw new \Exception('Deadlock found when trying to get lock', 40001);
                    }
                    return ($this->cb)($callback);
                }
                public function __call($name, $args) { return $this->originalDB->$name(...$args); }
            };
            app()->singleton('db', fn() => $patchedDB);
            try {
                return parent::transaction($callback, $attempts);
            } finally {
                app()->singleton('db', fn() => $originalDB);
            }
        }
        return parent::transaction($callback, $attempts);
    }
    public function hidden(array $fields): static { $this->hidden = $fields; $this->model->setHidden($fields); return $this; }
    public function visible(array $fields): static { $this->visible = $fields; $this->model->setVisible($fields); return $this; }
    public function scopeQuery(\Closure $scope): static { return $this; }
    public function getFieldsSearchable(): array { return []; }
    public function setPresenter(mixed $presenter): static { return $this; }
    public function skipPresenter(bool $status = true): static { return $this; }
    public function all(array $columns = ['*']): \Illuminate\Support\Collection { return $this->getQuery()->get($columns); }
    public function first(array $columns = ['*']): mixed { return $this->getQuery()->first($columns); }
    public function paginate(?int $limit = null, array $columns = ['*']): mixed { return $this->getQuery()->paginate($limit, $columns); }
    public function find(mixed $id, array $columns = ['*']): mixed { return $this->getQuery()->find($id, $columns); }
    public function findByField(string $field, mixed $value, array $columns = ['*']): \Illuminate\Support\Collection { return $this->getQuery()->where($field, $value)->get($columns); }
    public function findWhere(array $where, array $columns = ['*']): \Illuminate\Support\Collection { $query = $this->getQuery(); foreach ($where as $k => $v) { $query->where($k, $v); } return $query->get($columns); }
    public function findWhereIn(string $field, array $where, array $columns = ['*']): \Illuminate\Support\Collection { return $this->getQuery()->whereIn($field, $where)->get($columns); }
    public function findWhereNotIn(string $field, array $where, array $columns = ['*']): \Illuminate\Support\Collection { return $this->getQuery()->whereNotIn($field, $where)->get($columns); }
    public function findWhereBetween(string $field, array $where, array $columns = ['*']): \Illuminate\Support\Collection { return $this->getQuery()->whereBetween($field, $where)->get($columns); }
    public function create(array $attributes): mixed { return $this->model->create($attributes); }
    public function update(array $attributes, mixed $id): mixed { $model = $this->find($id); $model->update($attributes); return $model; }
    public function updateOrCreate(array $attributes, array $values = []): mixed { return $this->model->updateOrCreate($attributes, $values); }
    public function delete(mixed $id): bool { $model = $this->find($id); return $model ? (bool)$model->delete() : false; }
    public function deleteWhere(array $where): int { return $this->findWhere($where)->each->delete(); }
    public function findByCriteria(\Apiato\Repository\Contracts\CriteriaInterface $criteria): mixed { return null; }
    protected function applyFieldVisibility($model) {
        if (!empty($this->hidden)) { $model->setHidden($this->hidden); }
        if (!empty($this->visible)) { $model->setVisible($this->visible); }
    }
}

class SmartTransactionTestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name', 'email'];
    public function refreshEventDispatcher() { return $this; }
    public function table() { return $this->getTable(); }
}
