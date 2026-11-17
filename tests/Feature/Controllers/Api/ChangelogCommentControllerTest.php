<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Helpers\ChangelogEntries;
use App\Models\ChangelogComment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    private const string ENTRY = '01M7NBFWK5XNH908RFDGEAJXKN';

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => ['author_name', 'is_mine', 'is_private', 'is_anonymous', 'body', 'replies', 'created_at', 'updated_at'],
    ];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
    }

    #[Test]
    public function it_lists_the_comments_the_person_may_read(): void
    {
        Sanctum::actingAs(User::factory()->create());
        ChangelogComment::factory()->anonymous()->create(['entry_id' => self::ENTRY, 'body' => 'Dwight, you ignorant slut.']);
        ChangelogComment::factory()->private()->create(['entry_id' => self::ENTRY]);

        $this->json('GET', '/api/v1/changelog-entries/'.self::ENTRY.'/comments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => ['*' => $this->jsonStructure]])
            ->assertJsonPath('data.0.attributes.author_name', 'Anonymous');
    }

    #[Test]
    public function it_creates_a_comment(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/changelog-entries/'.self::ENTRY.'/comments', ['body' => 'That is what she said.', 'is_private' => true])
            ->assertCreated()
            ->assertJsonStructure(['data' => $this->jsonStructure])
            ->assertJsonPath('data.attributes.is_private', true)
            ->assertJsonPath('data.attributes.is_mine', true);
    }

    #[Test]
    public function it_validates_the_body_when_creating_a_comment(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/changelog-entries/'.self::ENTRY.'/comments', ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    #[Test]
    public function it_refuses_a_comment_made_only_of_markup(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/changelog-entries/'.self::ENTRY.'/comments', ['body' => '<b></b>'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    #[Test]
    public function it_returns_not_found_for_a_change_that_does_not_exist(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/changelog-entries/01M7NBFWM0EKND6XPWKYPCYYN4/comments')->assertNotFound();
    }
}
