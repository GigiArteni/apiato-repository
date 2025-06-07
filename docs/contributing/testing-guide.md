# Testing Guide

All contributions must be covered by tests. This project uses PHPUnit for unit and integration testing.

---

## 1. Running Tests

```powershell
composer test
```

---

## 2. Test Coverage

```powershell
composer test-coverage
```

---

## 3. Writing Tests

- Place unit tests in `tests/Unit/`
- Place integration/feature tests in `tests/Feature/`
- Use descriptive method names and assertions
- Mock external dependencies where possible

---

## 4. Best Practices

- Write tests for all new features and bug fixes
- Keep tests isolated and repeatable
- Use factories and seeders for test data
- Run tests before submitting a pull request

---

See [Development Setup](development-setup.md) for environment setup.
