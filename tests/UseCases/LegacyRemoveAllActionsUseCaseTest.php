<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchActionHookUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyRemoveAllActionsUseCase;

covers(LegacyRemoveAllActionsUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addActionUseCase = new LegacyAddActionUseCase($this->container);
    $this->dispatchActionUseCase = new LegacyDispatchActionHookUseCase($this->container);
    $this->useCase = new LegacyRemoveAllActionsUseCase($this->container);
});

describe('LegacyRemoveAllActionsUseCase::removeHook()', function () {
    test('removes all actions at specific priority', function () {
        $callCount10 = 0;
        $callCount20 = 0;

        $this->addActionUseCase->add('test_action', function () use (&$callCount10) {
            $callCount10++;
        }, 10);
        $this->addActionUseCase->add('test_action', function () use (&$callCount10) {
            $callCount10++;
        }, 10);
        $this->addActionUseCase->add('test_action', function () use (&$callCount20) {
            $callCount20++;
        }, 20);

        $result = $this->useCase->removeHook('test_action', 10);
        $this->dispatchActionUseCase->dispatch('test_action');

        expect($result)->toBeTrue();
        expect($callCount10)->toBe(0);
        expect($callCount20)->toBe(1);
    });

    test('returns true on successful removal', function () {
        $this->addActionUseCase->add('test_action', fn() => null, 10);

        $result = $this->useCase->removeHook('test_action', 10);

        expect($result)->toBeTrue();
    });

    test('returns true even when no actions exist at priority', function () {
        $result = $this->useCase->removeHook('non_existent_hook', 10);

        expect($result)->toBeTrue();
    });

    test('does not affect actions at other priorities', function () {
        $callCount5 = 0;
        $callCount15 = 0;

        $this->addActionUseCase->add('test_action', function () use (&$callCount5) {
            $callCount5++;
        }, 5);
        $this->addActionUseCase->add('test_action', function () use (&$callCount15) {
            $callCount15++;
        }, 15);

        $this->useCase->removeHook('test_action', 10);
        $this->dispatchActionUseCase->dispatch('test_action');

        expect($callCount5)->toBe(1);
        expect($callCount15)->toBe(1);
    });

    test('does not affect actions on different hooks', function () {
        $callCount = 0;

        $this->addActionUseCase->add('hook_one', function () use (&$callCount) {
            $callCount++;
        }, 10);
        $this->addActionUseCase->add('hook_two', function () use (&$callCount) {
            $callCount++;
        }, 10);

        $this->useCase->removeHook('hook_one', 10);
        $this->dispatchActionUseCase->dispatch('hook_two');

        expect($callCount)->toBe(1);
    });
});
