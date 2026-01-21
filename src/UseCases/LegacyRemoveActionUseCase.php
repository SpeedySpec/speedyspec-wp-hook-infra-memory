<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyRemoveActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyRemoveActionUseCase implements LegacyRemoveActionUseCaseInterface
{
    public function __construct(private HookContainerInterface $hookContainer)
    {
    }

    public function removeHook(string $hook_name, callable $callback, int $priority = 10): bool
    {
        $hook = match (true) {
            is_string($callback) => new StringHookInvoke($callback, $priority),
            is_array($callback) => new ArrayHookInvoke($callback, $priority),
            default => new ObjectHookInvoke($callback, $priority),
        };
        $this->hookContainer->remove( new StringHookName($hook_name), $hook );

        return true;
    }
}
