<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyCurrentFilterUseCaseInterface;

class LegacyCurrentFilterUseCase implements LegacyCurrentFilterUseCaseInterface
{
    public function __construct(private CurrentHookInterface $currentHook)
    {
    }

    public function currentFilter(): string|false
    {
        $current = $this->currentHook->getCurrentHook();

        return $current ? $current->getName() : false;
    }
}
