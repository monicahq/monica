<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Helpers\ChangelogEntries;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogVoteControllerTest extends TestCase
{
    use RefreshDatabase;

    private const string ENTRY = '01M7NBFWK7BEESY8WABBMXF3E6';

    /** @var array<int|string, mixed> */
    private array $jsonStructure = ['data' => ['type', 'id', 'attributes' => ['direction', 'updated_at']]];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
    }

    #[Test]
    public function it_casts_and_withdraws_a_vote(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->json('PUT', '/api/v1/changelog-entries/'.self::ENTRY.'/vote', ['direction' => 'down'])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.direction', 'down');

        $this->json('DELETE', '/api/v1/changelog-entries/'.self::ENTRY.'/vote')->assertNoContent();

        $this->assertDatabaseMissing('changelog_votes', ['user_id' => $user->id]);
    }

    #[Test]
    public function it_validates_the_direction(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('PUT', '/api/v1/changelog-entries/'.self::ENTRY.'/vote', ['direction' => 'sideways'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('direction');
    }

    #[Test]
    public function it_returns_not_found_for_a_change_nobody_can_see(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('PUT', '/api/v1/changelog-entries/01M7NBFWKFAM1JXYF0A7RKJ865/vote', ['direction' => 'up'])->assertNotFound();
    }
}
