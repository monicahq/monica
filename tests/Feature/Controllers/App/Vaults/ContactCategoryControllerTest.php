<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private ValueList $categories;

    private StructureVersion $version;

    private Field $field;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->categories = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $this->user->account_id]);

        $template = Template::factory()->create(['account_id' => $this->user->account_id]);
        $this->version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = SectionVersion::factory()->create(['account_id' => $this->user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $this->version->id,
            'section_version_id' => $section->id,
        ]);

        $this->field = Field::factory()->create([
            'section_version_id' => $section->id,
            'type' => FieldTypeEnum::Multiselect,
            'label' => 'Category',
            'value_list_id' => $this->categories->id,
        ]);
    }

    #[Test]
    public function it_moves_the_selected_people_and_offers_to_undo(): void
    {
        $sales = $this->category('Sales');
        $jim = $this->contact('Jim', 'Halpert');
        $dwight = $this->contact('Dwight', 'Schrute');

        $this->actingAs($this->user)
            ->put(route('contacts.categories.update', $this->vault), ['contacts' => [
                ['contact' => $jim->uuid, 'categories' => [$sales->id]],
                ['contact' => $dwight->uuid, 'categories' => [$sales->id]],
            ]])
            ->assertRedirect(route('contacts.index', $this->vault))
            ->assertSessionHas('contactCategoryChange', [
                'category' => 'Sales',
                'changed' => 2,
                'unchanged' => [],
                'previous' => [$jim->uuid => [], $dwight->uuid => []],
            ]);

        $this->assertSame([$sales->id], $jim->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());
    }

    #[Test]
    public function it_moves_the_others_when_a_selected_person_is_already_deleted(): void
    {
        $sales = $this->category('Sales');
        $creed = $this->contact('Creed', 'Bratton');
        $creed->delete();
        $jim = $this->contact('Jim', 'Halpert');

        $this->actingAs($this->user)
            ->put(route('contacts.categories.update', $this->vault), ['contacts' => [
                ['contact' => $creed->uuid, 'categories' => [$sales->id]],
                ['contact' => $jim->uuid, 'categories' => [$sales->id]],
            ]])
            ->assertRedirect(route('contacts.index', $this->vault))
            ->assertSessionHas('contactCategoryChange', [
                'category' => 'Sales',
                'changed' => 1,
                'unchanged' => ['That contact does not exist.'],
                'previous' => [$jim->uuid => []],
            ]);
    }

    #[Test]
    public function it_reads_every_category_the_form_sends(): void
    {
        $sales = $this->category('Sales');
        $accounting = $this->category('Accounting');
        $jim = $this->contact('Jim', 'Halpert');
        $angela = $this->contact('Angela', 'Martin');

        $this->actingAs($this->user)
            ->put(route('contacts.categories.update', $this->vault), [
                'restores' => '1',
                'contacts' => [
                    ['contact' => $jim->uuid, 'categories' => [(string) $sales->id]],
                    ['contact' => $angela->uuid, 'categories' => [(string) $accounting->id]],
                ],
            ])
            ->assertRedirect(route('contacts.index', $this->vault));

        $this->assertSame([$sales->id], $jim->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());
        $this->assertSame([$accounting->id], $angela->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());
    }

    #[Test]
    public function it_puts_back_the_categories_the_undo_form_carries(): void
    {
        $sales = $this->category('Sales');
        $accounting = $this->category('Accounting');
        $angela = $this->contact('Angela', 'Martin');
        $this->hold($angela, [$sales->id]);

        $this->actingAs($this->user)
            ->put(route('contacts.categories.update', $this->vault), [
                'restores' => '1',
                'contacts' => [['contact' => $angela->uuid, 'categories' => [$accounting->id]]],
            ])
            ->assertRedirect(route('contacts.index', $this->vault))
            ->assertSessionHas('status', 'The categories of 1 person are back as they were.')
            ->assertSessionMissing('contactCategoryChange');

        $this->assertSame([$accounting->id], $angela->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());
    }

    #[Test]
    public function it_refuses_more_than_five_hundred_people(): void
    {
        $contacts = array_map(fn (): array => ['contact' => (string) Str::uuid()], range(1, 501));

        $this->actingAs($this->user)
            ->put(route('contacts.categories.update', $this->vault), ['contacts' => $contacts])
            ->assertSessionHasErrors('contacts');
    }

    #[Test]
    public function it_does_not_find_a_person_of_another_vault(): void
    {
        $stamford = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $this->user->account_id, 'vault_id' => $stamford->id]);

        $this->actingAs($this->user)
            ->put(route('contacts.categories.update', $this->vault), ['contacts' => [['contact' => $karen->uuid]]])
            ->assertNotFound();
    }

    private function category(string $label): ValueListItem
    {
        return ValueListItem::factory()->create([
            'account_id' => $this->user->account_id,
            'value_list_id' => $this->categories->id,
            'label' => $label,
        ]);
    }

    private function contact(string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'structure_version_id' => $this->version->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    /** @param  list<int>  $itemIds */
    private function hold(Contact $contact, array $itemIds): void
    {
        FieldValue::factory()->create([
            'account_id' => $this->user->account_id,
            'valuable_id' => $contact->id,
            'field_id' => $this->field->id,
            'value_string' => null,
            'value_list_item_ids' => $itemIds,
        ]);
    }
}
