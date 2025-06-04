<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Repository Generator Settings (l5-repository compatible)
    |--------------------------------------------------------------------------
    */
    'generator' => [
        'basePath' => app_path(),
        'rootNamespace' => 'App\\',
        'stubsOverridePath' => app_path(),
        'paths' => [
            'models' => 'Entities',
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
    | Enhanced Cache Settings (auto-enabled for better performance)
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
        'tags' => [
            'enabled' => env('REPOSITORY_CACHE_TAGS_ENABLED', true),
            'auto_generate' => true,
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
        ],
        'acceptedConditions' => [
            '=', '!=', '<>', '>', '<', '>=', '<=',
            'like', 'ilike', 'not_like',
            'in', 'not_in', 'notin',
            'between', 'not_between',
            'date', 'date_between',
            'exists', 'not_exists'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation (l5-repository compatible)
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
    | Fractal Presenter (l5-repository compatible with enhancements)
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
    | Performance Settings (auto-enabled)
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'query_optimization' => env('REPOSITORY_QUERY_OPTIMIZATION', true),
        'memory_optimization' => env('REPOSITORY_MEMORY_OPTIMIZATION', true),
        'connection_reuse' => env('REPOSITORY_CONNECTION_REUSE', true),
        'lazy_loading' => env('REPOSITORY_LAZY_LOADING', true),
    ],
];
