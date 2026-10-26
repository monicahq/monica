<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Nightwatch\Http\Middleware\Sample;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NightwatchSamplingTest extends TestCase
{
    #[Test]
    public function it_always_keeps_the_admin_space(): void
    {
        $this->assertContains(Sample::always(), Route::getRoutes()->getByName('admin.overview.index')->gatherMiddleware());
    }

    #[Test]
    public function it_keeps_one_unmatched_address_in_a_hundred(): void
    {
        $this->assertContains(Sample::rate(0.01), Route::getRoutes()->getByName('notFound.show')->gatherMiddleware());
    }

    #[Test]
    public function it_keeps_one_status_check_in_a_hundred(): void
    {
        $this->assertContains(Sample::rate(0.01), Route::getRoutes()->getByName('api.v1.status')->gatherMiddleware());
    }
}
