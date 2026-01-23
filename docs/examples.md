# Examples

This document provides common usage patterns and recipes for the memory infrastructure package.

## Basic Examples

### Setting Up the Hook System

```php
<?php

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use SpeedySpec\WP\Hook\Domain\HookServiceContainer;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;

// Bootstrap the hook system
function bootstrapHooks(): HookContainerInterface
{
    $container = HookServiceContainer::getInstance();

    $container->add(
        HookRunAmountInterface::class,
        fn() => new HookRunAmountService()
    );

    $container->add(
        CurrentHookInterface::class,
        fn() => new CurrentHookService()
    );

    $container->add(
        HookContainerInterface::class,
        fn($c) => new MemoryHookContainer(
            $c->get(HookRunAmountInterface::class),
            $c->get(CurrentHookInterface::class)
        )
    );

    return $container->get(HookContainerInterface::class);
}

// Usage
$hooks = bootstrapHooks();
```

### Simple Filter

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

// Add a filter to uppercase content
$hooks->add(
    new StringHookName('the_title'),
    new ObjectHookInvoke(
        fn(string $title) => strtoupper($title),
        priority: 10
    )
);

// Apply the filter
$title = $hooks->filter(new StringHookName('the_title'), 'Hello World');
echo $title; // HELLO WORLD
```

### Simple Action

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

// Add an action to log events
$hooks->add(
    new StringHookName('user_login'),
    new ObjectHookInvoke(
        fn(int $userId, string $username) => error_log("User $username ($userId) logged in"),
        priority: 10
    )
);

// Dispatch the action
$hooks->dispatch(new StringHookName('user_login'), 42, 'john_doe');
```

---

## Callback Types

### Using Named Functions

```php
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

// Define a named function
function my_custom_filter(string $value): string
{
    return $value . ' - modified by my_custom_filter';
}

// Register the function as a callback
$hooks->add(
    new StringHookName('my_filter'),
    new StringHookInvoke('my_custom_filter', priority: 10)
);
```

### Using Object Methods

```php
use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class ContentModifier
{
    public function addDisclaimer(string $content): string
    {
        return $content . "\n\n*Disclaimer: This is sample content.*";
    }
}

$modifier = new ContentModifier();

$hooks->add(
    new StringHookName('the_content'),
    new ArrayHookInvoke(
        [$modifier, 'addDisclaimer'],
        priority: 100
    )
);
```

### Using Static Methods

```php
use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class Sanitizer
{
    public static function sanitizeTitle(string $title): string
    {
        return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    }
}

$hooks->add(
    new StringHookName('the_title'),
    new ArrayHookInvoke(
        [Sanitizer::class, 'sanitizeTitle'],
        priority: 5
    )
);
```

### Using Closures (with stored reference)

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

// Store the callback to enable removal later
$myCallback = new ObjectHookInvoke(
    fn(string $value) => trim($value),
    priority: 10
);

$hooks->add(new StringHookName('sanitize_input'), $myCallback);

// Later, you can remove it
$hooks->remove(new StringHookName('sanitize_input'), $myCallback);
```

---

## Priority Examples

### Early, Default, and Late Execution

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

$hookName = new StringHookName('process_data');

// Early execution (runs first)
$hooks->add($hookName, new ObjectHookInvoke(
    fn($data) => ['stage' => 'early', ...$data],
    priority: 1
));

// Default execution (runs second)
$hooks->add($hookName, new ObjectHookInvoke(
    fn($data) => ['stage' => 'default', ...$data],
    priority: 10
));

// Late execution (runs last)
$hooks->add($hookName, new ObjectHookInvoke(
    fn($data) => ['stage' => 'late', ...$data],
    priority: 100
));

$result = $hooks->filter($hookName, ['initial' => true]);
// Each filter transforms the data in order: early -> default -> late
```

### Guaranteed First/Last Execution

```php
// Always run first (use negative or very low priority)
$hooks->add($hookName, new ObjectHookInvoke(
    fn($value) => "START: " . $value,
    priority: PHP_INT_MIN
));

// Always run last
$hooks->add($hookName, new ObjectHookInvoke(
    fn($value) => $value . " :END",
    priority: PHP_INT_MAX
));
```

---

## Real-World Patterns

### Plugin-Style Architecture

```php
use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

interface PluginInterface
{
    public function register(HookContainerInterface $hooks): void;
}

class SEOPlugin implements PluginInterface
{
    public function register(HookContainerInterface $hooks): void
    {
        $hooks->add(
            new StringHookName('the_title'),
            new ArrayHookInvoke([$this, 'optimizeTitle'], priority: 10)
        );

        $hooks->add(
            new StringHookName('the_content'),
            new ArrayHookInvoke([$this, 'addMetaTags'], priority: 5)
        );
    }

    public function optimizeTitle(string $title): string
    {
        return $title . ' | My Site';
    }

    public function addMetaTags(string $content): string
    {
        return $content . '<!-- SEO meta tags -->';
    }
}

// Register the plugin
$seoPlugin = new SEOPlugin();
$seoPlugin->register($hooks);
```

### Event System

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class EventDispatcher
{
    public function __construct(
        private HookContainerInterface $hooks
    ) {}

    public function dispatch(string $eventName, mixed ...$args): void
    {
        $this->hooks->dispatch(new StringHookName($eventName), ...$args);
    }

    public function listen(string $eventName, callable $listener, int $priority = 10): ObjectHookInvoke
    {
        $callback = new ObjectHookInvoke($listener, $priority);
        $this->hooks->add(new StringHookName($eventName), $callback);
        return $callback;
    }

    public function unlisten(string $eventName, ObjectHookInvoke $callback): void
    {
        $this->hooks->remove(new StringHookName($eventName), $callback);
    }
}

// Usage
$events = new EventDispatcher($hooks);

$callback = $events->listen('user.created', function ($user) {
    echo "Welcome, {$user['name']}!";
});

$events->dispatch('user.created', ['id' => 1, 'name' => 'Alice']);

// Later, remove the listener
$events->unlisten('user.created', $callback);
```

### Middleware Pipeline

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class MiddlewarePipeline
{
    private StringHookName $hookName;

    public function __construct(
        private HookContainerInterface $hooks,
        string $pipelineName
    ) {
        $this->hookName = new StringHookName("middleware.$pipelineName");
    }

    public function pipe(callable $middleware, int $priority = 10): self
    {
        $this->hooks->add(
            $this->hookName,
            new ObjectHookInvoke($middleware, $priority)
        );
        return $this;
    }

    public function process(mixed $payload): mixed
    {
        return $this->hooks->filter($this->hookName, $payload);
    }
}

// Usage
$pipeline = new MiddlewarePipeline($hooks, 'request');

$pipeline
    ->pipe(fn($req) => [...$req, 'authenticated' => true], priority: 10)
    ->pipe(fn($req) => [...$req, 'validated' => true], priority: 20)
    ->pipe(fn($req) => [...$req, 'processed' => true], priority: 30);

$result = $pipeline->process(['path' => '/api/users']);
// Result: ['path' => '/api/users', 'authenticated' => true, 'validated' => true, 'processed' => true]
```

### Caching with Hooks

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class CachedDataLoader
{
    private array $cache = [];

    public function __construct(
        private HookContainerInterface $hooks
    ) {}

    public function load(string $key): mixed
    {
        // Check cache first
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // Load data (allow hooks to provide data)
        $data = $this->hooks->filter(
            new StringHookName("load_data.$key"),
            null
        );

        // Allow post-processing
        $data = $this->hooks->filter(
            new StringHookName("loaded_data.$key"),
            $data
        );

        // Cache and return
        $this->cache[$key] = $data;
        return $data;
    }
}

// Register data providers
$hooks->add(
    new StringHookName('load_data.users'),
    new ObjectHookInvoke(fn() => ['alice', 'bob', 'charlie'], priority: 10)
);

// Register post-processors
$hooks->add(
    new StringHookName('loaded_data.users'),
    new ObjectHookInvoke(fn($users) => array_map('strtoupper', $users), priority: 10)
);

$loader = new CachedDataLoader($hooks);
$users = $loader->load('users'); // ['ALICE', 'BOB', 'CHARLIE']
```

---

## Debugging Hooks

### Logging All Hook Executions

```php
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class HookLogger
{
    private array $log = [];

    public function wrapHook(
        HookContainerInterface $hooks,
        string $hookName,
        ObjectHookInvoke $callback
    ): void {
        $wrappedCallback = new ObjectHookInvoke(
            function (...$args) use ($hookName, $callback) {
                $start = microtime(true);
                $result = $callback(...$args);
                $duration = microtime(true) - $start;

                $this->log[] = [
                    'hook' => $hookName,
                    'callback' => $callback->getName(),
                    'duration' => $duration,
                    'args' => $args,
                ];

                return $result;
            },
            $callback->getPriority()
        );

        $hooks->add(new StringHookName($hookName), $wrappedCallback);
    }

    public function getLog(): array
    {
        return $this->log;
    }
}
```

### Inspecting Hook State

```php
use SpeedySpec\WP\Hook\Domain\HookServiceContainer;
use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;

// Get services from container
$container = HookServiceContainer::getInstance();
$hookRunAmountService = $container->get(HookRunAmountInterface::class);
$currentHookService = $container->get(CurrentHookInterface::class);

// Check if a hook has been run
$count = $hookRunAmountService->getRunAmount(new StringHookName('init'));
echo "init hook has run $count times";

// Check current hook during execution
$hooks->add(
    new StringHookName('my_hook'),
    new ObjectHookInvoke(function () use ($currentHookService) {
        $current = $currentHookService->getCurrentHook();
        echo "Currently running: " . $current?->getName();

        $traceback = $currentHookService->hookTraceback();
        echo "Hook stack: " . implode(' -> ', $traceback);
    })
);
```
