<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\ValueObject\HookInvokableOption;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject::add()', function () {
    test('can add filter with function callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new StringHookInvoke('strtoupper');
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback, $options);
        $result = $subject->filter('hello');

        expect($result)->toBe('HELLO');
    });

    test('can add filter with object method callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'filter']);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 2);

        $subject->add($callback, $options);
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

        $callback = new ArrayHookInvoke([$staticClass::class, 'transform']);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback, $options);
        $result = $subject->filter('test');

        expect($result)->toBe('test_transformed');
    });

    test('can add filter with closure callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $closure = fn(string $value): string => $value . '_closure';
        $callback = new ObjectHookInvoke($closure);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback, $options);
        $result = $subject->filter('test');

        expect($result)->toBe('test_closure');
    });

    test('can add two filters with same priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_first');
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_second');
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback1, $options);
        $subject->add($callback2, $options);

        $result = $subject->filter('test');

        expect($result)->toContain('_first')
            ->or->toContain('_second');
    });

    test('can add two filters with different priorities', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_priority10');
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_priority5');

        $subject->add($callback1, new HookInvokableOption(priority: 10, acceptedArgs: 1));
        $subject->add($callback2, new HookInvokableOption(priority: 5, acceptedArgs: 1));

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
        });
        $callbackB = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'b';
            return $v;
        });
        $callbackC = new ObjectHookInvoke(function ($v) use (&$callOrder) {
            $callOrder[] = 'c';
            return $v;
        });

        $subject->add($callbackA, new HookInvokableOption(priority: 10, acceptedArgs: 1));
        $subject->add($callbackB, new HookInvokableOption(priority: 5, acceptedArgs: 1));
        $subject->add($callbackC, new HookInvokableOption(priority: 8, acceptedArgs: 1));

        $subject->filter('test');

        expect($callOrder)->toBe(['b', 'c', 'a']);
    });
});
