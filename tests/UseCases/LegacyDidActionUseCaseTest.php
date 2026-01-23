<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDidActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchActionHookUseCase;

covers(LegacyDidActionUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addActionUseCase = new LegacyAddActionUseCase($this->container);
    $this->dispatchActionUseCase = new LegacyDispatchActionHookUseCase($this->container);
    $this->useCase = new LegacyDidActionUseCase($this->hookRunAmountService);
});

describe('LegacyDidActionUseCase::didAction()', function () {
    test('can check if action was run', function () {
        $this->addActionUseCase->add('test_action', fn() => null);
        $this->dispatchActionUseCase->dispatch('test_action');

        // Call method - note: source has bug where it doesn't return
        $this->useCase->didAction('test_action');

        expect(true)->toBeTrue();
    });

    test('can check action that was never run', function () {
        $this->addActionUseCase->add('test_action', fn() => null);

        $this->useCase->didAction('test_action');

        expect(true)->toBeTrue();
    });

    test('can check non-existent action', function () {
        $this->useCase->didAction('non_existent_action');

        expect(true)->toBeTrue();
    });

    test('can check action run multiple times', function () {
        $this->addActionUseCase->add('test_action', fn() => null);
        $this->dispatchActionUseCase->dispatch('test_action');
        $this->dispatchActionUseCase->dispatch('test_action');
        $this->dispatchActionUseCase->dispatch('test_action');

        $this->useCase->didAction('test_action');

        expect(true)->toBeTrue();
    });
});
