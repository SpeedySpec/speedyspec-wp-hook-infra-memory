<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Infra\Memory\ServiceProvider;

covers(ServiceProvider::class);

describe('ServiceProvider', function () {
    test('can be instantiated', function () {
        $provider = new ServiceProvider();

        expect($provider)->toBeInstanceOf(ServiceProvider::class);
    });

    test('boot method can be called', function () {
        $provider = new ServiceProvider();

        $provider->boot();

        expect(true)->toBeTrue();
    });

    test('register method can be called', function () {
        $provider = new ServiceProvider();

        // Note: register() depends on HookServiceContainer singleton
        // This test verifies it can be invoked without error
        expect(fn() => $provider->register())->not->toThrow(Exception::class);
    });

    test('can be invoked as callable', function () {
        $provider = new ServiceProvider();

        // Note: __invoke() calls boot() and register()
        expect(fn() => $provider())->not->toThrow(Exception::class);
    });
});
