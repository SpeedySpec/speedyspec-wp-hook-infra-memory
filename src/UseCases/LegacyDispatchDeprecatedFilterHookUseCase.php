<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\CalledDeprecatedHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDispatchDeprecatedFilterHookUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyDispatchDeprecatedFilterHookUseCase implements LegacyDispatchDeprecatedFilterHookUseCaseInterface
{
    public function __construct(
        private HookContainerInterface $hookContainer,
        private CalledDeprecatedHookInterface $calledDeprecatedFilter,
    ) {
    }

    public function dispatch(
        string $hook_name,
        array $args,
        string $version,
        string $replacement = '',
        string $message = ''
    ): mixed {
        $hook = new StringHookName( $hookName );
        $value = array_shift( $args );
        $this->calledDeprecated->calledDeprecatedHook(
            $hook,
            $version,
            $replacement,
            $message,
            ...array_merge([$value], $args )
        );
        return $this->hookContainer->filter( new StringHookName( $hook_name ), $value, ...$args );
    }
}
