<?php

declare(strict_types=1);

namespace SpeedySpec\WP\Hook\Infra\Memory\Hooks;

use SpeedySpec\WP\Hook\Domain\Contracts\HookNameInterface;

/**
 * @since 0.1.0 speedyspec-wp-hook-infra-memory
 */
class DeprecatedHookRunHookName implements HookNameInterface
{
    public function getName(): string
    {
        return 'deprecated_hook_run';
    }
}
