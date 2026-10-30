<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class ContactSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_saves_a_section_and_adds_a_new_category_to_the_list(): void
    {
        Queue::fake();

        [$user, $vault, $dwight, $section, $list] = $this->dwightWithCategories();
        $sales = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales', 'position' => 1]);
        $field = $section->sectionVersion->fields->sole();

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/sections/{$section->id}", [
            'fields' => [$field->id => [$sales->id]],
            'new_choices' => [$field->id => ['Beet farmer']],
        ]);

        $beetFarmer = ValueListItem::query()->where('value_list_id', $list->id)->where('label', 'Beet farmer')->sole();

        $response->assertOk();
        $response->assertJsonPath('data.attributes.categories', [
            ['id' => $sales->id, 'label' => 'Sales'],
            ['id' => $beetFarmer->id, 'label' => 'Beet farmer'],
        ]);
    }

    #[Test]
    public function it_refuses_a_new_category_naming_one_no_longer_offered(): void
    {
        Queue::fake();

        [$user, $vault, $dwight, $section, $list] = $this->dwightWithCategories();
        ValueListItem::factory()->disabled()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Party Planning Committee']);
        $field = $section->sectionVersion->fields->sole();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/sections/{$section->id}", [
            'new_choices' => [$field->id => ['party planning committee']],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fields');

        $this->assertSame(0, FieldValue::query()->where('valuable_id', $dwight->id)->count());
    }

    #[Test]
    public function it_refuses_a_new_category_longer_than_a_label(): void
    {
        [$user, $vault, $dwight, $section] = $this->dwightWithCategories();
        $field = $section->sectionVersion->fields->sole();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/sections/{$section->id}", [
            'new_choices' => [$field->id => [str_repeat('a', 256)]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors("new_choices.{$field->id}.0");
    }

    #[Test]
    public function it_does_not_find_a_section_of_another_structure(): void
    {
        [$user, $vault, $dwight] = $this->dwightWithCategories();
        $elsewhere = StructureVersionSection::factory()->create();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/sections/{$elsewhere->id}", [])
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_contact_through_the_wrong_vault_of_the_same_account(): void
    {
        [$user, , $dwight, $section] = $this->dwightWithCategories();
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$otherVault->uuid}/contacts/{$dwight->uuid}/sections/{$section->id}", [])
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: StructureVersionSection, 4: ValueList} */
    private function dwightWithCategories(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::withFields($user, [['type' => FieldTypeEnum::Multiselect, 'label' => 'Category']]);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $section = StructureVersionSection::query()->where('structure_version_id', $version->id)->sole();
        Field::query()->where('section_version_id', $section->section_version_id)->update(['value_list_id' => $list->id]);
        $dwight = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $version->id,
        ]);

        return [$user, $vault, $dwight, $section, $list];
    }
}
