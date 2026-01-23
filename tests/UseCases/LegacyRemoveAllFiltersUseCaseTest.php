<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddFilterUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchFilterHookUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyRemoveAllFiltersUseCase;

covers(LegacyRemoveAllFiltersUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addFilterUseCase = new LegacyAddFilterUseCase($this->container);
    $this->dispatchFilterUseCase = new LegacyDispatchFilterHookUseCase($this->container);
    $this->useCase = new LegacyRemoveAllFiltersUseCase($this->container);
});

describe('LegacyRemoveAllFiltersUseCase::removeHook()', function () {
    test('removes all filters at specific priority', function () {
        $callCount10 = 0;
        $callCount20 = 0;

        $this->addFilterUseCase->add('test_filter', function ($v) use (&$callCount10) {
            $callCount10++;
            return $v;
        }, 10);
        $this->addFilterUseCase->add('test_filter', function ($v) use (&$callCount10) {
            $callCount10++;
            return $v;
        }, 10);
        $this->addFilterUseCase->add('test_filter', function ($v) use (&$callCount20) {
            $callCount20++;
            return $v;
        }, 20);

        $result = $this->useCase->removeHook('test_filter', 10);
        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($result)->toBeTrue();
        expect($callCount10)->toBe(0);
        expect($callCount20)->toBe(1);
    });

    test('returns true on successful removal', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v, 10);

        $result = $this->useCase->removeHook('test_filter', 10);

        expect($result)->toBeTrue();
    });

    test('returns true even when no filters exist at priority', function () {
        $result = $this->useCase->removeHook('non_existent_hook', 10);

        expect($result)->toBeTrue();
    });

    test('does not affect filters at other priorities', function () {
        $callCount5 = 0;
        $callCount15 = 0;

        $this->addFilterUseCase->add('test_filter', function ($v) use (&$callCount5) {
            $callCount5++;
            return $v;
        }, 5);
        $this->addFilterUseCase->add('test_filter', function ($v) use (&$callCount15) {
            $callCount15++;
            return $v;
        }, 15);

        $this->useCase->removeHook('test_filter', 10);
        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($callCount5)->toBe(1);
        expect($callCount15)->toBe(1);
    });

    test('does not affect filters on different hooks', function () {
        $callCount = 0;

        $this->addFilterUseCase->add('hook_one', function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        }, 10);
        $this->addFilterUseCase->add('hook_two', function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        }, 10);

        $this->useCase->removeHook('hook_one', 10);
        $this->dispatchFilterUseCase->filter('hook_two', 'value');

        expect($callCount)->toBe(1);
    });
});
