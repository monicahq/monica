<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupOverviewViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupOverviewViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ticks_the_people_in_the_group_today_and_not_those_who_left(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $ryan = GroupMember::factory()->former()->create(['group_id' => $group->id]);
        $pam = GroupMember::factory()->create(['group_id' => $group->id]);

        $participants = new GroupOverviewViewModel(user: $user, vault: $vault, group: $group)->participants();

        $this->assertSame([$pam->contact_id, $ryan->contact_id], array_column($participants, 'id'));
        $this->assertSame([true, false], array_column($participants, 'isTicked'));
    }

    #[Test]
    public function it_offers_the_active_types_in_the_order_of_the_type_picker(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $party = $this->lifeEventTypeAsking($user, 'Party');
        $retired = $this->lifeEventTypeAsking($user, 'Booze cruise');
        $retired->update(['is_active' => false]);

        $viewModel = new GroupOverviewViewModel(user: $user, vault: $vault, group: Group::factory()->create(['vault_id' => $vault->id]));

        $this->assertContains($party->id, array_column($viewModel->types(), 'id'));
        $this->assertNotContains($retired->id, array_column($viewModel->types(), 'id'));
        $this->assertSame($viewModel->types()[0]['id'], $viewModel->chosenTypeId());
    }

    #[Test]
    public function it_says_once_that_the_files_are_kept_when_files_are_not_offered(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $holding = Group::factory()->create(['vault_id' => $vault->id]);
        $empty = Group::factory()->create(['vault_id' => $vault->id]);
        GroupFile::factory()->create(['group_id' => $holding->id]);

        $this->assertNull(new GroupOverviewViewModel(user: $user, vault: $vault, group: $holding)->keptFilesLine());

        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->assertNotNull(new GroupOverviewViewModel(user: $user, vault: $vault, group: $holding)->keptFilesLine());
        $this->assertNull(new GroupOverviewViewModel(user: $user, vault: $vault, group: $empty)->keptFilesLine());
    }
}
