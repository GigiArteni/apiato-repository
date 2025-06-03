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
            'between', 'not_between'
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
    | Apiato Enhancements (auto-enabled)
    |--------------------------------------------------------------------------
    */
    'apiato' => [
        'hashid_enabled' => env('HASHID_ENABLED', true),
        'auto_cache_clear' => true,
        'enhanced_search' => true,
    ],
];
