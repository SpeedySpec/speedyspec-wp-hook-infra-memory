<?php

declare(strict_types=1);

namespace SpeedySpec\WP\Hook\Infra\Memory\Services;

use ReturnTypeWillChange;
use SpeedySpec\WP\Hook\Domain\Contracts\HookActionInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookFilterInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookInvokableInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookPriorityInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookSubjectInterface;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;

/**
 * @since 0.0.0 speedyspec-wp-hook-infra-memory
 */
class MemoryHookSubject implements HookSubjectInterface
{
    /**
     * @since 0.0.0 speedyspec-wp-hook-infra-memory
     */
    private array $hooks = [];

    /**
     * @since 0.0.0 speedyspec-wp-hook-infra-memory
     */
    private array $callbackToPriorities = [];

    private array $prioritiesToCallback = [];

    private array $sorted = [];

    private bool $needsSorting = false;

    public function __construct(
        private CurrentHookService $currentHookService,
    ) {
    }

    public function add( HookInvokableInterface|HookActionInterface|HookFilterInterface $callback ): void
    {
        $name = $callback->getName();
        $priority = $callback instanceof HookPriorityInterface ? $callback->getPriority() : 10;
        $this->hooks[$name] = $callback;
        $this->callbackToPriorities[$name] = $priority;
        $this->prioritiesToCallback[$priority][$name] = true;
        $this->needsSorting = true;
    }

    public function remove( HookInvokableInterface|HookActionInterface|HookFilterInterface $callback ): void
    {
        $name = $callback->getName();
        $priority = $callback instanceof HookPriorityInterface ? $callback->getPriority() : 10;
        unset($this->hooks[$name]);
        unset($this->prioritiesToCallback[$priority][$name]);
        unset($this->callbackToPriorities[$name]);
        $this->needsSorting = true;
    }

    public function removeAll(?int $priority = null): void
    {
        if (null === $priority) {
            $this->hooks = [];
            $this->sorted = [];
            $this->callbackToPriorities = [];
            $this->prioritiesToCallback = [];
            $this->needsSorting = false;
        } else {
            foreach (($this->prioritiesToCallback[$priority] ?? []) as $name => $_) {
                unset($this->hooks[ $name ]);
                unset($this->callbackToPriorities[ $name ]);
            }
            $this->prioritiesToCallback[ $priority ] = [];
            $this->needsSorting = true;
        }
    }

    public function dispatch(...$args,): void
    {
        if ($this->needsSorting) {
            $this->sort();
        }

        foreach ($this->sorted as $hook) {
            $this->currentHookService->addCallback($hook->getName());
            $hook(...$args);
            $this->currentHookService->removeCallback();
        }
    }

    #[ReturnTypeWillChange]
    public function filter(mixed $value, ...$args): mixed
    {
        if ($this->needsSorting) {
            $this->sort();
        }

        foreach ($this->sorted as $name => $hook) {
            $this->currentHookService->addCallback($name);
            $value = $hook($value, ...$args);
            $this->currentHookService->removeCallback();
        }

        return $value;
    }

    public function hasCallbacks(
        HookInvokableInterface|HookActionInterface|HookFilterInterface|null $callback = null,
        ?int $priority = null
    ): bool {
        return match (true) {
            $callback === null && $priority === null => ! empty($this->hooks),
            $callback !== null && $priority === null => isset($this->hooks[$callback->getName()]),
            $callback === null && $priority !== null => ! empty($this->prioritiesToCallback[$priority]),
            default => isset($this->callbackToPriorities[$callback->getName()]) && $this->callbackToPriorities[$callback->getName()] === $priority,
        };
    }

    public function sort(): void
    {
        $sorting = $this->hooks;

        uksort($sorting, function ($a, $b) {
            return ($this->callbackToPriorities[$a] ?? 10) <=> ($this->callbackToPriorities[$b] ?? 10);
        });

        $this->sorted = $sorting;
        $this->needsSorting = false;
    }
}
