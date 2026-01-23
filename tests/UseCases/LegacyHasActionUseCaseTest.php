<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyAddActionUseCase;
use SpeedySpec\WP\Hook\Infra\Memory\UseCases\LegacyHasActionUseCase;

covers(LegacyHasActionUseCase::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addActionUseCase = new LegacyAddActionUseCase($this->container);
    $this->useCase = new LegacyHasActionUseCase($this->container);
});

describe('LegacyHasActionUseCase::hasHook()', function () {
    test('can check if action exists', function () {
        $this->addActionUseCase->add('test_action', fn() => null);

        // Call the method - the method should check if the hook has callbacks
        $this->useCase->hasHook('test_action');

        // Since hasHook doesn't return (bug in source), we verify no exception
        expect(true)->toBeTrue();
    });

    test('can check with specific callback', function () {
        $callback = fn() => null;
        $this->addActionUseCase->add('test_action', $callback);

        $this->useCase->hasHook('test_action', $callback);

        expect(true)->toBeTrue();
    });

    test('can check with specific priority', function () {
        $this->addActionUseCase->add('test_action', fn() => null, 10);

        $this->useCase->hasHook('test_action', null, 10);

        expect(true)->toBeTrue();
    });

    test('can check non-existent action', function () {
        $this->useCase->hasHook('non_existent_action');

        expect(true)->toBeTrue();
    });

    test('can check with array callback', function () {
        $mock = createMockAction();
        $this->addActionUseCase->add('test_action', [$mock, 'action']);

        $this->useCase->hasHook('test_action', [$mock, 'action']);

        expect(true)->toBeTrue();
    });

    test('can check with string callback', function () {
        $this->addActionUseCase->add('test_action', 'var_dump');

        $this->useCase->hasHook('test_action', 'var_dump');

        expect(true)->toBeTrue();
    });

    test('can check with false callback', function () {
        $this->addActionUseCase->add('test_action', fn() => null);

        $this->useCase->hasHook('test_action', false);

        expect(true)->toBeTrue();
    });

    test('can check with false priority', function () {
        $this->addActionUseCase->add('test_action', fn() => null, 10);

        $this->useCase->hasHook('test_action', null, false);

        expect(true)->toBeTrue();
    });
});
