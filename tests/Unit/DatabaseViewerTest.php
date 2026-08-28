<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use LaraDb\LaraDbServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The database viewer reads every row of every table, so what keeps it out of
 * anywhere it does not belong is worth asserting rather than assuming. It is a
 * development dependency, so a production install built with --no-dev does not
 * carry it at all, and these are the controls that hold everywhere else.
 */
class DatabaseViewerTest extends TestCase
{
    #[Test]
    public function it_is_installed(): void
    {
        $this->assertTrue(
            class_exists(LaraDbServiceProvider::class),
            'Without this the assertions below pass by saying nothing.',
        );
    }

    #[Test]
    public function it_is_off_unless_the_environment_is_local(): void
    {
        $this->assertNull(
            config('laradb.enabled'),
            'Leaving this unset is what limits the viewer to the local environment. Setting it publishes the viewer everywhere.',
        );
    }

    #[Test]
    public function it_is_never_reachable_without_signing_in(): void
    {
        $middleware = config('laradb.middleware');

        $this->assertIsArray($middleware);
        $this->assertContains('web', $middleware);
        $this->assertContains('auth', $middleware);
    }

    #[Test]
    public function it_registers_no_route_outside_the_local_environment(): void
    {
        $this->assertFalse($this->app->environment('local'));

        $names = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $names[] = (string) $route->getName();
        }

        $this->assertEmpty(
            array_filter($names, fn (string $name): bool => str_starts_with($name, 'laradb.')),
        );
    }
}
