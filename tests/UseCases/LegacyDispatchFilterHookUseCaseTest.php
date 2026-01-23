<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddFilterUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyDispatchFilterHookUseCase;

covers(LegacyDispatchFilterHookUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addFilterUseCase = new LegacyAddFilterUseCase($this->container);
    $this->useCase = new LegacyDispatchFilterHookUseCase($this->container);
});

describe('LegacyDispatchFilterHookUseCase::filter()', function () {
    test('filters value through callback', function () {
        $this->addFilterUseCase->add('test_filter', 'strtoupper');

        $result = $this->useCase->filter('test_filter', 'hello');

        expect($result)->toBe('HELLO');
    });

    test('returns original value when no filters registered', function () {
        $result = $this->useCase->filter('unregistered_filter', 'original_value');

        expect($result)->toBe('original_value');
    });

    test('chains multiple filters', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_first', 10);
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_second', 20);

        $result = $this->useCase->filter('test_filter', 'start');

        expect($result)->toBe('start_first_second');
    });

    test('filters with additional arguments', function () {
        $receivedArgs = [];
        $this->addFilterUseCase->add('test_filter', function ($value, $arg1, $arg2) use (&$receivedArgs) {
            $receivedArgs = [$value, $arg1, $arg2];
            return $value;
        }, 10, 3);

        $this->useCase->filter('test_filter', 'value', 'extra1', 'extra2');

        expect($receivedArgs)->toBe(['value', 'extra1', 'extra2']);
    });

    test('filters in priority order', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_priority20', 20);
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_priority5', 5);
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_priority10', 10);

        $result = $this->useCase->filter('test_filter', 'start');

        expect($result)->toBe('start_priority5_priority10_priority20');
    });

    test('increments hook run amount after filter', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v);

        $this->useCase->filter('test_filter', 'value1');
        $this->useCase->filter('test_filter', 'value2');

        $runAmount = $this->hookRunAmountService->getRunAmount(new StringHookName('test_filter'));
        expect($runAmount)->toBe(2);
    });

    test('handles null value', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v ?? 'default');

        $result = $this->useCase->filter('test_filter', null);

        expect($result)->toBe('default');
    });

    test('handles array value', function () {
        $this->addFilterUseCase->add('test_filter', function ($arr) {
            $arr[] = 'added';
            return $arr;
        });

        $result = $this->useCase->filter('test_filter', ['initial']);

        expect($result)->toBe(['initial', 'added']);
    });
});
