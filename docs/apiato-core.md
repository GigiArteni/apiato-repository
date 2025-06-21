# Apiato Core Concepts & Repository Integration

This guide explains the core concepts of the Apiato framework and how Apiato Repository fits into the Apiato ecosystem, enabling modular, scalable, and secure API development.

---

## 1. What is Apiato?

- **Apiato** is a Laravel-based framework for building scalable, modular APIs using the Porto architecture.
- It organizes code into **Containers** (feature modules) and **Ship** (shared/core code).
- Emphasizes separation of concerns, reusability, and testability.

---

## 2. Core Concepts

- **Containers**: Self-contained modules for each business domain (e.g., User, Order, Product).
- **Ship**: Core services, helpers, and base classes shared across containers.
- **Actions/Tasks**: Encapsulate business logic and data access.
- **Requests**: Handle validation and authorization.
- **HashIds**: Used for all IDs in APIs for security and obfuscation.

---

## 3. How Apiato Repository Integrates

- **Repositories**: Each container can have its own repository, following the repository pattern for data access.
- **HashId Integration**: Works seamlessly with Apiato's HashId system.
- **Criteria**: Supports Apiato's request criteria for parsing API query params.
- **Middleware**: Repository middleware complements Apiato's service providers and HTTP middleware.
- **Events**: Repository events integrate with Apiato's event-driven architecture.
- **Validation & Sanitization**: Leverages Apiato's request validation and sanitization for secure data handling.

---

## 4. Best Practices

- Organize all business logic in containers.
- Use repositories for all data access; never query models directly in controllers.
- Use criteria for reusable, testable query logic.
- Always use HashIds in APIs and URLs.
- Leverage middleware and events for cross-cutting concerns.

---

For more, see [Apiato v.13 Integration](apiato13.md), [Feature Matrix](feature-matrix.md), and [Real-World Examples](guides/real-world-examples.md).
