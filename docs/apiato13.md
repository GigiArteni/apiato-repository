# Apiato v.13 Integration Guide

This guide explains how Apiato Repository is designed for seamless, zero-config integration with Apiato v.13, and highlights the unique features and best practices for Apiato projects.

---

## 1. Zero-Config Integration

- **Auto-detection**: The package detects Apiato v.13 and configures itself automatically.
- **HashId support**: All repository methods work with HashIds out of the box.
- **Criteria**: RequestCriteria parses API query params (`search`, `filter`, `orderBy`, etc.) and supports enhanced search.

---

## 2. Apiato-Specific Features

- **Containers & Ship Structure**: Repositories, criteria, presenters, and validators are generated in the correct Apiato containers.
- **Event System**: Repository events integrate with Apiato's event-driven architecture.
- **Middleware**: Works with Apiato's service providers and supports custom middleware stacks.
- **Validation**: Uses Laravel validation, but can be extended for Apiato-specific rules.

---

## 3. Best Practices for Apiato Projects

- Use HashIds everywhere in APIs and URLs for security.
- Define all searchable fields (including relationships) in `$fieldSearchable`.
- Use criteria for reusable business logic and API query parsing.
- Leverage middleware for audit, caching, tenant-scope, and performance.
- Use event listeners for audit, notifications, and integrations.

---

## 4. Migration Tips

- See [Migration Guide](getting-started/migration-from-l5.md) for moving from l5-repository to Apiato Repository.
- Update all imports from `Prettus\Repository` to `Apiato\Repository`.
- Review and update custom criteria, presenters, and validators for namespace and feature changes.

---

## 5. Troubleshooting Apiato Integration

- If HashIds do not work, check that you are running Apiato v.13 and that all config files are published.
- For advanced configuration, see [Configuration Reference](reference/configuration.md).

---

For more, see the [Feature Matrix](feature-matrix.md), [Real-World Examples](guides/real-world-examples.md), and [API Methods Reference](reference/api-methods.md).
