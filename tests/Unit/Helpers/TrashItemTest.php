<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\DatePrecisionEnum;
use App\Enums\TrashItemKindEnum;
use App\Helpers\VaultTrash;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrashItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_the_days_left_from_the_deletion(): void
    {
        $vault = Vault::factory()->create();
        $jan = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subDays(27)]);

        $item = new VaultTrash(vault: $vault)->find(TrashItemKindEnum::Contact, $jan->uuid);

        $this->assertSame(3, $item->daysLeft());
        $this->assertTrue($item->expiresAt()->isSameDay(now()->addDays(3)));
        $this->assertTrue($item->canBeRestored());
    }

    #[Test]
    public function an_item_deleted_with_a_person_still_in_the_trash_waits_for_that_person(): void
    {
        $vault = Vault::factory()->create();
        $jan = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson', 'deleted_at' => now()]);
        $lifeEvent = LifeEvent::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'title' => 'Opened Serenity by Jan',
            'deleted_at' => now(),
            'deleted_with_type' => Contact::class,
            'deleted_with_id' => $jan->id,
        ]);

        $item = new VaultTrash(vault: $vault)->find(TrashItemKindEnum::LifeEvent, $lifeEvent->uuid);

        $this->assertSame('Opened Serenity by Jan', $item->title());
        $this->assertTrue($item->owner()->is($jan));
        $this->assertSame('Deleted with Jan Levinson. Restore Jan Levinson to bring this back.', $item->obstacle());
    }

    #[Test]
    public function a_relationship_waits_for_its_other_person(): void
    {
        $vault = Vault::factory()->create();
        $holly = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Holly', 'last_name' => 'Flax', 'deleted_at' => now()]);
        $relationship = Relationship::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'contact_b_id' => $holly->id, 'deleted_at' => now()]);

        $item = new VaultTrash(vault: $vault)->find(TrashItemKindEnum::Relationship, $relationship->uuid);

        $this->assertNull($item->owner());
        $this->assertSame('Holly Flax is in the trash. Restore Holly Flax first.', $item->obstacle());
    }

    #[Test]
    public function a_membership_and_a_note_wait_for_their_group(): void
    {
        $vault = Vault::factory()->create();
        $group = Group::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'name' => 'Finer Things Club']);
        $membership = GroupMember::factory()->create(['group_id' => $group->id, 'deleted_at' => now()]);
        $note = GroupNote::factory()->create(['group_id' => $group->id, 'body' => 'No talk of work.', 'deleted_at' => now()]);
        $trash = new VaultTrash(vault: $vault);

        $this->assertTrue($trash->find(TrashItemKindEnum::GroupMembership, $membership->uuid)->canBeRestored());

        $group->delete();

        $this->assertSame('The group Finer Things Club is in the trash. Restore the group first.', $trash->find(TrashItemKindEnum::GroupMembership, $membership->uuid)->obstacle());
        $this->assertSame('The group Finer Things Club is in the trash. Restore the group first.', $trash->find(TrashItemKindEnum::GroupNote, $note->uuid)->obstacle());
        $this->assertSame('No talk of work.', $trash->find(TrashItemKindEnum::GroupNote, $note->uuid)->title());
    }

    #[Test]
    public function a_record_is_named_by_its_own_kind_and_waits_for_that_kind(): void
    {
        $vault = Vault::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $vault->account_id, 'name_singular' => 'Animal']);
        $sprinkles = Entity::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Sprinkles', 'deleted_at' => now()]);
        $trash = new VaultTrash(vault: $vault);

        $this->assertSame('Animal', $trash->find(TrashItemKindEnum::Entity, $sprinkles->uuid)->typeLabel());
        $this->assertNull($trash->find(TrashItemKindEnum::Entity, $sprinkles->uuid)->obstacle());

        $type->delete();

        $this->assertSame('Its kind, Animal, was deleted from the account settings.', $trash->find(TrashItemKindEnum::Entity, $sprinkles->uuid)->obstacle());
    }

    #[Test]
    public function a_life_event_says_whom_it_is_about_and_its_year(): void
    {
        $vault = Vault::factory()->create();
        $janice = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Janice', 'last_name' => 'Litman-Goralnik']);
        $wedding = LifeEvent::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'happened_on' => '1999-06-12', 'date_precision' => DatePrecisionEnum::Day]);
        LifeEventParticipant::factory()->create(['life_event_id' => $wedding->id, 'participant_id' => $janice->id]);
        $wedding->delete();

        $item = new VaultTrash(vault: $vault)->find(TrashItemKindEnum::LifeEvent, $wedding->uuid);

        $this->assertSame('About Janice Litman-Goralnik · 1999', $item->context());
    }
}
