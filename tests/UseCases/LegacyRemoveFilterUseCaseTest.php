<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddFilterUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchFilterHookUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyRemoveFilterUseCase;

covers(LegacyRemoveFilterUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addFilterUseCase = new LegacyAddFilterUseCase($this->container);
    $this->dispatchFilterUseCase = new LegacyDispatchFilterHookUseCase($this->container);
    $this->useCase = new LegacyRemoveFilterUseCase($this->container);
});

describe('LegacyRemoveFilterUseCase::removeHook()', function () {
    test('removes filter with closure callback', function () {
        $callCount = 0;
        $callback = function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        };

        $this->addFilterUseCase->add('test_filter', $callback);
        $result = $this->useCase->removeHook('test_filter', $callback);
        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($result)->toBeTrue();
        expect($callCount)->toBe(0);
    });

    test('removes filter with string callback', function () {
        $this->addFilterUseCase->add('test_filter', 'strtoupper');
        $result = $this->useCase->removeHook('test_filter', 'strtoupper');

        expect($result)->toBeTrue();
    });

    test('removes filter with array callback', function () {
        $mock = createMockAction();
        $this->addFilterUseCase->add('test_filter', [$mock, 'filter']);
        $result = $this->useCase->removeHook('test_filter', [$mock, 'filter']);

        expect($result)->toBeTrue();
    });

    test('removes filter at specific priority', function () {
        $callCount10 = 0;
        $callCount20 = 0;

        $callback10 = function ($v) use (&$callCount10) {
            $callCount10++;
            return $v;
        };
        $callback20 = function ($v) use (&$callCount20) {
            $callCount20++;
            return $v;
        };

        $this->addFilterUseCase->add('test_filter', $callback10, 10);
        $this->addFilterUseCase->add('test_filter', $callback20, 20);
        $this->useCase->removeHook('test_filter', $callback10, 10);
        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($callCount10)->toBe(0);
        expect($callCount20)->toBe(1);
    });

    test('returns true even when removing non-existent filter', function () {
        $result = $this->useCase->removeHook('non_existent', fn($v) => $v);

        expect($result)->toBeTrue();
    });

    test('does not affect other filters on same hook', function () {
        $callCount1 = 0;
        $callCount2 = 0;

        $callback1 = function ($v) use (&$callCount1) {
            $callCount1++;
            return $v;
        };
        $callback2 = function ($v) use (&$callCount2) {
            $callCount2++;
            return $v;
        };

        $this->addFilterUseCase->add('test_filter', $callback1);
        $this->addFilterUseCase->add('test_filter', $callback2);
        $this->useCase->removeHook('test_filter', $callback1);
        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($callCount1)->toBe(0);
        expect($callCount2)->toBe(1);
    });

    test('does not affect filters on different hooks', function () {
        $callCount = 0;
        $callback = function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        };

        $this->addFilterUseCase->add('hook_one', $callback);
        $this->addFilterUseCase->add('hook_two', $callback);
        $this->useCase->removeHook('hook_one', $callback);
        $this->dispatchFilterUseCase->filter('hook_two', 'value');

        expect($callCount)->toBe(1);
    });
});
