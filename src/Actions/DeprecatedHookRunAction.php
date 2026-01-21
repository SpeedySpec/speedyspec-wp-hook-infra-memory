<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\Actions;

use SpeedySpec\WP\Hook\Domain\Contracts\HookActionInterface;

/**
 * @since 0.1.0 speedyspec-wp-hook-infra-memory
 */
class DeprecatedHookRunAction implements HookActionInterface
{
    public function __construct(
        private HookContainerInterface $hookContainer,
    ) {
    }

    /**
     * Fires when a deprecated hook is called.
     *
     * @param string $hook
     *   The hook that was called.
     * @param string $replacement
     *   The hook that should be used as a replacement.
     * @param string $version
     *   The version of WordPress that deprecated the argument used.
     * @param string $message
     *   A message regarding the change.
     *
     * @since 4.6.0 WordPress
     * @since 0.1.0 speedyspec-wp-hook-infra-memory
     */
    public function dispatch( ...$args ): void
    {
        $this->hookContainer->dispatch(...$args);
    }
}
