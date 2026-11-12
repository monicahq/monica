<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\PossessionTags;

use App\Enums\VaultColourEnum;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Settings\Account\PossessionTags\PossessionTagsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTagsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_tags_of_the_account_sorted_by_name(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'To sell', 'colour' => VaultColourEnum::Green->value]);
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured', 'colour' => VaultColourEnum::Teal->value]);
        PossessionTag::factory()->create(['name' => 'Dundie award']);

        $viewModel = new PossessionTagsViewModel(user: $user);

        $this->assertSame(['Insured', 'To sell'], array_column($viewModel->tags(), 'name'));
        $this->assertFalse($viewModel->isEmpty());
    }

    #[Test]
    public function it_says_nothing_when_the_account_has_no_tag(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create();

        $viewModel = new PossessionTagsViewModel(user: $user);

        $this->assertSame([], $viewModel->tags());
        $this->assertSame('', $viewModel->countLine());
        $this->assertTrue($viewModel->isEmpty());
    }

    #[Test]
    public function it_says_how_many_possessions_lose_a_tag_when_it_is_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $insured = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'To sell']);

        $viewModel = new PossessionTagsViewModel(user: $user);

        $this->assertSame('', $viewModel->tags()[0]['deleteConsequence']);

        Possession::factory()->create(['vault_id' => $vault->id])->tags()->attach($insured);

        Possession::factory()->count(2)->create(['vault_id' => $vault->id])->each(fn (Possession $possession) => $possession->tags()->attach($insured));

        $this->assertSame('', new PossessionTagsViewModel(user: $user)->tags()[1]['deleteConsequence']);
    }
}
