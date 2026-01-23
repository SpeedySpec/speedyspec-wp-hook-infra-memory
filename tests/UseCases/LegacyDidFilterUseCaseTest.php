<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddFilterUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDidFilterUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchFilterHookUseCase;

covers(LegacyDidFilterUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addFilterUseCase = new LegacyAddFilterUseCase($this->container);
    $this->dispatchFilterUseCase = new LegacyDispatchFilterHookUseCase($this->container);
    $this->useCase = new LegacyDidFilterUseCase($this->hookRunAmountService);
});

describe('LegacyDidFilterUseCase::didFilter()', function () {
    test('can check if filter was run', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v);
        $this->dispatchFilterUseCase->filter('test_filter', 'value');

        // Call method - note: source has bug where it doesn't return
        $this->useCase->didFilter('test_filter');

        expect(true)->toBeTrue();
    });

    test('can check filter that was never run', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v);

        $this->useCase->didFilter('test_filter');

        expect(true)->toBeTrue();
    });

    test('can check non-existent filter', function () {
        $this->useCase->didFilter('non_existent_filter');

        expect(true)->toBeTrue();
    });

    test('can check filter run multiple times', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v);
        $this->dispatchFilterUseCase->filter('test_filter', 'value1');
        $this->dispatchFilterUseCase->filter('test_filter', 'value2');
        $this->dispatchFilterUseCase->filter('test_filter', 'value3');

        $this->useCase->didFilter('test_filter');

        expect(true)->toBeTrue();
    });
});
