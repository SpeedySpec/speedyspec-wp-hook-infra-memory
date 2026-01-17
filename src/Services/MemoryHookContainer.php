<?php

declare(strict_types=1);

namespace SpeedySpec\WP\Hook\Infra\Memory\Services;

use SpeedySpec\WP\Hook\Domain\Contracts\HookActionInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookFilterInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookInvokableInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookNameInterface;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\ValueObject\HookInvokableOption;

class MemoryHookContainer implements HookContainerInterface
{
    private array $hooks = [];

    public function __construct(
        private HookRunAmountService $hookRunAmountService,
        private CurrentHookService $currentHookService,
    ) {
    }

    public function add(
        HookNameInterface $name,
        HookInvokableInterface|HookActionInterface|HookFilterInterface $callback
    ): void {
        $this->hooks[$name->getName()] ??= new HookSubject($this->currentHookService);
        $this->hooks[$name->getName()]->add($callback);
    }

    public function remove(
        HookNameInterface $name,
        HookInvokableInterface|HookActionInterface|HookFilterInterface $callback
    ): void {
        $this->hooks[$hook->getName()] ??= new HookSubject($this->currentHookService);
        $this->hooks[$hook->getName()]->remove($callback);
    }

    public function dispatch(HookNameInterface $hook, ...$args): void
    {
        $this->currentHookService->addHook($hook->getName());
        $this->hooks[$hook->getName()] ??= new HookSubject($this->currentHookService);
        $this->hooks[$hook->getName()]->dispatch(...$args);
        $this->hookRunAmountService->incrementRunAmount($hook);
        $this->currentHookService->removeHook($hook->getName());
    }

    public function filter(HookNameInterface $hook, mixed $value, ...$args): mixed
    {
        $this->currentHookService->addHook($hook->getName());
        $this->hooks[$hook->getName()] ??= new HookSubject($this->currentHookService);
        $this->hooks[$hook->getName()]->filter($value, ...$args);
        $this->hookRunAmountService->incrementRunAmount($hook);
        $this->currentHookService->removeHook($hook->getName());
    }
}
