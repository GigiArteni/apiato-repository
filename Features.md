# Apiato Repository Features

This document lists all major features and methods provided by the Apiato Repository package (Laravel 12+, PHP 8.1+), based on the actual codebase and strict type-safe implementation.

## Core Repository Methods

- `all(array $columns = ['*']): Collection` — Get all records
- `first(array $columns = ['*']): mixed` — Get the first record
- `paginate(int $limit = null, array $columns = ['*']): mixed` — Paginate results
- `find(mixed $id, array $columns = ['*']): mixed` — Find by primary key
- `findByField(string $field, mixed $value, array $columns = ['*']): Collection` — Find by field value
- `findWhere(array $where, array $columns = ['*']): Collection` — Find by multiple where conditions
- `findWhereIn(string $field, array $where, array $columns = ['*']): Collection` — Find where field in array
- `findWhereNotIn(string $field, array $where, array $columns = ['*']): Collection` — Find where field not in array
- `findWhereBetween(string $field, array $where, array $columns = ['*']): Collection` — Find where field between values
- `create(array $attributes): mixed` — Create a new record
- `update(array $attributes, mixed $id): mixed` — Update a record
- `updateOrCreate(array $attributes, array $values = []): mixed` — Update or create a record
- `delete(mixed $id): bool` — Delete by primary key
- `deleteWhere(array $where): int` — Delete by conditions
- `with(array $relations): static` — Eager load relationships
- `has(string $relation): static` — Filter by existence of relationship
- `whereHas(string $relation, Closure $closure): static` — Filter by relationship with condition

## Criteria & Query Features

- Criteria system: add, remove, and apply custom query criteria
- RequestCriteria: parse and apply search/filter/order from HTTP request
- Enhanced search: phrase, fuzzy, and relevance search (with `selectRaw` and closure logic)
- Relationship field search: e.g., `roles.name:admin`
- Operator search: `>=`, `<=`, `>`, `<`, `!=`, etc.
- Array value search: e.g., `roles:admin,user`
- Null value search: e.g., `deleted_at:null`
- Boolean search: e.g., `active:true`, `active:false`
- Or filter: e.g., `or:email:foo@bar.com|status:active`
- Date search: equals, greater than, less than (with closure logic)
- Relationship + operator: e.g., `orders.total:>=:1000`
- Global and field-specific search via `search` and `filter` query params
- Bulk operations: bulk create, update, delete (with/without transaction)
- Transactional repository: safeCreate, safeUpdate, safeDelete, withTransaction, skipTransaction, isolation level, retry
- Caching support (via trait)

## Validation

- LaravelValidator: rules, custom messages, custom attributes, add/remove rules, validation for create/update

## Extensibility

- Custom Criteria: implement `CriteriaInterface` for reusable query logic
- Custom Validators, Transformers, Cacheable logic via interfaces/traits

## Type Safety

- All methods use strict type hints and return types (PHP 8.1+)
- All Criteria and repository methods are type-safe

## Query String Examples

Below are real-world examples of query strings supported by the repository package. These cover search, filter, include (with), and permutations.

1. `?search=gigiarteni` — Global search for 'gigiarteni' in all searchable fields
2. `?search[name]=John` — Search for 'John' in the 'name' field
3. `?search[name]=John&search[email]=john@example.com` — Search for 'John' in name and 'john@example.com' in email
4. `?filter[roles.name]=admin` — Filter users with related role 'admin'
5. `?filter[age][operator]=>=&filter[age][value]=18` — Filter users with age >= 18
6. `?filter[created_at][operator]=<&filter[created_at][value]=2025-12-31` — Filter users created before 2025-12-31
7. `?filter[status][]=active&filter[status][]=pending` — Filter users with status 'active' or 'pending'
8. `?filter[deleted_at]=` — Filter users where deleted_at is null
9. `?filter[active]=true` — Filter users where active is true
10. `?filter[active]=false` — Filter users where active is false
11. `?filter[or][0][]=email&filter[or][0][]==&filter[or][0][]=alice@example.com&filter[or][1][]=status&filter[or][1][]==&filter[or][1][]=active` — Or filter: email or status
12. `?search[bio]="senior developer"` — Phrase search in bio
13. `?search[name]=john~2` — Fuzzy search for 'john' in name
14. `?filter[orders.total][operator]=>=&filter[orders.total][value]=1000` — Filter by related orders total >= 1000
15. `?search=lead architect` — Global search for 'lead architect'
16. `?search[bio]="C++ developer"` — Phrase search with special characters
17. `?search[name]=jon~1&search[email]=jane~1` — Fuzzy search on multiple fields
18. `?filter[created_at]=2025-06-07` — Filter by exact date
19. `?with=roles,permissions` — Include relationships 'roles' and 'permissions'
20. `?search=John&filter[status]=active&with=roles` — Search for 'John', filter by status, include roles
21. `?search=developer&filter[roles.name]=admin&filter[active]=true&with=roles,permissions` — Combined search, filter, and include
22. `?search[name]=Alice&filter[created_at][operator]=>&filter[created_at][value]=2024-01-01` — Field search and date filter
23. `?filter[roles][]=admin&filter[roles][]=user` — Filter by multiple roles
24. `?search=foo bar&filter[deleted_at]=&with=roles` — Search, filter null, and include
25. `?search[bio]="team lead"&filter[status]=active&with=roles,company` — Phrase search, filter, and include

---

For more details, see the code in `src/Apiato/Repository/` and the tests in `tests/Unit/`.
