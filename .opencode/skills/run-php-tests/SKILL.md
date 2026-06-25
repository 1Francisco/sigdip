---
name: run-php-tests
description: Run PHPUnit tests with optional filter and stop-on-failure
---

## What it does

Runs `./vendor/bin/phpunit` with optional flags for filtering and debugging.

## Instructions

### 1. Ask the user

1. **Test filter** (optional) — e.g. `AnimalTest`, `test_index`, `ProductorApiTest`
2. **Stop on failure?** (default: yes)
3. **Verbose output?** (default: yes)

### 2. Run the command

```bash
cd {project_root} && ./vendor/bin/phpunit
```

With filter:
```bash
cd {project_root} && ./vendor/bin/phpunit --filter {filter}
```

With stop-on-failure:
```bash
cd {project_root} && ./vendor/bin/phpunit --stop-on-failure --filter {filter}
```

### 3. Notes

- DB is SQLite in-memory (already configured in `phpunit.xml`)
- Tests use `RefreshDatabase` trait — never `DatabaseTransactions`
- Roles must be created manually in `setUp()` with `Role::firstOrCreate()`
- API auth uses `Sanctum::actingAs($user)`
