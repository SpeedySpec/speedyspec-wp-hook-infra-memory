<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDispatchFilterHookUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyDispatchFilterHookUseCase implements LegacyDispatchFilterHookUseCaseInterface
{
    public function __construct(private HookContainerInterface $hookContainer)
    {
    }

    public function filter(string $hook_name, mixed $value, ...$args): mixed
    {
        return $this->hookContainer->filter( new StringHookName( $hook_name ), $value, ...$args );
    }
}
