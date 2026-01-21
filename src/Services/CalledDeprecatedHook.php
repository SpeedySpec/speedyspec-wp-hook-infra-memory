<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\Services;

use SpeedySpec\WP\Hook\Domain\Contracts\CalledDeprecatedHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookNameInterface;

/**
 * @since 0.1.0 speedyspec-wp-hook-infra-memory
 */
class CalledDeprecatedHook implements CalledDeprecatedHookInterface
{
    public function __construct(
    ) {
    }

    public function calledDeprecatedHook(
        HookNameInterface $hook,
        string $version,
        string $replacement = '',
        string $message = '',
        ...$args,
    ): bool {
        $this->hookContainer->dispatch( new DeprecatedHookRunHook, $hook->getName(), $version, $replacement, $message, ...$args );
    }
}
