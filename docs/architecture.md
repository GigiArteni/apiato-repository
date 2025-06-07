# Architecture & Lifecycle Diagrams

This page provides visual diagrams and flowcharts to help you understand the architecture, repository lifecycle, middleware flow, and event dispatching in Apiato Repository.

---

## 1. Repository Operation Lifecycle

```mermaid
graph TD;
    A[Controller/Service] --> B[Repository Method]
    B --> C{Middleware Stack}
    C -->|Audit| D[AuditMiddleware]
    C -->|Cache| E[CacheMiddleware]
    C -->|RateLimit| F[RateLimitMiddleware]
    C -->|TenantScope| G[TenantScopeMiddleware]
    C -->|Performance| H[PerformanceMonitorMiddleware]
    C --> I[Core Operation]
    I --> J[Sanitization]
    J --> K[Validation]
    K --> L[Model/Eloquent]
    L --> M[Events]
    M --> N[Presenter/Transformer]
    N --> O[Response]
```

---

## 2. Middleware Flow

```mermaid
flowchart LR
    Start --> Middleware1 --> Middleware2 --> Middleware3 --> Operation --> End
    subgraph MiddlewareStack
        Middleware1[Audit]
        Middleware2[Cache]
        Middleware3[RateLimit]
    end
```

---

## 3. Event Dispatching

```mermaid
graph TD;
    Operation -->|EntityCreated| Event1[RepositoryEntityCreated]
    Operation -->|EntityUpdated| Event2[RepositoryEntityUpdated]
    Operation -->|EntityDeleted| Event3[RepositoryEntityDeleted]
    Event1 --> Listener1[Audit Log]
    Event2 --> Listener2[Notification]
    Event3 --> Listener3[Cache Invalidation]
```

---

# Architecture Diagrams & Visuals

## 1. Middleware Pipeline

```mermaid
graph TD;
    A[Repository Method Call] --> B{Has Middleware?}
    B -- No --> C[Execute Method]
    B -- Yes --> D[Middleware Stack]
    D --> E[AuditMiddleware]
    D --> F[CacheMiddleware]
    D --> G[RateLimitMiddleware]
    D --> H[TenantScopeMiddleware]
    D --> I[PerformanceMonitorMiddleware]
    E & F & G & H & I --> J[Execute Method]
    J --> K[Return Result]
```

## 2. Event Flow

```mermaid
graph TD;
    A[Repository Operation] --> B[Dispatch Event]
    B --> C[Event Listener(s)]
    C --> D[Handle Side Effects (e.g., Audit, Cache, Notification)]
```

## 3. Transaction Handling

```mermaid
graph TD;
    A[Repository Method] --> B{Transaction Needed?}
    B -- No --> C[Execute Directly]
    B -- Yes --> D[Begin Transaction]
    D --> E[Execute Operation(s)]
    E --> F{Success?}
    F -- Yes --> G[Commit]
    F -- No --> H[Rollback]
    G & H --> I[Return Result]
```

---

For more, see [Feature Matrix](feature-matrix.md) and [API Methods Reference](reference/api-methods.md).

**See also:** [Advanced Features](guides/advanced-features.md), [Middleware Guide](tutorials/middleware.md), [Events Reference](reference/events.md), [Troubleshooting](reference/troubleshooting.md)
