<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\StructureAppliesToEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Models\Contact;
use App\Models\File;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'description',
                'colour',
                'position',
                'is_default',
                'contact_count',
                'last_visited_at',
                'faces',
                'upcoming_reminder_count',
                'shows_people',
                'shows_things',
                'shows_knowledge',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_lists_the_vaults_of_the_account_in_the_order_the_account_arranged(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms', 'position' => 2]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.page', 1);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'Dunder Mifflin');
        $response->assertJsonPath('data.1.attributes.name', 'Schrute Farms');
    }

    #[Test]
    public function it_does_not_list_the_vaults_of_another_account(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['name' => 'Vance Refrigeration']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_brings_an_absurd_page_size_back_into_range(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/vaults?per_page=999')->assertJsonPath('meta.per_page', 100);
        $this->json('GET', '/api/v1/vaults?per_page=0')->assertJsonPath('meta.per_page', 1);
        $this->json('GET', '/api/v1/vaults?per_page=-5')->assertJsonPath('meta.per_page', 1);
    }

    #[Test]
    public function it_answers_the_page_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin', 'position' => 1]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms', 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults?per_page=1&page=2');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.page', 2);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'Schrute Farms');
    }

    #[Test]
    public function it_shows_one_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch.',
            'colour' => '#4f8a49',
            'position' => 3,
            'contact_count' => 12,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'vault');
        $response->assertJsonPath('data.id', $vault->uuid);
        $response->assertJsonPath('data.attributes.name', 'Dunder Mifflin');
        $response->assertJsonPath('data.attributes.description', 'Everybody at the Scranton branch.');
        $response->assertJsonPath('data.attributes.colour', '#4f8a49');
        $response->assertJsonPath('data.attributes.position', 3);
        $response->assertJsonPath('data.attributes.is_default', false);
        $response->assertJsonPath('data.attributes.contact_count', 12);
        $response->assertJsonPath('data.attributes.created_at', $vault->created_at->timestamp);
        $response->assertJsonPath('data.links.self', route('api.v1.vaults.show', $vault));
        $response->assertJsonPath('data.links.web', route('vaults.show', $vault));
    }

    #[Test]
    public function it_returns_not_found_for_a_vault_from_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$stranger->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_numeric_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->id}")->assertNotFound();
    }

    #[Test]
    public function it_creates_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch.',
            'colour' => '#4f8a49',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'vault');
        $response->assertJsonPath('data.attributes.name', 'Dunder Mifflin');
        $response->assertJsonPath('data.attributes.description', 'Everybody at the Scranton branch.');
        $response->assertJsonPath('data.attributes.colour', '#4f8a49');
        $response->assertJsonPath('data.attributes.contact_count', 1);

        $vault = Vault::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'colour' => '#4f8a49',
        ]);
        $response->assertJsonPath('data.id', $vault->uuid);
        $response->assertJsonPath('data.links.self', route('api.v1.vaults.show', $vault));
    }

    #[Test]
    public function it_creates_a_vault_without_a_description_or_a_colour(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Schrute Farms',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.description', null);
        $response->assertJsonPath('data.attributes.colour', Vault::DEFAULT_COLOUR);
    }

    #[Test]
    public function it_refuses_a_vault_without_a_name(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', ['description' => 'Nothing to call it.']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('vaults', 0);
    }

    #[Test]
    public function it_changes_the_wording_of_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch.',
            'colour' => '#4f8a49',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => 'The branch, and everybody who walks through it.',
            'colour' => '#9a3f2b',
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.id', $vault->uuid);
        $response->assertJsonPath('data.attributes.name', 'Scranton');
        $response->assertJsonPath('data.attributes.description', 'The branch, and everybody who walks through it.');
        $response->assertJsonPath('data.attributes.colour', '#9a3f2b');

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'name' => 'Scranton',
            'description' => 'The branch, and everybody who walks through it.',
            'colour' => '#9a3f2b',
        ]);
    }

    #[Test]
    public function it_leaves_what_the_vault_holds_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'position' => 4,
            'contact_count' => 12,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 4);
        $response->assertJsonPath('data.attributes.is_default', true);
        $response->assertJsonPath('data.attributes.contact_count', 12);
    }

    #[Test]
    public function it_refuses_a_name_sent_as_null(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => null,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseHas('vaults', ['id' => $vault->id, 'name' => 'Dunder Mifflin']);
    }

    #[Test]
    public function it_returns_not_found_when_changing_a_vault_from_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$stranger->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('vaults', ['id' => $stranger->id, 'name' => 'Vance Refrigeration']);
    }

    #[Test]
    public function it_deletes_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}");

        $response->assertNoContent();
        $this->assertSame('', $response->content());
        $this->assertSoftDeleted('vaults', ['id' => $vault->id]);
    }

    #[Test]
    public function it_returns_not_found_when_deleting_a_vault_from_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$stranger->uuid}")->assertNotFound();

        $this->assertNotSoftDeleted('vaults', ['id' => $stranger->id]);
    }

    #[Test]
    public function it_makes_a_new_vault_the_default_one_when_asked(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'is_default' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.is_default', true);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_leaves_the_default_alone_when_the_request_says_false(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();
        $held = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Schrute Farms',
            'is_default' => false,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.is_default', false);
        $this->assertTrue($held->refresh()->is_default);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_makes_a_vault_the_default_one_on_a_change(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $held = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
            'is_default' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_default', true);
        $this->assertFalse($held->refresh()->is_default);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_says_what_a_new_contact_in_the_vault_starts_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();
        $chosen = $this->publishedContactTemplate($user, 'Work contact');

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('vault_default_templates', [
            'account_id' => $user->account_id,
            'vault_id' => Vault::query()->where('name', 'Dunder Mifflin')->sole()->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
        $this->assertDatabaseHas('contacts', [
            'vault_id' => Vault::query()->where('name', 'Dunder Mifflin')->sole()->id,
            'is_me' => true,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_says_what_a_new_contact_starts_from_on_a_change(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $chosen = $this->publishedContactTemplate($user, 'Work contact');

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
            'structure_version_id' => $chosen->structure->published_version_id,
        ])->assertOk();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_keeps_what_is_left_out_of_an_update(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'The Scranton branch.',
            'colour' => '#9a3f2b',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Sabre',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'name' => 'Sabre',
            'description' => 'The Scranton branch.',
            'colour' => '#9a3f2b',
        ]);
    }

    #[Test]
    public function it_hides_one_section_and_keeps_the_rest(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'shows_knowledge' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'shows_things' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.shows_people', true);
        $response->assertJsonPath('data.attributes.shows_things', false);
        $response->assertJsonPath('data.attributes.shows_knowledge', false);
        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'name' => 'Dunder Mifflin',
            'shows_people' => true,
            'shows_things' => false,
            'shows_knowledge' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_section_visibility_that_is_not_true_or_false(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'shows_people' => 'maybe',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('shows_people');
        $this->assertDatabaseHas('vaults', ['id' => $vault->id, 'shows_people' => true]);
    }

    #[Test]
    public function it_clears_a_field_sent_as_null(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'The Scranton branch.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'description' => null,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'name' => 'Dunder Mifflin',
            'description' => null,
        ]);
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template;
    }

    private function defaultVaultCount(User $user): int
    {
        return Vault::query()
            ->where('account_id', $user->account_id)
            ->where('is_default', true)
            ->count();
    }

    #[Test]
    public function it_refuses_a_vault_past_the_ceiling_with_the_cap_named(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', ['name' => 'Sabre']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertSame(1, $user->account->vaults()->count());
    }

    #[Test]
    public function it_reaches_a_vault_by_either_spelling_of_its_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}")->assertOk()->assertJsonPath('data.id', $vault->uuid);
        $this->json('GET', "/api/v1/vaults/{$vault->uuid}")->assertOk()->assertJsonPath('data.id', $vault->uuid);
    }

    #[Test]
    public function it_does_not_reach_the_readable_address_of_another_account(): void
    {
        $user = User::factory()->create();
        $theirs = Vault::factory()->create(['name' => 'Schrute Farms']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$theirs->slug}")->assertNotFound();
        $this->json('GET', "/api/v1/vaults/{$theirs->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_refuses_to_delete_the_default_vault_while_the_list_of_vaults_is_hidden(): void
    {
        $user = User::factory()->create(['shows_vault_list' => false]);
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('vault');
        $this->assertNotSoftDeleted('vaults', ['id' => $vault->id]);
    }

    #[Test]
    public function it_says_when_each_vault_was_last_opened_and_null_when_it_never_was(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'position' => 1, 'last_visited_at' => '2026-11-20 10:00:00']);
        $neverOpened = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2, 'last_visited_at' => null]);

        Sanctum::actingAs($user);

        $list = $this->json('GET', '/api/v1/vaults');

        $list->assertJsonPath('data.0.attributes.last_visited_at', CarbonImmutable::parse('2026-11-20 10:00:00')->timestamp);
        $list->assertJsonPath('data.1.attributes.last_visited_at', null);
        $this->json('GET', "/api/v1/vaults/{$neverOpened->uuid}")->assertJsonPath('data.attributes.last_visited_at', null);
    }

    #[Test]
    public function it_shows_the_four_newest_listed_contacts_as_faces_and_leaves_out_a_partial_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $contacts = collect(['Michael', 'Dwight', 'Jim', 'Pam', 'Angela'])
            ->map(fn (string $name, int $index): Contact => Contact::factory()->create([
                'vault_id' => $vault->id,
                'first_name' => $name,
                'last_name' => null,
                'created_at' => CarbonImmutable::parse('2026-01-01')->addDays($index),
            ]));
        Contact::factory()->partial()->create(['vault_id' => $vault->id, 'created_at' => '2026-02-01']);

        $angela = $contacts[4];
        $angela->avatar_file_id = File::factory()->image()->used()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ])->id;
        $angela->save();

        Sanctum::actingAs($user);

        $expected = [
            [
                'id' => $angela->uuid,
                'name' => $angela->name,
                'avatar_url' => route('api.v1.contacts.avatar.show', ['vault' => $vault, 'contact' => $angela->uuid]),
            ],
            ['id' => $contacts[3]->uuid, 'name' => $contacts[3]->name, 'avatar_url' => null],
            ['id' => $contacts[2]->uuid, 'name' => $contacts[2]->name, 'avatar_url' => null],
            ['id' => $contacts[1]->uuid, 'name' => $contacts[1]->name, 'avatar_url' => null],
        ];

        $this->json('GET', '/api/v1/vaults')->assertJsonPath('data.0.attributes.faces', $expected);
        $this->json('GET', "/api/v1/vaults/{$vault->uuid}")->assertJsonPath('data.attributes.faces', $expected);
    }

    #[Test]
    public function it_counts_the_reminders_awaiting_an_answer_before_the_month_is_out_overdue_included(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-15 12:00:00', 'UTC'));

        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $reminder = Reminder::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'remindable_type' => $contact->getMorphClass(),
            'remindable_id' => $contact->id,
            'is_active' => true,
        ]);

        foreach (['2026-11-01', '2026-11-20', '2026-12-10'] as $day) {
            ReminderOccurrence::factory()->for($reminder)->create([
                'local_date' => $day,
                'generation_key' => "1:{$day}",
                'scheduled_for' => CarbonImmutable::parse($day),
            ]);
        }

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/vaults')->assertJsonPath('data.0.attributes.upcoming_reminder_count', 2);
        $this->json('GET', "/api/v1/vaults/{$vault->uuid}")->assertJsonPath('data.attributes.upcoming_reminder_count', 2);
    }

    #[Test]
    public function it_lists_the_vault_opened_most_recently_first_when_asked(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin', 'position' => 1, 'last_visited_at' => null]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms', 'position' => 2, 'last_visited_at' => now()->subDay()]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults?sort=recent');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.name', 'Schrute Farms');
        $response->assertJsonPath('data.1.attributes.name', 'Dunder Mifflin');
    }

    #[Test]
    public function it_refuses_an_order_it_does_not_know(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('GET', '/api/v1/vaults?sort=name');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['sort']);
    }

    #[Test]
    public function it_reads_a_page_of_vaults_in_as_many_queries_whatever_its_size(): void
    {
        $user = User::factory()->create();
        $this->vaultWithCardValues($user);
        $this->vaultWithCardValues($user);

        Sanctum::actingAs($user);
        $this->json('GET', '/api/v1/vaults')->assertOk();

        $forTwo = $this->queriesOf(fn () => $this->json('GET', '/api/v1/vaults')->assertJsonCount(2, 'data'));

        $this->vaultWithCardValues($user);
        $this->vaultWithCardValues($user);
        $this->vaultWithCardValues($user);

        $forFive = $this->queriesOf(fn () => $this->json('GET', '/api/v1/vaults')->assertJsonCount(5, 'data'));

        $this->assertSame($forTwo, $forFive);
    }

    private function vaultWithCardValues(User $user): void
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contacts = Contact::factory()->count(2)->create(['vault_id' => $vault->id]);

        $contacts[0]->avatar_file_id = File::factory()->image()->used()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ])->id;
        $contacts[0]->save();

        ReminderOccurrence::factory()->for(Reminder::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'remindable_type' => $contacts[0]->getMorphClass(),
            'remindable_id' => $contacts[0]->id,
            'is_active' => true,
        ]))->create(['local_date' => now()->toDateString()]);
    }

    private function queriesOf(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $request();

        return count(DB::getQueryLog());
    }
}
