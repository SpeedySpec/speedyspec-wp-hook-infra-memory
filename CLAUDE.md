# CLAUDE.md

This file provides guidance to Claude Code when working with this package.

## Package Overview

**Package:** `speedyspec/speedyspec-wp-hook-infra-memory`

This is the **Infrastructure Layer** of a Domain-Driven Design (DDD) architecture for the WordPress Hook API. It provides an in-memory implementation of hook storage, suitable for testing, CLI applications, and lightweight scenarios.

## Technology Stack

- **PHP:** >=8.4 (uses modern PHP features: constructor property promotion, match expressions, named arguments, union types)
- **Testing:** Pest 3.0 or 4.0
- **Dependency:** `speedyspec/speedyspec-wp-hook-domain` ^1

## Domain-Driven Design Context

### DDD Layer Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                       │
│              (WordPress Legacy Functions API)               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                        │
│                      (Use Cases)                            │
│   LegacyAddFilterUseCase, LegacyDispatchActionHookUseCase   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      Domain Layer                           │
│            (speedyspec-wp-hook-domain package)              │
│   Contracts, Entities, Value Objects, Domain Services       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                  Infrastructure Layer                       │
│          ★ THIS PACKAGE (infra-memory) ★                    │
│   MemoryHookContainer, MemoryHookSubject, ServiceProvider   │
└─────────────────────────────────────────────────────────────┘
```

### Infrastructure Layer Responsibilities

The Infrastructure Layer in DDD:
1. **Implements Domain Interfaces** - Provides concrete implementations of contracts defined in the Domain layer
2. **Handles Technical Concerns** - Manages storage, external services, and framework integration
3. **Is Swappable** - Can be replaced without affecting Domain or Application layers
4. **Has No Business Logic** - Only technical implementation details

This package implements:
- `HookContainerInterface` → `MemoryHookContainer`
- `HookSubjectInterface` → `MemoryHookSubject`

## Directory Structure

```
src/
├── Services/
│   ├── MemoryHookContainer.php    # Main hook registry (implements HookContainerInterface)
│   ├── MemoryHookSubject.php      # Individual hook storage (implements HookSubjectInterface)
│   ├── CalledDeprecatedHook.php   # Deprecated hook handling
│   └── HookRunAmountService.php   # (from domain, used here)
├── UseCases/
│   ├── LegacyAddActionUseCase.php
│   ├── LegacyAddFilterUseCase.php
│   ├── LegacyCurrentActionUseCase.php
│   ├── LegacyCurrentFilterUseCase.php
│   ├── LegacyDidActionUseCase.php
│   ├── LegacyDidFilterUseCase.php
│   ├── LegacyDispatchActionHookUseCase.php
│   ├── LegacyDispatchDeprecatedActionHookUseCase.php
│   ├── LegacyDispatchDeprecatedFilterHookUseCase.php
│   ├── LegacyDispatchFilterHookUseCase.php
│   ├── LegacyDoingActionUseCase.php
│   ├── LegacyDoingFilterUseCase.php
│   ├── LegacyHasActionUseCase.php
│   ├── LegacyHasFilterUseCase.php
│   ├── LegacyRemoveActionUseCase.php
│   ├── LegacyRemoveAllActionsUseCase.php
│   ├── LegacyRemoveAllFiltersUseCase.php
│   └── LegacyRemoveFilterUseCase.php
├── Hooks/
│   └── DeprecatedHookRunHookName.php
├── Actions/
│   └── DeprecatedHookRunAction.php
└── ServiceProvider.php            # DI container registration
```

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/UseCases/LegacyAddFilterUseCaseTest.php

# Run tests with coverage
./vendor/bin/pest --coverage
```

## Testing Patterns

Tests use Pest 4 with the following patterns:

```php
<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;

covers(MemoryHookContainer::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
});

describe('MemoryHookContainer::add()', function () {
    test('can add a filter', function () {
        // Test implementation
    });
});
```

## Key Classes

### MemoryHookContainer
Main registry that holds all hooks. Implements `HookContainerInterface`.

```php
$container = new MemoryHookContainer($hookRunAmountService, $currentHookService);
$container->add($hookName, $callback);
$container->dispatch($hookName, ...$args);
$result = $container->filter($hookName, $value, ...$args);
```

### MemoryHookSubject
Stores callbacks for a single hook name. Manages priority sorting and callback execution.

```php
$subject = new MemoryHookSubject($currentHookService);
$subject->add($callback);           // Add callback
$subject->remove($callback);        // Remove specific callback
$subject->removeAll($priority);     // Remove all at priority
$subject->dispatch(...$args);       // Execute as action
$result = $subject->filter($value); // Execute as filter
$subject->hasCallbacks();           // Check if callbacks exist
```

### Use Cases (Application Layer)
Provide WordPress-compatible function signatures that delegate to the infrastructure:

```php
$addFilter = new LegacyAddFilterUseCase($container);
$addFilter->add('hook_name', 'callback_function', 10, 1);
```

## Coding Standards

- **Strict Types:** All files declare `strict_types=1`
- **PHP 8.4 Features:** Use constructor property promotion, match expressions, named arguments
- **Namespacing:** `SpeedySpec\WP\Hook\Infra\Memory\`
- **No Business Logic:** Infrastructure layer only implements technical concerns

## Common Tasks

### Adding a New Hook Storage Implementation
1. Implement the interface from the domain package
2. Register in `ServiceProvider.php`
3. Write Pest tests covering all interface methods

### Testing Use Cases
Use cases require both the container and domain services:
```php
$hookRunAmountService = new HookRunAmountService();
$currentHookService = new CurrentHookService();
$container = new MemoryHookContainer($hookRunAmountService, $currentHookService);
$useCase = new LegacyAddFilterUseCase($container);
```

## Dependencies

This package depends on `speedyspec/speedyspec-wp-hook-domain` which provides:
- `HookContainerInterface` - Contract for hook storage
- `HookSubjectInterface` - Contract for individual hook management
- `HookInvokableInterface` - Contract for callable hooks
- `StringHookName`, `ArrayHookInvoke`, `ObjectHookInvoke` - Value objects and entities
- `CurrentHookService`, `HookRunAmountService` - Domain services
