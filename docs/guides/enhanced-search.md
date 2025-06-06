# Enhanced Search: Real-World Power for Real-World APIs

Apiato Repository’s enhanced search is not just a feature—it’s a philosophy. It’s designed for real business needs: complex queries, relevance, boolean logic, fuzzy matching, and seamless API integration. This guide explains how it works, why it matters, and how to use it to build world-class APIs.

---

## 1. What Makes Enhanced Search Different?

- **Relevance scoring**: Results are ranked by how well they match the query.
- **Boolean logic**: Use +required, -excluded, and optional terms.
- **Fuzzy matching**: Find results even with typos or similar-sounding words.
- **Phrase search**: Exact matches for phrases in quotes.
- **Relationship and multi-field support**: Search across related models and multiple fields.
- **API-first**: Designed for query string parsing and frontend integration.

---

## 2. How Enhanced Search Works

- **Auto-activation**: Enhanced search is enabled by default, but can be toggled per request or globally.
- **Smart fallback**: For simple queries, it uses basic search for speed. For complex queries, it activates advanced logic.
- **Configurable**: Control all aspects via `config/repository.php` and `.env`.

---

## 3. Real-World API Patterns

**Exact Phrase Search:**
```bash
GET /api/users?search="senior developer"
```

**Boolean Operators:**
```bash
GET /api/users?search=+engineer +senior -intern
```

**Fuzzy Search:**
```bash
GET /api/users?search=john~2
```

**Relationship Search:**
```bash
GET /api/users?search=roles.name:admin;company.name:acme
```

**Multi-Field, Multi-Relationship:**
```bash
GET /api/users?search=posts.title:laravel;roles.name:editor;company.type:startup
```

**Combined with Filters and Sorting:**
```bash
GET /api/users?search=name:john&filter=status:active&orderBy=created_at&sortedBy=desc
```

---

## 4. Eloquent Usage

**Relevance-Scored Search:**
```php
$users = $repo->findWhere([
    ['name', 'like', '%john%'],
    ['bio', 'like', '%developer%'],
]);
```

**Boolean and Fuzzy Logic:**
```php
$users = $repo->findWhere([
    ['name', 'like', '%john%'],
    ['skills', 'like', '%laravel%'],
    // Fuzzy and boolean logic handled automatically
]);
```

---

## 5. Configuration & Tuning

**config/repository.php:**
```php
'apiato' => [
    'features' => [
        'enhanced_search' => env('REPOSITORY_ENHANCED_SEARCH', true),
        'auto_cache_tags' => env('REPOSITORY_AUTO_CACHE_TAGS', true),
    ],
    'search' => [
        'fuzzy_enabled' => env('REPOSITORY_FUZZY_SEARCH', true),
        'relevance_scoring' => env('REPOSITORY_RELEVANCE_SCORING', true),
        'max_search_terms' => env('REPOSITORY_MAX_SEARCH_TERMS', 50),
        'phrase_boost' => env('REPOSITORY_PHRASE_BOOST', 10),
        'required_boost' => env('REPOSITORY_REQUIRED_BOOST', 5),
    ]
],
```

**.env:**
```env
REPOSITORY_ENHANCED_SEARCH=true
REPOSITORY_FUZZY_SEARCH=true
REPOSITORY_RELEVANCE_SCORING=true
```

---

## 6. Frontend & API Integration

- **Query string parsing**: All features are available via API query strings—perfect for React, Vue, or mobile apps.
- **Relevance in results**: API responses include `relevance_score` for each item.
- **Force enhanced/basic search**: Use `enhanced=true` or `enhanced=false` in your API requests.

---

## 7. Best Practices

- **Always define all relevant fields in `$fieldSearchable`**—including relationships.
- **Use enhanced search for user-facing APIs**—it’s designed for real-world business needs.
- **Tune configuration for your dataset and use case**—see the config reference.
- **Test with real queries**—simulate real user searches and edge cases.

---

**Next:**
- [Caching & Performance →](caching-performance.md)
- [Real-World Examples →](real-world-examples.md)
