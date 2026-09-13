<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\RelationshipStatusEnum;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $relationship = Relationship::factory()->create();

        $this->assertTrue($relationship->account()->exists());
        $this->assertEquals($relationship->account_id, $relationship->account->id);
    }

    #[Test]
    public function it_belongs_to_a_vault(): void
    {
        $relationship = Relationship::factory()->create();

        $this->assertTrue($relationship->vault()->exists());
        $this->assertEquals($relationship->vault_id, $relationship->vault->id);
    }

    #[Test]
    public function it_belongs_to_a_relationship_type(): void
    {
        $relationship = Relationship::factory()->create();

        $this->assertTrue($relationship->relationshipType()->exists());
        $this->assertInstanceOf(RelationshipType::class, $relationship->relationshipType);
    }

    #[Test]
    public function it_belongs_to_a_contact_on_each_side(): void
    {
        $relationship = Relationship::factory()->create();

        $this->assertTrue($relationship->contactA()->exists());
        $this->assertTrue($relationship->contactB()->exists());
        $this->assertNotEquals($relationship->contactA->id, $relationship->contactB->id);
    }

    #[Test]
    public function it_puts_both_contacts_in_the_vault_it_belongs_to(): void
    {
        $relationship = Relationship::factory()->create();

        $this->assertEquals($relationship->vault_id, $relationship->contactA->vault_id);
        $this->assertEquals($relationship->vault_id, $relationship->contactB->vault_id);
    }

    #[Test]
    public function it_reads_its_status_as_an_enum(): void
    {
        $relationship = Relationship::factory()->ended()->create();

        $this->assertInstanceOf(RelationshipStatusEnum::class, $relationship->status);
        $this->assertEquals(RelationshipStatusEnum::Ended, $relationship->status);
    }

    #[Test]
    public function it_reads_its_dates_as_dates(): void
    {
        $relationship = Relationship::factory()->create([
            'started_on' => '1994-03-05',
            'ended_on' => '2005-11-21',
        ]);

        $this->assertEquals('1994-03-05', $relationship->started_on?->toDateString());
        $this->assertEquals('2005-11-21', $relationship->ended_on?->toDateString());
    }

    #[Test]
    public function it_is_addressed_by_its_uuid(): void
    {
        $relationship = Relationship::factory()->create();

        $this->assertEquals('uuid', $relationship->getRouteKeyName());
        $this->assertEquals($relationship->uuid, $relationship->getRouteKey());
    }

    #[Test]
    public function it_is_soft_deleted(): void
    {
        $relationship = Relationship::factory()->create();

        $relationship->delete();

        $this->assertSoftDeleted('relationships', ['id' => $relationship->id]);
    }

    #[Test]
    public function it_does_not_resolve_a_relationship_of_another_account(): void
    {
        $account = Account::factory()->create();
        $user = $this->assignUserToAccount(user: User::factory()->create(), account: $account);
        $relationship = Relationship::factory()->create();

        $this->actingAs($user);

        $this->assertNull(new Relationship()->resolveRouteBinding($relationship->uuid));
    }

    #[Test]
    public function it_resolves_a_relationship_of_the_account_asking(): void
    {
        $relationship = Relationship::factory()->create();
        $user = $this->assignUserToAccount(
            user: User::factory()->create(),
            account: Account::query()->findOrFail($relationship->account_id),
        );

        $this->actingAs($user);

        $this->assertEquals($relationship->id, new Relationship()->resolveRouteBinding($relationship->uuid)?->id);
    }

    #[Test]
    public function it_does_not_resolve_through_a_vault_it_is_not_in(): void
    {
        $relationship = Relationship::factory()->create();
        $account = Account::query()->findOrFail($relationship->account_id);
        $user = $this->assignUserToAccount(user: User::factory()->create(), account: $account);
        $otherVault = Vault::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

        $this->assertNull(
            Relationship::query()
                ->where('uuid', $relationship->uuid)
                ->where('vault_id', $otherVault->id)
                ->first()
        );
    }

    #[Test]
    public function it_keeps_several_relationships_between_the_same_pair(): void
    {
        $first = Relationship::factory()->create();
        $second = Relationship::factory()->create([
            'account_id' => $first->account_id,
            'vault_id' => $first->vault_id,
            'contact_a_id' => $first->contact_a_id,
            'contact_b_id' => $first->contact_b_id,
        ]);

        $this->assertNotEquals($first->id, $second->id);
        $this->assertEquals(2, Relationship::query()
            ->where('contact_a_id', $first->contact_a_id)
            ->where('contact_b_id', $first->contact_b_id)
            ->count());
    }

    #[Test]
    public function it_survives_a_contact_being_soft_deleted(): void
    {
        $relationship = Relationship::factory()->create();

        Contact::query()->findOrFail($relationship->contact_b_id)->delete();

        $this->assertDatabaseHas('relationships', ['id' => $relationship->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_finds_every_relationship_a_contact_is_part_of_from_either_side(): void
    {
        $vault = Vault::factory()->create();
        $judy = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        $ross = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        $monica = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);

        $asSideA = Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);
        $asSideB = Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $monica->id,
            'contact_b_id' => $judy->id,
        ]);

        $found = Relationship::query()->forContact($judy)->pluck('id');

        $this->assertCount(2, $found);
        $this->assertContains($asSideA->id, $found);
        $this->assertContains($asSideB->id, $found);
    }

    #[Test]
    public function it_excludes_a_relationship_that_was_deleted(): void
    {
        $relationship = Relationship::factory()->create();
        $contact = Contact::query()->findOrFail($relationship->contact_a_id);

        $relationship->delete();

        $this->assertCount(0, Relationship::query()->forContact($contact)->get());
    }

    #[Test]
    public function it_excludes_a_relationship_in_another_vault(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id]);
        $elsewhere = Vault::factory()->create(['account_id' => $account->id]);

        $judy = Contact::factory()->create(['account_id' => $account->id, 'vault_id' => $vault->id]);
        $mine = Relationship::factory()->create([
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'contact_a_id' => $judy->id,
        ]);
        Relationship::factory()->create(['account_id' => $account->id, 'vault_id' => $elsewhere->id]);

        $found = Relationship::query()->forContact($judy)->pluck('id');

        $this->assertCount(1, $found);
        $this->assertContains($mine->id, $found);
    }

    #[Test]
    public function it_answers_twice_for_a_pair_linked_twice(): void
    {
        $vault = Vault::factory()->create();
        $judy = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        $ross = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);

        Relationship::factory()->count(2)->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);

        $this->assertCount(2, Relationship::query()->forContact($judy)->get());
    }
}
