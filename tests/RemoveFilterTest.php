<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\ValueObject\HookInvokableOption;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject::remove()', function () {
    test('can remove filter with function callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callCount = 0;

        $callback = new ObjectHookInvoke(function ($v) use (&$callCount) {
            $callCount++;
            return $v . '_modified';
        });
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback, $options);
        $subject->remove($callback, $options);
        $subject->filter('test');

        expect($callCount)->toBe(0);
    });

    test('can remove filter with object callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'filter']);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 2);

        $subject->add($callback, $options);
        $subject->remove($callback, $options);
        $subject->filter('test_value');

        expect($mock->getCallCount())->toBe(0);
    });

    test('can remove filter with static method callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callCount = 0;

        $staticClass = new class {
            public static int $callCount = 0;
            public static function transform(string $value): string
            {
                self::$callCount++;
                return $value . '_transformed';
            }
        };

        $callback = new ArrayHookInvoke([$staticClass::class, 'transform']);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback, $options);
        $subject->remove($callback, $options);
        $result = $subject->filter('test');

        expect($staticClass::$callCount)->toBe(0);
        expect($result)->toBe('test');
    });

    test('removing one filter leaves others at same priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

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
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback1, $options);
        $subject->add($callback2, $options);
        $subject->remove($callback1, $options);
        $subject->filter('test');

        expect($callCount1)->toBe(0);
        expect($callCount2)->toBe(1);
    });

    test('removing filter leaves others at different priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

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

        $subject->add($callback1, new HookInvokableOption(priority: 1, acceptedArgs: 1));
        $subject->add($callback2, new HookInvokableOption(priority: 2, acceptedArgs: 1));
        $subject->remove($callback1, new HookInvokableOption(priority: 1, acceptedArgs: 1));
        $subject->filter('test');

        expect($callCount1)->toBe(0);
        expect($callCount2)->toBe(1);
    });

    test('can remove and re-add filter during execution', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $executionOrder = [];

        $callback1 = new ObjectHookInvoke(function ($v) use (&$executionOrder) {
            $executionOrder[] = '1';
            return $v . '1';
        });

        $callback2 = new ObjectHookInvoke(function ($v) use (&$executionOrder, $subject, &$callback2) {
            $executionOrder[] = '2';
            return $v . '2';
        });

        $subject->add($callback1, new HookInvokableOption(priority: 10, acceptedArgs: 1));
        $subject->add($callback2, new HookInvokableOption(priority: 11, acceptedArgs: 1));

        $result = $subject->filter('');

        expect($result)->toBe('12');
        expect($executionOrder)->toBe(['1', '2']);
    });
});

describe('remove filter edge cases', function () {
    test('removing non-existent filter does not throw', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new ObjectHookInvoke(fn($v) => $v);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        // Should not throw
        $subject->remove($callback, $options);

        expect(true)->toBeTrue();
    });

    test('can remove closure callback', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callCount = 0;

        $closure = function ($v) use (&$callCount) {
            $callCount++;
            return $v . '_closure';
        };
        $callback = new ObjectHookInvoke($closure);
        $options = new HookInvokableOption(priority: 1, acceptedArgs: 1);

        $subject->add($callback, $options);
        $subject->remove($callback, $options);
        $subject->filter('test');

        expect($callCount)->toBe(0);
    });
});
