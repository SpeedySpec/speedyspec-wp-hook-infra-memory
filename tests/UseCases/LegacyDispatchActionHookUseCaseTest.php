<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchActionHookUseCase;

covers(LegacyDispatchActionHookUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addActionUseCase = new LegacyAddActionUseCase($this->container);
    $this->useCase = new LegacyDispatchActionHookUseCase($this->container);
});

describe('LegacyDispatchActionHookUseCase::dispatch()', function () {
    test('dispatches action with no arguments', function () {
        $called = false;
        $this->addActionUseCase->add('test_action', function () use (&$called) {
            $called = true;
        });

        $this->useCase->dispatch('test_action');

        expect($called)->toBeTrue();
    });

    test('dispatches action with single argument', function () {
        $receivedArg = null;
        $this->addActionUseCase->add('test_action', function ($arg) use (&$receivedArg) {
            $receivedArg = $arg;
        }, 10, 1);

        $this->useCase->dispatch('test_action', 'test_value');

        expect($receivedArg)->toBe('test_value');
    });

    test('dispatches action with multiple arguments', function () {
        $receivedArgs = [];
        $this->addActionUseCase->add('test_action', function ($arg1, $arg2, $arg3) use (&$receivedArgs) {
            $receivedArgs = [$arg1, $arg2, $arg3];
        }, 10, 3);

        $this->useCase->dispatch('test_action', 'one', 'two', 'three');

        expect($receivedArgs)->toBe(['one', 'two', 'three']);
    });

    test('dispatches to multiple callbacks', function () {
        $callCount = 0;
        $this->addActionUseCase->add('test_action', function () use (&$callCount) {
            $callCount++;
        });
        $this->addActionUseCase->add('test_action', function () use (&$callCount) {
            $callCount++;
        });

        $this->useCase->dispatch('test_action');

        expect($callCount)->toBe(2);
    });

    test('dispatches callbacks in priority order', function () {
        $callOrder = [];
        $this->addActionUseCase->add('test_action', function () use (&$callOrder) {
            $callOrder[] = 'priority_20';
        }, 20);
        $this->addActionUseCase->add('test_action', function () use (&$callOrder) {
            $callOrder[] = 'priority_5';
        }, 5);
        $this->addActionUseCase->add('test_action', function () use (&$callOrder) {
            $callOrder[] = 'priority_10';
        }, 10);

        $this->useCase->dispatch('test_action');

        expect($callOrder)->toBe(['priority_5', 'priority_10', 'priority_20']);
    });

    test('does not throw when dispatching to unregistered hook', function () {
        $this->useCase->dispatch('unregistered_action');

        expect(true)->toBeTrue();
    });

    test('increments hook run amount after dispatch', function () {
        $this->addActionUseCase->add('test_action', fn() => null);

        $this->useCase->dispatch('test_action');
        $this->useCase->dispatch('test_action');

        $runAmount = $this->hookRunAmountService->getRunAmount(new StringHookName('test_action'));
        expect($runAmount)->toBe(2);
    });
});
