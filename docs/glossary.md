# Glossary: Key Terms in Apiato Repository

A quick reference for important terms, concepts, and patterns used throughout the documentation.

---

**Repository Pattern**: A design pattern that abstracts data access logic, providing a consistent API for querying and manipulating data.

**HashId**: An obfuscated, non-sequential string representation of a numeric ID, used for security and clean URLs.

**Criteria**: Reusable, encapsulated query logic that can be stacked and parameterized for filtering data.

**ScopeQuery**: A closure-based way to apply ad-hoc query logic to a repository.

**Cross-cutting logic**: Audit, cache, rate-limit, etc. that can be applied to repository operations, similar to HTTP middleware. (Middleware system removed)

**Bulk Operations**: High-performance methods for inserting, updating, or deleting many records at once.

**Validation**: Ensuring data meets defined rules before it is persisted or processed.

**Presenter**: A class that transforms data for output, often using Fractal.

**Transformer**: A class that defines how a model or resource is converted to an array or JSON for APIs.

**Event**: A signal that something happened in the repository (e.g., model created, updated, deleted), which can trigger listeners.

**Cache Tagging**: Associating cache entries with tags for fine-grained invalidation.

**Transaction**: A database operation that is atomic, consistent, isolated, and durable (ACID), often used for critical or batch operations.

**Deadlock**: A database state where two or more operations block each other, requiring retry logic.

**Apiato**: A Laravel-based framework for building scalable, modular APIs, with built-in support for HashIds and advanced patterns.

---

For more, see the [Feature Matrix](feature-matrix.md) and [API Methods Reference](reference/api-methods.md).
