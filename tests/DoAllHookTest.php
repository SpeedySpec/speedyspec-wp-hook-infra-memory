<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\MemoryHookSubject;

covers(MemoryHookSubject::class);

describe('MemoryHookSubject "all" hook behavior', function () {
    test('dispatches all hook with multiple calls', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $mock = createMockAction();
        $callback = new ArrayHookInvoke([$mock, 'action'], 1);

        $subject->add($callback);

        $subject->dispatch('all_arg');
        $subject->dispatch('all_arg');

        expect($mock->getCallCount())->toBe(2);
    });

    test('all hook receives correct arguments', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $receivedArgs = [];

        $callback = new ObjectHookInvoke(function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        }, 1);

        $subject->add($callback);
        $subject->dispatch('arg1', 'arg2', 'arg3');

        expect($receivedArgs)->toBe(['arg1', 'arg2', 'arg3']);
    });

    test('multiple callbacks on all hook execute in priority order', function () {
        $currentHookService = new CurrentHookService();
        $subject = new MemoryHookSubject($currentHookService);
        $callOrder = [];

        $callback1 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'first';
        }, 10);
        $callback2 = new ObjectHookInvoke(function () use (&$callOrder) {
            $callOrder[] = 'second';
        }, 5);

        $subject->add($callback1);
        $subject->add($callback2);

        $subject->dispatch();

        expect($callOrder)->toBe(['second', 'first']);
    });
});
