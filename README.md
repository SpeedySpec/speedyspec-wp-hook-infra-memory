# SpeedySpec WP Hook Infrastructure - Memory

An in-memory implementation of the WordPress hook system for the SpeedySpec WP Hook Domain package. This package provides
lightweight, fast hook storage suitable for testing, CLI applications, and scenarios where persistent hook storage is 
not required.

**Note:** Version 1.x is not semver-compatible and should not be considered stable. Consider the 1.0 version 0.x for
usage purposes. Version 2.x will be semver-compatible.

## Installation

```bash
composer require speedyspec/speedyspec-wp-hook-infra-memory
```

## Requirements

- PHP 8.4 or higher
- `speedyspec/speedyspec-wp-hook-domain` ^1.0

## Quick Start

### Basic Setup

```php
use SpeedySpec\WP\Hook\Domain\HookServiceContainer;
use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;

// Get the service container
$container = HookServiceContainer::getInstance();

// Register the memory infrastructure services (using interfaces for flexibility)
$container->add(HookRunAmountInterface::class, fn() => new HookRunAmountService());
$container->add(CurrentHookInterface::class, fn() => new CurrentHookService());
$container->add(
    HookContainerInterface::class,
    fn($c) => new MemoryHookContainer(
        $c->get(HookRunAmountInterface::class),
        $c->get(CurrentHookInterface::class)
    )
);

// Now use the hook system
$hooks = $container->get(HookContainerInterface::class);
```

### Using the ServiceProvider

For convenience, you can use the included service provider:

```php
use SpeedySpec\WP\Hook\Infra\Memory\ServiceProvider;
use SpeedySpec\WP\Hook\Domain\HookServiceContainer;
use SpeedySpec\WP\Hook\Domain\Contracts\CalledDeprecatedHookInterface;
use SpeedySpec\WP\Hook\Infra\Memory\Services\CalledDeprecatedHook;

$provider = new ServiceProvider();
$provider->register();

// If you need deprecated hook support, register CalledDeprecatedHook
$container = HookServiceContainer::getInstance();
$container->add(
    CalledDeprecatedHookInterface::class,
    fn() => new CalledDeprecatedHook()
);

// Services are now registered and ready to use
```

### Adding and Executing Hooks

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

// Get the hook container
$hooks = HookServiceContainer::getInstance()->get(HookContainerInterface::class);

// Add a filter callback
$callback = new ObjectHookInvoke(
    fn(string $value) => strtoupper($value),
    priority: 10
);
$hooks->add(new StringHookName('my_filter'), $callback);

// Apply the filter
$result = $hooks->filter(new StringHookName('my_filter'), 'hello');
// Result: 'HELLO'

// Add an action callback
$actionCallback = new ObjectHookInvoke(
    fn(string $message) => error_log($message),
    priority: 10
);
$hooks->add(new StringHookName('my_action'), $actionCallback);

// Dispatch the action
$hooks->dispatch(new StringHookName('my_action'), 'Something happened');
```

## When to Use This Package

**Use this package when:**
- Writing unit or integration tests
- Building CLI applications
- Creating lightweight scripts
- Prototyping hook-based features
- Running in environments where hook state doesn't need persistence

**Consider alternatives when:**
- You need hooks to persist across requests
- You're building a full WordPress-compatible application
- You need the `WP_Hook` class compatibility

## Documentation

For comprehensive documentation, see the [docs](./docs/) directory:

- [Services](./docs/services.md) - Detailed documentation of `MemoryHookContainer` and `MemoryHookSubject`
- [Testing](./docs/testing.md) - How to use this package in your tests
- [Examples](./docs/examples.md) - Common usage patterns and recipes

## Testing

```bash
composer install
./vendor/bin/pest
```

## License

BSD-3-Clause
