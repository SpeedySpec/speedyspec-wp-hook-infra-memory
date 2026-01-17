<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Domain\ValueObject\HookInvokableOption;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookContainer;

covers(MemoryHookContainer::class);

beforeEach(function () {
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new MemoryHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
});

describe('MemoryHookContainer::add()', function () {
    test('can add a filter with function callback', function () {
        $hookName = new StringHookName('test_hook');
        $callback = new StringHookInvoke('strtoupper');
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName, $callback, $options);

        // Verify by filtering - if it works, the filter was added
        $result = $this->container->filter($hookName, 'hello');
        expect($result)->toBe('HELLO');
    });

    test('can add a filter with object callback', function () {
        $hookName = new StringHookName('test_hook');
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'filter']);
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName, $callback, $options);
        $this->container->filter($hookName, 'test_value');

        expect($mock->getCallCount())->toBe(1);
    });

    test('can add multiple filters to same hook', function () {
        $hookName = new StringHookName('test_hook');
        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_first');
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_second');
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName, $callback1, $options);
        $this->container->add($hookName, $callback2, $options);

        $result = $this->container->filter($hookName, 'start');

        expect($result)->toContain('_first')
            ->or->toContain('_second');
    });

    test('can add filters to different hooks', function () {
        $hookName1 = new StringHookName('hook_one');
        $hookName2 = new StringHookName('hook_two');
        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_one');
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_two');
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName1, $callback1, $options);
        $this->container->add($hookName2, $callback2, $options);

        $result1 = $this->container->filter($hookName1, 'test');
        $result2 = $this->container->filter($hookName2, 'test');

        expect($result1)->toBe('test_one');
        expect($result2)->toBe('test_two');
    });
});

describe('MemoryHookContainer::remove()', function () {
    test('can remove a filter', function () {
        $hookName = new StringHookName('test_hook');
        $callCount = 0;
        $callback = new ObjectHookInvoke(function ($v) use (&$callCount) {
            $callCount++;
            return $v . '_modified';
        });
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName, $callback, $options);
        $this->container->remove($hookName, $callback, $options);
        $this->container->filter($hookName, 'test');

        expect($callCount)->toBe(0);
    });

    test('removing filter does not affect other filters', function () {
        $hookName = new StringHookName('test_hook');
        $callCount1 = 0;
        $callCount2 = 0;

        $callback1 = new ObjectHookInvoke(function ($v) use (&$callCount1) {
            $callCount1++;
            return $v;
        });
        $callback2 = new ObjectHookInvoke(function ($v) use (&$callCount2) {
            $callCount2++;
            return $v;
        });
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName, $callback1, $options);
        $this->container->add($hookName, $callback2, $options);
        $this->container->remove($hookName, $callback1, $options);
        $this->container->filter($hookName, 'test');

        expect($callCount1)->toBe(0);
        expect($callCount2)->toBe(1);
    });
});

describe('MemoryHookContainer::dispatch()', function () {
    test('dispatches action to registered callbacks', function () {
        $hookName = new StringHookName('test_action');
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'action']);
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName, $callback, $options);
        $this->container->dispatch($hookName, 'arg');

        expect($mock->getCallCount())->toBe(1);
    });

    test('dispatch increments hook run amount', function () {
        $hookName = new StringHookName('test_action');
        $callback = new ObjectHookInvoke(fn() => null);
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 0);

        $this->container->add($hookName, $callback, $options);
        $this->container->dispatch($hookName);
        $this->container->dispatch($hookName);

        expect($this->hookRunAmountService->getRunAmount($hookName))->toBe(2);
    });

    test('dispatch tracks current hook', function () {
        $hookName = new StringHookName('tracked_hook');
        $capturedHook = null;

        $callback = new ObjectHookInvoke(function () use (&$capturedHook) {
            $capturedHook = $this->currentHookService->getCurrentHook();
        });
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 0);

        $this->container->add($hookName, $callback, $options);
        $this->container->dispatch($hookName);

        expect($capturedHook?->getName())->toBe('tracked_hook');
    });
});

describe('MemoryHookContainer::filter()', function () {
    test('filters value through registered callbacks', function () {
        $hookName = new StringHookName('test_filter');
        $callback = new ObjectHookInvoke(fn($v) => strtoupper($v));
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName, $callback, $options);
        $result = $this->container->filter($hookName, 'hello');

        expect($result)->toBe('HELLO');
    });

    test('filter increments hook run amount', function () {
        $hookName = new StringHookName('test_filter');
        $callback = new ObjectHookInvoke(fn($v) => $v);
        $options = new HookInvokableOption(priority: 10, acceptedArgs: 1);

        $this->container->add($hookName, $callback, $options);
        $this->container->filter($hookName, 'value1');
        $this->container->filter($hookName, 'value2');

        expect($this->hookRunAmountService->getRunAmount($hookName))->toBe(2);
    });

    test('filter returns original value when no callbacks registered', function () {
        $hookName = new StringHookName('empty_filter');

        $result = $this->container->filter($hookName, 'original');

        expect($result)->toBe('original');
    });
});
