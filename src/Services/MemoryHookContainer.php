<?php

declare(strict_types=1);

namespace SpeedySpec\WP\Hook\Infra\Memory\Services;

use SpeedySpec\WP\Hook\Domain\Contracts\HookActionInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookFilterInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookInvokableInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookNameInterface;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;

/**
 * @since 0.0.0 speedyspec-wp-hook-infra-memory
 */
class MemoryHookContainer implements HookContainerInterface
{
    private array $hooks = [];

    public function __construct(
        private HookRunAmountService $hookRunAmountService,
        private CurrentHookService $currentHookService,
    ) {
    }

    public function add(
        HookNameInterface $hook,
        HookInvokableInterface|HookActionInterface|HookFilterInterface $callback
    ): void {
        $name = $hook->getName();
        $this->hooks[$name] ??= new MemoryHookSubject($this->currentHookService);
        $this->hooks[$name]->add($callback);
    }

    public function remove(
        HookNameInterface $hook,
        HookInvokableInterface|HookActionInterface|HookFilterInterface $callback
    ): void {
        $name = $hook->getName();
        $this->hooks[$name] ??= new MemoryHookSubject($this->currentHookService);
        $this->hooks[$name]->remove($callback);
    }

    public function removeAll( HookNameInterface $hook, ?int $priority = null,): void
    {
        $name = $hook->getName();
        $this->hooks[$name] ??= new MemoryHookSubject($this->currentHookService);
        $this->hooks[$name]->removeAll($priority);
    }

    public function dispatch( HookNameInterface $hook, ...$args ): void
    {
        $name = $hook->getName();
        $this->currentHookService->addHook($name);
        $this->hooks[$name] ??= new MemoryHookSubject($this->currentHookService);
        $this->hooks[$name]->dispatch(...$args);
        $this->hookRunAmountService->incrementRunAmount($hook);
        $this->currentHookService->removeHook();
    }

    public function filter( HookNameInterface $hook, mixed $value, ...$args ): mixed
    {
        $name = $hook->getName();
        $this->currentHookService->addHook($name);
        $this->hooks[$name] ??= new MemoryHookSubject($this->currentHookService);
        $value = $this->hooks[$name]->filter($value, ...$args);
        $this->hookRunAmountService->incrementRunAmount($hook);
        $this->currentHookService->removeHook();
        return $value;
    }

    public function hasCallbacks(
        HookNameInterface $hook,
        HookInvokableInterface|HookActionInterface|HookFilterInterface|null $callback = null,
        ?int $priority = null,
    ): bool {
        $name = $hook->getName();
        $this->hooks[$name] ??= new MemoryHookSubject($this->currentHookService);
        return $this->hooks[$name]->hasCallbacks($callback, $priority);
    }
}
