<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddFilterUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchFilterHookUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDoingFilterUseCase;

covers(LegacyDoingFilterUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addFilterUseCase = new LegacyAddFilterUseCase($this->container);
    $this->dispatchFilterUseCase = new LegacyDispatchFilterHookUseCase($this->container);
    $this->useCase = new LegacyDoingFilterUseCase($this->currentHookService);
});

describe('LegacyDoingFilterUseCase::isDoingFilter()', function () {
    test('returns false when not executing any filter', function () {
        $result = $this->useCase->isDoingFilter();

        expect($result)->toBeFalse();
    });

    test('returns false for specific hook when not executing', function () {
        $result = $this->useCase->isDoingFilter('test_filter');

        expect($result)->toBeFalse();
    });

    test('returns true during filter execution', function () {
        $doingFilterResult = null;
        $this->addFilterUseCase->add('test_filter', function ($v) use (&$doingFilterResult) {
            $doingFilterResult = $this->useCase->isDoingFilter();
            return $v;
        });

        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($doingFilterResult)->toBeTrue();
    });

    test('returns true for specific hook during its execution', function () {
        $doingSpecificFilter = null;
        $this->addFilterUseCase->add('test_filter', function ($v) use (&$doingSpecificFilter) {
            $doingSpecificFilter = $this->useCase->isDoingFilter('test_filter');
            return $v;
        });

        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($doingSpecificFilter)->toBeTrue();
    });

    test('returns false for different hook during execution', function () {
        $doingOtherFilter = null;
        $this->addFilterUseCase->add('test_filter', function ($v) use (&$doingOtherFilter) {
            $doingOtherFilter = $this->useCase->isDoingFilter('other_filter');
            return $v;
        });

        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($doingOtherFilter)->toBeFalse();
    });

    test('returns false after filter execution completes', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v);
        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        $result = $this->useCase->isDoingFilter('test_filter');

        expect($result)->toBeFalse();
    });
});
