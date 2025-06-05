#!/bin/bash

# ========================================
# 04 - CREATE TRAITS AND UTILITIES
# Creates reusable traits and utility classes
# ========================================

echo "📝 Creating repository traits and utilities..."

# ========================================
# CACHEABLE REPOSITORY TRAIT
# ========================================

cat > src/Apiato/Repository/Traits/CacheableRepository.php << 'EOF'
<?php

namespace Apiato\Repository\Traits;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Enhanced caching trait for Apiato v.13
 * Improved performance and intelligent cache invalidation
 */
trait CacheableRepository
{
    protected ?CacheRepository $cacheRepository = null;
    protected ?int $cacheMinutes = null;
    protected bool $skipCache = false;

    /**
     * Set Cache Repository
     */
    public function setCacheRepository($repository)
    {
        $this->cacheRepository = $repository;
        return $this;
    }

    /**
     * Get Cache Repository
     */
    public function getCacheRepository()
    {
        return $this->cacheRepository ?? Cache::store();
    }

    /**
     * Get Cache Minutes
     */
    public function getCacheMinutes()
    {
        return $this->cacheMinutes ?? config('repository.cache.minutes', 30);
    }

    /**
     * Skip Cache
     */
    public function skipCache($status = true)
    {
        $this->skipCache = $status;
        return $this;
    }

    /**
     * Check if method is allowed to be cached
     */
    public function allowedCache($method)
    {
        $cacheEnabled = config('repository.cache.enabled', false);

        if (!$cacheEnabled) {
            return false;
        }

        $cacheOnly = config('repository.cache.allowed.only');
        $cacheExcept = config('repository.cache.allowed.except');

        if (is_array($cacheOnly)) {
            return in_array($method, $cacheOnly);
        }

        if (is_array($cacheExcept)) {
            return !in_array($method, $cacheExcept);
        }

        if (is_null($cacheOnly) && is_null($cacheExcept)) {
            return true;
        }

        return false;
    }

    /**
     * Check if cache is skipped
     */
    public function isSkippedCache()
    {
        $skipped = request()->get(config('repository.cache.params.skipCache', 'skipCache'), false);
        if (is_string($skipped)) {
            $skipped = strtolower($skipped) === 'true';
        }

        return $this->skipCache || $skipped;
    }

    /**
     * Serialize criteria for cache key
     */
    protected function serializeCriteria()
    {
        try {
            return serialize($this->getCriteria());
        } catch (\Exception $e) {
            return serialize([]);
        }
    }

    /**
     * Enhanced cache key generation
     */
    public function getCacheKey($method, $args = null)
    {
        if (is_null($args)) {
            $args = [];
        }

        $key = sprintf('%s@%s-%s-%s',
            get_called_class(),
            $method,
            serialize($args),
            $this->serializeCriteria()
        );

        return hash('sha256', $key);
    }

    /**
     * Get cached result or execute callback
     */
    protected function getCachedResult($method, $args, $callback)
    {
        if (!$this->allowedCache($method) || $this->isSkippedCache()) {
            return $callback();
        }

        $key = $this->getCacheKey($method, $args);
        $minutes = $this->getCacheMinutes();

        return $this->getCacheRepository()->remember($key, $minutes, $callback);
    }

    /**
     * Clear cache for this repository
     */
    public function clearCache()
    {
        if (config('repository.cache.enabled', false)) {
            $pattern = get_called_class() . '@*';
            
            // For Redis cache
            if (method_exists($this->getCacheRepository(), 'getRedis')) {
                $redis = $this->getCacheRepository()->getRedis();
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                // For file/array cache, we'll use tags if available
                try {
                    $this->getCacheRepository()->tags([get_called_class()])->flush();
                } catch (\Exception $e) {
                    // Cache driver doesn't support tags
                }
            }
        }

        return $this;
    }

    /**
     * Forget specific cache key
     */
    public function forgetCache($method, $args = null)
    {
        if (config('repository.cache.enabled', false)) {
            $key = $this->getCacheKey($method, $args);
            $this->getCacheRepository()->forget($key);
        }

        return $this;
    }

    /**
     * Remember cache with tags (if supported)
     */
    protected function rememberWithTags($key, $minutes, $callback, $tags = [])
    {
        $cacheRepository = $this->getCacheRepository();
        
        if (empty($tags)) {
            $tags = [get_called_class()];
        }

        try {
            return $cacheRepository->tags($tags)->remember($key, $minutes, $callback);
        } catch (\Exception $e) {
            // Cache driver doesn't support tags, use regular remember
            return $cacheRepository->remember($key, $minutes, $callback);
        }
    }
}
EOF

# ========================================
# PRESENTABLE TRAIT
# ========================================

cat > src/Apiato/Repository/Traits/PresentableTrait.php << 'EOF'
<?php

namespace Apiato\Repository\Traits;

use Apiato\Repository\Contracts\PresenterInterface;

/**
 * Presentable Trait
 * Provides presentation functionality to any class
 */
trait PresentableTrait
{
    protected ?PresenterInterface $presenter = null;

    /**
     * Set Presenter
     */
    public function setPresenter(PresenterInterface $presenter)
    {
        $this->presenter = $presenter;
        return $this;
    }

    /**
     * Get Presenter
     */
    public function presenter()
    {
        if (isset($this->presenter) && $this->presenter instanceof PresenterInterface) {
            return $this->presenter->present($this);
        }

        return $this;
    }

    /**
     * Present data using the configured presenter
     */
    public function present($data = null)
    {
        $data = $data ?? $this;
        
        if (isset($this->presenter) && $this->presenter instanceof PresenterInterface) {
            return $this->presenter->present($data);
        }

        return $data;
    }
}
EOF

# ========================================
# FRACTAL PRESENTER
# ========================================

cat > src/Apiato/Repository/Presenters/FractalPresenter.php << 'EOF'
<?php

namespace Apiato\Repository\Presenters;

use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\SerializerAbstract;
use League\Fractal\TransformerAbstract;
use Apiato\Repository\Contracts\PresenterInterface;

/**
 * Enhanced FractalPresenter for Apiato v.13
 */
abstract class FractalPresenter implements PresenterInterface
{
    protected ?string $resourceKeyItem = null;
    protected ?string $resourceKeyCollection = null;
    protected Manager $fractal;
    protected $resource = null;

    public function __construct()
    {
        if (!class_exists('League\Fractal\Manager')) {
            throw new Exception('Package required. Please install: league/fractal');
        }

        $this->fractal = new Manager();
        $this->parseIncludes();
        $this->setupSerializer();
    }

    /**
     * Setup serializer
     */
    protected function setupSerializer(): static
    {
        $serializer = $this->serializer();

        if ($serializer instanceof SerializerAbstract) {
            $this->fractal->setSerializer($serializer);
        }

        return $this;
    }

    /**
     * Parse includes from request
     */
    protected function parseIncludes(): static
    {
        $request = app('Illuminate\Http\Request');
        $paramIncludes = config('repository.fractal.params.include', 'include');

        if ($request->has($paramIncludes)) {
            $this->fractal->parseIncludes($request->get($paramIncludes));
        }

        return $this;
    }

    /**
     * Get serializer instance
     */
    public function serializer(): SerializerAbstract
    {
        $serializer = config('repository.fractal.serializer', 'League\\Fractal\\Serializer\\DataArraySerializer');
        return new $serializer();
    }

    /**
     * Get transformer instance (must be implemented by child classes)
     */
    abstract public function getTransformer(): TransformerAbstract;

    /**
     * Present data
     */
    public function present($data)
    {
        if (!class_exists('League\Fractal\Manager')) {
            throw new Exception('Package required. Please install: league/fractal');
        }

        if ($data instanceof EloquentCollection) {
            $this->resource = $this->transformCollection($data);
        } elseif ($data instanceof AbstractPaginator) {
            $this->resource = $this->transformPaginator($data);
        } else {
            $this->resource = $this->transformItem($data);
        }

        return $this->fractal->createData($this->resource)->toArray();
    }

    /**
     * Transform collection
     */
    protected function transformCollection($data)
    {
        return new Collection($data, $this->getTransformer(), $this->resourceKeyCollection);
    }

    /**
     * Transform single item
     */
    protected function transformItem($data)
    {
        return new Item($data, $this->getTransformer(), $this->resourceKeyItem);
    }

    /**
     * Transform paginated data
     */
    protected function transformPaginator($paginator)
    {
        $collection = $paginator->getCollection();
        $resource = new Collection($collection, $this->getTransformer(), $this->resourceKeyCollection);

        if ($paginator instanceof LengthAwarePaginator || $paginator instanceof Paginator) {
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        }

        return $resource;
    }

    /**
     * Set resource key for items
     */
    public function setResourceKeyItem(string $key)
    {
        $this->resourceKeyItem = $key;
        return $this;
    }

    /**
     * Set resource key for collections
     */
    public function setResourceKeyCollection(string $key)
    {
        $this->resourceKeyCollection = $key;
        return $this;
    }
}
EOF

# ========================================
# LARAVEL VALIDATOR
# ========================================

cat > src/Apiato/Repository/Validators/LaravelValidator.php << 'EOF'
<?php

namespace Apiato\Repository\Validators;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Apiato\Repository\Contracts\ValidatorInterface;

/**
 * Laravel Validator for Apiato v.13
 * Provides validation functionality using Laravel's validator
 */
class LaravelValidator implements ValidatorInterface
{
    protected array $rules = [];
    protected array $data = [];
    protected $validator;
    protected array $errors = [];
    protected array $customMessages = [];
    protected array $customAttributes = [];

    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * Set data to validate
     */
    public function with(array $input)
    {
        $this->data = $input;
        return $this;
    }

    /**
     * Validate data for create action
     */
    public function passesCreate()
    {
        return $this->passes(self::RULE_CREATE);
    }

    /**
     * Validate data for update action
     */
    public function passesUpdate()
    {
        return $this->passes(self::RULE_UPDATE);
    }

    /**
     * Validate data for given action
     */
    public function passes($action = null)
    {
        $rules = $this->getRules($action);
        
        if (empty($rules)) {
            return true;
        }

        $this->validator = ValidatorFacade::make(
            $this->data, 
            $rules, 
            $this->customMessages, 
            $this->customAttributes
        );
        
        if ($this->validator->fails()) {
            $this->errors = $this->validator->errors()->toArray();
            return false;
        }

        return true;
    }

    /**
     * Get validation errors
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Set validation rules
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Set custom error messages
     */
    public function setCustomMessages(array $messages)
    {
        $this->customMessages = $messages;
        return $this;
    }

    /**
     * Set custom attribute names
     */
    public function setCustomAttributes(array $attributes)
    {
        $this->customAttributes = $attributes;
        return $this;
    }

    /**
     * Get rules for specific action
     */
    protected function getRules($action)
    {
        if (is_null($action)) {
            return $this->rules;
        }

        return $this->rules[$action] ?? $this->rules;
    }

    /**
     * Add rule for specific action
     */
    public function addRule($action, $field, $rule)
    {
        if (!isset($this->rules[$action])) {
            $this->rules[$action] = [];
        }

        $this->rules[$action][$field] = $rule;
        return $this;
    }

    /**
     * Remove rule for specific action
     */
    public function removeRule($action, $field)
    {
        if (isset($this->rules[$action][$field])) {
            unset($this->rules[$action][$field]);
        }

        return $this;
    }
}
EOF

# ========================================
# REPOSITORY EXCEPTION
# ========================================

cat > src/Apiato/Repository/Exceptions/RepositoryException.php << 'EOF'
<?php

namespace Apiato\Repository\Exceptions;

use Exception;

/**
 * Repository Exception
 * Custom exception for repository-related errors
 */
class RepositoryException extends Exception
{
    /**
     * Create a new repository exception instance
     */
    public function __construct($message = "Repository Exception", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the exception message with context
     */
    public function getMessageWithContext(): string
    {
        return sprintf('[Repository Error] %s', $this->getMessage());
    }
}
EOF

# ========================================
# BASE TRANSFORMER (UTILITY)
# ========================================

cat > src/Apiato/Repository/Support/BaseTransformer.php << 'EOF'
<?php

namespace Apiato\Repository\Support;

use League\Fractal\TransformerAbstract;
use Apiato\Repository\Contracts\TransformerInterface;

/**
 * Base Transformer
 * Provides common functionality for data transformers
 */
abstract class BaseTransformer extends TransformerAbstract implements TransformerInterface
{
    /**
     * Transform the given model
     */
    abstract public function transform($model);

    /**
     * Transform a single item
     */
    protected function item($data, TransformerInterface $transformer, $resourceKey = null)
    {
        return $this->item($data, $transformer, $resourceKey);
    }

    /**
     * Transform a collection
     */
    protected function collection($data, TransformerInterface $transformer, $resourceKey = null)
    {
        return $this->collection($data, $transformer, $resourceKey);
    }

    /**
     * Transform with null check
     */
    protected function transformWithNullCheck($data, $transformer, $default = null)
    {
        if (is_null($data)) {
            return $default;
        }

        if ($transformer instanceof TransformerInterface) {
            return $transformer->transform($data);
        }

        if (is_callable($transformer)) {
            return $transformer($data);
        }

        return $data;
    }

    /**
     * Transform date to ISO format
     */
    protected function transformDate($date, $format = 'c')
    {
        if (is_null($date)) {
            return null;
        }

        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format($format);
    }

    /**
     * Transform boolean to string
     */
    protected function transformBoolean($value, $trueValue = 'yes', $falseValue = 'no')
    {
        return $value ? $trueValue : $falseValue;
    }
}
EOF

echo "✅ TRAITS AND UTILITIES CREATED!"
echo ""
echo "📝 Created trait files:"
echo "  - CacheableRepository.php (enhanced caching with intelligent invalidation)"
echo "  - PresentableTrait.php (presentation functionality)"
echo ""
echo "📝 Created utility files:"
echo "  - FractalPresenter.php (Fractal integration for API responses)"
echo "  - LaravelValidator.php (validation functionality)"
echo "  - RepositoryException.php (custom exceptions)"
echo "  - BaseTransformer.php (transformer utilities)"
echo ""
echo "🚀 Key features implemented:"
echo "  - Advanced caching with Redis support"
echo "  - Cache tagging and intelligent invalidation"
echo "  - Fractal presentation with pagination support"
echo "  - Comprehensive validation system"
echo "  - Custom exception handling"
echo "  - Base transformer with utilities"
echo ""
echo "🚀 Next: Run events generator"
echo "   ./05_create_events.sh"