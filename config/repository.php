<?php

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
];
