# Real-World Examples: Business-Driven Solutions

Apiato Repository is built for real business needs. This guide showcases practical, production-ready examples for common domains—so you can see how to solve real problems, not just toy ones.

---

## 1. E-commerce: Customer Segmentation & Orders

**Find VIP customers with recent high-value orders:**
```php
$vipCustomers = $repo->whereHas('orders', function($q) {
    $q->where('total', '>', 1000)
      ->where('created_at', '>=', now()->subYear())
      ->whereIn('status', ['completed', 'shipped']);
})
->where('lifetime_value', '>', 5000)
->with(['orders.products', 'profile'])
->get();
```

**API Example:**
```bash
GET /api/users?search=orders.total:>=:1000;orders.status:completed&filter=lifetime_value:>=:5000&with=orders.products,profile
```

---

## 2. HR & Talent: Advanced Candidate Search

**Find senior React developers in London, not contractors:**
```php
$candidates = $repo->whereHas('skills', fn($q) => $q->where('name', 'React'))
    ->where('level', 'senior')
    ->where('location', 'London')
    ->where('contract_type', '!=', 'contract')
    ->get();
```

**API Example:**
```bash
GET /api/candidates?search=skills.name:React;level:senior;location:London;contract_type:not:contract
```

---

## 3. Project Management: Team Assignment

**Find available developers for a project:**
```php
$available = $repo->whereHas('skills', fn($q) => $q->whereIn('skill_id', $skillIds))
    // Use a custom criteria or scopeQuery for 'doesn't have' logic if needed
    ->scopeQuery(function($query) {
        return $query->whereDoesntHave('projects', function($q) {
            $q->where('status', 'active');
        });
    })
    ->where('status', 'active')
    ->get();
```
// Note: 'whereDoesntHave' is an Eloquent method. If your repository supports scopeQuery, use it as above. Otherwise, implement a custom criteria for this logic.

---

## 4. Inventory: Low Stock & Supplier Reliability

**Find products low in stock from reliable suppliers:**
```php
$products = $repo->where('current_stock', '<=', 'reorder_level')
    ->whereHas('supplier', fn($q) => $q->where('reliability_score', '>', 8))
    ->get();
```

---

## 5. Multi-Tenancy: Data Isolation

**Find users in a company with specific permissions:**
```php
$users = $repo->where('company_id', 'ghi789')
    ->whereHas('roles.permissions', fn($q) => $q->where('resource_id', 'jkl012')->where('action', 'read'))
    ->get();
```

---

## 6. Advanced API Query Patterns

**Complex search, filter, and relationship traversal:**
```bash
GET /api/users?search=roles.name:admin;company.projects.status:active&filter=status:active&with=roles,company.projects
```

---

## 7. Best Practices

- **Model your real business logic as criteria and relationships.**
- **Use HashIds everywhere for security and consistency.**
- **Test with real-world data and edge cases.**

---

## 8. Multi-Model & Domain Examples

- **Order Processing:**
  ```php
  $orders = $orderRepo->where('status', 'pending')
      ->whereHas('customer', fn($q) => $q->where('vip', true))
      ->with(['items.product', 'customer'])
      ->get();
  ```
- **Product Inventory:**
  ```php
  $products = $productRepo->where('stock', '<', 10)
      ->whereHas('supplier', fn($q) => $q->where('active', true))
      ->get();
  ```
- **Multi-Tenancy (Company/Org):**
  ```php
  $users = $userRepo->middleware(['tenant-scope:company_id'])
      ->where('role', 'manager')
      ->get();
  ```
- **Cross-Model Bulk Operations:**
  ```php
  $repo->bulkUpsert($data, ['external_id'], ['name', 'email', 'updated_at']);
  ```

---

**Next:**
- [API Reference →](../reference/api-methods.md)
- [Building Your Own Repository →](../tutorials/building-user-repository.md)
