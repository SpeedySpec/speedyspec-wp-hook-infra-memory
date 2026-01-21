<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\CalledDeprecatedHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDispatchDeprecatedActionHookUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyDispatchDeprecatedActionHookUseCase implements LegacyDispatchDeprecatedActionHookUseCaseInterface
{
    public function __construct(
        private HookContainerInterface $hookContainer,
        private CalledDeprecatedHookInterface $calledDeprecated,
    ) {
    }

    public function dispatch(
        string $hookName,
        array $args,
        string $version,
        string $replacement = '',
        string $message = ''
    ): void {
        $hook = new StringHookName( $hookName );
        $this->calledDeprecated->calledDeprecatedHook( $hook, $version, $replacement, $message, ...$args );
        $this->hookContainer->dispatch( $hook, ...$args );
    }
}
