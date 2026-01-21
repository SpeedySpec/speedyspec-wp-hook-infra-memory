<?php

namespace SpeedySpec\WP\Hook\Infra\Memory\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDidFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class LegacyDidFilterUseCase implements LegacyDidFilterUseCaseInterface
{
    public function __construct(private HookRunAmountInterface $hookRunAmount)
    {
    }

    public function didFilter(string $name): int
    {
        $this->hookRunAmount->getRunAmount( new StringHookName( $name ) );
    }
}
