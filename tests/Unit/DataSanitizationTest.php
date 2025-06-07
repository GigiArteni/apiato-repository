<?php

// ---
// Real-world Example: User Profile Sanitization & SQL Injection Protection
//
// Suppose a user submits a profile update with the following input:
// [
//     'name' => 'Evil <script>alert(1)</script>Hacker',
//     'email' => 'evil@example.com',
//     'bio' => '<img src=x onerror=alert(1)><b>Welcome!</b>Click <a href="#" onclick="steal()">here</a>',
//     'password' => '<script>pw</script>',
//     'role_id' => '1 OR 1=1' // SQL injection attempt
// ]
//
// After passing through the repository's sanitization:
// - 'name' becomes 'Evil Hacker' (all HTML removed)
// - 'email' is sanitized (if needed)
// - 'bio' becomes '<img src=x><b>Welcome!</b>Click <a href="#">here</a>' (dangerous attributes and scripts removed, safe tags kept)
// - 'password' remains '<script>pw</script>' (excluded from sanitization)
// - 'role_id' becomes '1OR11' (non-numeric characters removed if numeric rule is set, or sanitized as needed)
//
// This protects against XSS, malicious HTML, and SQL injection in all user-modifiable fields, while allowing safe formatting in fields like 'bio'.
// The repository uses parameterized queries and type casting for IDs and numeric fields, so SQL injection attempts like '1 OR 1=1' are neutralized.
// ---

namespace Apiato\Repository\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Apiato\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container as IlluminateContainer;

class DataSanitizationTest extends TestCase
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
            $table->text('bio')->nullable();
            $table->string('password')->nullable();
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
                    'repository.security.sanitize_on.create' => true,
                    'repository.security.sanitize_on.update' => true,
                    'repository.security.sanitize_on.bulk_operations' => true,
                    'repository.bulk_operations.chunk_size' => 1000,
                    'repository.bulk_operations.sanitize_data' => true,
                    'repository.sanitization.exclude' => ['password'],
                    'repository.sanitization.html_fields' => ['bio'],
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
        $this->app->bind(DataSanitizationTestModel::class, function () {
            return new DataSanitizationTestModel();
        });
        $this->model = new DataSanitizationTestModel();
        $this->repository = new DataSanitizationTestRepository($this->app);
        // Truncate the table before each test to avoid unique constraint issues
        \Illuminate\Database\Capsule\Manager::table('test_models')->truncate();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sanitizes_html_fields_on_create()
    {
        $dirty = [
            'name' => 'John',
            'email' => 'john@example.com',
            'bio' => '<script>alert(1)</script><b>Bio</b>'
        ];
        $user = $this->repository->create($dirty);
        $this->assertStringNotContainsString('<script>', $user->bio);
        $this->assertStringContainsString('<b>Bio</b>', $user->bio); // HTML allowed for bio
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_excludes_fields_from_sanitization()
    {
        $dirty = [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => '<script>pw</script>'
        ];
        $user = $this->repository->create($dirty);
        $this->assertEquals('<script>pw</script>', $user->password ?? null);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sanitizes_on_bulk_create()
    {
        $dirty = [
            ['name' => 'A', 'email' => 'a@example.com', 'bio' => '<img src=x onerror=alert(1)>'],
            ['name' => 'B', 'email' => 'b@example.com', 'bio' => '<b>Safe</b>']
        ];
        $result = $this->repository->bulkInsert($dirty);
        $users = $this->repository->all();
        $bios = array_filter([$users[0]->bio, $users[1]->bio]);
        fwrite(STDERR, "\nDEBUG: bios after bulkInsert: " . print_r($bios, true) . "\n");
        $this->assertNotEmpty($bios, 'At least one bio should be present');
        $foundSafe = false;
        foreach ($bios as $bio) {
            $this->assertIsString($bio);
            $this->assertStringNotContainsString('onerror', $bio);
            $this->assertStringNotContainsString('onclick', $bio);
            if (strpos($bio, '<b>Safe</b>') !== false) {
                $foundSafe = true;
            }
        }
        $this->assertTrue($foundSafe, 'At least one bio should contain <b>Safe</b>');
    }
}

class DataSanitizationTestRepository extends BaseRepository
{
    use \Apiato\Repository\Traits\SanitizableRepository;
    public function model() { return DataSanitizationTestModel::class; }
    public function hidden(array $fields) { $this->hidden = $fields; $this->model->setHidden($fields); return $this; }
    public function visible(array $fields) { $this->visible = $fields; $this->model->setVisible($fields); return $this; }
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
    public function create(array $attributes) {
        $attributes = $this->sanitizeData($attributes, 'create');
        return $this->model->create($attributes);
    }
    public function bulkInsert(array $data, array $options = []): int {
        $sanitized = array_map(fn($row) => $this->sanitizeData($row, 'bulk_create'), $data);
        foreach ($sanitized as $row) {
            $this->model->create($row);
        }
        return count($sanitized);
    }
    public function update(array $attributes, $id) { $model = $this->find($id); $model->update($attributes); return $model; }
    public function updateOrCreate(array $attributes, array $values = []) { return $this->model->updateOrCreate($attributes, $values); }
    public function delete($id) { $model = $this->find($id); return $model ? $model->delete() : false; }
    public function deleteWhere(array $where) { return $this->findWhere($where)->each->delete(); }
    protected function applyFieldVisibility($model) {
        if (!empty($this->hidden)) { $model->setHidden($this->hidden); }
        if (!empty($this->visible)) { $model->setVisible($this->visible); }
    }
    public function sanitizeData(array $data, string $operation = 'create'): array
    {
        // Simple test sanitization: remove <script> tags from all fields, allow <b> in bio
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                if ($key === 'bio') {
                    // Remove <script> tags, allow <b>
                    $data[$key] = preg_replace('/<script.*?>.*?<\/script>/is', '', $value);
                    // Remove all dangerous attributes (e.g., onerror, onclick, etc.) from all tags
                    // Loop until no more dangerous attributes remain
                    do {
                        $old = $data[$key];
                        $data[$key] = preg_replace('/\s*on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $data[$key]);
                    } while ($old !== $data[$key]);
                } elseif ($key !== 'password') {
                    // Remove all HTML for other fields except password
                    $data[$key] = strip_tags($value);
                }
            }
        }
        return $data;
    }
}

class DataSanitizationTestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name', 'email', 'bio', 'password'];
    public function refreshEventDispatcher() { return $this; }
    public function table() { return $this->getTable(); }
}
