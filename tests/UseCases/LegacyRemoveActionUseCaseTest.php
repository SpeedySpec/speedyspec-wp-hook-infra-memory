<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchActionHookUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyRemoveActionUseCase;

covers(LegacyRemoveActionUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addActionUseCase = new LegacyAddActionUseCase($this->container);
    $this->dispatchActionUseCase = new LegacyDispatchActionHookUseCase($this->container);
    $this->useCase = new LegacyRemoveActionUseCase($this->container);
});

describe('LegacyRemoveActionUseCase::removeHook()', function () {
    test('removes action with closure callback', function () {
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
        };

        $this->addActionUseCase->add('test_action', $callback);
        $result = $this->useCase->removeHook('test_action', $callback);
        $this->dispatchActionUseCase->dispatch('test_action');

        expect($result)->toBeTrue();
        expect($callCount)->toBe(0);
    });

    test('removes action with string callback', function () {
        $this->addActionUseCase->add('test_action', 'var_dump');
        $result = $this->useCase->removeHook('test_action', 'var_dump');

        expect($result)->toBeTrue();
    });

    test('removes action with array callback', function () {
        $mock = createMockAction();
        $this->addActionUseCase->add('test_action', [$mock, 'action']);
        $result = $this->useCase->removeHook('test_action', [$mock, 'action']);

        expect($result)->toBeTrue();
    });

    test('removes action at specific priority', function () {
        $callCount10 = 0;
        $callCount20 = 0;

        $callback10 = function () use (&$callCount10) {
            $callCount10++;
        };
        $callback20 = function () use (&$callCount20) {
            $callCount20++;
        };

        $this->addActionUseCase->add('test_action', $callback10, 10);
        $this->addActionUseCase->add('test_action', $callback20, 20);
        $this->useCase->removeHook('test_action', $callback10, 10);
        $this->dispatchActionUseCase->dispatch('test_action');

        expect($callCount10)->toBe(0);
        expect($callCount20)->toBe(1);
    });

    test('returns true even when removing non-existent action', function () {
        $result = $this->useCase->removeHook('non_existent', fn() => null);

        expect($result)->toBeTrue();
    });

    test('does not affect other actions on same hook', function () {
        $callCount1 = 0;
        $callCount2 = 0;

        $callback1 = function () use (&$callCount1) {
            $callCount1++;
        };
        $callback2 = function () use (&$callCount2) {
            $callCount2++;
        };

        $this->addActionUseCase->add('test_action', $callback1);
        $this->addActionUseCase->add('test_action', $callback2);
        $this->useCase->removeHook('test_action', $callback1);
        $this->dispatchActionUseCase->dispatch('test_action');

        expect($callCount1)->toBe(0);
        expect($callCount2)->toBe(1);
    });

    test('does not affect actions on different hooks', function () {
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
        };

        $this->addActionUseCase->add('hook_one', $callback);
        $this->addActionUseCase->add('hook_two', $callback);
        $this->useCase->removeHook('hook_one', $callback);
        $this->dispatchActionUseCase->dispatch('hook_two');

        expect($callCount)->toBe(1);
    });
});
