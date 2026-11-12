<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactStructureVersionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_contact_onto_a_published_version_it_names(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'account_id' => $user->account_id, 'version_number' => 1]);
        $second = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'account_id' => $user->account_id, 'version_number' => 2]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $first->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template-version", [
            'structure_version_id' => $second->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.structure_version_id', $second->id);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'structure_version_id' => $second->id]);
    }

    #[Test]
    public function it_moves_a_contact_onto_whatever_the_template_publishes_now_when_no_version_is_named(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $old = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'account_id' => $user->account_id, 'version_number' => 1]);
        $current = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'account_id' => $user->account_id, 'version_number' => 2]);

        $template->structure->update(['published_version_id' => $current->id]);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $old->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template-version", []);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.structure_version_id', $current->id);
    }

    #[Test]
    public function it_returns_not_found_for_a_draft_version(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'account_id' => $user->account_id, 'version_number' => 1]);
        $draft = StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'account_id' => $user->account_id,
            'version_number' => 2,
            'status' => VersionStatusEnum::Draft,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $published->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template-version", [
            'structure_version_id' => $draft->id,
        ])->assertNotFound();

        $this->assertEquals($published->id, $contact->refresh()->structure_version_id);
    }

    #[Test]
    public function it_returns_not_found_for_a_version_of_another_template(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'account_id' => $user->account_id, 'version_number' => 1]);
        $elsewhere = StructureVersion::factory()->published()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $published->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template-version", [
            'structure_version_id' => $elsewhere->id,
        ])->assertNotFound();

        $this->assertEquals($published->id, $contact->refresh()->structure_version_id);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template-version", [])->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_reached_through_the_wrong_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$other->uuid}/contacts/{$contact->uuid}/template-version", [])->assertNotFound();
    }
}
