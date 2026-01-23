<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddActionUseCase;

covers(LegacyAddActionUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->useCase = new LegacyAddActionUseCase($this->container);
});

describe('LegacyAddActionUseCase::add()', function () {
    test('adds action with string callback', function () {
        $result = $this->useCase->add('test_action', 'var_dump');

        expect($result)->toBeTrue();
    });

    test('adds action with array callback', function () {
        $mock = createMockAction();
        $result = $this->useCase->add('test_action', [$mock, 'action']);

        expect($result)->toBeTrue();
    });

    test('adds action with closure callback', function () {
        $result = $this->useCase->add('test_action', fn() => null);

        expect($result)->toBeTrue();
    });

    test('adds action with custom priority', function () {
        $result = $this->useCase->add('test_action', fn() => null, 20);

        expect($result)->toBeTrue();
    });

    test('adds action with accepted_args parameter', function () {
        $result = $this->useCase->add('test_action', function ($arg1, $arg2) {
            // Handle args
        }, 10, 2);

        expect($result)->toBeTrue();
    });

    test('returns true on successful add', function () {
        $result = $this->useCase->add('hook_name', fn() => null);

        expect($result)->toBeTrue();
    });

    test('adds multiple actions to same hook', function () {
        $result1 = $this->useCase->add('same_hook', fn() => null);
        $result2 = $this->useCase->add('same_hook', fn() => null);

        expect($result1)->toBeTrue();
        expect($result2)->toBeTrue();
    });

    test('adds actions to different hooks', function () {
        $result1 = $this->useCase->add('hook_one', fn() => null);
        $result2 = $this->useCase->add('hook_two', fn() => null);

        expect($result1)->toBeTrue();
        expect($result2)->toBeTrue();
    });
});
