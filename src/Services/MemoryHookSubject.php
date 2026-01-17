<?php

declare(strict_types=1);

namespace SpeedySpec\WP\Hook\Infra\Memory\Services;

use ReturnTypeWillChange;
use SpeedySpec\WP\Hook\Domain\Contracts\HookActionInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookFilterInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookInvokableInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookSubjectInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\HookPriorityInterface;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;

class MemoryHookSubject implements HookSubjectInterface
{
    private array $hooks = [];

    private array $priorities = [];

    private array $sorted = [];

    private bool $needsSorting = false;

    public function __construct(
        private CurrentHookService $currentHookService,
    ) {
    }

    public function add( HookInvokableInterface|HookActionInterface|HookFilterInterface $callback ): void
    {
        $this->hooks[$callback->getName()] = $callback;
        $this->priorities[$callback->getName()] = match (true) {
            $callback instanceof HookPriorityInterface => $callback->getPriority(),
            default => 10,
        };
        $this->needsSorting = true;
    }

    public function remove( HookInvokableInterface|HookActionInterface|HookFilterInterface $callback ): void
    {
        unset($this->hooks[$callback->getName()]);
        unset($this->priorities[$callback->getName()]);
        $this->needsSorting = true;
    }

    public function dispatch(...$args,): void
    {
        if ($this->needsSorting) {
            $this->sort();
        }

        foreach ($this->sorted as $hook) {
            $this->currentHookService->addCallback($hook->getName());
            $hook->invoke(...$args);
            $this->currentHookService->removeCallback();
        }
    }

    #[ReturnTypeWillChange]
    public function filter(mixed $value, ...$args): mixed
    {
        if (empty($this->sorted)) {
            $this->sort();
        }

        foreach ($this->sorted as $name => $hook) {
            $this->currentHookService->addCallback($name);
            $value = $hook->invoke($value, ...$args);
            $this->currentHookService->removeCallback();
        }

        return $value;
    }

    public function sort(): void
    {
        $sorting = $this->hooks;

        uksort($sorting, function ($a, $b) {
            return ($this->priorities[$a] ?? 10) <=> ($this->priorities[$b] ?? 10);
        });

        $this->sorted = $sorting;
        $this->needsSorting = false;
    }
}
