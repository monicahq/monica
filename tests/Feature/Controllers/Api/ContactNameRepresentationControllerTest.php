<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactNameRepresentationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $jsonStructure = [
        'id',
        'kind',
        'language',
        'script',
        'phonetic_system',
        'formatted_name',
        'prefix',
        'given_name',
        'additional_name',
        'family_name',
        'suffix',
        'name_order',
        'position',
        'is_display',
    ];

    #[Test]
    public function it_lists_every_way_the_name_of_a_contact_is_written(): void
    {
        [$user, $vault, $contact] = $this->michael();
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $contact->id, 'script_code' => 'Kana', 'formatted_name' => 'マイケル・スコット']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', route('api.v1.contacts.nameRepresentations', [$vault, $contact]));

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure]]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.is_display', true);
        $response->assertJsonPath('data.1.formatted_name', 'マイケル・スコット');
        $response->assertJsonPath('data.1.is_display', false);
    }

    #[Test]
    public function it_replaces_the_list_and_answers_with_the_contact(): void
    {
        Queue::fake();
        [$user, $vault, $contact] = $this->michael();

        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.contacts.nameRepresentations.update', [$vault, $contact]), [
            'name_representations' => [
                ['id' => $contact->displayNameRepresentation->uuid, 'kind' => 'written', 'given_name' => 'Michael', 'family_name' => 'Scott'],
                ['kind' => 'transliteration', 'language' => 'ja', 'script' => 'Kana', 'formatted_name' => 'マイケル・スコット'],
            ],
            'display' => 1,
            'nickname' => 'Prison Mike',
            'maiden_name' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'マイケル・スコット');
        $response->assertJsonPath('data.attributes.nickname', 'Prison Mike');
        $response->assertJsonCount(2, 'data.attributes.name_representations');
        $response->assertJsonPath('data.attributes.name_representations.1.is_display', true);
    }

    #[Test]
    public function it_keeps_what_is_left_out_of_an_update(): void
    {
        Queue::fake();
        [$user, $vault, $contact] = $this->michael();
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $contact->id, 'script_code' => 'Kana', 'formatted_name' => 'マイケル・スコット']);
        $contact->update(['maiden_name' => 'Scarn']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.contacts.nameRepresentations.update', [$vault, $contact]), [
            'nickname' => 'Prison Mike',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.nickname', 'Prison Mike');
        $response->assertJsonPath('data.attributes.maiden_name', 'Scarn');
        $response->assertJsonCount(2, 'data.attributes.name_representations');
        $response->assertJsonPath('data.attributes.name_representations.0.is_display', true);
        $response->assertJsonPath('data.attributes.name_representations.1.formatted_name', 'マイケル・スコット');
    }

    #[Test]
    public function it_clears_a_field_sent_as_null(): void
    {
        Queue::fake();
        [$user, $vault, $contact] = $this->michael();
        $contact->update(['nickname' => 'Prison Mike']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.contacts.nameRepresentations.update', [$vault, $contact]), [
            'nickname' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.nickname', null);
        $response->assertJsonCount(1, 'data.attributes.name_representations');
    }

    #[Test]
    public function it_refuses_a_pronunciation_with_no_system(): void
    {
        [$user, $vault, $contact] = $this->michael();

        Sanctum::actingAs($user);

        $this->json('PUT', route('api.v1.contacts.nameRepresentations.update', [$vault, $contact]), [
            'name_representations' => [['kind' => 'phonetic', 'formatted_name' => 'Maikeru']],
            'display' => 0,
            'nickname' => null,
            'maiden_name' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors('name_representations.0.phonetic_system');
    }

    #[Test]
    public function it_refuses_a_way_of_writing_that_belongs_to_another_contact(): void
    {
        [$user, $vault, $contact] = $this->michael();
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', route('api.v1.contacts.nameRepresentations.update', [$vault, $contact]), [
            'name_representations' => [['id' => $dwight->displayNameRepresentation->uuid, 'kind' => 'written', 'given_name' => 'Dwight']],
            'display' => 0,
            'nickname' => null,
            'maiden_name' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors('name_representations.0.id');
    }

    #[Test]
    public function it_does_not_find_a_contact_through_another_vault_or_another_account(): void
    {
        [$user, , $contact] = $this->michael();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $stranger = User::factory()->create();

        Sanctum::actingAs($user);
        $this->json('GET', route('api.v1.contacts.nameRepresentations', [$elsewhere, $contact]))->assertNotFound();

        Sanctum::actingAs($stranger);
        $this->json('GET', "/api/v1/vaults/{$contact->vault->uuid}/contacts/{$contact->uuid}/name-representations")->assertNotFound();
    }

    #[Test]
    public function it_does_not_change_a_contact_through_another_vault_or_another_account(): void
    {
        [$user, , $contact] = $this->michael();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $payload = ['name_representations' => [['kind' => 'written', 'given_name' => 'Toby']], 'display' => 0, 'nickname' => null, 'maiden_name' => null];

        Sanctum::actingAs($user);
        $this->json('PUT', route('api.v1.contacts.nameRepresentations.update', [$elsewhere, $contact]), $payload)->assertNotFound();

        Sanctum::actingAs(User::factory()->create());
        $this->json('PUT', "/api/v1/vaults/{$contact->vault->uuid}/contacts/{$contact->uuid}/name-representations", $payload)->assertNotFound();

        $this->assertSame('Michael', $contact->refresh()->first_name);
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function michael(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael', 'last_name' => 'Scott']);

        return [$user, $vault, $contact];
    }
}
