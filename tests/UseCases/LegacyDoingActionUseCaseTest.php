<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchActionHookUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDoingActionUseCase;

covers(LegacyDoingActionUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addActionUseCase = new LegacyAddActionUseCase($this->container);
    $this->dispatchActionUseCase = new LegacyDispatchActionHookUseCase($this->container);
    $this->useCase = new LegacyDoingActionUseCase($this->currentHookService);
});

describe('LegacyDoingActionUseCase::isDoingAction()', function () {
    test('returns false when not executing any action', function () {
        $result = $this->useCase->isDoingAction();

        expect($result)->toBeFalse();
    });

    test('returns false for specific hook when not executing', function () {
        $result = $this->useCase->isDoingAction('test_action');

        expect($result)->toBeFalse();
    });

    test('returns true during action execution', function () {
        $doingActionResult = null;
        $this->addActionUseCase->add('test_action', function () use (&$doingActionResult) {
            $doingActionResult = $this->useCase->isDoingAction();
        });

        $this->dispatchActionUseCase->dispatch('test_action');

        expect($doingActionResult)->toBeTrue();
    });

    test('returns true for specific hook during its execution', function () {
        $doingSpecificAction = null;
        $this->addActionUseCase->add('test_action', function () use (&$doingSpecificAction) {
            $doingSpecificAction = $this->useCase->isDoingAction('test_action');
        });

        $this->dispatchActionUseCase->dispatch('test_action');

        expect($doingSpecificAction)->toBeTrue();
    });

    test('returns false for different hook during execution', function () {
        $doingOtherAction = null;
        $this->addActionUseCase->add('test_action', function () use (&$doingOtherAction) {
            $doingOtherAction = $this->useCase->isDoingAction('other_action');
        });

        $this->dispatchActionUseCase->dispatch('test_action');

        expect($doingOtherAction)->toBeFalse();
    });

    test('returns false after action execution completes', function () {
        $this->addActionUseCase->add('test_action', fn() => null);
        $this->dispatchActionUseCase->dispatch('test_action');

        $result = $this->useCase->isDoingAction('test_action');

        expect($result)->toBeFalse();
    });
});
