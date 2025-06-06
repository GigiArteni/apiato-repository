# HashId Integration: Secure, Seamless, and Automatic

One of the most powerful features of Apiato Repository is its **native, automatic HashId support**. This means you can use obfuscated, non-sequential IDs everywhere—without any extra code or risk of leaking database internals.

---

## 1. What are HashIds (and Why Should You Care)?

- **HashIds** turn database IDs (like `123`) into obfuscated, non-sequential strings (like `gY6N8`).
- **Why?**
  - Prevents users from guessing or scraping your data (security by design).
  - Makes URLs, APIs, and logs safer and more professional.
  - Required by Apiato and many modern API standards.

---

## 2. How Does Apiato Repository Handle HashIds?

- **Automatic decoding**: Pass a HashId to any repository method—find, update, delete, relationships, search, filters, criteria—and it "just works."
- **No manual decoding**: No more `Hashids::decode()` in your controllers or services.
- **Works in API query strings**: Your API consumers can use HashIds in all endpoints.

---

## 3. Real-World Usage Examples

**Find by HashId:**
```php
$user = $repo->find('gY6N8'); // HashId decoded automatically
```

**Find multiple by HashId:**
```php
$users = $repo->findWhereIn('id', ['abc123', 'def456']);
```

**Relationship queries:**
```php
$users = $repo->whereHas('posts', fn($q) => $q->where('category_id', 'abc123'))->get();
```

**API Example:**
```bash
GET /api/users?search=role_id:abc123
```

---

## 4. Configuration & Environment

**config/repository.php:**
```php
'apiato' => [
    'hashids' => [
        'enabled' => env('REPOSITORY_HASHIDS_ENABLED', true),
        'auto_decode' => env('REPOSITORY_HASHIDS_AUTO_DECODE', true),
        'decode_search' => env('REPOSITORY_HASHIDS_DECODE_SEARCH', true),
        'decode_filters' => env('REPOSITORY_HASHIDS_DECODE_FILTERS', true),
    ],
],
```

**.env:**
```env
REPOSITORY_HASHIDS_ENABLED=true
REPOSITORY_HASHIDS_AUTO_DECODE=true
REPOSITORY_HASHIDS_DECODE_SEARCH=true
REPOSITORY_HASHIDS_DECODE_FILTERS=true
```

---

## 5. Best Practices

- **Always use HashIds in your APIs and URLs**—it’s automatic and secure.
- **Define all ID fields in `$fieldSearchable`**—HashId decoding will be applied everywhere.
- **Test your API endpoints with both integer IDs and HashIds** (for migration scenarios).

---

**Next:**
- [Enhanced Search →](enhanced-search.md)
- [Real-World Examples →](real-world-examples.md)
