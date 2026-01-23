<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDidActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyDidActionUseCase implements LegacyDidActionUseCaseInterface
{
    public function __construct(private HookRunAmountInterface $hookRunAmount)
    {
    }

    public function didAction(string $name): int
    {
        return $this->hookRunAmount->getRunAmount( new StringHookName( $name ) );
    }
}
