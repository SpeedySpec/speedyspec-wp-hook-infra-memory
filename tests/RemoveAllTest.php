<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject::removeAll()', function () {
    test('removes all callbacks when no priority specified', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_1', 5);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_2', 10);
        $callback3 = new ObjectHookInvoke(fn($v) => $v . '_3', 15);

        $subject->add($callback1);
        $subject->add($callback2);
        $subject->add($callback3);

        $subject->removeAll();

        expect($subject->hasCallbacks())->toBeFalse();
    });

    test('removes callbacks at specific priority only', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callCount5 = 0;
        $callCount10 = 0;

        $callback5 = new ObjectHookInvoke(function ($v) use (&$callCount5) {
            $callCount5++;
            return $v;
        }, 5);
        $callback10 = new ObjectHookInvoke(function ($v) use (&$callCount10) {
            $callCount10++;
            return $v;
        }, 10);

        $subject->add($callback5);
        $subject->add($callback10);

        $subject->removeAll(10);
        $subject->filter('test');

        expect($callCount5)->toBe(1);
        expect($callCount10)->toBe(0);
    });

    test('removes multiple callbacks at same priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callCount = 0;

        $callback1 = new ObjectHookInvoke(function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        }, 10);
        $callback2 = new ObjectHookInvoke(function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        }, 10);
        $callback3 = new ObjectHookInvoke(function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        }, 5);

        $subject->add($callback1);
        $subject->add($callback2);
        $subject->add($callback3);

        $subject->removeAll(10);
        $subject->filter('test');

        expect($callCount)->toBe(1);
    });

    test('does nothing when removing from non-existent priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callCount = 0;

        $callback = new ObjectHookInvoke(function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        }, 10);

        $subject->add($callback);
        $subject->removeAll(5);
        $subject->filter('test');

        expect($callCount)->toBe(1);
    });

    test('clears internal state after removeAll with no priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new ObjectHookInvoke(fn($v) => $v . '_modified', 10);

        $subject->add($callback);
        $subject->removeAll();

        $result = $subject->filter('test');

        expect($result)->toBe('test');
    });
});
