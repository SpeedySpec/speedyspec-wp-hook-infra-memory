<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject::add()', function () {
    test('can add filter with function callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new StringHookInvoke('strtoupper', 1);

        $subject->add($callback);
        $result = $subject->filter('hello');

        expect($result)->toBe('HELLO');
    });

    test('can add filter with object method callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'filter'], 1);

        $subject->add($callback);
        $subject->filter('test_value');

        expect($mock->getCallCount())->toBe(1);
    });

    test('can add filter with static method callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

        $staticClass = new class {
            public static function transform(string $value): string
            {
                return $value . '_transformed';
            }
        };

        $callback = new ArrayHookInvoke([$staticClass::class, 'transform'], 1);

        $subject->add($callback);
        $result = $subject->filter('test');

        expect($result)->toBe('test_transformed');
    });

    test('can add filter with closure callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $closure = fn(string $value): string => $value . '_closure';
        $callback = new ObjectHookInvoke($closure, 1);

        $subject->add($callback);
        $result = $subject->filter('test');

        expect($result)->toBe('test_closure');
    });

    test('can add two filters with same priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_first', 1);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_second', 1);

        $subject->add($callback1);
        $subject->add($callback2);

        $result = $subject->filter('test');

        // Both filters at same priority should execute in registration order
        expect($result)->toBe('test_first_second');
    });

    test('can add two filters with different priorities', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_priority10', 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_priority5', 5);

        $subject->add($callback1);
        $subject->add($callback2);

        $result = $subject->filter('test');

        expect($result)->toBe('test_priority5_priority10');
    });

    test('sorts callbacks by priority after adding', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callOrder = [];

        $callbackA = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'a';
            return $v;
        }, 10);
        $callbackB = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'b';
            return $v;
        }, 5);
        $callbackC = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'c';
            return $v;
        }, 8);

        $subject->add($callbackA);
        $subject->add($callbackB);
        $subject->add($callbackC);

        $subject->filter('test');

        expect($callOrder)->toBe(['b', 'c', 'a']);
    });
});
