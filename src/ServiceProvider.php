<?php

namespace src;

use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookSubjectInterface;
use SpeedySpec\WP\Hook\Domain\HookServiceContainer;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

class ServiceProvider
{
    public function boot(): void
    {
    }

    public function register(): void
    {
        $container = HookServiceContainer::getInstance();
        $container->add(HookRunAmountService::class, fn() => new HookRunAmountService());
        $container->add(CurrentHookService::class, fn() => new CurrentHookService());
        $container->add(
            HookContainerInterface::class,
            fn($c) => new MemoryHookContainer($c->get(HookRunAmountService::class), $c->get(CurrentHookService::class))
        );
        $container->add(
            HookSubjectInterface::class,
            fn ($c) => new MemoryHookSubject($c->get(CurrentHookService::class))
        );
    }

    public function __invoke(): void
    {
        $this->boot();
        $this->register();
    }
}
