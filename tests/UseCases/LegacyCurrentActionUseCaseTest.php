<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyCurrentActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchActionHookUseCase;

covers(LegacyCurrentActionUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addActionUseCase = new LegacyAddActionUseCase($this->container);
    $this->dispatchActionUseCase = new LegacyDispatchActionHookUseCase($this->container);
    $this->useCase = new LegacyCurrentActionUseCase($this->currentHookService);
});

describe('LegacyCurrentActionUseCase::currentAction()', function () {
    test('returns false when not executing any action', function () {
        $result = $this->useCase->currentAction();

        expect($result)->toBeFalse();
    });

    test('returns hook name during action execution', function () {
        $currentActionResult = null;
        $this->addActionUseCase->add('test_action', function () use (&$currentActionResult) {
            $currentActionResult = $this->useCase->currentAction();
        });

        $this->dispatchActionUseCase->dispatch('test_action');

        expect($currentActionResult)->toBe('test_action');
    });

    test('returns correct hook name for nested actions', function () {
        $outerActionName = null;
        $innerActionName = null;

        $this->addActionUseCase->add('outer_action', function () use (&$outerActionName, &$innerActionName) {
            $outerActionName = $this->useCase->currentAction();
            $this->dispatchActionUseCase->dispatch('inner_action');
        });

        $this->addActionUseCase->add('inner_action', function () use (&$innerActionName) {
            $innerActionName = $this->useCase->currentAction();
        });

        $this->dispatchActionUseCase->dispatch('outer_action');

        expect($outerActionName)->toBe('outer_action');
        expect($innerActionName)->toBe('inner_action');
    });

    test('returns false after action execution completes', function () {
        $this->addActionUseCase->add('test_action', fn() => null);
        $this->dispatchActionUseCase->dispatch('test_action');

        $result = $this->useCase->currentAction();

        expect($result)->toBeFalse();
    });

    test('returns hook name for different action hooks', function () {
        $actionName1 = null;
        $actionName2 = null;

        $this->addActionUseCase->add('action_one', function () use (&$actionName1) {
            $actionName1 = $this->useCase->currentAction();
        });

        $this->addActionUseCase->add('action_two', function () use (&$actionName2) {
            $actionName2 = $this->useCase->currentAction();
        });

        $this->dispatchActionUseCase->dispatch('action_one');
        $this->dispatchActionUseCase->dispatch('action_two');

        expect($actionName1)->toBe('action_one');
        expect($actionName2)->toBe('action_two');
    });
});
