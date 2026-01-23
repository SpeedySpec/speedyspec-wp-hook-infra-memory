<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
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

describe('MemoryHookContainer::removeAll()', function () {
    test('removes all callbacks at specific priority', function () {
        $hookName = new StringHookName('test_hook');
        $callCount10 = 0;
        $callCount20 = 0;

        $callback10 = new ObjectHookInvoke(function ($v) use (&$callCount10) {
            $callCount10++;
            return $v;
        }, 10);
        $callback20 = new ObjectHookInvoke(function ($v) use (&$callCount20) {
            $callCount20++;
            return $v;
        }, 20);

        $this->container->add($hookName, $callback10);
        $this->container->add($hookName, $callback20);
        $this->container->removeAll($hookName, 10);
        $this->container->filter($hookName, 'test');

        expect($callCount10)->toBe(0);
        expect($callCount20)->toBe(1);
    });

    test('removes all callbacks when no priority specified', function () {
        $hookName = new StringHookName('test_hook');
        $callCount = 0;

        $callback1 = new ObjectHookInvoke(function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        }, 5);
        $callback2 = new ObjectHookInvoke(function ($v) use (&$callCount) {
            $callCount++;
            return $v;
        }, 15);

        $this->container->add($hookName, $callback1);
        $this->container->add($hookName, $callback2);
        $this->container->removeAll($hookName);
        $this->container->filter($hookName, 'test');

        expect($callCount)->toBe(0);
    });

    test('does not affect other hooks', function () {
        $hookName1 = new StringHookName('hook_one');
        $hookName2 = new StringHookName('hook_two');
        $callCount1 = 0;
        $callCount2 = 0;

        $callback1 = new ObjectHookInvoke(function ($v) use (&$callCount1) {
            $callCount1++;
            return $v;
        }, 10);
        $callback2 = new ObjectHookInvoke(function ($v) use (&$callCount2) {
            $callCount2++;
            return $v;
        }, 10);

        $this->container->add($hookName1, $callback1);
        $this->container->add($hookName2, $callback2);
        $this->container->removeAll($hookName1);
        $this->container->filter($hookName2, 'test');

        expect($callCount1)->toBe(0);
        expect($callCount2)->toBe(1);
    });
});

describe('MemoryHookContainer::hasCallbacks()', function () {
    test('returns false when no callbacks registered', function () {
        $hookName = new StringHookName('empty_hook');

        $result = $this->container->hasCallbacks($hookName);

        expect($result)->toBeFalse();
    });

    test('returns true when callbacks are registered', function () {
        $hookName = new StringHookName('test_hook');
        $callback = new ObjectHookInvoke(fn($v) => $v, 10);

        $this->container->add($hookName, $callback);

        expect($this->container->hasCallbacks($hookName))->toBeTrue();
    });

    test('returns false after all callbacks removed', function () {
        $hookName = new StringHookName('test_hook');
        $callback = new ObjectHookInvoke(fn($v) => $v, 10);

        $this->container->add($hookName, $callback);
        $this->container->remove($hookName, $callback);

        expect($this->container->hasCallbacks($hookName))->toBeFalse();
    });

    test('returns true for hook with remaining callbacks after partial removal', function () {
        $hookName = new StringHookName('test_hook');
        $callback1 = new ObjectHookInvoke(fn($v) => $v, 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_other', 20);

        $this->container->add($hookName, $callback1);
        $this->container->add($hookName, $callback2);
        $this->container->remove($hookName, $callback1);

        expect($this->container->hasCallbacks($hookName))->toBeTrue();
    });
});
