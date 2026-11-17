<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactRelationshipControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'relationship_type' => ['id', 'name', 'is_symmetric'],
            'contact_a' => ['id', 'slug', 'name', 'is_partial', 'role'],
            'contact_b' => ['id', 'slug', 'name', 'is_partial', 'role'],
            'status',
            'started_on',
            'ended_on',
            'created_at',
            'updated_at',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_the_relationships_of_a_contact_from_either_side(): void
    {
        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'contact_a_id' => $dwight->id, 'contact_b_id' => $jim->id]);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'contact_a_id' => $dwight->id, 'contact_b_id' => $pam->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_links_two_contacts_of_the_vault(): void
    {
        Queue::fake();
        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships", [
            'relationship_type' => $type->uuid,
            'other_contact' => $pam->slug,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.status', 'current');
        $this->assertDatabaseHas('relationships', ['relationship_type_id' => $type->id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);
    }

    #[Test]
    public function it_refuses_to_link_somebody_to_themselves(): void
    {
        [$user, $vault, $jim] = $this->jimAndPam();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships", [
            'relationship_type' => $type->uuid,
            'other_contact' => $jim->uuid,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['other_contact']);
        $this->assertDatabaseCount('relationships', 0);
    }

    #[Test]
    public function it_does_not_find_another_person_in_another_vault(): void
    {
        [$user, $vault, $jim] = $this->jimAndPam();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['vault_id' => $stamford->id, 'account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships", [
            'relationship_type' => $type->uuid,
            'other_contact' => $karen->uuid,
        ])->assertNotFound();
    }

    #[Test]
    public function it_says_so_when_the_vault_has_no_template_for_new_contacts(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jim']);
        $type = RelationshipType::factory()->directed()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships", [
            'relationship_type' => $type->uuid,
            'first_name' => 'Cece',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['first_name' => 'This vault has no template for new contacts to start from. Choose one in the vault settings.']);
        $this->assertDatabaseCount('relationships', 0);
    }

    #[Test]
    public function it_refuses_an_existing_contact_and_a_name_at_once(): void
    {
        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships", [
            'relationship_type' => $type->uuid,
            'other_contact' => $pam->uuid,
            'first_name' => 'Cece',
        ])->assertJsonValidationErrors(['other_contact']);
    }

    #[Test]
    public function it_names_somebody_who_is_not_written_down_yet(): void
    {
        Queue::fake();
        [$user, $vault, , $pam] = $this->jimAndPam();
        $type = RelationshipType::factory()->directed()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$pam->uuid}/relationships", [
            'relationship_type' => $type->uuid,
            'first_name' => 'Cece',
            'last_name' => 'Halpert',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.contact_b.is_partial', true);
        $this->assertDatabaseHas('contacts', ['vault_id' => $vault->id, 'first_name' => 'Cece', 'last_name' => 'Halpert', 'is_partial' => true]);
    }

    #[Test]
    public function it_shows_a_relationship_through_either_person(): void
    {
        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$pam->uuid}/relationships/{$relationship->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.id', $relationship->uuid);
    }

    #[Test]
    public function it_does_not_find_a_relationship_through_somebody_who_is_not_in_it(): void
    {
        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/relationships/{$relationship->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_contact_through_the_wrong_vault(): void
    {
        [$user, , $jim] = $this->jimAndPam();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$stamford->uuid}/contacts/{$jim->uuid}/relationships")->assertNotFound();
    }

    #[Test]
    public function it_ends_a_relationship_and_keeps_what_was_left_out(): void
    {
        Queue::fake();
        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $relationship = Relationship::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'contact_a_id' => $jim->id,
            'contact_b_id' => $pam->id,
            'started_on' => '2007-05-10',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships/{$relationship->uuid}", [
            'status' => 'ended',
            'ended_on' => '2008-01-15',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.status', 'ended');
        $response->assertJsonPath('data.attributes.started_on', '2007-05-10');
        $response->assertJsonPath('data.attributes.ended_on', '2008-01-15');
        $response->assertJsonPath('data.attributes.relationship_type.id', $relationship->relationshipType->uuid);
    }

    #[Test]
    public function it_refuses_a_status_it_does_not_know(): void
    {
        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships/{$relationship->uuid}", [
            'status' => 'complicated',
        ])->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function it_deletes_a_relationship_and_keeps_both_people(): void
    {
        Queue::fake();
        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$pam->uuid}/relationships/{$relationship->uuid}")->assertNoContent();

        $this->assertSoftDeleted($relationship);
        $this->assertModelExists($jim);
        $this->assertModelExists($pam);
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: Contact} */
    private function jimAndPam(): array
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jim']);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam']);

        return [$user, $vault, $jim, $pam];
    }

    private function vaultReadyForContacts(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        return $vault;
    }
}
