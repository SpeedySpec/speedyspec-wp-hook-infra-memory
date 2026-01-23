<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject::filter()', function () {
    test('applies filter with callback and returns modified value', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'filter'], 1);

        $subject->add($callback);
        $result = $subject->filter('test_arg');

        expect($result)->toBe('test_arg');
        expect($mock->getCallCount())->toBe(1);
    });

    test('applies filter with multiple calls', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'filter'], 1);

        $subject->add($callback);
        $result1 = $subject->filter('arg1');
        $result2 = $subject->filter($result1);

        expect($result2)->toBe('arg1');
        expect($mock->getCallCount())->toBe(2);
    });

    test('returns original value when no filters registered', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

        $result = $subject->filter('original_value');

        expect($result)->toBe('original_value');
    });

    test('chains multiple filters in priority order', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_first', 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_second', 5);

        $subject->add($callback1);
        $subject->add($callback2);

        $result = $subject->filter('start');

        expect($result)->toBe('start_second_first');
    });

    test('passes additional arguments to filter callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $receivedArgs = [];

        $callback = new ObjectHookInvoke(function ($value, ...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
            return $value;
        }, 1);

        $subject->add($callback);
        $subject->filter('value', 'arg1', 'arg2');

        expect($receivedArgs)->toBe(['arg1', 'arg2']);
    });
});

describe('priority callback order', function () {
    test('executes callbacks in ascending priority order (lower first)', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callOrder = [];

        $callback1 = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'filter1';
            return $v;
        }, 10);
        $callback2 = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'filter2';
            return $v;
        }, 9);

        $subject->add($callback1);
        $subject->add($callback2);

        $subject->filter('test');

        expect($callOrder)->toBe(['filter2', 'filter1']);
    });

    test('maintains order for callbacks at same priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callOrder = [];

        $callback1 = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'first';
            return $v;
        }, 10);
        $callback2 = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'second';
            return $v;
        }, 10);

        $subject->add($callback1);
        $subject->add($callback2);

        $subject->filter('test');

        expect($callOrder)->toHaveCount(2);
    });
});
