<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_only_the_people_in_the_group_today_as_current_members(): void
    {
        $group = Group::factory()->create(['name' => 'Scranton branch']);
        $jim = GroupMember::factory()->create(['group_id' => $group->id]);
        GroupMember::factory()->former()->create(['group_id' => $group->id]);

        $this->assertEquals([$jim->id], $group->currentMembers()->pluck('id')->all());
    }

    #[Test]
    public function it_hides_the_membership_of_a_deleted_contact_until_the_contact_comes_back(): void
    {
        $group = Group::factory()->create(['name' => 'Scranton branch']);
        $ryan = GroupMember::factory()->create(['group_id' => $group->id]);

        $ryan->contact->delete();

        $this->assertCount(0, $group->currentMembers()->get());

        $ryan->contact->restore();

        $this->assertCount(1, $group->currentMembers()->get());
    }

    #[Test]
    public function it_is_addressed_by_a_slug_built_from_its_name_and_rebuilt_when_renamed(): void
    {
        $group = Group::factory()->create(['name' => 'Finer Things Club']);

        $this->assertSame('finer-things-club', $group->slug);

        $group->update(['name' => 'Party Planning Committee']);

        $this->assertSame('party-planning-committee', $group->slug);
    }

    #[Test]
    public function it_takes_the_next_slug_when_another_group_of_the_vault_holds_it_even_deleted(): void
    {
        $vault = Vault::factory()->create();
        $first = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch']);
        $first->delete();

        $second = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch']);
        $elsewhere = Group::factory()->create(['name' => 'Scranton branch']);

        $this->assertNotSame('scranton-branch', $second->slug);
        $this->assertSame('scranton-branch', $elsewhere->slug);
    }

    #[Test]
    public function it_is_never_addressed_by_a_word_a_route_fixes(): void
    {
        $group = Group::factory()->create(['name' => 'New']);

        $this->assertNotSame('new', $group->slug);
    }
}
