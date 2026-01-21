<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyAddFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyAddFilterUseCase implements LegacyAddFilterUseCaseInterface
{
    public function __construct(private HookContainerInterface $hookContainer)
    {
    }

    public function add(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): true
    {
        $hook = match (true) {
            is_string($callback) => new StringHookInvoke($callback, $priority),
            is_array($callback) => new ArrayHookInvoke($callback, $priority),
            default => new ObjectHookInvoke($callback, $priority),
        };

        $this->hookContainer->add( new StringHookName($hook_name), $hook );

        return true;
    }
}
