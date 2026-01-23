<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

covers(HookRunAmountService::class);

describe('HookRunAmountService', function () {
    test('returns 0 for hook that was never run', function () {
        $service = new HookRunAmountService();
        $hookName = new StringHookName('test_hook');

        $runAmount = $service->getRunAmount($hookName);

        expect($runAmount)->toBe(0);
    });

    test('increments run amount for hook', function () {
        $service = new HookRunAmountService();
        $hookName = new StringHookName('test_hook');

        $service->incrementRunAmount($hookName);

        expect($service->getRunAmount($hookName))->toBe(1);
    });

    test('increments run amount multiple times', function () {
        $service = new HookRunAmountService();
        $hookName = new StringHookName('test_hook');

        $service->incrementRunAmount($hookName);
        $service->incrementRunAmount($hookName);
        $service->incrementRunAmount($hookName);

        expect($service->getRunAmount($hookName))->toBe(3);
    });

    test('tracks run amounts separately for different hooks', function () {
        $service = new HookRunAmountService();
        $hookName1 = new StringHookName('hook_one');
        $hookName2 = new StringHookName('hook_two');

        $service->incrementRunAmount($hookName1);
        $service->incrementRunAmount($hookName1);
        $service->incrementRunAmount($hookName2);

        expect($service->getRunAmount($hookName1))->toBe(2);
        expect($service->getRunAmount($hookName2))->toBe(1);
    });

    test('different StringHookName instances with same name share count', function () {
        $service = new HookRunAmountService();
        $hookName1 = new StringHookName('same_hook');
        $hookName2 = new StringHookName('same_hook');

        $service->incrementRunAmount($hookName1);

        expect($service->getRunAmount($hookName2))->toBe(1);
    });
});
