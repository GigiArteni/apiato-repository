# Implementing Advanced Search in Apiato Repository

This tutorial demonstrates how to implement and customize advanced search features using Apiato Repository, including enhanced search, boolean logic, fuzzy matching, relevance scoring, and relationship queries.

---

## 1. Enabling Enhanced Search

- Ensure enhanced search is enabled in your config or `.env`:
  ```env
  REPOSITORY_ENHANCED_SEARCH=true
  REPOSITORY_FUZZY_SEARCH=true
  REPOSITORY_RELEVANCE_SCORING=true
  ```

---

## 2. Defining Searchable Fields

- In your repository, define all fields (including relationships) you want to be searchable:
  ```php
  protected $fieldSearchable = [
      'name' => 'like',
      'email' => 'like',
      'bio' => 'like',
      'company.name' => 'like',
      'roles.name' => 'like',
  ];
  ```

---

## 3. Using Boolean and Fuzzy Search

- API Example:
  ```bash
  GET /api/users?search=+developer +senior -intern
  GET /api/users?search=john~2
  ```
- Eloquent Example:
  ```php
  $users = $repo->findWhere([
      ['name', 'like', '%john%'],
      ['bio', 'like', '%developer%'],
  ]);
  ```

---

## 4. Relevance Scoring and Phrase Search

- API Example:
  ```bash
  GET /api/users?search="senior developer"&enhanced=true
  ```
- Results will be ranked by relevance.

---

## 5. Relationship and Multi-Field Search

- API Example:
  ```bash
  GET /api/users?search=roles.name:admin;company.name:acme
  ```
- Eloquent Example:
  ```php
  $users = $repo->whereHas('roles', fn($q) => $q->where('name', 'admin'))
      ->whereHas('company', fn($q) => $q->where('name', 'acme'))
      ->get();
  ```

---

## 6. Customizing Search Logic

- Override `applySearch` in your repository for custom logic.
- Use criteria for reusable, complex search patterns.

---

## 7. Best Practices

- Always define all relevant fields in `$fieldSearchable`.
- Test with real-world queries and edge cases.
- Tune configuration for your dataset and use case.

---

For more, see the [Enhanced Search Guide](../guides/enhanced-search.md) and [API Methods Reference](../reference/api-methods.md).
