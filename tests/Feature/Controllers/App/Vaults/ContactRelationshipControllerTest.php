<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\RelationshipStatusEnum;
use App\Enums\RelationshipTypeEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
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
            ->assertSee('Parent ↔ Child', escape: false)
            ->assertSee('Sibling')
            ->assertSee('Michael Scott');
    }

    #[Test]
    public function it_leaves_a_deactivated_type_out_of_the_picker(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $category = $this->categoryOf($user);
        RelationshipType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Beet farmer',
            'role_a' => 'Beet farmer',
            'role_b' => 'Beet farmer',
        ]);

        $this->actingAs($user)
            ->get(route('contacts.relationships.new', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertDontSee('Beet farmer');
    }

    #[Test]
    public function it_offers_the_way_in_from_the_record(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee(route('contacts.relationships.new', ['vault' => $contact->vault, 'contact' => $contact]), escape: false);
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
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]))
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
    public function it_puts_the_viewed_contact_on_the_other_side_when_the_roles_are_swapped(): void
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
                'swapped' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('relationships', [
            'contact_a_id' => $other->id,
            'contact_b_id' => $contact->id,
        ]);
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
    public function it_writes_somebody_new_down_as_a_regular_contact_when_asked(): void
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
                'creates_record' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contacts', [
            'vault_id' => $contact->vault_id,
            'first_name' => 'Dwight',
            'is_partial' => false,
        ]);
    }

    #[Test]
    public function it_writes_two_people_down_when_neither_name_is_known(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $type = $this->typesOf($user)['parent'];

        foreach (range(1, 2) as $ignored) {
            $this->actingAs($user)
                ->post(route('contacts.relationships.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                    'relationship_type_id' => $type->id,
                    'mode' => 'unknown',
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertEquals(2, Relationship::query()->count());
        $this->assertEquals(2, Contact::query()->where('is_partial', true)->count());
        $this->assertEquals(
            2,
            Relationship::query()->distinct()->pluck('contact_b_id')->count()
        );
    }

    #[Test]
    public function it_refuses_a_type_that_can_no_longer_be_chosen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $other = $this->contactOn($contact->vault, 'Dwight', 'Schrute');
        $category = $this->categoryOf($user);
        $type = RelationshipType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        $this->actingAs($user)
            ->post(route('contacts.relationships.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'relationship_type_id' => $type->id,
                'mode' => 'existing',
                'other_contact_id' => $other->id,
            ])
            ->assertSessionHasErrors('relationship_type_id');

        $this->assertEquals(0, Relationship::query()->count());
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
    public function it_refuses_a_contact_from_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $elsewhere = $this->contactOf($user, 'Dwight', 'Schrute');
        $type = $this->typesOf($user)['parent'];

        $this->actingAs($user)
            ->post(route('contacts.relationships.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'relationship_type_id' => $type->id,
                'mode' => 'existing',
                'other_contact_id' => $elsewhere->id,
            ])
            ->assertNotFound();

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
    public function it_refuses_somebody_in_another_account(): void
    {
        $contact = $this->contactOf(User::factory()->create(), 'Michael', 'Scott');

        $this->actingAs(User::factory()->create())
            ->get(route('contacts.relationships.new', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_somebody_in_another_vault(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user, 'Michael', 'Scott');
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('contacts.relationships.new', ['vault' => $elsewhere, 'contact' => $contact]))
            ->assertNotFound();
    }

    #[Test]
    public function a_visitor_is_sent_to_sign_in(): void
    {
        $contact = $this->contactOf(User::factory()->create(), 'Michael', 'Scott');

        $this->get(route('contacts.relationships.new', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertRedirect(route('auth.login.new'));
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
            'applies_to' => TemplateAppliesToEnum::Contact,
        ], [
            'account_id' => $vault->account_id,
            'template_version_id' => $version->id,
        ]);

        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function publishedVersion(Vault $vault): TemplateVersion
    {
        $default = VaultDefaultTemplate::query()
            ->where('vault_id', $vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->first();

        if ($default !== null) {
            return $default->templateVersion;
        }

        $template = Template::factory()->create([
            'account_id' => $vault->account_id,
            'applies_to' => TemplateAppliesToEnum::Contact,
        ]);

        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
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
