<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyHasActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

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
            is_string($callback) => new StringHookInvoke($callback, is_int($priority) ? $priority : 10),
            is_array($callback) => new ArrayHookInvoke($callback, is_int($priority) ? $priority : 10),
            is_object($callback) => new ObjectHookInvoke($callback, is_int($priority) ? $priority : 10),
            default => null,
        };
        return $this->hookContainer->hasCallbacks(new StringHookName($hook_name), $hook, is_int($priority) ? $priority : null);
    }
}
