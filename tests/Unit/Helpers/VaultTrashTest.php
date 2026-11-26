<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\TrashItemKindEnum;
use App\Helpers\TrashItem;
use App\Helpers\VaultTrash;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Entity;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\Relationship;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultTrashTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_what_was_deleted_from_the_vault_newest_first(): void
    {
        $vault = Vault::factory()->create();
        $jan = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'deleted_at' => now()->subDays(3)]);
        $party = Group::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'name' => 'Party planning committee', 'deleted_at' => now()->subDay()]);
        Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam']);

        $items = new VaultTrash(vault: $vault)->paginate(kind: null, perPage: 10)->items();

        $this->assertCount(2, $items);
        $this->assertTrue($items[0]->record->is($party));
        $this->assertTrue($items[1]->record->is($jan));
    }

    #[Test]
    public function it_leaves_out_what_another_vault_holds(): void
    {
        $vault = Vault::factory()->create();
        $otherVault = Vault::factory()->create(['account_id' => $vault->account_id]);
        Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $otherVault->id, 'deleted_at' => now()]);
        GroupMember::factory()->create([
            'group_id' => Group::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $otherVault->id])->id,
            'deleted_at' => now(),
        ]);

        $this->assertSame(0, new VaultTrash(vault: $vault)->paginate(kind: null, perPage: 10)->total());
    }

    #[Test]
    public function it_lists_an_item_deleted_with_its_owner_only_under_its_own_kind(): void
    {
        $vault = Vault::factory()->create();
        $jan = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        $lifeEvent = LifeEvent::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'deleted_at' => now(),
            'deleted_with_type' => Contact::class,
            'deleted_with_id' => $jan->id,
        ]);
        $trash = new VaultTrash(vault: $vault);

        $this->assertSame(1, $trash->paginate(kind: null, perPage: 10)->total());
        $this->assertTrue($trash->paginate(kind: TrashItemKindEnum::LifeEvent, perPage: 10)->items()[0]->record->is($lifeEvent));
        $this->assertSame(['total' => 1, 'kinds' => ['contact' => 1, 'life-event' => 1]], $trash->counts());
    }

    #[Test]
    public function it_leaves_out_what_was_deleted_with_the_vault_or_by_a_merge(): void
    {
        $vault = Vault::factory()->create();
        Entity::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now(), 'deleted_with_vault' => true]);
        $absorbed = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        $merge = ContactMerge::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'merged_contact_id' => $absorbed->id,
        ]);
        Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'deleted_at' => now(),
            'deleted_with_type' => ContactMerge::class,
            'deleted_with_id' => $merge->id,
        ]);

        $trash = new VaultTrash(vault: $vault);

        $this->assertSame(0, $trash->paginate(kind: null, perPage: 10)->total());
        $this->assertSame(0, $trash->paginate(kind: TrashItemKindEnum::Relationship, perPage: 10)->total());
    }

    #[Test]
    public function a_kind_behind_a_flag_that_is_off_is_absent(): void
    {
        $vault = Vault::factory()->create();
        GroupFile::factory()->create([
            'group_id' => Group::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id])->id,
            'deleted_at' => now(),
        ]);
        $trash = new VaultTrash(vault: $vault);

        $this->assertSame(1, $trash->paginate(kind: null, perPage: 10)->total());

        Feature::for($vault->account)->deactivate('file-upload');
        Feature::flushCache();

        $this->assertSame(0, $trash->paginate(kind: null, perPage: 10)->total());
        $this->expectException(ModelNotFoundException::class);
        $trash->find(TrashItemKindEnum::GroupFile, GroupFile::withTrashed()->firstOrFail()->uuid);
    }

    #[Test]
    public function it_pages_the_trash(): void
    {
        $vault = Vault::factory()->create();
        Contact::factory()->count(3)->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);

        $page = new VaultTrash(vault: $vault)->paginate(kind: null, perPage: 2, page: 2);

        $this->assertSame(3, $page->total());
        $this->assertCount(1, $page->items());
    }

    #[Test]
    public function it_finds_one_item_only_when_it_is_in_the_trash_of_the_vault(): void
    {
        $vault = Vault::factory()->create();
        $jan = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        $pam = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        $trash = new VaultTrash(vault: $vault);

        $this->assertInstanceOf(TrashItem::class, $trash->find(TrashItemKindEnum::Contact, $jan->uuid));

        $this->expectException(ModelNotFoundException::class);
        $trash->find(TrashItemKindEnum::Contact, $pam->uuid);
    }

    #[Test]
    public function it_gives_the_items_whose_time_is_up(): void
    {
        $vault = Vault::factory()->create();
        $old = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subDays(31)]);
        Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subDays(29)]);

        $expired = new VaultTrash(vault: $vault)->expiredBefore(now()->subDays(30));

        $this->assertCount(1, $expired);
        $this->assertTrue($expired->first()->record->is($old));
    }
}
