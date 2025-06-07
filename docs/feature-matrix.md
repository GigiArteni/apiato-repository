# Feature Matrix & Capabilities Overview

This page provides a high-level overview of all features, traits, contracts, and advanced capabilities of the Apiato Repository package. Use it as a quick reference to understand what is available and where to find more details.

| Feature                        | Description                                                                 | Reference/Guide                          |
|--------------------------------|-----------------------------------------------------------------------------|------------------------------------------|
| HashId Integration             | Automatic decoding/encoding of IDs everywhere                                | [HashId Integration](guides/hashid-integration.md) |
| Enhanced Search                | Boolean, fuzzy, relevance, phrase, relationship, API-first                   | [Enhanced Search](guides/enhanced-search.md) |
| Smart Transactions             | Auto, conditional, batch, isolation, deadlock retry                          | [Advanced Features](guides/advanced-features.md) |
| Bulk Operations                | High-performance insert/update/upsert/delete, chunking, stats                 | [Advanced Features](guides/advanced-features.md) |
| Repository Middleware          | Audit, cache, rate-limit, tenant-scope, performance, custom                   | [Advanced Features](guides/advanced-features.md) |
| Data Sanitization              | Automatic integration with Apiato's sanitizeInput, custom rules               | [Security & Sanitization](reference/api-methods.md#sanitization--security) |
| Caching                        | Intelligent, taggable, auto-invalidation, manual control                      | [Caching & Performance](guides/caching-performance.md) |
| Criteria & Scopes              | Reusable, parameterized, stackable, scopeQuery                               | [Advanced Features](guides/advanced-features.md) |
| Presenters & Transformers      | Fractal integration, custom transformers, field visibility                    | [Presenters & Transformers](reference/api-methods.md#presenters--transformers) |
| Validation                     | Laravel-style, custom rules, auto-integration                                 | [Validation](reference/api-methods.md#validation) |
| Event System                   | Full CRUD/bulk lifecycle, custom listeners, payloads                          | [Events Reference](reference/events.md) |
| API/Controller Integration     | Real-world API patterns, request criteria, search/filter/orderBy              | [Real-World Examples](guides/real-world-examples.md) |
| Artisan Generators             | Repositories, criteria, presenters, validators, transformers, entities        | [Artisan Commands](reference/artisan-commands.md) |
| Advanced Configuration         | All features tunable via config/env, per-repo overrides                      | [Configuration Reference](reference/configuration.md) |
| Security & Best Practices      | HashIds, sanitization, validation, audit, rate-limiting                      | [Troubleshooting](reference/troubleshooting.md) |
| Testing                        | Unit/integration, HashIds, criteria, caching, events, best practices          | [Testing Repositories](tutorials/testing-repositories.md) |
| Migration                      | Drop-in replacement for l5-repository, migration guide                        | [Migration Guide](getting-started/migration-from-l5.md) |

---

# Feature-to-Documentation Matrix

This table maps every major feature, trait, middleware, and advanced capability in the codebase to its documentation location. Use it to quickly find detailed docs, examples, and best practices for any feature.

| Feature / Trait / Middleware         | Docs Section(s)                                                                                 |
|--------------------------------------|-----------------------------------------------------------------------------------------------|
| **CRUD & Query Methods**             | [Basic Usage](guides/basic-usage.md), [API Methods](reference/api-methods.md)                 |
| **Advanced Search**                  | [Enhanced Search](guides/enhanced-search.md), [Implementing Search](tutorials/implementing-search.md) |
| **Bulk Operations**                  | [Bulk Operations](guides/advanced-features.md#1-batch-operations), [Bulk Ops Tutorial](tutorials/bulk-operations.md) |
| **Smart Transactions**               | [Advanced Features](guides/advanced-features.md#smart-transactions), [Bulk Ops Tutorial](tutorials/bulk-operations.md) |
| **HashId Integration**               | [HashId Integration](guides/hashid-integration.md), [Apiato v.13](apiato13.md)                |
| **Sanitization & Security**          | [Security & Sanitization](tutorials/security-sanitization.md), [Advanced Features](guides/advanced-features.md#sanitization) |
| **Caching**                          | [Caching & Performance](guides/caching-performance.md), [Advanced Features](guides/advanced-features.md#caching) |
| **Criteria & Scopes**                | [Advanced Features](guides/advanced-features.md#criteria), [Basic Usage](guides/basic-usage.md#5-advanced-criteria-scopes-and-caching) |
| **Field Visibility**                 | [Advanced Features](guides/advanced-features.md#field-visibility)                             |
| **Events**                           | [Events Reference](reference/events.md), [Advanced Features](guides/advanced-features.md#event-system) |
| **Presenters & Transformers**        | [Presenters & Transformers](reference/api-methods.md#presenters--transformers), [Tutorial](tutorials/building-user-repository.md#9-presenters--transformers) |
| **Validation**                       | [Validation](reference/api-methods.md#validation), [Security & Sanitization](tutorials/security-sanitization.md#validation) |
| **Repository Middleware**            | [Middleware Guide](tutorials/middleware.md), [Advanced Features](guides/advanced-features.md#middleware), [API Methods](reference/api-methods.md#middleware-system) |
| **Custom Middleware**                | [Middleware Guide](tutorials/middleware.md#custom-middleware), [API Methods](reference/api-methods.md#middleware-system) |
| **Audit Middleware**                 | [Middleware Guide](tutorials/middleware.md#audit-middleware), [API Methods](reference/api-methods.md#middleware-system) |
| **Cache Middleware**                 | [Middleware Guide](tutorials/middleware.md#cache-middleware), [Caching & Performance](guides/caching-performance.md) |
| **RateLimit Middleware**             | [Middleware Guide](tutorials/middleware.md#rate-limit-middleware)                              |
| **TenantScope Middleware**           | [Middleware Guide](tutorials/middleware.md#tenant-scope-middleware)                            |
| **PerformanceMonitor Middleware**    | [Middleware Guide](tutorials/middleware.md#performance-monitor-middleware)                     |
| **Contracts & Traits**               | [API Methods](reference/api-methods.md#contracts--traits)                                      |
| **Artisan Commands**                 | [Artisan Commands](reference/artisan-commands.md)                                              |
| **Testing**                          | [Testing Repositories](tutorials/testing-repositories.md), [Testing Guide](contributing/testing-guide.md) |
| **Apiato Integration**               | [Apiato v.13 Guide](apiato13.md), [Apiato Core](apiato-core.md)                                |
| **Laravel Integration**              | [Laravel Guide](laravel.md)                                                                    |
| **Configuration**                    | [Configuration Reference](reference/configuration.md)                                          |
| **Troubleshooting**                  | [Troubleshooting](reference/troubleshooting.md)                                                |
| **Real-World Examples**              | [Real-World Examples](guides/real-world-examples.md), [Building User Repository](tutorials/building-user-repository.md) |
| **Performance Tips**                 | [Performance Optimization](tutorials/performance-optimization.md), [Caching & Performance](guides/caching-performance.md) |
| **Best Practices**                   | [Advanced Features](guides/advanced-features.md#best-practices), [Apiato Core](apiato-core.md#4-best-practices) |

---

**For a deep dive on any feature, follow the links above or see the full documentation index.**
