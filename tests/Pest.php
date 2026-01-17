<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()
    ->group('hooks', 'memory')
    ->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createMockAction(): object
{
    return new class {
        private int $callCount = 0;
        private array $events = [];

        public function action(...$args): void
        {
            $this->callCount++;
            $this->events[] = [
                'action' => 'action',
                'args' => $args,
            ];
        }

        public function action2(...$args): void
        {
            $this->callCount++;
            $this->events[] = [
                'action' => 'action2',
                'args' => $args,
            ];
        }

        public function filter(...$args): mixed
        {
            $this->callCount++;
            $this->events[] = [
                'filter' => 'filter',
                'args' => $args,
            ];
            return $args[0] ?? null;
        }

        public function filter2(...$args): mixed
        {
            $this->callCount++;
            $this->events[] = [
                'filter' => 'filter2',
                'args' => $args,
            ];
            return $args[0] ?? null;
        }

        public function getCallCount(): int
        {
            return $this->callCount;
        }

        public function getEvents(): array
        {
            return $this->events;
        }

        public static function staticAction(...$args): void
        {
            // Static action callback
        }
    };
}
