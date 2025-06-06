# Troubleshooting: Diagnose, Fix, and Succeed

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

**See also:** [Migration Guide](../getting-started/migration-from-l5.md), [Configuration Reference](configuration.md), [Guides](../guides/)
