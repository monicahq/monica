<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Helpers\ChangelogEntries;
use App\Models\ChangelogSeenEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'entry_type',
            'area',
            'title',
            'body',
            'body_html',
            'link',
            'day',
            'is_seen',
            'my_vote',
            'comment_count',
            'published_at',
        ],
        'links' => ['comments', 'web'],
    ];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
    }

    #[Test]
    public function it_lists_the_changes_of_the_period_in_the_order_of_the_screen(): void
    {
        $user = User::factory()->create(['created_at' => '2026-11-01 00:00:00']);
        ChangelogSeenEntry::factory()->create(['user_id' => $user->id, 'entry_id' => '01M7NBFWK5XNH908RFDGEAJXKN']);
        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/changelog-entries');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total', 'period', 'unseen_count', 'days', 'features', 'improvements', 'fixes']]);
        $response->assertJsonPath('data.1.attributes.is_seen', true);
        $response->assertJsonPath('data.0.attributes.is_seen', false);
        $response->assertJsonPath('meta.unseen_count', 3);
        $response->assertJsonPath('meta.fixes', 2);
    }
}
