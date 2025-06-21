# FAQ & Common Pitfalls

This page answers frequently asked questions and highlights common pitfalls when using Apiato Repository.

---

## General

**Q: Is Apiato Repository a drop-in replacement for l5-repository?**
A: Yes, with minimal changes. See [Migration Guide](getting-started/migration-from-l5.md).

**Q: Does it work with Laravel outside Apiato?**
A: Yes, but some features (like HashId auto-integration) are Apiato-specific.

---

## HashId & Search

**Q: Why are my HashIds not decoded?**
A: Ensure all ID fields are in `$fieldSearchable` and HashId integration is enabled in config.

**Q: Why does search not work on relationships?**
A: Add relationship fields (e.g., `roles.name`) to `$fieldSearchable`.

---

## Middleware & Caching

**Q: Why is cache not cleared after updates?**
A: Check your cache driver (Redis recommended) and cache config. Use `$repo->clearCache()` if needed.

**Q: How do I apply middleware to only some operations?**
A: Use per-operation middleware: `$repo->middleware(['audit'])->update($data, $id);`

---

## Transactions & Bulk Ops

**Q: How do I wrap multiple operations in a transaction?**
A: Use `$repo->transaction(fn() => ...)` or `$repo->withTransaction()`.

**Q: Why do I get deadlocks on bulk operations?**
A: Tune batch size, use deadlock retry logic, and monitor DB logs.

---

## Validation

**Q: Why does validation fail unexpectedly?**
A: Check your validator rules and required fields. Use `$repo->validator()->errors()` for details.

---

## Events & Listeners

**Q: How do I listen for repository events?**
A: Use Laravel's `Event::listen()` with the event classes listed in [Events Reference](reference/events.md).

---

## Best Practices

- Always use HashIds in APIs.
- Use criteria for reusable business logic.
- Leverage middleware for cross-cutting concerns.
- Test all custom logic and edge cases.

---

For more, see [Troubleshooting](reference/troubleshooting.md) and [Real-World Examples](guides/real-world-examples.md).
