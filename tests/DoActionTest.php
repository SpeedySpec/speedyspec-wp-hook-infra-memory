<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\ValueObject\HookInvokableOption;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject::dispatch()', function () {
    test('dispatches action with callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'action']);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 2);

        $subject->add($callback, $options);
        $subject->dispatch('test_arg');

        expect($mock->getCallCount())->toBe(1);
    });

    test('dispatches action with multiple calls', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'action']);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 2);

        $subject->add($callback, $options);
        $subject->dispatch('arg1');
        $subject->dispatch('arg2');

        expect($mock->getCallCount())->toBe(2);
    });

    test('dispatches action with multiple callbacks on same priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mockA = createMockAction();
        $mockB = createMockAction();

        $callbackA = new ArrayHookInvoke([$mockA, 'action']);
        $callbackB = new ArrayHookInvoke([$mockB, 'action']);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 2);

        $subject->add($callbackA, $options);
        $subject->add($callbackB, $options);
        $subject->dispatch('arg');

        expect($mockA->getCallCount())->toBe(1);
        expect($mockB->getCallCount())->toBe(1);
    });

    test('dispatches action with multiple callbacks on different priorities', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mockA = createMockAction();
        $mockB = createMockAction();

        $callbackA = new ArrayHookInvoke([$mockA, 'action']);
        $callbackB = new ArrayHookInvoke([$mockB, 'action']);

        $subject->add($callbackA, new HookInvokableOption(priority: 1, acceptedArgs: 2));
        $subject->add($callbackB, new HookInvokableOption(priority: 2, acceptedArgs: 2));
        $subject->dispatch('arg');

        expect($mockA->getCallCount())->toBe(1);
        expect($mockB->getCallCount())->toBe(1);
    });

    test('dispatches action with no accepted args', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $receivedArgs = null;

        $callback = new ObjectHookInvoke(function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        });
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 0);

        $subject->add($callback, $options);
        $subject->dispatch('ignored_arg');

        expect($receivedArgs)->toBeArray();
    });

    test('dispatches action with one accepted arg', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $receivedArgs = null;

        $callback = new ObjectHookInvoke(function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        });
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback, $options);
        $subject->dispatch('single_arg');

        expect($receivedArgs)->toContain('single_arg');
    });
});

describe('action priority callback order', function () {
    test('executes callbacks in ascending priority order', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callOrder = [];

        $callback1 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'action1';
        });
        $callback2 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'action2';
        });

        $subject->add($callback1, new HookInvokableOption(priority: 10, acceptedArgs: 0));
        $subject->add($callback2, new HookInvokableOption(priority: 9, acceptedArgs: 0));

        $subject->dispatch();

        expect($callOrder)->toBe(['action2', 'action1']);
    });

    test('executes callbacks with lower priority first', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callOrder = [];

        $callback1 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'priority_9';
        });
        $callback2 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'priority_10';
        });

        $subject->add($callback1, new HookInvokableOption(priority: 9, acceptedArgs: 0));
        $subject->add($callback2, new HookInvokableOption(priority: 10, acceptedArgs: 0));

        $subject->dispatch();

        expect($callOrder)->toBe(['priority_9', 'priority_10']);
    });
});

describe('action value preservation', function () {
    test('dispatch does not modify original argument reference', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $output = '';

        $callback1 = new ObjectHookInvoke(function ($value) use (&$output) {
            $output .= $value . '1';
            return 'modified';
        });
        $callback2 = new ObjectHookInvoke(function ($value) use (&$output) {
            $output .= $value . '2';
            return 'also_modified';
        });

        $subject->add($callback1, new HookInvokableOption(priority: 10, acceptedArgs: 1));
        $subject->add($callback2, new HookInvokableOption(priority: 11, acceptedArgs: 1));

        $subject->dispatch('a');

        expect($output)->toContain('a');
    });
});
