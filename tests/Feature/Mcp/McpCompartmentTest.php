<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateContact;
use App\Mcp\Tools\GetContact;
use App\Mcp\Tools\ListContacts;
use App\Mcp\Tools\ListTemplateVersions;
use App\Mcp\Tools\ListVaults;
use App\Mcp\Tools\RemoveTemplateSection;
use App\Mcp\Tools\SetContactFavourite;
use App\Models\Contact;
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

        MonicaServer::actingAs($user)->tool(ListVaults::class)
            ->assertOk()
            ->assertSee('Dunder Mifflin')
            ->assertDontSee('Vance Refrigeration');
    }

    #[Test]
    public function it_reports_a_vault_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();

        MonicaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $other->uuid])
            ->assertHasErrors();
    }

    #[Test]
    public function it_reports_a_contact_of_another_vault_of_the_same_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(GetContact::class, [
            'vault' => $asked->uuid,
            'contact' => $contact->uuid,
        ])->assertHasErrors();
    }

    #[Test]
    public function it_reports_a_template_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = Template::factory()->create();

        MonicaServer::actingAs($user)->tool(ListTemplateVersions::class, ['template' => $other->uuid])
            ->assertHasErrors();
    }

    #[Test]
    public function it_writes_nothing_into_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();

        MonicaServer::actingAs($user)->tool(CreateContact::class, [
            'vault' => $other->uuid,
            'first_name' => 'Michael',
        ])->assertHasErrors();

        $this->assertDatabaseMissing('contacts', ['first_name' => 'Michael', 'vault_id' => $other->id]);
    }

    #[Test]
    public function it_does_not_favourite_a_contact_of_another_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(SetContactFavourite::class, [
            'vault' => $asked->uuid,
            'contact' => $contact->uuid,
            'favourite' => true,
        ])->assertHasErrors();

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

        MonicaServer::actingAs($user)->tool(RemoveTemplateSection::class, [
            'template' => $asked->uuid,
            'version' => 1,
            'section' => (string) $row->id,
        ])->assertHasErrors();

        $this->assertModelExists($row);
    }
}
