<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'author',
                'action',
                'parameters',
                'description',
                'source',
                'ip_address',
                'created_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_journal_of_the_account_newest_first(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        Log::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'action' => UserActionEnum::AccountCreation->value,
        ]);

        Log::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ApiKeyCreation->value,
            'parameters' => ['name' => 'Dundie awards bot'],
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/logs');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.type', 'log');
        $response->assertJsonPath('data.0.attributes.action', 'api_key_created');
        $response->assertJsonPath('data.1.attributes.action', 'account_created');
        $response->assertJsonPath('meta.page', 1);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_does_not_list_the_journal_of_another_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $strangerAccount = Account::factory()->create();
        Log::factory()->create(['account_id' => $strangerAccount->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/logs');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    /**
     * An entry nobody is behind is exactly the one worth reading, so it is in
     * the journal rather than filtered out of it.
     */
    #[Test]
    public function it_lists_what_the_application_did_on_its_own(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        Log::factory()->bySystem()->create(['account_id' => $account->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/logs');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.author', 'The system');
        $response->assertJsonPath('data.0.attributes.source', LogSourceEnum::System->value);
        $response->assertJsonPath('data.0.attributes.ip_address', null);
    }

    #[Test]
    public function it_brings_an_absurd_page_size_back_into_range(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        Log::factory()->create(['account_id' => $account->id]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/logs?per_page=100000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);

        $this->json('GET', '/api/v1/logs?per_page=0')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);
    }

    #[Test]
    public function it_answers_the_page_that_was_asked_for(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        Log::factory()->count(3)->create(['account_id' => $account->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/logs?per_page=2&page=2');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.page', 2);
        $response->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function it_shows_one_entry_of_the_journal(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $log = Log::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ApiKeyCreation->value,
            'parameters' => ['name' => 'Dundie awards bot'],
            'ip_address' => '192.168.1.24',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/logs/{$log->id}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.id', (string) $log->id);
        $response->assertJsonPath('data.attributes.author', 'Michael Scott');
        $response->assertJsonPath('data.attributes.action', 'api_key_created');
        $response->assertJsonPath('data.attributes.parameters', ['name' => 'Dundie awards bot']);
        $response->assertJsonPath('data.attributes.description', 'Created the API key called Dundie awards bot');
        $response->assertJsonPath('data.attributes.ip_address', '192.168.1.24');
        $response->assertJsonPath('data.attributes.created_at', $log->created_at->timestamp);
    }

    #[Test]
    public function it_returns_not_found_for_an_entry_of_another_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $strangerAccount = Account::factory()->create();
        $log = Log::factory()->create(['account_id' => $strangerAccount->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/logs/{$log->id}")->assertNotFound();
    }

    #[Test]
    public function it_writes_the_sentence_in_the_language_of_the_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'locale' => 'fr_FR',
        ]);

        $log = Log::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ApiKeyCreation->value,
            'parameters' => ['name' => 'Dundie awards bot'],
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.description', 'A créé la clé API appelée Dundie awards bot');
    }

    /**
     * A value somebody marked sensitive records that it changed and not what
     * to, and until the specification settles how those are kept the column
     * stays out of the API altogether.
     */
    #[Test]
    public function it_does_not_hand_out_what_changed(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $log = Log::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'changes' => ['email' => ['before' => 'michael@dundermifflin.com', 'after' => 'mscott@dundermifflin.com']],
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/logs/{$log->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.attributes.changes')
            ->assertDontSee('mscott@dundermifflin.com');
    }

    #[Test]
    #[DataProvider('endpoints')]
    public function it_refuses_a_caller_without_a_key(string $method, string $url): void
    {
        $this->json($method, $url)->assertUnauthorized();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function endpoints(): array
    {
        return [
            'list' => ['GET', '/api/v1/logs'],
            'show' => ['GET', '/api/v1/logs/1'],
        ];
    }
}
