<?php

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Apiato\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container as IlluminateContainer;

class AdvancedBulkOperationTest extends TestCase
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
            $table->string('status')->nullable();
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
                    'repository.bulk_operations.enabled' => true,
                    'repository.bulk_operations.chunk_size' => 1000,
                    'repository.bulk_operations.use_transactions' => true,
                    'repository.bulk_operations.sanitize_data' => true,
                    'repository.bulk_operations.validate_hashids' => false,
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
        $this->app->bind(AdvancedBulkTestModel::class, function () {
            return new AdvancedBulkTestModel();
        });
        $this->model = new AdvancedBulkTestModel();
        $this->repository = new AdvancedBulkTestRepository($this->app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_insert_and_upsert()
    {
        $data = [
            ['name' => 'BulkA', 'email' => 'bulka@example.com', 'status' => 'pending'],
            ['name' => 'BulkB', 'email' => 'bulkb@example.com', 'status' => 'pending'],
        ];
        $inserted = $this->repository->bulkInsert($data);
        $this->assertEquals(2, $inserted);

        // Upsert: update BulkA, insert BulkC
        $upsertData = [
            ['name' => 'BulkA', 'email' => 'bulka@example.com', 'status' => 'active'],
            ['name' => 'BulkC', 'email' => 'bulkc@example.com', 'status' => 'pending'],
        ];
        $result = $this->repository->bulkUpsert($upsertData, ['email'], ['name', 'status']);
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals('active', $this->repository->findByField('email', 'bulka@example.com')->first()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_update_and_delete()
    {
        $this->repository->bulkInsert([
            ['name' => 'BulkD', 'email' => 'bulkd@example.com', 'status' => 'pending'],
            ['name' => 'BulkE', 'email' => 'bulke@example.com', 'status' => 'pending'],
        ]);
        $affected = $this->repository->bulkUpdate(['status' => 'done'], ['status' => 'pending']);
        $this->assertGreaterThanOrEqual(2, $affected);
        $deleted = $this->repository->bulkDelete(['status' => 'done']);
        $this->assertGreaterThanOrEqual(2, $deleted);
    }
}

class AdvancedBulkTestRepository extends BaseRepository
{
    public function model(): string
    {
        return AdvancedBulkTestModel::class;
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
    public function update(array $attributes, mixed $id): mixed { return $this->model->where('id', $id)->update($attributes); }
    public function updateOrCreate(array $attributes, array $values = []): mixed { return $this->model->updateOrCreate($attributes, $values); }
    public function delete(mixed $id): bool { return (bool) $this->model->destroy($id); }
    public function deleteWhere(array $where): int { $query = $this->getQuery(); foreach ($where as $k => $v) { $query->where($k, $v); } return $query->delete(); }
    public function findByCriteria(\Apiato\Repository\Contracts\CriteriaInterface $criteria): mixed { return null; }
    protected function applyFieldVisibility($model) {
        if (!empty($this->hidden)) { $model->setHidden($this->hidden); }
        if (!empty($this->visible)) { $model->setVisible($this->visible); }
    }
}

class AdvancedBulkTestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name', 'email', 'status'];
    public function refreshEventDispatcher() { return $this; }
    public function table() { return $this->getTable(); }
}
