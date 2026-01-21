<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyHasFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyHasFilterUseCase implements LegacyHasFilterUseCaseInterface
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
            is_object($callback) => new ObjectHookInvoke($callback, $priority),
            default => null,
        };
        $this->hookContainer->hasCallbacks(new StringHookName($hook_name), $hook, is_int($priority) ? $priority : null);
    }
}
