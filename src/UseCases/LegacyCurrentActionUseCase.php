<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyCurrentActionUseCaseInterface;

class LegacyCurrentActionUseCase implements LegacyCurrentActionUseCaseInterface
{
    public function __construct(private CurrentHookInterface $currentHook)
    {
    }

    public function currentAction(): string|false
    {
        $current = $this->currentHook->getCurrentHook();

        return $current ? $current->getName() : false;
    }
}
