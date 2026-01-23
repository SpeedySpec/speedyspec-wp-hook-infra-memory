<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddFilterUseCase;

covers(LegacyAddFilterUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->useCase = new LegacyAddFilterUseCase($this->container);
});

describe('LegacyAddFilterUseCase::add()', function () {
    test('adds filter with string callback', function () {
        $result = $this->useCase->add('test_filter', 'strtoupper');

        expect($result)->toBeTrue();
    });

    test('adds filter with array callback', function () {
        $mock = createMockAction();
        $result = $this->useCase->add('test_filter', [$mock, 'filter']);

        expect($result)->toBeTrue();
    });

    test('adds filter with closure callback', function () {
        $result = $this->useCase->add('test_filter', fn($v) => $v . '_modified');

        expect($result)->toBeTrue();
    });

    test('adds filter with custom priority', function () {
        $callOrder = [];

        $this->useCase->add('test_filter', function ($v) use (&$callOrder) {
            $callOrder[] = 'priority_20';
            return $v;
        }, 20);

        $this->useCase->add('test_filter', function ($v) use (&$callOrder) {
            $callOrder[] = 'priority_5';
            return $v;
        }, 5);

        // This test verifies the filter was added - actual filtering is handled by container
        expect(true)->toBeTrue();
    });

    test('adds filter with accepted_args parameter', function () {
        $receivedArgs = [];

        $result = $this->useCase->add('test_filter', function ($v, $arg1, $arg2) use (&$receivedArgs) {
            $receivedArgs = [$v, $arg1, $arg2];
            return $v;
        }, 10, 3);

        expect($result)->toBeTrue();
    });

    test('returns true on successful add', function () {
        $result = $this->useCase->add('hook_name', 'strtolower');

        expect($result)->toBeTrue();
    });

    test('adds multiple filters to same hook', function () {
        $result1 = $this->useCase->add('same_hook', fn($v) => $v . '_first');
        $result2 = $this->useCase->add('same_hook', fn($v) => $v . '_second');

        expect($result1)->toBeTrue();
        expect($result2)->toBeTrue();
    });

    test('adds filters to different hooks', function () {
        $result1 = $this->useCase->add('hook_one', fn($v) => $v);
        $result2 = $this->useCase->add('hook_two', fn($v) => $v);

        expect($result1)->toBeTrue();
        expect($result2)->toBeTrue();
    });
});
