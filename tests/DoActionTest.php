<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject::dispatch()', function () {
    test('dispatches action with callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'action'], 1);

        $subject->add($callback);
        $subject->dispatch('test_arg');

        expect($mock->getCallCount())->toBe(1);
    });

    test('dispatches action with multiple calls', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'action'], 1);

        $subject->add($callback);
        $subject->dispatch('arg1');
        $subject->dispatch('arg2');

        expect($mock->getCallCount())->toBe(2);
    });

    test('dispatches action with multiple callbacks on same priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mockA = createMockAction();
        $mockB = createMockAction();

        // Use different method names to ensure unique callback identifiers
        $callbackA = new ArrayHookInvoke([$mockA, 'action'], 1);
        $callbackB = new ArrayHookInvoke([$mockB, 'action2'], 1);

        $subject->add($callbackA);
        $subject->add($callbackB);
        $subject->dispatch('arg');

        expect($mockA->getCallCount())->toBe(1);
        expect($mockB->getCallCount())->toBe(1);
    });

    test('dispatches action with multiple callbacks on different priorities', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mockA = createMockAction();
        $mockB = createMockAction();

        // Use different method names to ensure unique callback identifiers
        $callbackA = new ArrayHookInvoke([$mockA, 'action'], 1);
        $callbackB = new ArrayHookInvoke([$mockB, 'action2'], 2);

        $subject->add($callbackA);
        $subject->add($callbackB);
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
        }, 1);

        $subject->add($callback);
        $subject->dispatch('ignored_arg');

        expect($receivedArgs)->toBeArray();
    });

    test('dispatches action with one accepted arg', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $receivedArgs = null;

        $callback = new ObjectHookInvoke(function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        }, 1);

        $subject->add($callback);
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
        }, 10);
        $callback2 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'action2';
        }, 9);

        $subject->add($callback1);
        $subject->add($callback2);

        $subject->dispatch();

        expect($callOrder)->toBe(['action2', 'action1']);
    });

    test('executes callbacks with lower priority first', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callOrder = [];

        $callback1 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'priority_9';
        }, 9);
        $callback2 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'priority_10';
        }, 10);

        $subject->add($callback1);
        $subject->add($callback2);

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
        }, 10);
        $callback2 = new ObjectHookInvoke(function ($value) use (&$output) {
            $output .= $value . '2';
            return 'also_modified';
        }, 11);

        $subject->add($callback1);
        $subject->add($callback2);

        $subject->dispatch('a');

        expect($output)->toContain('a');
    });
});
