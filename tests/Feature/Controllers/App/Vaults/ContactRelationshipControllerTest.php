<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\RelationshipStatusEnum;
use App\Enums\RelationshipTypeEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactRelationshipControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_form_with_the_types_the_account_may_choose(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $this->typesOf($user);

        $this->actingAs($user)
            ->get(route('contacts.relationships.new', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee('Michael Scott');
    }

    #[Test]
    public function it_records_a_relationship_to_somebody_already_written_down(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $other = $this->contactOn($contact->vault, 'Dwight', 'Schrute');
        $type = $this->typesOf($user)['parent'];

        $this->actingAs($user)
            ->post(route('contacts.relationships.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'relationship_type_id' => $type->id,
                'mode' => 'existing',
                'other_contact_id' => $other->id,
            ])
            ->assertRedirect(route('contacts.show', [
                'vault' => $contact->vault,
                'contact' => $contact,
                'page' => ContactViewModel::PAGE_RELATIONSHIPS,
            ]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('relationships', [
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $contact->id,
            'contact_b_id' => $other->id,
            'status' => RelationshipStatusEnum::Current->value,
        ]);

        $this->assertEquals(1, Relationship::query()->count());
    }

    #[Test]
    public function it_writes_somebody_new_down_and_keeps_them_out_of_the_people_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $type = $this->typesOf($user)['parent'];

        $this->actingAs($user)
            ->post(route('contacts.relationships.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'relationship_type_id' => $type->id,
                'mode' => 'known',
                'first_name' => 'Dwight',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contacts', [
            'vault_id' => $contact->vault_id,
            'first_name' => 'Dwight',
            'is_partial' => true,
        ]);

        $this->assertEquals(1, Relationship::query()->count());
    }

    #[Test]
    public function it_refuses_linking_somebody_to_themselves(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $type = $this->typesOf($user)['parent'];

        $this->actingAs($user)
            ->post(route('contacts.relationships.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'relationship_type_id' => $type->id,
                'mode' => 'existing',
                'other_contact_id' => $contact->id,
            ])
            ->assertSessionHasErrors('other_contact_id');

        $this->assertEquals(0, Relationship::query()->count());
    }

    #[Test]
    public function it_asks_for_a_type_and_a_way_of_naming_the_other_side(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');

        $this->actingAs($user)
            ->post(route('contacts.relationships.create', ['vault' => $contact->vault, 'contact' => $contact]), [])
            ->assertSessionHasErrors(['relationship_type_id', 'mode']);
    }

    #[Test]
    public function it_asks_for_a_first_name_when_the_name_is_said_to_be_known(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $type = $this->typesOf($user)['parent'];

        $this->actingAs($user)
            ->post(route('contacts.relationships.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'relationship_type_id' => $type->id,
                'mode' => 'known',
            ])
            ->assertSessionHasErrors('first_name');
    }

    #[Test]
    public function it_opens_the_form_that_corrects_a_relationship(): void
    {
        $user = User::factory()->create();
        $relationship = $this->relationshipOf($user);
        $contact = Contact::query()->findOrFail($relationship->contact_a_id);

        $this->actingAs($user)
            ->get(route('contacts.relationships.edit', [
                'vault' => $relationship->vault,
                'contact' => $contact,
                'relationship' => $relationship,
            ]))
            ->assertStatus(200);
    }

    #[Test]
    public function it_changes_a_relationship(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationship = $this->relationshipOf($user);
        $contact = Contact::query()->findOrFail($relationship->contact_a_id);
        $sibling = RelationshipType::query()
            ->where('account_id', $user->account_id)
            ->where('key', RelationshipTypeEnum::Sibling->value)
            ->firstOrFail();

        $this->actingAs($user)
            ->put(route('contacts.relationships.update', [
                'vault' => $relationship->vault,
                'contact' => $contact,
                'relationship' => $relationship,
            ]), [
                'relationship' => "{$sibling->id}-a",
                'has_ended' => '1',
                'started_on' => '1990-04-01',
                'ended_on' => '2001-06-30',
            ])
            ->assertRedirect(route('contacts.show', [
                'vault' => $relationship->vault,
                'contact' => $contact,
                'page' => 'relationships',
            ]));

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'relationship_type_id' => $sibling->id,
            'status' => RelationshipStatusEnum::Ended->value,
        ]);
    }

    #[Test]
    public function it_keeps_a_relationship_going_when_the_box_is_left_unticked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationship = $this->relationshipOf($user);
        $relationship->update(['status' => RelationshipStatusEnum::Ended, 'ended_on' => '2001-06-30']);
        $contact = Contact::query()->findOrFail($relationship->contact_a_id);

        $this->actingAs($user)
            ->put(route('contacts.relationships.update', [
                'vault' => $relationship->vault,
                'contact' => $contact,
                'relationship' => $relationship,
            ]), [
                'relationship' => "{$relationship->relationship_type_id}-a",
                'ended_on' => '2001-06-30',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'status' => RelationshipStatusEnum::Current->value,
            'ended_on' => null,
        ]);
    }

    #[Test]
    public function it_refuses_a_relationship_value_it_cannot_read(): void
    {
        $user = User::factory()->create();
        $relationship = $this->relationshipOf($user);
        $contact = Contact::query()->findOrFail($relationship->contact_a_id);

        $this->actingAs($user)
            ->put(route('contacts.relationships.update', [
                'vault' => $relationship->vault,
                'contact' => $contact,
                'relationship' => $relationship,
            ]), ['relationship' => "{$relationship->relationship_type_id}-x"])
            ->assertSessionHasErrors('relationship');
    }

    #[Test]
    public function it_refuses_a_relationship_that_does_not_involve_the_contact(): void
    {
        $user = User::factory()->create();
        $relationship = $this->relationshipOf($user);
        $stray = $this->contactOn($relationship->vault, 'Rachel', 'Green');
        $urls = [
            'vault' => $relationship->vault,
            'contact' => $stray,
            'relationship' => $relationship,
        ];

        $this->actingAs($user)
            ->get(route('contacts.relationships.edit', $urls))
            ->assertStatus(404);

        $this->actingAs($user)
            ->put(route('contacts.relationships.update', $urls), ['relationship' => "{$relationship->relationship_type_id}-a"])
            ->assertStatus(404);
    }

    #[Test]
    public function it_removes_a_relationship(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationship = $this->relationshipOf($user);
        $contact = Contact::query()->findOrFail($relationship->contact_a_id);

        $this->actingAs($user)
            ->delete(route('contacts.relationships.destroy', [
                'vault' => $relationship->vault,
                'contact' => $contact,
                'relationship' => $relationship,
            ]))
            ->assertRedirect(route('contacts.show', [
                'vault' => $relationship->vault,
                'contact' => $contact,
                'page' => 'relationships',
            ]));

        $this->assertSoftDeleted($relationship);
        $this->assertModelExists($contact);
    }

    #[Test]
    public function it_refuses_to_remove_a_relationship_that_does_not_involve_the_contact(): void
    {
        $user = User::factory()->create();
        $michael = $this->contactOf($user, 'Michael', 'Scott');
        $dwight = $this->contactOn($michael->vault, 'Dwight', 'Schrute');
        $jim = $this->contactOn($michael->vault, 'Jim', 'Halpert');
        $relationship = Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $michael->vault_id,
            'relationship_type_id' => $this->typesOf($user)['sibling']->id,
            'contact_a_id' => $jim->id,
            'contact_b_id' => $dwight->id,
        ]);

        $this->actingAs($user)
            ->delete(route('contacts.relationships.destroy', [
                'vault' => $michael->vault,
                'contact' => $michael,
                'relationship' => $relationship,
            ]))
            ->assertStatus(404);

        $this->assertNotSoftDeleted($relationship);
    }

    private function relationshipOf(User $user): Relationship
    {
        $judy = $this->contactOf($user, 'Judy', 'Geller');
        $ross = $this->contactOn($judy->vault, 'Ross', 'Geller');

        return Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $judy->vault_id,
            'relationship_type_id' => $this->typesOf($user)['parent']->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);
    }

    private function contactOf(User $user, string $firstName, string $lastName): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return $this->contactOn($vault, $firstName, $lastName);
    }

    private function contactOn(Vault $vault, string $firstName, string $lastName): Contact
    {
        $version = $this->publishedVersion($vault);

        VaultDefaultTemplate::query()->firstOrCreate([
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact,
        ], [
            'account_id' => $vault->account_id,
            'structure_version_id' => $version->id,
        ]);

        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function publishedVersion(Vault $vault): StructureVersion
    {
        $default = VaultDefaultTemplate::query()
            ->where('vault_id', $vault->id)
            ->where('applies_to', StructureAppliesToEnum::Contact)
            ->first();

        if ($default !== null) {
            return $default->structureVersion;
        }

        $template = Template::factory()->create([
            'account_id' => $vault->account_id,
        ]);

        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    private function categoryOf(User $user): RelationshipCategory
    {
        return RelationshipCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Family',
            'position' => 1,
        ]);
    }

    /** @return array<string, RelationshipType> */
    private function typesOf(User $user): array
    {
        $category = $this->categoryOf($user);

        return [
            'parent' => RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create([
                'account_id' => $user->account_id,
                'relationship_category_id' => $category->id,
                'position' => 1,
            ]),
            'sibling' => RelationshipType::factory()->system(RelationshipTypeEnum::Sibling)->create([
                'account_id' => $user->account_id,
                'relationship_category_id' => $category->id,
                'position' => 2,
            ]),
        ];
    }
}
