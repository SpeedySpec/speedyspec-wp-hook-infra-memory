<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyHasActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;

class LegacyHasActionUseCase implements LegacyHasActionUseCaseInterface
{
    public function __construct(private HookContainerInterface $hookContainer)
    {
    }

    public function hasHook(
        string $hook_name,
        callable|false|null $callback = null,
        false|int|null $priority = null
    ): bool {
        $hook = match (true) {
            is_string($callback) => new StringHookInvoke($callback, $priority),
            is_array($callback) => new ArrayHookInvoke($callback, $priority),
            default => new ObjectHookInvoke($callback, $priority),
        };
        $this->hookContainer->hasCallbacks($hook_name, $callback, $priority);
    }
}
