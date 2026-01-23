<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Infra\Memory\Hooks\DeprecatedHookRunHookName;

covers(DeprecatedHookRunHookName::class);

describe('DeprecatedHookRunHookName', function () {
    test('returns deprecated_hook_run as hook name', function () {
        $hookName = new DeprecatedHookRunHookName();

        expect($hookName->getName())->toBe('deprecated_hook_run');
    });

    test('implements HookNameInterface', function () {
        $hookName = new DeprecatedHookRunHookName();

        expect($hookName)->toBeInstanceOf(\SpeedySpec\WP\Hook\Domain\Contracts\HookNameInterface::class);
    });

    test('returns consistent name on multiple calls', function () {
        $hookName = new DeprecatedHookRunHookName();

        $name1 = $hookName->getName();
        $name2 = $hookName->getName();

        expect($name1)->toBe($name2);
        expect($name1)->toBe('deprecated_hook_run');
    });
});
