<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Repository Generator Configuration
    |--------------------------------------------------------------------------
    */
    'generator' => [
        'basePath' => app_path(),
        'rootNamespace' => 'App\\',
        'stubsOverridePath' => app_path(),
        'paths' => [
            'models' => 'Ship/Parents/Models',
            'repositories' => 'Containers/{container}/Data/Repositories',
            'interfaces' => 'Containers/{container}/Data/Repositories',
            'criteria' => 'Containers/{container}/Data/Criteria',
            'presenters' => 'Containers/{container}/UI/API/Transformers',
            'validators' => 'Containers/{container}/Data/Validators',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('REPOSITORY_CACHE_ENABLED', true),
        'minutes' => env('REPOSITORY_CACHE_MINUTES', 60),
        'store' => env('REPOSITORY_CACHE_STORE', 'default'),
        'clear_on_write' => env('REPOSITORY_CACHE_CLEAR_ON_WRITE', true),
        'skip_uri' => env('REPOSITORY_CACHE_SKIP_URI', 'skipCache'),
        'allowed_methods' => [
            'all', 'paginate', 'find', 'findOrFail', 'findByField',
            'findWhere', 'findWhereFirst', 'findWhereIn', 'findWhereNotIn',
            'findWhereBetween'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Criteria Configuration
    |--------------------------------------------------------------------------
    */
    'criteria' => [
        'params' => [
            'search' => 'search',
            'searchFields' => 'searchFields',
            'searchJoin' => 'searchJoin',
            'filter' => 'filter',
            'filterJoin' => 'filterJoin',
            'orderBy' => 'orderBy',
            'sortedBy' => 'sortedBy',
            'include' => 'include',
            'with' => 'with',
            'compare' => 'compare',
            'having' => 'having',
            'groupBy' => 'groupBy',
        ],
        'acceptedConditions' => [
            '=', '!=', '<>', '>', '<', '>=', '<=', 'like', 'ilike', 'not_like',
            'in', 'not_in', 'notin', 'between', 'not_between',
            'date_between', 'date_equals', 'date_not_equals',
            'today', 'yesterday', 'this_week', 'last_week',
            'this_month', 'last_month', 'this_year', 'last_year',
            'number_range', 'number_between', 'null', 'not_null', 'notnull',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HashId Configuration (Apiato Integration)
    |--------------------------------------------------------------------------
    */
    'hashid' => [
        'enabled' => env('HASHID_ENABLED', true),
        'auto_detect' => true,
        'auto_encode' => true,
        'min_length' => 4,
        'max_length' => 20,
        'fields' => ['id', '*_id'],
        'fallback_to_numeric' => true,
        'cache_decoded_ids' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fractal Presenter Configuration
    |--------------------------------------------------------------------------
    */
    'fractal' => [
        'params' => [
            'include' => 'include',
            'exclude' => 'exclude',
            'fields' => 'fields',
            'meta' => 'meta',
        ],
        'serializer' => \League\Fractal\Serializer\DataArraySerializer::class,
        'auto_includes' => [
            'enabled' => true,
            'max_nested_level' => 5,
            'lazy_load_threshold' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'enabled' => true,
        'throw_validation_exceptions' => true,
        'validate_includes' => true,
        'validate_filters' => true,
        'validate_hashids' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Security Limits
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'per_page' => 15,
        'max_per_page' => 100,
        'max_includes' => 10,
        'max_search_terms' => 20,
        'max_filter_terms' => 20,
        'query_timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Apiato Integration
    |--------------------------------------------------------------------------
    */
    'apiato' => [
        'enabled' => env('APIATO_ENABLED', false),
        'container_path' => 'app/Containers',
        'ship_path' => 'app/Ship',
        'auto_bind_repositories' => true,
        'use_porto_structure' => true,
        'auto_register_criteria' => true,
        'hashid_integration' => true,
    ],
];
