# AGENTS.md

Configuration and guidance for AI agents working with this package.

## Package Context

- **Name:** `speedyspec/speedyspec-wp-hook-infra-memory`
- **Type:** Infrastructure Layer (DDD)
- **PHP Version:** >=8.4
- **Test Framework:** Pest 3.0 or 4.0
- **Domain Dependency:** `speedyspec/speedyspec-wp-hook-domain` ^1

## Agent Guidelines

### Code Generation Rules

1. **Always use strict types:**
   ```php
   <?php

   declare(strict_types=1);
   ```

2. **Use PHP 8.4 features:**
   - Constructor property promotion
   - Match expressions instead of switch
   - Named arguments where it improves clarity
   - Union types and intersection types
   - `readonly` properties where appropriate
   - First-class callable syntax `$obj->method(...)`

3. **Follow DDD Infrastructure Layer principles:**
   - Implement interfaces from the domain layer
   - No business logic - only technical concerns
   - Infrastructure is replaceable without affecting domain

4. **Namespace convention:**
   ```php
   namespace SpeedySpec\WP\Hook\Infra\Memory\{SubDirectory};
   ```

### Testing Guidelines

1. **Test file naming:** `{ClassName}Test.php`
2. **Use Pest 4 syntax:**
   ```php
   covers(ClassName::class);

   beforeEach(function () {
       // Setup
   });

   describe('ClassName::methodName()', function () {
       test('describes expected behavior', function () {
           expect($result)->toBe($expected);
       });
   });
   ```

3. **Test helper in `tests/Pest.php`:**
   ```php
   function createMockAction(): object
   ```

### File Organization

| Directory | Purpose |
|-----------|---------|
| `src/Services/` | Infrastructure implementations of domain interfaces |
| `src/UseCases/` | Application layer use cases with WordPress-compatible signatures |
| `src/Hooks/` | Hook name value objects specific to this infrastructure |
| `src/Actions/` | Action implementations |
| `tests/` | Pest test files |
| `tests/UseCases/` | Use case tests |
| `docs/` | Additional documentation |

## Domain-Driven Design Reference

### Infrastructure Layer Responsibilities

The Infrastructure Layer:
- **Implements** contracts defined in the Domain Layer
- **Provides** technical implementations (storage, external APIs, frameworks)
- **Is swappable** - can be replaced without changing Domain or Application layers
- **Contains no business logic** - only technical concerns

### Layer Dependencies (Allowed)

```
Infrastructure → Domain (allowed)
Infrastructure → Application (allowed for use cases)
Infrastructure → Presentation (NOT allowed)
Domain → Infrastructure (NOT allowed - use interfaces)
```

### This Package's Role

```
┌────────────────────────────────────────────────────────┐
│                   Domain Layer                          │
│              (speedyspec-wp-hook-domain)                │
│                                                         │
│  Contracts:                                             │
│  - HookContainerInterface                               │
│  - HookSubjectInterface                                 │
│  - HookInvokableInterface                               │
│  - CurrentHookInterface                                 │
│  - HookRunAmountInterface                               │
└────────────────────────────────────────────────────────┘
                          │
                          │ implements
                          ▼
┌────────────────────────────────────────────────────────┐
│              Infrastructure Layer                       │
│         (speedyspec-wp-hook-infra-memory)              │
│                                                         │
│  Implementations:                                       │
│  - MemoryHookContainer implements HookContainerInterface│
│  - MemoryHookSubject implements HookSubjectInterface    │
│                                                         │
│  Use Cases (Application Layer hosted here):             │
│  - LegacyAddFilterUseCase                               │
│  - LegacyDispatchActionHookUseCase                      │
│  - etc.                                                 │
└────────────────────────────────────────────────────────┘
```

## Common Tasks

### Task: Add New Infrastructure Implementation

1. Check domain interface in `speedyspec-wp-hook-domain`
2. Create implementation in `src/Services/`
3. Implement all interface methods
4. Register in `ServiceProvider.php`
5. Write Pest tests

### Task: Add New Use Case

1. Check interface in domain's `Contracts/UseCases/`
2. Create use case in `src/UseCases/`
3. Inject required dependencies via constructor
4. Implement the interface method
5. Register in `ServiceProvider.php`
6. Write Pest tests in `tests/UseCases/`

### Task: Write Tests

```bash
# Create test file
touch tests/UseCases/NewUseCaseTest.php

# Run tests
./vendor/bin/pest

# Run specific test
./vendor/bin/pest tests/UseCases/NewUseCaseTest.php

# Run with coverage
./vendor/bin/pest --coverage
```

### Task: Fix Failing Test

1. Read the test file to understand expected behavior
2. Read the source implementation
3. Check domain contracts for interface requirements
4. Fix implementation to match contract
5. Run tests to verify

## Key Interfaces to Know

### From Domain Package

```php
interface HookContainerInterface {
    public function add(HookNameInterface $hook, HookInvokableInterface|HookActionInterface|HookFilterInterface $callback): void;
    public function remove(HookNameInterface $hook, HookInvokableInterface|HookActionInterface|HookFilterInterface $callback): void;
    public function removeAll(HookNameInterface $hook, ?int $priority = null): void;
    public function dispatch(HookNameInterface $hook, ...$args): void;
    public function filter(HookNameInterface $hook, mixed $value, ...$args): mixed;
    public function hasCallbacks(HookNameInterface $hook, ?HookInvokableInterface $callback = null, ?int $priority = null): bool;
}

interface HookSubjectInterface {
    public function add(HookInvokableInterface|HookActionInterface|HookFilterInterface $callback): void;
    public function remove(HookInvokableInterface|HookActionInterface|HookFilterInterface $callback): void;
    public function removeAll(?int $priority = null): void;
    public function dispatch(...$args): void;
    public function filter(mixed $value, ...$args): mixed;
    public function hasCallbacks(?HookInvokableInterface $callback = null, ?int $priority = null): bool;
}
```

## Prompts for Common Operations

### Generate a New Use Case Test
```
Write a Pest 4 test for {UseCaseName} in the speedyspec-wp-hook-infra-memory package.
Follow the existing test patterns using beforeEach for setup and describe/test blocks.
```

### Implement a Domain Interface
```
Implement {InterfaceName} from speedyspec-wp-hook-domain as a memory-based implementation.
Use PHP 8.4 features and follow the existing patterns in src/Services/.
```

### Debug a Failing Test
```
The test in {TestFile} is failing. Read the test expectations, the source implementation,
and the domain interface contract. Identify the discrepancy and fix the implementation.
```
