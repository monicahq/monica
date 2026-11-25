<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicDirectoryRouteConflictTest extends TestCase
{
    #[Test]
    public function no_directory_of_public_takes_the_path_of_a_route(): void
    {
        $paths = collect(Route::getRoutes()->getRoutes())
            ->map(fn (RoutingRoute $route): string => $route->uri())
            ->unique();

        $directories = collect(glob(public_path('*'), GLOB_ONLYDIR) ?: [])
            ->map(fn (string $directory): string => basename($directory));

        foreach ($directories as $directory) {
            $this->assertNotContains(
                $directory,
                $paths,
                "The web server serves `public/{$directory}` itself, so the route `/{$directory}` never reaches the application.",
            );
        }
    }
}
