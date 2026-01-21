<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDoingFilterUseCaseInterface;

class LegacyDoingFilterUseCase implements LegacyDoingFilterUseCaseInterface
{
    public function __construct(private CurrentHookInterface $currentHook)
    {
    }

    public function isDoingFilter(?string $name = null): bool
    {
        $hook = $this->currentHook->getCurrentHook();
        if ( $name !== null ) {
            return $hook?->getName() === $name;
        }

        return $hook !== null;
    }
}
