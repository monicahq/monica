<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Enums\ValueListEnum;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactSelectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_a_checkbox_for_the_page_and_one_for_each_person(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $michael = $this->contact($vault, 'Michael', 'Scott');
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertOk()
            ->assertSee('x-data="contactSelection(', false)
            ->assertSee('aria-label="Select everybody on this page"', false)
            ->assertSee('aria-label="Select Michael Scott"', false)
            ->assertSee('aria-label="Select Dwight Schrute"', false)
            ->assertSee('data-contact-uuid="'.$michael->uuid.'"', false)
            ->assertSee('data-contact-uuid="'.$dwight->uuid.'"', false);
    }

    #[Test]
    public function it_offers_a_toolbar_for_the_selected_people(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertOk()
            ->assertSee('role="group"', false)
            ->assertSee('aria-label="Actions for the selected people"', false)
            ->assertSee('aria-label="Clear the selection"', false);
    }

    #[Test]
    public function it_offers_the_categories_of_the_account_and_their_removal(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales']);

        $this->contact($vault, 'Jim', 'Halpert');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertOk()
            ->assertSee('Change category')
            ->assertSee('Remove category')
            ->assertSee(route('contacts.categories.update', $vault))
            ->assertSee(route('contacts.deletion.show', $vault));
    }

    #[Test]
    public function it_says_why_the_category_cannot_be_changed_when_the_list_is_empty(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Jim', 'Halpert');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertOk()
            ->assertSee('aria-describedby="contact-categories-empty"', false)
            ->assertSee('Your list of categories is empty.');
    }

    #[Test]
    public function it_offers_to_undo_a_change_of_category(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $jim = $this->contact($vault, 'Jim', 'Halpert');

        $this->actingAs($user)
            ->withSession(['contactCategoryChange' => [
                'category' => 'Sales',
                'changed' => 1,
                'unchanged' => [],
                'previous' => [$jim->uuid => [7]],
            ]])
            ->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertOk()
            ->assertSee('Undo')
            ->assertSee('name="restores"', false)
            ->assertSee('name="contacts[0][contact]" value="'.$jim->uuid.'"', false);
    }

    private function contact(Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'structure_version_id' => $vault->defaultTemplates()->sole()->structure_version_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function vaultReadyForContacts(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Basic contact',
        ]);

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
