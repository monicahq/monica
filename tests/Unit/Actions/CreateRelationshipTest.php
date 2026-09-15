<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateRelationship;
use App\Enums\RelationshipStatusEnum;
use App\Enums\RelationshipTypeEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotLinkSomebodyToThemselves;
use App\Exceptions\RelationshipTypeIsNotAvailable;
use App\Exceptions\RelationshipWouldCrossVaults;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_relationship_between_two_people_already_written_down(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild);

        $relationship = new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $type,
            otherContact: $dwight,
        )->execute();

        $this->assertInstanceOf(Relationship::class, $relationship);
        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $michael->id,
            'contact_b_id' => $dwight->id,
            'status' => RelationshipStatusEnum::Current->value,
        ]);
    }

    #[Test]
    public function it_writes_one_row_and_never_a_mirror(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');

        new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            otherContact: $dwight,
        )->execute();

        $this->assertEquals(1, Relationship::query()->count());
    }

    #[Test]
    public function it_records_it_in_the_history(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');

        new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            otherContact: $dwight,
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipCreation);
    }

    #[Test]
    public function it_keeps_the_viewed_contact_on_the_first_side_of_a_directed_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');

        $relationship = new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            otherContact: $dwight,
        )->execute();

        $this->assertEquals($michael->id, $relationship->contact_a_id);
        $this->assertEquals($dwight->id, $relationship->contact_b_id);
    }

    #[Test]
    public function it_moves_the_viewed_contact_to_the_other_side_when_the_roles_are_swapped(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');

        $relationship = new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            otherContact: $dwight,
            swapped: true,
        )->execute();

        $this->assertEquals($dwight->id, $relationship->contact_a_id);
        $this->assertEquals($michael->id, $relationship->contact_b_id);
    }

    #[Test]
    public function it_stores_a_symmetric_pair_in_one_canonical_order(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');
        $type = $this->typeOf($user, RelationshipTypeEnum::Sibling);

        $fromMichael = new CreateRelationship(
            user: $user, vault: $vault, contact: $michael, relationshipType: $type, otherContact: $dwight,
        )->execute();

        $fromDwight = new CreateRelationship(
            user: $user, vault: $vault, contact: $dwight, relationshipType: $type, otherContact: $michael,
        )->execute();

        $this->assertEquals($fromMichael->contact_a_id, $fromDwight->contact_a_id);
        $this->assertEquals($fromMichael->contact_b_id, $fromDwight->contact_b_id);
        $this->assertEquals(min($michael->id, $dwight->id), $fromMichael->contact_a_id);
    }

    #[Test]
    public function it_writes_the_other_person_down_when_they_are_new(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');

        $relationship = new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            firstName: 'Dwight',
            lastName: 'Schrute',
        )->execute();

        $this->assertDatabaseHas('contacts', [
            'id' => $relationship->contact_b_id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'is_partial' => true,
        ]);
    }

    #[Test]
    public function it_writes_the_other_person_down_as_a_regular_contact_when_asked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');

        $relationship = new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            firstName: 'Dwight',
            createsRecord: true,
        )->execute();

        $this->assertDatabaseHas('contacts', [
            'id' => $relationship->contact_b_id,
            'first_name' => 'Dwight',
            'is_partial' => false,
        ]);
    }

    #[Test]
    public function it_writes_two_separate_people_down_when_neither_name_is_known(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild);

        $first = new CreateRelationship(
            user: $user, vault: $vault, contact: $michael, relationshipType: $type,
        )->execute();

        $second = new CreateRelationship(
            user: $user, vault: $vault, contact: $michael, relationshipType: $type,
        )->execute();

        $this->assertNotEquals($first->contact_b_id, $second->contact_b_id);
        $this->assertEquals(2, Relationship::query()->count());
        $this->assertTrue(Contact::query()->findOrFail($first->contact_b_id)->isNameless());
    }

    #[Test]
    public function it_writes_nothing_when_the_link_is_refused(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild);

        new CreateRelationship(
            user: $user, vault: $vault, contact: $michael, relationshipType: $type, otherContact: $dwight,
        )->execute();

        $contactsBefore = Contact::query()->count();

        try {
            new CreateRelationship(
                user: $user, vault: $vault, contact: $dwight, relationshipType: $type, otherContact: $michael,
            )->execute();
            $this->fail('The loop should have been refused.');
        } catch (RelationshipWouldLoopParentage) {
        }

        $this->assertEquals($contactsBefore, Contact::query()->count());
        $this->assertEquals(1, Relationship::query()->count());
    }

    #[Test]
    public function it_refuses_linking_somebody_to_themselves(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');

        $this->expectException(CannotLinkSomebodyToThemselves::class);

        new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            otherContact: $michael,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_contact_in_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $elsewhere = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($elsewhere, 'Dwight', 'Schrute');

        $this->expectException(RelationshipWouldCrossVaults::class);

        new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            otherContact: $dwight,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');

        $stranger = User::factory()->create();
        $theirContact = $this->contactOn($this->vaultOf($stranger), 'Jim', 'Halpert');

        $this->expectException(ModelNotFoundException::class);

        new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::ParentChild),
            otherContact: $theirContact,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');

        $this->expectException(ModelNotFoundException::class);

        new CreateRelationship(
            user: $user,
            vault: $vault,
            contact: $michael,
            relationshipType: $this->typeOf(User::factory()->create(), RelationshipTypeEnum::ParentChild),
            otherContact: $dwight,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_type_that_can_no_longer_be_chosen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild);
        $type->update(['is_active' => false]);

        $this->expectException(RelationshipTypeIsNotAvailable::class);

        new CreateRelationship(
            user: $user, vault: $vault, contact: $michael, relationshipType: $type, otherContact: $dwight,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_parentage_link_that_would_close_a_loop(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');
        $angela = $this->contactOn($vault, 'Angela', 'Martin');
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild);

        new CreateRelationship(user: $user, vault: $vault, contact: $michael, relationshipType: $type, otherContact: $dwight)->execute();
        new CreateRelationship(user: $user, vault: $vault, contact: $dwight, relationshipType: $type, otherContact: $angela)->execute();

        $this->expectException(RelationshipWouldLoopParentage::class);

        new CreateRelationship(user: $user, vault: $vault, contact: $angela, relationshipType: $type, otherContact: $michael)->execute();
    }

    #[Test]
    public function a_custom_type_named_parent_never_enters_the_loop_check(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');

        $impostor = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $this->categoryOf($user)->id,
            'name' => 'Parent',
            'role_a' => 'Parent',
            'role_b' => 'Child',
        ]);

        new CreateRelationship(user: $user, vault: $vault, contact: $michael, relationshipType: $impostor, otherContact: $dwight)->execute();
        new CreateRelationship(user: $user, vault: $vault, contact: $dwight, relationshipType: $impostor, otherContact: $michael)->execute();

        $this->assertEquals(2, Relationship::query()->count());
    }

    #[Test]
    public function it_lets_the_same_pair_hold_several_relationships(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');

        new CreateRelationship(user: $user, vault: $vault, contact: $michael, relationshipType: $this->typeOf($user, RelationshipTypeEnum::Sibling), otherContact: $dwight)->execute();
        new CreateRelationship(user: $user, vault: $vault, contact: $michael, relationshipType: $this->typeOf($user, RelationshipTypeEnum::Colleague), otherContact: $dwight)->execute();

        $this->assertEquals(2, Relationship::query()->count());
    }

    private function vaultOf(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->create([
            'account_id' => $vault->account_id,
        ]);

        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $version->update(['status' => VersionStatusEnum::Published]);

        VaultDefaultTemplate::query()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact,
            'structure_version_id' => $version->id,
        ]);

        return $vault->refresh();
    }

    private function contactOn(Vault $vault, string $firstName, string $lastName): Contact
    {
        $default = VaultDefaultTemplate::query()
            ->where('vault_id', $vault->id)
            ->where('applies_to', StructureAppliesToEnum::Contact)
            ->firstOrFail();

        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $default->structure_version_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function categoryOf(User $user): RelationshipCategory
    {
        return RelationshipCategory::query()->firstOrCreate(
            ['account_id' => $user->account_id, 'name' => 'Family'],
            ['position' => 1],
        );
    }

    private function typeOf(User $user, RelationshipTypeEnum $case): RelationshipType
    {
        return RelationshipType::factory()->system($case)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $this->categoryOf($user)->id,
        ]);
    }
}
