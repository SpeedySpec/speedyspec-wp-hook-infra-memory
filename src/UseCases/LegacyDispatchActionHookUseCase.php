<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDispatchActionHookUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyDispatchActionHookUseCase implements LegacyDispatchActionHookUseCaseInterface
{
    public function __construct(private HookContainerInterface $hookContainer)
    {
    }

    public function dispatch(string $hook_name, ...$args): void
    {
        $this->hookContainer->dispatch( new StringHookName( $hook_name ), ...$args );
    }
}
