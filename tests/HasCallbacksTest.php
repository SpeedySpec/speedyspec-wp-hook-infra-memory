<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject::hasCallbacks()', function () {
    test('returns false when no callbacks registered', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);

        expect($subject->hasCallbacks())->toBeFalse();
    });

    test('returns true when callbacks are registered', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new ObjectHookInvoke(fn($v) => $v, 10);

        $subject->add($callback);

        expect($subject->hasCallbacks())->toBeTrue();
    });

    test('returns true when specific callback is registered', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new ObjectHookInvoke(fn($v) => $v, 10);

        $subject->add($callback);

        expect($subject->hasCallbacks($callback))->toBeTrue();
    });

    test('returns false when specific callback is not registered', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback1 = new ObjectHookInvoke(fn($v) => $v, 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_other', 10);

        $subject->add($callback1);

        expect($subject->hasCallbacks($callback2))->toBeFalse();
    });

    test('returns true when callbacks exist at specific priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new ObjectHookInvoke(fn($v) => $v, 10);

        $subject->add($callback);

        expect($subject->hasCallbacks(priority: 10))->toBeTrue();
    });

    test('returns false when no callbacks exist at specific priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new ObjectHookInvoke(fn($v) => $v, 10);

        $subject->add($callback);

        expect($subject->hasCallbacks(priority: 5))->toBeFalse();
    });

    test('returns true when specific callback exists at specific priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new ObjectHookInvoke(fn($v) => $v, 10);

        $subject->add($callback);

        expect($subject->hasCallbacks($callback, 10))->toBeTrue();
    });

    test('returns false when specific callback exists but at different priority', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callback = new ObjectHookInvoke(fn($v) => $v, 10);

        $subject->add($callback);

        expect($subject->hasCallbacks($callback, 5))->toBeFalse();
    });
});
