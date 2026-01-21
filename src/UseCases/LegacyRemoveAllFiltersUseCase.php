<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyRemoveAllActionsUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyRemoveAllFiltersUseCase implements LegacyRemoveAllActionsUseCaseInterface
{
    public function __construct(private HookContainerInterface $hookContainer)
    {
    }

    public function removeHook(string $hook_name, int $priority = 10): true
    {
        $this->hookContainer->removeAll( new StringHookName($hook_name), $priority );

        return true;
    }
}
