<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateRelationship;
use App\Enums\RelationshipStatusEnum;
use App\Enums\RelationshipTypeEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\RelationshipDatesAreOutOfOrder;
use App\Exceptions\RelationshipTypeIsNotAvailable;
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
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_nature_of_a_relationship(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $sibling = $this->typeOf($user, RelationshipTypeEnum::Sibling);

        $changed = new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $sibling,
            status: RelationshipStatusEnum::Current,
        )->execute();

        $this->assertInstanceOf(Relationship::class, $changed);
        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'relationship_type_id' => $sibling->id,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipUpdate
            && $job->parameters['name'] === 'Judy Geller'
            && $job->parameters['other'] === 'Ross Geller');
    }

    #[Test]
    public function it_gives_the_contact_the_role_it_is_said_to_hold(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $judy = $relationship->contactA;
        $ross = $relationship->contactB;

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $judy,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
            contactHoldsRoleA: false,
        )->execute();

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'contact_a_id' => $ross->id,
            'contact_b_id' => $judy->id,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipUpdate
            && $job->parameters['name'] === 'Ross Geller'
            && $job->parameters['other'] === 'Judy Geller');

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $judy,
            relationship: $relationship->refresh(),
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
            contactHoldsRoleA: true,
        )->execute();

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);
    }

    #[Test]
    public function it_refuses_a_contact_the_relationship_does_not_involve(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        $this->expectException(ModelNotFoundException::class);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $this->contactOn($vault, 'Rachel', 'Green'),
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
        )->execute();
    }

    #[Test]
    public function it_puts_a_symmetric_nature_into_its_one_canonical_order(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $lower = min($relationship->contact_a_id, $relationship->contact_b_id);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $this->typeOf($user, RelationshipTypeEnum::Sibling),
            status: RelationshipStatusEnum::Current,
            contactHoldsRoleA: false,
        )->execute();

        $this->assertDatabaseHas('relationships', ['id' => $relationship->id, 'contact_a_id' => $lower]);
    }

    #[Test]
    public function it_marks_a_relationship_as_ended_and_current_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Ended,
            endedOn: CarbonImmutable::parse('2001-06-30'),
        )->execute();

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'status' => RelationshipStatusEnum::Ended->value,
        ]);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship->refresh(),
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
            endedOn: CarbonImmutable::parse('2001-06-30'),
        )->execute();

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'status' => RelationshipStatusEnum::Current->value,
        ]);
        $this->assertNull($relationship->refresh()->ended_on);
    }

    #[Test]
    public function it_ends_a_relationship_on_a_date_nobody_wrote_down(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Ended,
        )->execute();

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'status' => RelationshipStatusEnum::Ended->value,
            'ended_on' => null,
        ]);
    }

    #[Test]
    public function it_renames_a_partial_contact_on_the_other_side(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $relationship->contactB->update(['is_partial' => true]);
        $relationship->contactB->displayNameRepresentation->update(['given_name' => 'Ben', 'family_name' => null]);
        $relationship->contactB->refresh();

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
            otherFirstName: 'Ben',
            otherLastName: 'Geller',
        )->execute();

        $this->assertDatabaseHas('contacts', [
            'id' => $relationship->contact_b_id,
            'first_name' => 'Ben',
            'last_name' => 'Geller',
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactNameUpdate
            && $job->parameters['name'] === 'Ben Geller');
    }

    #[Test]
    public function it_keeps_every_other_part_of_the_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $relationship->contactB->update(['is_partial' => true, 'nickname' => 'Benny']);
        $relationship->contactB->displayNameRepresentation->update(['additional_name' => 'Hilda']);
        $relationship->contactB->refresh();

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
            otherFirstName: 'Ben',
            otherLastName: 'Geller',
        )->execute();

        $this->assertDatabaseHas('contacts', [
            'id' => $relationship->contact_b_id,
            'first_name' => 'Ben',
            'middle_name' => 'Hilda',
            'nickname' => 'Benny',
        ]);
    }

    #[Test]
    public function it_leaves_the_name_alone_when_none_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $relationship->contactB->update(['is_partial' => true]);
        $relationship->contactB->displayNameRepresentation->delete();

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
        )->execute();

        $this->assertTrue($relationship->contactB->refresh()->isNameless());
        Queue::assertNotPushed(LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactNameUpdate);
    }

    #[Test]
    public function it_gives_a_partial_contact_a_record_of_their_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $relationship->contactB->update(['is_partial' => true]);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
            createsRecord: true,
        )->execute();

        $this->assertDatabaseHas('contacts', [
            'id' => $relationship->contact_b_id,
            'is_partial' => false,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactPromoted);
    }

    #[Test]
    public function it_refuses_a_record_once_the_free_offer_is_full(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $relationship->contactB->update(['is_partial' => true]);

        try {
            new UpdateRelationship(
                user: $user,
                vault: $vault,
                contact: $relationship->contactA,
                relationship: $relationship,
                relationshipType: $relationship->relationshipType,
                status: RelationshipStatusEnum::Current,
                createsRecord: true,
            )->execute();

            $this->fail('The record was given past the ceiling.');
        } catch (AuthorizationException $e) {
            $this->assertSame('The free plan holds 1 contact across every vault, and this account already has it. Full access lifts the ceiling.', $e->getMessage());
        }

        $this->assertDatabaseHas('contacts', [
            'id' => $relationship->contact_b_id,
            'is_partial' => true,
        ]);
    }

    #[Test]
    public function it_refuses_a_record_for_a_contact_who_already_has_one(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        $this->expectException(ModelNotFoundException::class);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
            createsRecord: true,
        )->execute();
    }

    #[Test]
    public function it_refuses_to_rename_a_contact_with_a_record(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        $this->expectException(ModelNotFoundException::class);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
            otherFirstName: 'Emma',
        )->execute();
    }

    #[Test]
    public function it_leaves_the_name_unchanged_when_the_change_is_refused(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $parentage = $this->typeOf($user, RelationshipTypeEnum::ParentChild);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');
        $judy->update(['is_partial' => true]);

        Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $parentage->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);

        $sibling = Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $this->typeOf($user, RelationshipTypeEnum::Sibling)->id,
            'contact_a_id' => $ross->id,
            'contact_b_id' => $judy->id,
        ]);

        try {
            new UpdateRelationship(
                user: $user,
                vault: $vault,
                contact: $ross,
                relationship: $sibling,
                relationshipType: $parentage,
                status: RelationshipStatusEnum::Current,
                otherFirstName: 'Sandra',
            )->execute();

            $this->fail('The change should have been refused.');
        } catch (RelationshipWouldLoopParentage) {
            $this->assertSame('Judy', $judy->refresh()->first_name);
        }
    }

    #[Test]
    public function it_writes_and_clears_the_dates_a_relationship_ran_between(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Ended,
            startedOn: CarbonImmutable::parse('1990-04-01'),
            endedOn: CarbonImmutable::parse('2001-06-30'),
        )->execute();

        $this->assertSame('1990-04-01', $relationship->refresh()->started_on?->format('Y-m-d'));
        $this->assertSame('2001-06-30', $relationship->refresh()->ended_on?->format('Y-m-d'));

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship->refresh(),
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
        )->execute();

        $this->assertNull($relationship->refresh()->started_on);
        $this->assertNull($relationship->refresh()->ended_on);
    }

    #[Test]
    public function it_refuses_an_end_that_falls_before_the_start(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);

        $this->expectException(RelationshipDatesAreOutOfOrder::class);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Ended,
            startedOn: CarbonImmutable::parse('2001-06-30'),
            endedOn: CarbonImmutable::parse('1990-04-01'),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_change_that_would_make_somebody_their_own_ancestor(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $parentage = $this->typeOf($user, RelationshipTypeEnum::ParentChild);

        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');

        Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $parentage->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);

        $sibling = Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $this->typeOf($user, RelationshipTypeEnum::Sibling)->id,
            'contact_a_id' => $ross->id,
            'contact_b_id' => $judy->id,
        ]);

        $this->expectException(RelationshipWouldLoopParentage::class);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $ross,
            relationship: $sibling,
            relationshipType: $parentage,
            status: RelationshipStatusEnum::Current,
        )->execute();
    }

    #[Test]
    public function it_keeps_a_withdrawn_nature_when_the_link_already_carries_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $relationship->relationshipType->update(['is_active' => false]);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType->refresh(),
            status: RelationshipStatusEnum::Ended,
        )->execute();

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'status' => RelationshipStatusEnum::Ended->value,
        ]);
    }

    #[Test]
    public function it_refuses_a_withdrawn_nature_chosen_as_a_new_one(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $relationship = $this->relationshipOn($vault, $user);
        $sibling = $this->typeOf($user, RelationshipTypeEnum::Sibling);
        $sibling->update(['is_active' => false]);

        $this->expectException(RelationshipTypeIsNotAvailable::class);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $sibling->refresh(),
            status: RelationshipStatusEnum::Current,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_relationship_in_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $vault = $this->vaultOf($stranger);
        $relationship = $this->relationshipOn($vault, $stranger);

        $this->expectException(ModelNotFoundException::class);

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $relationship->contactA,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Current,
        )->execute();
    }

    #[Test]
    public function it_stops_an_ended_romantic_link_counting_as_a_current_partner(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultOf($user);
        $monica = $this->contactOn($vault, 'Monica', 'Geller');
        $chandler = $this->contactOn($vault, 'Chandler', 'Bing');

        $relationship = Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $this->typeOf($user, RelationshipTypeEnum::Partner)->id,
            'contact_a_id' => $monica->id,
            'contact_b_id' => $chandler->id,
        ]);

        $this->assertCount(1, Contact::query()->currentPartnersOf($monica)->get());

        new UpdateRelationship(
            user: $user,
            vault: $vault,
            contact: $monica,
            relationship: $relationship,
            relationshipType: $relationship->relationshipType,
            status: RelationshipStatusEnum::Ended,
        )->execute();

        $this->assertCount(0, Contact::query()->currentPartnersOf($monica)->get());
    }

    private function relationshipOn(Vault $vault, User $user): Relationship
    {
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');

        return Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $this->typeOf($user, RelationshipTypeEnum::ParentChild)->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);
    }

    private function vaultOf(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->create([
            'account_id' => $user->account_id,
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

    private function typeOf(User $user, RelationshipTypeEnum $case): RelationshipType
    {
        return RelationshipType::query()
            ->where('account_id', $user->account_id)
            ->where('key', $case->value)
            ->firstOr(fn (): RelationshipType => RelationshipType::factory()->system($case)->create([
                'account_id' => $user->account_id,
                'relationship_category_id' => RelationshipCategory::query()->firstOrCreate(
                    ['account_id' => $user->account_id, 'name' => 'Family'],
                    ['position' => 1],
                )->id,
            ]));
    }
}
