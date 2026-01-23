<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddFilterUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyCurrentFilterUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchFilterHookUseCase;

covers(LegacyCurrentFilterUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addFilterUseCase = new LegacyAddFilterUseCase($this->container);
    $this->dispatchFilterUseCase = new LegacyDispatchFilterHookUseCase($this->container);
    $this->useCase = new LegacyCurrentFilterUseCase($this->currentHookService);
});

describe('LegacyCurrentFilterUseCase::currentFilter()', function () {
    test('returns false when not executing any filter', function () {
        $result = $this->useCase->currentFilter();

        expect($result)->toBeFalse();
    });

    test('returns hook name during filter execution', function () {
        $currentFilterResult = null;
        $this->addFilterUseCase->add('test_filter', function ($v) use (&$currentFilterResult) {
            $currentFilterResult = $this->useCase->currentFilter();
            return $v;
        });

        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        expect($currentFilterResult)->toBe('test_filter');
    });

    test('returns correct hook name for nested filters', function () {
        $outerFilterName = null;
        $innerFilterName = null;

        $this->addFilterUseCase->add('outer_filter', function ($v) use (&$outerFilterName, &$innerFilterName) {
            $outerFilterName = $this->useCase->currentFilter();
            $this->dispatchFilterUseCase->filter('inner_filter', 'inner_value');
            return $v;
        });

        $this->addFilterUseCase->add('inner_filter', function ($v) use (&$innerFilterName) {
            $innerFilterName = $this->useCase->currentFilter();
            return $v;
        });

        $this->dispatchFilterUseCase->filter('outer_filter', 'outer_value');

        expect($outerFilterName)->toBe('outer_filter');
        expect($innerFilterName)->toBe('inner_filter');
    });

    test('returns false after filter execution completes', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v);
        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        $result = $this->useCase->currentFilter();

        expect($result)->toBeFalse();
    });

    test('returns hook name for different filter hooks', function () {
        $filterName1 = null;
        $filterName2 = null;

        $this->addFilterUseCase->add('filter_one', function ($v) use (&$filterName1) {
            $filterName1 = $this->useCase->currentFilter();
            return $v;
        });

        $this->addFilterUseCase->add('filter_two', function ($v) use (&$filterName2) {
            $filterName2 = $this->useCase->currentFilter();
            return $v;
        });

        $this->dispatchFilterUseCase->filter('filter_one', 'value');
        $this->dispatchFilterUseCase->filter('filter_two', 'value');

        expect($filterName1)->toBe('filter_one');
        expect($filterName2)->toBe('filter_two');
    });
});
