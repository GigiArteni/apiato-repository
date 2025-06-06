# Artisan Commands Reference: Automate Everything

Apiato Repository comes with a suite of artisan commands to generate repositories, criteria, presenters, validators, and more. These commands save time, enforce best practices, and ensure consistency across your codebase.

---

## 1. Generate a Repository

```powershell
php artisan make:repository UserRepository
php artisan make:repository UserRepository --model=User
```
- Creates a repository class (optionally linked to a model).

---

## 2. Generate Criteria

```powershell
php artisan make:criteria ActiveUsersCriteria
```
- Creates a reusable criteria class for encapsulating business logic.

---

## 3. Generate a Complete Entity

```powershell
php artisan make:entity User --presenter --validator
```
- Creates a model, repository, presenter, and validator in one step.

---

## 4. Generate a Presenter

```powershell
php artisan make:presenter UserPresenter --transformer=UserTransformer
```
- Creates a presenter class for transforming output (API, UI, etc).

---

## 5. Generate a Validator

```powershell
php artisan make:validator UserValidator --rules=create,update
```
- Creates a validator class with rules for create/update.

---

## 6. Generate a Transformer

```powershell
php artisan make:transformer UserTransformer --model=User
```
- Creates a transformer for API output.

---

## 7. Best Practices

- **Use generators for all new repositories and criteria**—they enforce structure and save time.
- **Customize generated code** to fit your business logic, but keep the structure for maintainability.
- **Document your custom criteria and transformers** for your team.

---

**See also:** [Building a User Repository](../tutorials/building-user-repository.md), [Advanced Features](../guides/advanced-features.md)
