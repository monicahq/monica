<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_groups_of_the_vault_by_name_with_their_headcount_today(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $branch = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch', 'started_on' => '1990-01-01', 'started_precision' => DatePrecisionEnum::Year]);
        GroupMember::factory()->count(2)->create(['group_id' => $branch->id]);
        GroupMember::factory()->former()->create(['group_id' => $branch->id]);
        Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        Group::factory()->create(['name' => 'Utica branch']);

        $groups = new GroupsViewModel(user: $user, vault: $vault)->groups()->items();

        $this->assertSame(['Finer things club', 'Scranton branch'], array_column($groups, 'name'));
        $this->assertSame('Nobody in it today', $groups[0]['headcount']);
        $this->assertSame('2 people today', $groups[1]['headcount']);
        $this->assertSame('Since 1990', $groups[1]['period']);
    }

    #[Test]
    public function it_pages_the_list(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Group::factory()->count(12)->create(['vault_id' => $vault->id]);

        $groups = new GroupsViewModel(user: $user, vault: $vault, size: 10)->groups();

        $this->assertCount(10, $groups->items());
        $this->assertSame(12, $groups->total());
    }

    #[Test]
    public function it_offers_back_the_group_deleted_a_moment_ago(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new GroupsViewModel(user: $user, vault: $vault, deletedGroup: ['uuid' => '7c1e5a92-4b3d-4f80-a6e2-93d0b1c4f857', 'name' => 'Finer things club']);

        $this->assertSame('The group Finer things club is deleted.', $viewModel->deletedGroup()['line']);
        $this->assertNull(new GroupsViewModel(user: $user, vault: $vault)->deletedGroup());
    }
}
