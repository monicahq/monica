<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\AddTemplateSection;
use App\Mcp\Tools\CreateContact;
use App\Mcp\Tools\DeleteEntityType;
use App\Mcp\Tools\DeleteRelationshipCategory;
use App\Mcp\Tools\DeleteRelationshipType;
use App\Mcp\Tools\DeleteTemplate;
use App\Mcp\Tools\GetContact;
use App\Mcp\Tools\GetMeContact;
use App\Mcp\Tools\ListContacts;
use App\Mcp\Tools\ListTemplateVersions;
use App\Mcp\Tools\ListVaults;
use App\Mcp\Tools\RemoveTemplateSection;
use App\Mcp\Tools\SetContactFavourite;
use App\Mcp\Tools\UpdateContactName;
use App\Models\Contact;
use App\Models\EntityType;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpCompartmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_only_the_vaults_of_the_account_the_key_belongs_to(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        Vault::factory()->create(['name' => 'Vance Refrigeration']);

        EricaServer::actingAs($user)->tool(ListVaults::class)
            ->assertOk()
            ->assertSee('Dunder Mifflin')
            ->assertDontSee('Vance Refrigeration');
    }

    #[Test]
    public function it_reports_a_vault_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $other->uuid])
            ->assertHasErrors(['That vault does not exist.']);
    }

    #[Test]
    public function it_reports_a_contact_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $other->id, 'account_id' => $other->account_id]);

        EricaServer::actingAs($user)->tool(GetContact::class, [
            'vault' => $other->uuid,
            'contact' => $contact->uuid,
        ])->assertHasErrors(['That vault does not exist.']);
    }

    #[Test]
    public function it_reports_a_contact_of_another_vault_of_the_same_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(GetContact::class, [
            'vault' => $asked->uuid,
            'contact' => $contact->uuid,
        ])->assertHasErrors(['That contact does not exist.']);
    }

    #[Test]
    public function it_reports_the_me_contact_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();
        Contact::factory()->me()->create(['vault_id' => $other->id, 'account_id' => $other->account_id]);

        EricaServer::actingAs($user)->tool(GetMeContact::class, ['vault' => $other->uuid])
            ->assertHasErrors(['That vault does not exist.']);
    }

    #[Test]
    public function it_reports_a_template_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = Template::factory()->create();

        EricaServer::actingAs($user)->tool(ListTemplateVersions::class, ['template' => $other->uuid])
            ->assertHasErrors(['That template does not exist.']);
    }

    #[Test]
    public function it_writes_nothing_into_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();

        EricaServer::actingAs($user)->tool(CreateContact::class, [
            'vault' => $other->uuid,
            'first_name' => 'Michael',
        ])->assertHasErrors(['That vault does not exist.']);

        $this->assertDatabaseMissing('contacts', ['first_name' => 'Michael', 'vault_id' => $other->id]);
    }

    #[Test]
    public function it_does_not_rename_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();
        $contact = Contact::factory()->create([
            'vault_id' => $other->id,
            'account_id' => $other->account_id,
            'first_name' => 'Michael',
        ]);

        EricaServer::actingAs($user)->tool(UpdateContactName::class, [
            'vault' => $other->uuid,
            'contact' => $contact->uuid,
            'first_name' => 'Pam',
        ])->assertHasErrors(['That vault does not exist.']);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => 'Michael']);
    }

    #[Test]
    public function it_does_not_favourite_a_contact_of_another_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SetContactFavourite::class, [
            'vault' => $asked->uuid,
            'contact' => $contact->uuid,
            'favourite' => true,
        ])->assertHasErrors(['That contact does not exist.']);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_favourite' => false]);
    }

    #[Test]
    public function it_reports_a_section_of_another_template_as_not_existing(): void
    {
        $user = User::factory()->create();
        $asked = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $asked->structure->id]);
        $elsewhere = Template::factory()->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => StructureVersion::factory()->create(['structure_id' => $elsewhere->structure->id])->id,
            'section_version_id' => SectionVersion::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        EricaServer::actingAs($user)->tool(RemoveTemplateSection::class, [
            'template' => $asked->uuid,
            'version' => 1,
            'section' => (string) $row->id,
        ])->assertHasErrors(['That section does not exist.']);

        $this->assertModelExists($row);
    }

    #[Test]
    public function it_writes_nothing_into_a_template_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = Template::factory()->create();
        StructureVersion::factory()->create(['structure_id' => $other->structure->id]);

        EricaServer::actingAs($user)->tool(AddTemplateSection::class, [
            'template' => $other->uuid,
            'version' => 1,
            'name' => 'Schrute Farms',
        ])->assertHasErrors(['That template does not exist.']);

        $this->assertDatabaseMissing('sections', ['name' => 'Schrute Farms']);
    }

    #[Test]
    public function it_does_not_delete_a_template_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = Template::factory()->create();

        EricaServer::actingAs($user)->tool(DeleteTemplate::class, ['template' => $other->uuid])
            ->assertHasErrors(['That template does not exist.']);

        $this->assertNotSoftDeleted($other);
    }

    #[Test]
    public function it_does_not_delete_an_entity_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = EntityType::factory()->create();

        EricaServer::actingAs($user)->tool(DeleteEntityType::class, ['entity_type' => $other->uuid])
            ->assertHasErrors(['That entity type does not exist.']);

        $this->assertNotSoftDeleted($other);
    }

    #[Test]
    public function it_does_not_delete_a_relationship_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = RelationshipType::factory()->create();

        EricaServer::actingAs($user)->tool(DeleteRelationshipType::class, ['relationship_type' => $other->uuid])
            ->assertHasErrors(['That relationship type does not exist.']);

        $this->assertNotSoftDeleted($other);
    }

    #[Test]
    public function it_does_not_delete_a_relationship_category_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = RelationshipCategory::factory()->create();

        EricaServer::actingAs($user)->tool(DeleteRelationshipCategory::class, ['relationship_category' => $other->uuid])
            ->assertHasErrors(['That relationship category does not exist.']);

        $this->assertNotSoftDeleted($other);
    }

    #[Test]
    public function it_never_says_forbidden_where_it_means_not_found(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $other->uuid])
            ->assertDontSee('forbidden')
            ->assertDontSee('No query results');
    }
}
