<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Helpers\ChangelogEntries;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogSeenControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_every_change_seen(): void
    {
        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
        $user = User::factory()->create(['created_at' => '2026-11-01 00:00:00']);
        Sanctum::actingAs($user);

        $this->json('PUT', '/api/v1/changelog-entries/seen')->assertNoContent();

        $this->json('GET', '/api/v1/changelog-entries')->assertJsonPath('meta.unseen_count', 0);
    }
}
