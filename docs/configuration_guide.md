# Configuration - Complete Setup & Customization

Comprehensive guide to configuring Apiato Repository for optimal performance, security, and functionality across all environments from development to enterprise production.

## 📚 Table of Contents

- [Core Configuration](#-core-configuration)
- [Environment-Specific Setup](#-environment-specific-setup)
- [Cache Configuration](#-cache-configuration)
- [Database Optimization](#-database-optimization)
- [HashId Configuration](#-hashid-configuration)
- [Performance Tuning](#-performance-tuning)
- [Security Configuration](#-security-configuration)
- [Monitoring & Logging](#-monitoring--logging)

## ⚙️ Core Configuration

### Basic Repository Configuration

```php
<?php
// config/repository.php - Complete configuration file

return [
    /*
    |--------------------------------------------------------------------------
    | Repository Generator Settings
    |--------------------------------------------------------------------------
    | Configure paths, namespaces, and templates for code generation
    */
    'generator' => [
        'basePath' => app_path(),
        'rootNamespace' => 'App\\',
        'stubsOverridePath' => resource_path('stubs/repository'),
        
        'paths' => [
            'models' => 'Models',
            'repositories' => 'Repositories',
            'interfaces' => 'Repositories/Contracts',
            'criteria' => 'Criteria',
            'transformers' => 'Transformers',
            'presenters' => 'Presenters',
            'validators' => 'Validators',
            'controllers' => 'Http/Controllers',
            'requests' => 'Http/Requests',
            'tests' => 'Tests',
            'provider' => 'Providers/RepositoryServiceProvider',
        ],
        
        'templates' => [
            'repository' => 'repository.stub',
            'criteria' => 'criteria.stub',
            'presenter' => 'presenter.stub',
            'transformer' => 'transformer.stub',
            'validator' => 'validator.stub',
            'controller' => 'controller.stub',
            'request' => 'request.stub',
            'test' => 'test.stub',
        ],
        
        'defaults' => [
            'author' => env('GENERATOR_AUTHOR', 'Apiato Developer'),
            'email' => env('GENERATOR_EMAIL', 'dev@apiato.io'),
            'version' => env('APP_VERSION', '1.0.0'),
            'license' => 'MIT',
            'include_docblocks' => true,
            'include_relationships' => true,
            'include_timestamps' => true,
            'include_soft_deletes' => false,
            'strict_types' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    | Default pagination settings for all repositories
    */
    'pagination' => [
        'limit' => env('REPOSITORY_PAGINATION_LIMIT', 15),
        'max_limit' => env('REPOSITORY_PAGINATION_MAX', 100),
        'page_name' => 'page',
        'page_size_name' => 'per_page',
        'simple' => false, // Use simple pagination for better performance
        'show_disabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    | Repository caching settings with intelligent invalidation
    */
    'cache' => [
        'enabled' => env('REPOSITORY_CACHE_ENABLED', true),
        'minutes' => env('REPOSITORY_CACHE_MINUTES', 30),
        'repository' => env('REPOSITORY_CACHE_STORE', 'redis'),
        'prefix' => env('REPOSITORY_CACHE_PREFIX', 'repo'),
        'compression' => env('REPOSITORY_CACHE_COMPRESSION', true),
        'serializer' => env('REPOSITORY_CACHE_SERIALIZER', 'igbinary'),
        
        'clean' => [
            'enabled' => env('REPOSITORY_CACHE_CLEAN_ENABLED', true),
            'on' => [
                'create' => true,
                'update' => true,
                'delete' => true,
            ],
        ],
        
        'params' => [
            'skipCache' => 'skipCache',
            'cacheTime' => 'cacheTime',
        ],
        
        'allowed' => [
            'only' => env('REPOSITORY_CACHE_ONLY') ? explode(',', env('REPOSITORY_CACHE_ONLY')) : null,
            'except' => env('REPOSITORY_CACHE_EXCEPT') ? explode(',', env('REPOSITORY_CACHE_EXCEPT')) : null,
        ],
        
        'tags' => [
            'enabled' => env('REPOSITORY_CACHE_TAGS_ENABLED', true),
            'generator' => env('REPOSITORY_CACHE_TAG_GENERATOR', 'model'), // model, custom
            'prefix' => env('REPOSITORY_CACHE_TAG_PREFIX', 'repo_tag'),
        ],
        
        'levels' => [
            'l1' => [ // Application cache
                'enabled' => true,
                'size' => 1000, // Number of items
                'ttl' => 300, // 5 minutes
            ],
            'l2' => [ // Redis cache  
                'enabled' => true,
                'ttl' => 1800, // 30 minutes
            ],
            'l3' => [ // File cache
                'enabled' => false,
                'ttl' => 3600, // 1 hour
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Criteria Configuration
    |--------------------------------------------------------------------------
    | Settings for RequestCriteria and custom criteria
    */
    'criteria' => [
        'params' => [
            'search' => env('CRITERIA_SEARCH_PARAM', 'search'),
            'searchFields' => env('CRITERIA_SEARCH_FIELDS_PARAM', 'searchFields'),
            'filter' => env('CRITERIA_FILTER_PARAM', 'filter'),
            'orderBy' => env('CRITERIA_ORDER_BY_PARAM', 'orderBy'),
            'sortedBy' => env('CRITERIA_SORTED_BY_PARAM', 'sortedBy'),
            'with' => env('CRITERIA_WITH_PARAM', 'with'),
            'withCount' => env('CRITERIA_WITH_COUNT_PARAM', 'withCount'),
        ],
        
        'acceptedConditions' => [
            '=', '!=', '<>', '>', '<', '>=', '<=',
            'like', 'ilike', 'not_like',
            'in', 'not_in', 'notin',
            'between', 'not_between', 'date_between',
            'null', 'not_null', 'is_null', 'is_not_null',
            'exists', 'not_exists',
            'regex', 'not_regex',
        ],
        
        'searchable' => [
            'wildcard_position' => env('SEARCH_WILDCARD_POSITION', 'both'), // left, right, both
            'case_sensitive' => env('SEARCH_CASE_SENSITIVE', false),
            'accent_sensitive' => env('SEARCH_ACCENT_SENSITIVE', false),
            'min_search_length' => env('SEARCH_MIN_LENGTH', 2),
            'max_search_length' => env('SEARCH_MAX_LENGTH', 255),
        ],
        
        'relationships' => [
            'max_depth' => env('CRITERIA_MAX_RELATIONSHIP_DEPTH', 3),
            'auto_eager_load' => env('CRITERIA_AUTO_EAGER_LOAD', true),
            'prevent_n_plus_one' => env('CRITERIA_PREVENT_N_PLUS_ONE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    | Repository validation settings
    */
    'validation' => [
        'enabled' => env('REPOSITORY_VALIDATION_ENABLED', true),
        'skip_on_console' => env('REPOSITORY_VALIDATION_SKIP_CONSOLE', false),
        'skip_on_testing' => env('REPOSITORY_VALIDATION_SKIP_TESTING', false),
        
        'rules' => [
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
        ],
        
        'messages' => [
            'enabled' => true,
            'locale' => env('REPOSITORY_VALIDATION_LOCALE', app()->getLocale()),
            'fallback_locale' => env('REPOSITORY_VALIDATION_FALLBACK_LOCALE', 'en'),
        ],
        
        'exceptions' => [
            'stop_on_first_failure' => env('REPOSITORY_VALIDATION_STOP_ON_FIRST', true),
            'include_trace' => env('REPOSITORY_VALIDATION_INCLUDE_TRACE', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fractal Presenter Configuration
    |--------------------------------------------------------------------------
    | Settings for Fractal-based data presentation
    */
    'fractal' => [
        'params' => [
            'include' => env('FRACTAL_INCLUDE_PARAM', 'include'),
            'exclude' => env('FRACTAL_EXCLUDE_PARAM', 'exclude'),
        ],
        
        'serializer' => env('FRACTAL_SERIALIZER', \League\Fractal\Serializer\DataArraySerializer::class),
        
        'auto_includes' => [
            'enabled' => env('FRACTAL_AUTO_INCLUDES', true),
            'max_depth' => env('FRACTAL_MAX_INCLUDE_DEPTH', 2),
        ],
        
        'pagination' => [
            'adapter' => env('FRACTAL_PAGINATION_ADAPTER', \League\Fractal\Pagination\IlluminatePaginatorAdapter::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Apiato Enhancements
    |--------------------------------------------------------------------------
    | Enhanced features specific to Apiato Repository
    */
    'apiato' => [
        'hashid_enabled' => env('APIATO_HASHID_ENABLED', true),
        'hashid_connection' => env('APIATO_HASHID_CONNECTION', 'default'),
        'auto_cache_clear' => env('APIATO_AUTO_CACHE_CLEAR', true),
        'enhanced_search' => env('APIATO_ENHANCED_SEARCH', true),
        'performance_monitoring' => env('APIATO_PERFORMANCE_MONITORING', true),
        'event_dispatching' => env('APIATO_EVENT_DISPATCHING', true),
        
        'compatibility' => [
            'l5_repository' => env('APIATO_L5_COMPATIBILITY', true),
            'alias_classes' => env('APIATO_ALIAS_CLASSES', true),
            'legacy_methods' => env('APIATO_LEGACY_METHODS', true),
        ],
        
        'features' => [
            'auto_relationship_loading' => env('APIATO_AUTO_RELATIONSHIP_LOADING', true),
            'intelligent_caching' => env('APIATO_INTELLIGENT_CACHING', true),
            'query_optimization' => env('APIATO_QUERY_OPTIMIZATION', true),
            'memory_optimization' => env('APIATO_MEMORY_OPTIMIZATION', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    | Performance monitoring and optimization settings
    */
    'performance' => [
        'monitoring' => [
            'enabled' => env('REPOSITORY_PERFORMANCE_MONITORING', true),
            'slow_query_threshold' => env('REPOSITORY_SLOW_QUERY_THRESHOLD', 100), // milliseconds
            'memory_threshold' => env('REPOSITORY_MEMORY_THRESHOLD', 256 * 1024 * 1024), // 256MB
            'log_slow_queries' => env('REPOSITORY_LOG_SLOW_QUERIES', true),
            'track_memory_usage' => env('REPOSITORY_TRACK_MEMORY_USAGE', true),
        ],
        
        'optimization' => [
            'chunked_processing' => env('REPOSITORY_CHUNKED_PROCESSING', true),
            'chunk_size' => env('REPOSITORY_CHUNK_SIZE', 1000),
            'lazy_loading' => env('REPOSITORY_LAZY_LOADING', true),
            'query_caching' => env('REPOSITORY_QUERY_CACHING', true),
            'result_caching' => env('REPOSITORY_RESULT_CACHING', true),
        ],
        
        'limits' => [
            'max_results' => env('REPOSITORY_MAX_RESULTS', 10000),
            'max_includes' => env('REPOSITORY_MAX_INCLUDES', 10),
            'max_search_terms' => env('REPOSITORY_MAX_SEARCH_TERMS', 5),
            'max_filter_conditions' => env('REPOSITORY_MAX_FILTER_CONDITIONS', 20),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration  
    |--------------------------------------------------------------------------
    | Security settings for repositories
    */
    'security' => [
        'rate_limiting' => [
            'enabled' => env('REPOSITORY_RATE_LIMITING', false),
            'max_requests' => env('REPOSITORY_MAX_REQUESTS', 1000),
            'window_minutes' => env('REPOSITORY_RATE_WINDOW', 60),
        ],
        
        'input_sanitization' => [
            'enabled' => env('REPOSITORY_INPUT_SANITIZATION', true),
            'strip_tags' => env('REPOSITORY_STRIP_TAGS', true),
            'escape_html' => env('REPOSITORY_ESCAPE_HTML', true),
        ],
        
        'query_protection' => [
            'prevent_sql_injection' => env('REPOSITORY_PREVENT_SQL_INJECTION', true),
            'sanitize_search_terms' => env('REPOSITORY_SANITIZE_SEARCH', true),
            'validate_sort_fields' => env('REPOSITORY_VALIDATE_SORT_FIELDS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    | Repository logging settings
    */
    'logging' => [
        'enabled' => env('REPOSITORY_LOGGING_ENABLED', true),
        'level' => env('REPOSITORY_LOG_LEVEL', 'info'),
        'channel' => env('REPOSITORY_LOG_CHANNEL', 'repository'),
        
        'events' => [
            'log_queries' => env('REPOSITORY_LOG_QUERIES', false),
            'log_slow_operations' => env('REPOSITORY_LOG_SLOW_OPERATIONS', true),
            'log_errors' => env('REPOSITORY_LOG_ERRORS', true),
            'log_cache_operations' => env('REPOSITORY_LOG_CACHE_OPERATIONS', false),
        ],
        
        'format' => [
            'include_user_id' => env('REPOSITORY_LOG_INCLUDE_USER_ID', true),
            'include_ip_address' => env('REPOSITORY_LOG_INCLUDE_IP', true),
            'include_request_id' => env('REPOSITORY_LOG_INCLUDE_REQUEST_ID', true),
            'include_stack_trace' => env('REPOSITORY_LOG_INCLUDE_STACK_TRACE', false),
        ],
    ],
];
```

## 🌍 Environment-Specific Setup

### Development Environment

```bash
# .env.development
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_CACHE_MINUTES=5
REPOSITORY_CACHE_STORE=array

# Enable detailed logging
REPOSITORY_LOGGING_ENABLED=true
REPOSITORY_LOG_LEVEL=debug
REPOSITORY_LOG_QUERIES=true
REPOSITORY_LOG_CACHE_OPERATIONS=true

# Performance monitoring
REPOSITORY_PERFORMANCE_MONITORING=true
REPOSITORY_SLOW_QUERY_THRESHOLD=50
REPOSITORY_TRACK_MEMORY_USAGE=true

# HashId settings
APIATO_HASHID_ENABLED=true
HASHID_LENGTH=6
HASHID_SALT="${APP_KEY}_dev"

# Validation
REPOSITORY_VALIDATION_ENABLED=true
REPOSITORY_VALIDATION_STOP_ON_FIRST=false
REPOSITORY_VALIDATION_INCLUDE_TRACE=true

# Generator defaults
GENERATOR_AUTHOR="Development Team"
GENERATOR_EMAIL="dev@yourapp.com"
```

### Testing Environment

```bash
# .env.testing
REPOSITORY_CACHE_ENABLED=false
REPOSITORY_VALIDATION_ENABLED=true
REPOSITORY_VALIDATION_SKIP_TESTING=false

# Disable performance monitoring for faster tests
REPOSITORY_PERFORMANCE_MONITORING=false
REPOSITORY_LOGGING_ENABLED=false

# Use in-memory database
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Simplified HashIds for testing
APIATO_HASHID_ENABLED=true
HASHID_SALT="test_salt"

# Disable rate limiting
REPOSITORY_RATE_LIMITING=false

# Enable all security features for testing
REPOSITORY_INPUT_SANITIZATION=true
REPOSITORY_PREVENT_SQL_INJECTION=true
```

### Staging Environment

```bash
# .env.staging
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_CACHE_MINUTES=30
REPOSITORY_CACHE_STORE=redis

# Production-like performance monitoring
REPOSITORY_PERFORMANCE_MONITORING=true
REPOSITORY_SLOW_QUERY_THRESHOLD=100
REPOSITORY_LOG_SLOW_OPERATIONS=true

# Enhanced security
REPOSITORY_RATE_LIMITING=true
REPOSITORY_MAX_REQUESTS=500
REPOSITORY_INPUT_SANITIZATION=true

# HashId with staging salt
HASHID_SALT="${APP_KEY}_staging"

# Moderate logging
REPOSITORY_LOGGING_ENABLED=true
REPOSITORY_LOG_LEVEL=info
REPOSITORY_LOG_QUERIES=false
```

### Production Environment

```bash
# .env.production
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_CACHE_MINUTES=60
REPOSITORY_CACHE_STORE=redis
REPOSITORY_CACHE_COMPRESSION=true
REPOSITORY_CACHE_SERIALIZER=igbinary

# Optimized performance settings
REPOSITORY_PERFORMANCE_MONITORING=true
REPOSITORY_SLOW_QUERY_THRESHOLD=200
REPOSITORY_CHUNKED_PROCESSING=true
REPOSITORY_CHUNK_SIZE=1000
REPOSITORY_LAZY_LOADING=true

# Maximum security
REPOSITORY_RATE_LIMITING=true
REPOSITORY_MAX_REQUESTS=1000
REPOSITORY_RATE_WINDOW=60
REPOSITORY_INPUT_SANITIZATION=true
REPOSITORY_PREVENT_SQL_INJECTION=true
REPOSITORY_SANITIZE_SEARCH=true

# Production HashId settings
HASHID_LENGTH=8
HASHID_SALT="${APP_KEY}_prod_secure"

# Minimal logging for performance
REPOSITORY_LOGGING_ENABLED=true
REPOSITORY_LOG_LEVEL=warning
REPOSITORY_LOG_QUERIES=false
REPOSITORY_LOG_CACHE_OPERATIONS=false
REPOSITORY_LOG_ERRORS=true
```

## 📦 Cache Configuration

### Redis Cache Setup

```php
// config/cache.php - Redis optimization for repositories
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        'serializer' => env('REPOSITORY_CACHE_SERIALIZER', 'igbinary'),
        'compress' => env('REPOSITORY_CACHE_COMPRESSION', true),
        'prefix' => env('REPOSITORY_CACHE_PREFIX', 'repo') . ':',
    ],
    
    // Dedicated repository cache store
    'repository' => [
        'driver' => 'redis',
        'connection' => 'repository_cache',
        'serializer' => 'igbinary',
        'compress' => true,
        'prefix' => 'apiato_repo:',
    ],
],

// config/database.php - Redis connections
'redis' => [
    'cache' => [
        'host' => env('REDIS_CACHE_HOST', env('REDIS_HOST', '127.0.0.1')),
        'password' => env('REDIS_CACHE_PASSWORD', env('REDIS_PASSWORD', null)),
        'port' => env('REDIS_CACHE_PORT', env('REDIS_PORT', 6379)),
        'database' => env('REDIS_CACHE_DB', 1),
        'options' => [
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
            'serializer' => 'igbinary',
            'compression' => 'lz4',
        ],
    ],
    
    'repository_cache' => [
        'host' => env('REDIS_REPO_HOST', env('REDIS_HOST', '127.0.0.1')),
        'password' => env('REDIS_REPO_PASSWORD', env('REDIS_PASSWORD', null)),
        'port' => env('REDIS_REPO_PORT', env('REDIS_PORT', 6379)),
        'database' => env('REDIS_REPO_DB', 2),
        'options' => [
            'prefix' => 'apiato_repo:',
            'serializer' => 'igbinary',
            'compression' => 'lz4',
            'pool' => [
                'max_connections' => env('REDIS_REPO_POOL_MAX', 20),
                'min_connections' => env('REDIS_REPO_POOL_MIN', 5),
            ],
        ],
    ],
],
```

### Multi-Level Cache Configuration

```php
// config/repository-cache.php - Advanced caching
return [
    'levels' => [
        'memory' => [
            'enabled' => env('REPO_CACHE_MEMORY_ENABLED', true),
            'driver' => 'array',
            'max_items' => env('REPO_CACHE_MEMORY_MAX_ITEMS', 1000),
            'ttl' => env('REPO_CACHE_MEMORY_TTL', 300), // 5 minutes
            'serialize' => false,
        ],
        
        'redis' => [
            'enabled' => env('REPO_CACHE_REDIS_ENABLED', true),
            'driver' => 'redis',
            'connection' => 'repository_cache',
            'ttl' => env('REPO_CACHE_REDIS_TTL', 1800), // 30 minutes
            'serialize' => true,
            'compress' => true,
        ],
        
        'file' => [
            'enabled' => env('REPO_CACHE_FILE_ENABLED', false),
            'driver' => 'file',
            'path' => storage_path('cache/repositories'),
            'ttl' => env('REPO_CACHE_FILE_TTL', 3600), // 1 hour
            'serialize' => true,
            'compress' => true,
        ],
    ],
    
    'strategies' => [
        'read_through' => true,
        'write_through' => true,
        'write_behind' => false,
        'refresh_ahead' => env('REPO_CACHE_REFRESH_AHEAD', true),
    ],
    
    'invalidation' => [
        'auto' => true,
        'tags' => true,
        'cascade' => true,
        'events' => ['created', 'updated', 'deleted'],
    ],
];
```

## 🗄️ Database Optimization

### Connection Configuration

```php
// config/database.php - Optimized database connections
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => 'InnoDB',
        'options' => [
            // Connection optimization
            PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', false),
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            
            // MySQL specific optimizations
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => 
                "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION',
                 SESSION query_cache_type=ON,
                 SESSION query_cache_size=1048576,
                 SESSION tmp_table_size=16777216,
                 SESSION max_heap_table_size=16777216",
        ],
        
        // Read/Write splitting
        'read' => [
            'host' => explode(',', env('DB_READ_HOSTS', env('DB_HOST', '127.0.0.1'))),
            'sticky' => env('DB_STICKY', true),
        ],
        'write' => [
            'host' => explode(',', env('DB_WRITE_HOSTS', env('DB_HOST', '127.0.0.1'))),
        ],
        
        // Connection pooling
        'pool' => [
            'enabled' => env('DB_POOL_ENABLED', false),
            'min_connections' => env('DB_POOL_MIN', 5),
            'max_connections' => env('DB_POOL_MAX', 50),
            'acquire_timeout' => env('DB_POOL_ACQUIRE_TIMEOUT', 60),
            'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 300),
            'max_lifetime' => env('DB_POOL_MAX_LIFETIME', 1800),
        ],
    ],
],

// Query optimization settings
'query' => [
    'log' => env('DB_LOG_QUERIES', false),
    'slow_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
    'cache' => [
        'enabled' => env('DB_QUERY_CACHE_ENABLED', true),
        'ttl' => env('DB_QUERY_CACHE_TTL', 300),
        'tags' => env('DB_QUERY_CACHE_TAGS', true),
    ],
],
```

### Index Optimization Configuration

```php
// config/database-indexes.php - Index recommendations and monitoring
return [
    'monitoring' => [
        'enabled' => env('DB_INDEX_MONITORING', true),
        'slow_query_threshold' => env('DB_INDEX_SLOW_THRESHOLD', 100),
        'missing_index_detection' => env('DB_DETECT_MISSING_INDEXES', true),
        'unused_index_detection' => env('DB_DETECT_UNUSED_INDEXES', true),
    ],
    
    'recommendations' => [
        'auto_suggest' => env('DB_AUTO_SUGGEST_INDEXES', true),
        'composite_indexes' => env('DB_SUGGEST_COMPOSITE_INDEXES', true),
        'covering_indexes' => env('DB_SUGGEST_COVERING_INDEXES', true),
    ],
    
    'optimization' => [
        'force_index_usage' => env('DB_FORCE_INDEX_USAGE', false),
        'analyze_tables' => env('DB_AUTO_ANALYZE_TABLES', true),
        'update_statistics' => env('DB_AUTO_UPDATE_STATISTICS', true),
    ],
];
```

## 🔑 HashId Configuration

### Basic HashId Setup

```php
// config/hashids.php - HashId configuration
return [
    /*
    |--------------------------------------------------------------------------
    | Default HashIds Connection
    |--------------------------------------------------------------------------
    */
    'default' => env('HASHID_CONNECTION', 'default'),
    
    /*
    |--------------------------------------------------------------------------
    | HashIds Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'default' => [
            'salt' => env('HASHID_SALT', env('APP_KEY')),
            'length' => env('HASHID_LENGTH', 6),
            'alphabet' => env('HASHID_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
        ],
        
        // Different connections for different models
        'users' => [
            'salt' => env('HASHID_USERS_SALT', env('APP_KEY') . '_users'),
            'length' => env('HASHID_USERS_LENGTH', 8),
            'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
        ],
        
        'orders' => [
            'salt' => env('HASHID_ORDERS_SALT', env('APP_KEY') . '_orders'),
            'length' => env('HASHID_ORDERS_LENGTH', 10),
            'alphabet' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', // Exclude similar characters
        ],
        
        'sensitive' => [
            'salt' => env('HASHID_SENSITIVE_SALT', env('APP_KEY') . '_sensitive_' . date('Y-m')),
            'length' => env('HASHID_SENSITIVE_LENGTH', 12),
            'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Model Mapping
    |--------------------------------------------------------------------------
    | Map models to specific HashId connections
    */
    'model_mapping' => [
        'App\Models\User' => 'users',
        'App\Models\Order' => 'orders',
        'App\Models\Payment' => 'sensitive',
        'App\Models\Transaction' => 'sensitive',
        // Default connection for unmapped models
        '*' => 'default',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'rotate_salt' => env('HASHID_ROTATE_SALT', false),
        'rotation_interval' => env('HASHID_ROTATION_INTERVAL', 'monthly'), // daily, weekly, monthly
        'validate_decoded' => env('HASHID_VALIDATE_DECODED', true),
        'rate_limit_decoding' => env('HASHID_RATE_LIMIT_DECODING', true),
        'max_decode_attempts' => env('HASHID_MAX_DECODE_ATTEMPTS', 100),
        'decode_attempt_window' => env('HASHID_DECODE_WINDOW', 60), // minutes
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'cache_enabled' => env('HASHID_CACHE_ENABLED', true),
        'cache_ttl' => env('HASHID_CACHE_TTL', 3600), // 1 hour
        'batch_processing' => env('HASHID_BATCH_PROCESSING', true),
        'lazy_loading' => env('HASHID_LAZY_LOADING', true),
    ],
];
```

### Multi-Tenant HashId Configuration

```php
// config/hashids-tenant.php - Tenant-aware HashId configuration
return [
    'tenant_aware' => env('HASHID_TENANT_AWARE', false),
    
    'tenant_detection' => [
        'method' => env('HASHID_TENANT_METHOD', 'header'), // header, subdomain, path, user
        'header_name' => env('HASHID_TENANT_HEADER', 'X-Tenant-ID'),
        'subdomain_pattern' => env('HASHID_TENANT_SUBDOMAIN_PATTERN', '{tenant}.domain.com'),
        'path_pattern' => env('HASHID_TENANT_PATH_PATTERN', '/tenant/{tenant}'),
    ],
    
    'tenant_salt_strategy' => [
        'strategy' => env('HASHID_TENANT_SALT_STRATEGY', 'append'), // append, prepend, hash
        'separator' => env('HASHID_TENANT_SALT_SEPARATOR', '_'),
        'hash_algorithm' => env('HASHID_TENANT_HASH_ALGORITHM', 'sha256'),
    ],
    
    'tenant_isolation' => [
        'strict' => env('HASHID_TENANT_STRICT_ISOLATION', true),
        'cross_tenant_validation' => env('HASHID_CROSS_TENANT_VALIDATION', false),
        'tenant_migration_support' => env('HASHID_TENANT_MIGRATION_SUPPORT', false),
    ],
];
```

## ⚡ Performance Tuning

### Query Optimization

```php
// config/repository-performance.php - Performance optimization
return [
    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    */
    'query' => [
        'eager_loading' => [
            'enabled' => env('REPO_EAGER_LOADING', true),
            'max_depth' => env('REPO_EAGER_LOADING_MAX_DEPTH', 3),
            'auto_detect' => env('REPO_EAGER_LOADING_AUTO_DETECT', true),
            'prevent_n_plus_one' => env('REPO_PREVENT_N_PLUS_ONE', true),
        ],
        
        'chunking' => [
            'enabled' => env('REPO_CHUNKING_ENABLED', true),
            'default_size' => env('REPO_CHUNK_SIZE', 1000),
            'max_size' => env('REPO_MAX_CHUNK_SIZE', 5000),
            'auto_chunk_threshold' => env('REPO_AUTO_CHUNK_THRESHOLD', 10000),
        ],
        
        'query_builder' => [
            'optimize_selects' => env('REPO_OPTIMIZE_SELECTS', true),
            'optimize_joins' => env('REPO_OPTIMIZE_JOINS', true),
            'optimize_where_clauses' => env('REPO_OPTIMIZE_WHERE_CLAUSES', true),
            'use_exists_instead_of_count' => env('REPO_USE_EXISTS_INSTEAD_OF_COUNT', true),
        ],
        
        'result_processing' => [
            'lazy_collections' => env('REPO_LAZY_COLLECTIONS', true),
            'streaming_responses' => env('REPO_STREAMING_RESPONSES', false),
            'memory_efficient_pagination' => env('REPO_MEMORY_EFFICIENT_PAGINATION', true),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Memory Management
    |--------------------------------------------------------------------------
    */
    'memory' => [
        'tracking' => [
            'enabled' => env('REPO_MEMORY_TRACKING', true),
            'threshold_warning' => env('REPO_MEMORY_WARNING_THRESHOLD', 128 * 1024 * 1024), // 128MB
            'threshold_critical' => env('REPO_MEMORY_CRITICAL_THRESHOLD', 256 * 1024 * 1024), // 256MB
            'auto_cleanup' => env('REPO_MEMORY_AUTO_CLEANUP', true),
        ],
        
        'optimization' => [
            'object_pooling' => env('REPO_OBJECT_POOLING', false),
            'pool_size' => env('REPO_OBJECT_POOL_SIZE', 100),
            'garbage_collection' => env('REPO_AUTO_GARBAGE_COLLECTION', true),
            'gc_probability' => env('REPO_GC_PROBABILITY', 0.1), // 10% chance
        ],
        
        'limits' => [
            'max_results_in_memory' => env('REPO_MAX_RESULTS_IN_MEMORY', 10000),
            'max_concurrent_queries' => env('REPO_MAX_CONCURRENT_QUERIES', 10),
            'query_timeout' => env('REPO_QUERY_TIMEOUT', 30), // seconds
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Connection Management
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'pooling' => [
            'enabled' => env('REPO_CONNECTION_POOLING', false),
            'min_connections' => env('REPO_MIN_CONNECTIONS', 5),
            'max_connections' => env('REPO_MAX_CONNECTIONS', 50),
            'idle_timeout' => env('REPO_CONNECTION_IDLE_TIMEOUT', 300), // seconds
        ],
        
        'optimization' => [
            'persistent_connections' => env('REPO_PERSISTENT_CONNECTIONS', false),
            'connection_reuse' => env('REPO_CONNECTION_REUSE', true),
            'prepared_statements' => env('REPO_PREPARED_STATEMENTS', true),
        ],
    ],
];
```

### Monitoring Configuration

```php
// config/repository-monitoring.php - Performance monitoring
return [
    'enabled' => env('REPO_MONITORING_ENABLED', true),
    
    'metrics' => [
        'response_time' => env('REPO_MONITOR_RESPONSE_TIME', true),
        'memory_usage' => env('REPO_MONITOR_MEMORY_USAGE', true),
        'query_count' => env('REPO_MONITOR_QUERY_COUNT', true),
        'cache_hit_rate' => env('REPO_MONITOR_CACHE_HIT_RATE', true),
        'error_rate' => env('REPO_MONITOR_ERROR_RATE', true),
    ],
    
    'thresholds' => [
        'slow_response' => env('REPO_SLOW_RESPONSE_THRESHOLD', 1000), // milliseconds
        'high_memory' => env('REPO_HIGH_MEMORY_THRESHOLD', 128 * 1024 * 1024), // 128MB
        'too_many_queries' => env('REPO_TOO_MANY_QUERIES_THRESHOLD', 20),
        'low_cache_hit_rate' => env('REPO_LOW_CACHE_HIT_RATE_THRESHOLD', 80), // percentage
        'high_error_rate' => env('REPO_HIGH_ERROR_RATE_THRESHOLD', 5), // percentage
    ],
    
    'alerts' => [
        'enabled' => env('REPO_ALERTS_ENABLED', true),
        'slack_webhook' => env('REPO_ALERTS_SLACK_WEBHOOK'),
        'email_recipients' => env('REPO_ALERTS_EMAIL_RECIPIENTS'),
        'alert_frequency' => env('REPO_ALERT_FREQUENCY', 'once_per_hour'),
    ],
    
    'storage' => [
        'driver' => env('REPO_MONITORING_STORAGE', 'redis'),
        'retention_days' => env('REPO_MONITORING_RETENTION_DAYS', 30),
        'aggregation_interval' => env('REPO_MONITORING_AGGREGATION_INTERVAL', 300), // 5 minutes
    ],
];
```

## 🔒 Security Configuration

### Input Validation & Sanitization

```php
// config/repository-security.php - Security configuration
return [
    /*
    |--------------------------------------------------------------------------
    | Input Validation
    |--------------------------------------------------------------------------
    */
    'input_validation' => [
        'enabled' => env('REPO_INPUT_VALIDATION', true),
        'strict_mode' => env('REPO_STRICT_VALIDATION', true),
        
        'sanitization' => [
            'strip_tags' => env('REPO_STRIP_TAGS', true),
            'escape_html' => env('REPO_ESCAPE_HTML', true),
            'trim_whitespace' => env('REPO_TRIM_WHITESPACE', true),
            'normalize_unicode' => env('REPO_NORMALIZE_UNICODE', true),
        ],
        
        'limits' => [
            'max_string_length' => env('REPO_MAX_STRING_LENGTH', 65535),
            'max_array_elements' => env('REPO_MAX_ARRAY_ELEMENTS', 1000),
            'max_nesting_depth' => env('REPO_MAX_NESTING_DEPTH', 5),
        ],
        
        'blacklist' => [
            'sql_keywords' => env('REPO_BLACKLIST_SQL_KEYWORDS', true),
            'script_tags' => env('REPO_BLACKLIST_SCRIPT_TAGS', true),
            'file_paths' => env('REPO_BLACKLIST_FILE_PATHS', true),
            'email_patterns' => env('REPO_BLACKLIST_EMAIL_PATTERNS', false),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Query Protection
    |--------------------------------------------------------------------------
    */
    'query_protection' => [
        'sql_injection_prevention' => env('REPO_PREVENT_SQL_INJECTION', true),
        'prepared_statements_only' => env('REPO_PREPARED_STATEMENTS_ONLY', true),
        'validate_column_names' => env('REPO_VALIDATE_COLUMN_NAMES', true),
        'validate_table_names' => env('REPO_VALIDATE_TABLE_NAMES', true),
        'restrict_raw_queries' => env('REPO_RESTRICT_RAW_QUERIES', false),
        
        'allowed_operators' => [
            '=', '!=', '<>', '>', '<', '>=', '<=',
            'like', 'not like', 'ilike',
            'in', 'not in',
            'between', 'not between',
            'is null', 'is not null',
        ],
        
        'forbidden_functions' => [
            'load_file', 'into_outfile', 'into_dumpfile',
            'benchmark', 'sleep', 'get_lock',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('REPO_RATE_LIMITING', false),
        'global_limit' => env('REPO_GLOBAL_RATE_LIMIT', 1000),
        'per_user_limit' => env('REPO_PER_USER_RATE_LIMIT', 100),
        'per_ip_limit' => env('REPO_PER_IP_RATE_LIMIT', 500),
        'window_minutes' => env('REPO_RATE_LIMIT_WINDOW', 60),
        
        'actions' => [
            'create' => env('REPO_RATE_LIMIT_CREATE', 50),
            'update' => env('REPO_RATE_LIMIT_UPDATE', 100),
            'delete' => env('REPO_RATE_LIMIT_DELETE', 20),
            'search' => env('REPO_RATE_LIMIT_SEARCH', 200),
        ],
        
        'exceptions' => [
            'admin_users' => env('REPO_RATE_LIMIT_EXEMPT_ADMINS', true),
            'internal_requests' => env('REPO_RATE_LIMIT_EXEMPT_INTERNAL', true),
            'trusted_ips' => env('REPO_RATE_LIMIT_TRUSTED_IPS', ''),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    */
    'access_control' => [
        'enforce_ownership' => env('REPO_ENFORCE_OWNERSHIP', false),
        'tenant_isolation' => env('REPO_TENANT_ISOLATION', false),
        'permission_based_filtering' => env('REPO_PERMISSION_BASED_FILTERING', false),
        
        'field_level_security' => [
            'enabled' => env('REPO_FIELD_LEVEL_SECURITY', false),
            'sensitive_fields' => [
                'password', 'api_key', 'secret', 'token',
                'ssn', 'credit_card', 'bank_account',
            ],
            'admin_only_fields' => [
                'created_at', 'updated_at', 'deleted_at',
                'created_by', 'updated_by', 'deleted_by',
            ],
        ],
    ],
];
```

## 📊 Monitoring & Logging

### Logging Configuration

```php
// config/logging.php - Repository-specific logging
'channels' => [
    'repository' => [
        'driver' => 'daily',
        'path' => storage_path('logs/repository.log'),
        'level' => env('REPO_LOG_LEVEL', 'info'),
        'days' => env('REPO_LOG_RETENTION_DAYS', 14),
        'permission' => 0664,
        'locking' => false,
        'tap' => [App\Logging\RepositoryLogFormatter::class],
    ],
    
    'repository_performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/repository-performance.log'),
        'level' => 'info',
        'days' => 7,
        'tap' => [App\Logging\PerformanceLogFormatter::class],
    ],
    
    'repository_security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/repository-security.log'),
        'level' => 'warning',
        'days' => 30,
        'tap' => [App\Logging\SecurityLogFormatter::class],
    ],
    
    'repository_errors' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'level' => 'error',
    ],
];
```

### Monitoring Integration

```php
// config/repository-monitoring-integration.php
return [
    'providers' => [
        'new_relic' => [
            'enabled' => env('REPO_NEW_RELIC_ENABLED', false),
            'app_name' => env('NEW_RELIC_APP_NAME'),
            'license_key' => env('NEW_RELIC_LICENSE_KEY'),
            'track_custom_metrics' => true,
            'track_repository_operations' => true,
        ],
        
        'datadog' => [
            'enabled' => env('REPO_DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY'),
            'host' => env('DATADOG_HOST', 'app'),
            'tags' => [
                'environment' => env('APP_ENV'),
                'version' => env('APP_VERSION'),
                'service' => 'repository',
            ],
        ],
        
        'prometheus' => [
            'enabled' => env('REPO_PROMETHEUS_ENABLED', false),
            'push_gateway' => env('PROMETHEUS_PUSH_GATEWAY'),
            'job_name' => env('PROMETHEUS_JOB_NAME', 'apiato-repository'),
            'metrics_endpoint' => env('PROMETHEUS_METRICS_ENDPOINT', '/metrics'),
        ],
        
        'custom' => [
            'enabled' => env('REPO_CUSTOM_MONITORING_ENABLED', false),
            'webhook_url' => env('REPO_CUSTOM_MONITORING_WEBHOOK'),
            'headers' => env('REPO_CUSTOM_MONITORING_HEADERS', '{}'),
            'format' => env('REPO_CUSTOM_MONITORING_FORMAT', 'json'),
        ],
    ],
    
    'metrics' => [
        'repository_operations' => [
            'enabled' => true,
            'include_timing' => true,
            'include_memory' => true,
            'include_query_count' => true,
        ],
        
        'cache_operations' => [
            'enabled' => true,
            'include_hit_rate' => true,
            'include_size' => true,
            'include_evictions' => true,
        ],
        
        'error_tracking' => [
            'enabled' => true,
            'include_stack_trace' => false,
            'include_user_context' => true,
            'include_request_data' => false,
        ],
    ],
];
```

---

**Next:** Learn about **[Troubleshooting](troubleshooting.md)** for common issues and solutions.