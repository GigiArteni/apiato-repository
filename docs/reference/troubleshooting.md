# Troubleshooting: Diagnose, Fix, and Succeed

> **Quick Navigation:**
> - [1. Cache Not Clearing or Not Working](#1-cache-not-clearing-or-not-working)
> - [2. HashIds Not Working](#2-hashids-not-working)
> - [3. Migration Errors (from l5-repository)](#3-migration-errors-from-l5-repository)
> - [4. Query String Parsing Issues](#4-query-string-parsing-issues)
> - [5. Validation Errors](#5-validation-errors)
> - [6. Best Practices for Debugging](#6-best-practices-for-debugging)
> - [7. Advanced Debugging & Edge Cases](#7-advanced-debugging--edge-cases)
> - [8. Where to Get Help](#8-where-to-get-help)

Even the best tools need a little help sometimes. This guide covers the most common issues, their causes, and proven solutions—so you can get back to building, fast.

---

## 1. Cache Not Clearing or Not Working

**Symptoms:**
- Data doesn’t update after create/update/delete
- Stale results in API or UI

**Solutions:**
- Check `config('repository.cache')` settings
- Ensure your cache driver supports tagging (Redis recommended)
- Use `$repo->clearCache()` after bulk operations
- Use `$repo->skipCache()` for one-off queries
- Clear all cache: `php artisan cache:clear`

---

## 2. HashIds Not Working

**Symptoms:**
- 404 or not found when using HashIds in API or Eloquent
- IDs not decoded in relationships or filters

**Solutions:**
- Ensure `REPOSITORY_HASHIDS_ENABLED=true` in `.env`
- Check that all ID fields are in `$fieldSearchable`
- Test with both integer IDs and HashIds
- Review custom criteria for manual decoding (should not be needed)

---

## 3. Migration Errors (from l5-repository)

**Symptoms:**
- Namespace errors, missing classes, or broken criteria

**Solutions:**
- Update all imports from `Prettus\Repository` to `Apiato\Repository`
- Publish and review new config: `php artisan vendor:publish --tag=repository`
- Clear config cache: `php artisan config:clear`
- Review custom presenters, criteria, and validators for namespace changes

---

## 4. Query String Parsing Issues

**Symptoms:**
- Complex search strings not parsed correctly
- API returns unexpected results

**Solutions:**
- URL encode special characters in query strings
- Test with simple queries first, then add complexity
- Check server configuration for query string limits
- Use Laravel’s request debugging to inspect parameters

---

## 5. Validation Errors

**Symptoms:**
- Validation fails unexpectedly on create/update

**Solutions:**
- Review your validator rules
- Ensure you’re passing all required fields
- Catch exceptions and inspect `$repo->validator()->errors()`

---

## 6. Best Practices for Debugging

- **Read the error message**—it often tells you exactly what’s wrong
- **Check the logs**—Laravel’s log files are your friend
- **Write tests**—catch issues before they hit production
- **Ask for help**—open an issue or check the community

---

## 7. Advanced Debugging & Edge Cases

- **Middleware Issues**
  - Ensure all middleware are registered in [`config/repository.php`](../../../config/repository.php).
  - Check for typos in middleware names or parameters.
  - Use logs to trace middleware execution order (see [Performance & Caching Guide](../guides/caching-performance.md)).
- **Bulk Operation Problems**
  - Tune batch size for your DB engine (see [Bulk Operations Tutorial](../tutorials/bulk-operations.md)).
  - Monitor for deadlocks and use built-in retry logic.
- **Transaction Failures**
  - Use `$repo->getTransactionStats()` to debug transaction state.
  - Check for nested/recursive transactions and use `$repo->skipTransaction()` if needed (see [Smart Transactions](../guides/advanced-features.md#smart-transactions)).
- **Event Listener Not Triggered**
  - Ensure you are listening to the correct event class (see [Events Reference](events.md)).
  - Use `Event::fake()` in tests to assert event dispatching.
- **Sanitization Not Working**
  - Confirm `sanitizeInput` is enabled in config and used in your requests (see [Security & Sanitization Tutorial](../tutorials/security-sanitization.md)).
  - Listen for `DataSanitizedEvent` to debug sanitization changes.
- **API Query Parsing**
  - Use Laravel’s request debugging to inspect all incoming query params.
  - Test with both simple and complex queries to isolate issues (see [Enhanced Search Guide](../guides/enhanced-search.md)).

**Common Error Messages:**
- `Class 'Prettus\Repository\...' not found` — Update all imports to `Apiato\Repository`.
- `Cache driver does not support tagging` — Switch to Redis or another supported driver.
- `Hashids decoding failed` — Check if HashIds are enabled and IDs are valid.
- `ValidationException` — Review your validator rules and required fields.

---

## 8. Where to Get Help

- **Docs & Guides**: Start with the [FAQ](../faq.md), [Glossary](../glossary.md), and [Guides](../guides/getting-started.md).
- **Community**: Open an issue on [GitHub](https://github.com/GigiArteni/apiato-repository/issues) or join the Apiato community ([Community Resources](../community-resources.md)).
- **Professional Support**: For enterprise support, contact the maintainers via the official channels.

---

## 9. Advanced Scenarios & Edge Cases

- **Custom Cache Drivers**
  - Ensure your driver supports tagging (Redis recommended).
  - If using a custom cache, test with `$repo->clearCache()` and monitor for stale data.
- **Multi-Database Setups**
  - Confirm all connections are configured in `config/database.php`.
  - Use repository methods with explicit connection if needed.
- **Event Debugging**
  - Use Laravel's `Event::fake()` and `Event::assertDispatched()` in tests.
  - Log all repository events for troubleshooting.
- **Middleware Debugging**
  - Add logging to custom middleware `handle()` methods.
  - Use `$repo->middleware(['audit', 'performance:1000'])->all();` to trace execution.
- **Bulk Operation Failures**
  - Check for DB deadlocks, increase `REPOSITORY_BULK_CHUNK_SIZE` if needed.
  - Use `$repo->getTransactionStats()` for transaction debugging.

---

**See also:** [Testing Guide](../contributing/testing-guide.md), [Configuration Reference](configuration.md), [API Methods Reference](api-methods.md)
