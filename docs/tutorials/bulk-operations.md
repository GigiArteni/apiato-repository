# Bulk Operations & Smart Transactions Tutorial

This tutorial covers high-performance bulk operations and smart transaction management in Apiato Repository, including batch insert/update/upsert/delete, chunking, stats, auto-transactions, isolation levels, and deadlock retry logic.

---

## 1. Bulk Insert/Update/Upsert/Delete

- Insert many records efficiently:
  ```php
  $stats = $repo->bulkInsert($records, [
      'batch_size' => 2000,
      'chunk_callback' => fn($i, $t, $c) => echo "Progress: {$t}/{$c}\n"
  ]);
  ```
- Update or upsert in bulk:
  ```php
  $repo->bulkUpsert($records, ['id'], ['name', 'email', 'updated_at']);
  $repo->bulkUpdate($records, ['status' => 'active']);
  $repo->bulkDelete(['status' => 'inactive']);
  ```

---

## 2. Chunking & Progress Tracking

- Process large datasets in chunks:
  ```php
  $repo->chunk(1000, function($users) {
      foreach ($users as $user) {
          // Process user
      }
  });
  ```

---

## 3. Smart Transactions

- Auto-wrap critical operations in transactions:
  ```php
  $repo->safeCreate($data); // Auto transaction if needed
  $repo->withTransaction()->update($data, $id);
  ```
- Conditional and batch transactions:
  ```php
  $repo->conditionalTransaction($shouldWrap, fn() => $repo->update($data, $id));
  $repo->batchOperations([
      fn() => $repo->create($userData),
      fn() => $profileRepo->create($profileData),
  ]);
  ```
- Custom isolation levels:
  ```php
  $repo->withIsolationLevel('SERIALIZABLE')->withTransaction()->update($data, $id);
  ```

---

## 4. Deadlock Retry Logic

- Automatic deadlock detection and retry:
  ```php
  $repo->transaction(function() use ($data) {
      $user = $repo->create($data['user']);
      $profile = $profileRepo->create($data['profile']);
      return ['user' => $user, 'profile' => $profile];
  });
  // Retries up to 3 times on deadlock with exponential backoff
  ```

---

## 5. Best Practices

- Use bulk ops for admin/data migration tasks.
- Always wrap critical/batch ops in transactions.
- Monitor for deadlocks and tune batch sizes.

---

For more, see the [API Methods Reference](../reference/api-methods.md#bulk-operations) and [Advanced Features](../guides/advanced-features.md).
