# Configuration Reference: Tune Every Aspect

Apiato Repository is highly configurable. This reference explains every option, with context, rationale, and real-world advice for tuning your setup.

---

## 1. Where to Configure

- **File:** `config/repository.php` (publish with `php artisan vendor:publish --tag=repository`)
- **Environment:** `.env` variables override config for easy deployment tuning.

---

## 2. Key Configuration Options

### Pagination
- `'pagination.limit'` — Default page size for `paginate()` (default: 15)

### Caching
- `'cache.enabled'` — Enable/disable caching (default: true)
- `'cache.minutes'` — Cache duration in minutes (default: 30)
- `'cache.clean.enabled'` — Enable auto-invalidation (default: true)
- `'cache.clean.on'` — Which actions clear cache: create, update, delete

### Criteria & Query Parameters
- `'criteria.params'` — Map query string params to repository logic (search, filter, orderBy, etc)

### HashId Integration
- `'apiato.hashids.enabled'` — Enable HashId support (default: true)
- `'apiato.hashids.auto_decode'` — Auto-decode HashIds in all queries (default: true)
- `'apiato.hashids.decode_search'` — Decode HashIds in search params (default: true)
- `'apiato.hashids.decode_filters'` — Decode HashIds in filters (default: true)

### Enhanced Search
- `'apiato.features.enhanced_search'` — Enable advanced search (default: true)
- `'apiato.search.fuzzy_enabled'` — Enable fuzzy search (default: true)
- `'apiato.search.relevance_scoring'` — Enable relevance scoring (default: true)
- `'apiato.search.max_search_terms'` — Limit for search terms (default: 50)
- `'apiato.search.phrase_boost'` — Boost for exact phrase matches (default: 10)
- `'apiato.search.required_boost'` — Boost for required terms (default: 5)

---

## 3. Example: config/repository.php

```php
return [
    'pagination' => ['limit' => 15],
    'cache' => [
        'enabled' => true,
        'minutes' => 30,
        'clean' => [
            'enabled' => true,
            'on' => [
                'create' => true,
                'update' => true,
                'delete' => true,
            ]
        ],
    ],
    'criteria' => [
        'params' => [
            'search' => 'search',
            'searchFields' => 'searchFields',
            'filter' => 'filter',
            'orderBy' => 'orderBy',
            'sortedBy' => 'sortedBy',
        ],
    ],
    'apiato' => [
        'hashids' => [
            'enabled' => true,
            'auto_decode' => true,
            'decode_search' => true,
            'decode_filters' => true,
        ],
        'features' => [
            'enhanced_search' => true,
            'auto_cache_tags' => true,
        ],
        'search' => [
            'fuzzy_enabled' => true,
            'relevance_scoring' => true,
            'max_search_terms' => 50,
            'phrase_boost' => 10,
            'required_boost' => 5,
        ],
    ],
];
```

---

## 4. Environment Variables

Set these in `.env` to override config for different environments:
```env
REPOSITORY_CACHE_ENABLED=true
REPOSITORY_CACHE_MINUTES=30
REPOSITORY_CACHE_CLEAN_ENABLED=true
REPOSITORY_HASHIDS_ENABLED=true
REPOSITORY_HASHIDS_AUTO_DECODE=true
REPOSITORY_HASHIDS_DECODE_SEARCH=true
REPOSITORY_HASHIDS_DECODE_FILTERS=true
REPOSITORY_ENHANCED_SEARCH=true
REPOSITORY_FUZZY_SEARCH=true
REPOSITORY_RELEVANCE_SCORING=true
REPOSITORY_MAX_SEARCH_TERMS=50
REPOSITORY_PHRASE_BOOST=10
REPOSITORY_REQUIRED_BOOST=5
```

---

## 5. Best Practices

- **Tune cache duration** for your workload—shorter for fast-changing data, longer for static data.
- **Enable enhanced search** for user-facing APIs, disable for admin-only or internal tools if not needed.
- **Use environment variables** for easy deployment and CI/CD.
- **Review config after upgrades**—new features may add new options.
- **Document your configuration** for your team—explain why you chose certain settings.
- **Test configuration changes** in staging before deploying to production.

---

**See also:** [Troubleshooting](troubleshooting.md), [API Methods](api-methods.md), [Guides](../guides/)
