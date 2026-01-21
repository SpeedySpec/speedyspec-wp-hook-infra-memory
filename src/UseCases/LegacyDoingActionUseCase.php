<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDoingActionUseCaseInterface;

class LegacyDoingActionUseCase implements LegacyDoingActionUseCaseInterface
{
    public function __construct(private CurrentHookInterface $currentHook)
    {
    }

    public function isDoingAction(?string $name = null): bool
    {
        $hook = $this->currentHook->getCurrentHook();
        if ( $name !== null ) {
            return $hook?->getName() === $name;
        }

        return $hook !== null;
    }
}
