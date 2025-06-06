<?php

use Apiato\Repository\Middleware\AuditMiddleware;
use Apiato\Repository\Middleware\CacheMiddleware;
use Apiato\Repository\Middleware\PerformanceMonitorMiddleware;
use Apiato\Repository\Middleware\RateLimitMiddleware;
use Apiato\Repository\Middleware\TenantScopeMiddleware;

return [
    /*
    |--------------------------------------------------------------------------
    | Repository Generator Settings
    |--------------------------------------------------------------------------
    */
    'generator' => [
        'basePath' => app_path(),
        'rootNamespace' => 'App\\',
        'stubsOverridePath' => app_path(),
        'paths' => [
            'models' => 'Models',
            'repositories' => 'Repositories',
            'interfaces' => 'Repositories',
            'criteria' => 'Criteria',
            'transformers' => 'Transformers',
            'presenters' => 'Presenters',
            'validators' => 'Validators',
            'controllers' => 'Http/Controllers',
            'provider' => 'RepositoryServiceProvider',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'limit' => 15
    ],

    /*
    |--------------------------------------------------------------------------
    | Enhanced Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('REPOSITORY_CACHE_ENABLED', true),
        'minutes' => env('REPOSITORY_CACHE_MINUTES', 30),
        'repository' => 'cache',
        'clean' => [
            'enabled' => env('REPOSITORY_CACHE_CLEAN_ENABLED', true),
            'on' => [
                'create' => true,
                'update' => true,
                'delete' => true,
            ]
        ],
        'params' => [
            'skipCache' => 'skipCache',
        ],
        'allowed' => [
            'only' => null,
            'except' => null
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Criteria (RequestCriteria compatible with enhancements)
    |--------------------------------------------------------------------------
    */
    'criteria' => [
        'params' => [
            'search' => 'search',
            'searchFields' => 'searchFields',
            'filter' => 'filter',
            'orderBy' => 'orderBy',
            'sortedBy' => 'sortedBy',
            'with' => 'with',
            'searchJoin' => 'searchJoin'
        ],
        'acceptedConditions' => [
            '=', '!=', '<>', '>', '<', '>=', '<=',
            'like', 'ilike', 'not_like',
            'in', 'not_in', 'notin',
            'between', 'not_between'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'enabled' => true,
        'rules' => [
            'create' => 'create',
            'update' => 'update'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Fractal Presenter
    |--------------------------------------------------------------------------
    */
    'fractal' => [
        'params' => [
            'include' => 'include',
        ],
        'serializer' => \League\Fractal\Serializer\DataArraySerializer::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Apiato v.13 Integration Settings
    |--------------------------------------------------------------------------
    */
    'apiato' => [
        'hashids' => [
            // Automatically detect and use Apiato's vinkla/hashids
            'enabled' => env('REPOSITORY_HASHIDS_ENABLED', true),
            'auto_decode' => env('REPOSITORY_HASHIDS_AUTO_DECODE', true),
            'auto_encode' => env('REPOSITORY_HASHIDS_AUTO_ENCODE', false), // Let Apiato handle encoding
            'decode_search' => env('REPOSITORY_HASHIDS_DECODE_SEARCH', true),
            'decode_filters' => env('REPOSITORY_HASHIDS_DECODE_FILTERS', true),
            'fields' => ['id', '*_id'], // Fields to process for HashIds
        ],
        'performance' => [
            'enhanced_caching' => env('REPOSITORY_ENHANCED_CACHE', true),
            'query_optimization' => env('REPOSITORY_QUERY_OPTIMIZATION', true),
            'eager_loading_detection' => env('REPOSITORY_EAGER_LOADING_DETECTION', true),
            'batch_operations' => env('REPOSITORY_BATCH_OPERATIONS', true),
        ],
        'features' => [
            'auto_cache_tags' => env('REPOSITORY_AUTO_CACHE_TAGS', true),
            'enhanced_search' => env('REPOSITORY_ENHANCED_SEARCH', true),
            'smart_relationships' => env('REPOSITORY_SMART_RELATIONSHIPS', true),
            'event_dispatching' => env('REPOSITORY_EVENT_DISPATCHING', true),
        ],
        'logging' => [
            'enabled' => env('REPOSITORY_LOGGING_ENABLED', false),
            'level' => env('REPOSITORY_LOGGING_LEVEL', 'info'),
            'log_queries' => env('REPOSITORY_LOG_QUERIES', false),
            'log_performance' => env('REPOSITORY_LOG_PERFORMANCE', false),
        ]
    ],
    /*
    |--------------------------------------------------------------------------
    | Security & Sanitization Settings
    |--------------------------------------------------------------------------
    | Integrate with Apiato's sanitizeInput() for secure data handling
    */
    'security' => [
        'sanitize_input' => env('REPOSITORY_SANITIZE_INPUT', true),
        'sanitize_on' => [
            'create' => env('REPOSITORY_SANITIZE_CREATE', true),
            'update' => env('REPOSITORY_SANITIZE_UPDATE', true), 
            'updateOrCreate' => env('REPOSITORY_SANITIZE_UPSERT', true),
            'bulk_operations' => env('REPOSITORY_SANITIZE_BULK', true),
        ],
        'sanitize_fields' => [
            'exclude' => ['password', 'password_confirmation', 'token'], // Never sanitize these
            'html_fields' => ['description', 'bio', 'content'], // HTML purify these
            'email_fields' => ['email', 'contact_email'], // Email sanitization
        ],
        'fallback_sanitization' => env('REPOSITORY_FALLBACK_SANITIZE', true), // For non-Apiato projects
        'audit_sanitization' => env('REPOSITORY_AUDIT_SANITIZE', false), // Log sanitization changes
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Transaction Settings  
    |--------------------------------------------------------------------------
    | Smart transaction handling for data integrity
    */
    'transactions' => [
        'auto_wrap_bulk' => env('REPOSITORY_AUTO_TRANSACTION_BULK', true), // Auto-wrap bulk operations
        'auto_wrap_single' => env('REPOSITORY_AUTO_TRANSACTION_SINGLE', false), // Manual control for single ops
        'timeout' => env('REPOSITORY_TRANSACTION_TIMEOUT', 30), // Transaction timeout in seconds
        'isolation_level' => env('REPOSITORY_ISOLATION_LEVEL', null), // READ_COMMITTED, SERIALIZABLE, etc.
        'retry_deadlocks' => env('REPOSITORY_RETRY_DEADLOCKS', true), // Auto-retry on deadlock
        'max_retries' => env('REPOSITORY_MAX_RETRIES', 3),
        'retry_delay' => env('REPOSITORY_RETRY_DELAY', 100), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Bulk Operations
    |--------------------------------------------------------------------------
    | Enhanced bulk operations with sanitization and transactions
    */
    'bulk_operations' => [
        'enabled' => env('REPOSITORY_BULK_OPERATIONS', true),
        'chunk_size' => env('REPOSITORY_BULK_CHUNK_SIZE', 1000), // Process in chunks
        'use_transactions' => env('REPOSITORY_BULK_TRANSACTIONS', true),
        'sanitize_data' => env('REPOSITORY_BULK_SANITIZE', true),
        'validate_hashids' => env('REPOSITORY_BULK_VALIDATE_HASHIDS', true),
        'log_performance' => env('REPOSITORY_BULK_LOG_PERFORMANCE', false),
    ],
    /*
    |--------------------------------------------------------------------------
    | Repository Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'default_stack' => ['audit', 'cache:30'],
        'available' => [
            'audit' => AuditMiddleware::class,
            'cache' => CacheMiddleware::class,
            'rate-limit' => RateLimitMiddleware::class,
            'tenant-scope' => TenantScopeMiddleware::class,
            'performance' => PerformanceMonitorMiddleware::class,
        ]
    ],
];
