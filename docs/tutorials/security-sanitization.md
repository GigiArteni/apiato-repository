# Security, Sanitization & Validation Tutorial

This tutorial covers all security-related features in Apiato Repository: automatic data sanitization, validation, HashId security, audit logging, and best practices for secure APIs.

---

## 1. Automatic Data Sanitization

- All create/update operations are sanitized using Apiato's `sanitizeInput()`.
- Custom rules can be defined per-repository:
  ```php
  protected $customSanitizationRules = [
      'email' => 'email',
      'bio' => 'html_purify',
      'name' => 'string',
  ];
  ```
- Sanitization is applied to all bulk operations, criteria, and relationships.

---

## 2. Validation

- Use Laravel-style validators for create/update:
  ```php
  $repo->validator()->with(['name' => 'Test'])->passes();
  $errors = $repo->validator()->errors();
  ```
- Custom rules and messages are supported.

---

## 3. HashId Security

- All ID fields are automatically decoded/encoded using HashIds.
- Prevents ID enumeration and data scraping.
- Always use HashIds in APIs and URLs.

---

## 4. Audit Logging & Event-Driven Security

- Use audit middleware to log all changes:
  ```php
  $repo->middleware(['audit'])->create($data);
  ```
- Listen for events to trigger security actions:
  ```php
  Event::listen(DataSanitizedEvent::class, function($event) {
      SecurityLogger::logSanitization($event);
  });
  ```

---

## 5. Best Practices

- Always define all ID fields in `$fieldSearchable`.
- Use audit and rate-limit middleware for sensitive operations.
- Validate and sanitize all user input.
- Test with both valid and malicious data.

---

For more, see the [API Methods Reference](../reference/api-methods.md#sanitization--security), [HashId Integration](../guides/hashid-integration.md), and [Troubleshooting](../reference/troubleshooting.md).
