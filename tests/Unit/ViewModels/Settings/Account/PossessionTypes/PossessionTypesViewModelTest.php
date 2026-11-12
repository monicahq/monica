<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\PossessionTypes;

use App\Models\PossessionType;
use App\Models\Section;
use App\Models\User;
use App\ViewModels\Settings\Account\PossessionTypes\PossessionTypesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_of_the_account_sorted_by_name(): void
    {
        $user = User::factory()->create();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Appliance']);
        PossessionType::factory()->create(['name' => 'Dunder Mifflin paper']);

        $viewModel = new PossessionTypesViewModel(user: $user);

        $this->assertSame(['Appliance', 'Vehicle'], array_column($viewModel->possessionTypes(), 'name'));
        $this->assertFalse($viewModel->isEmpty());
    }

    #[Test]
    public function it_draws_a_type_without_icon_with_the_stand_in_glyph(): void
    {
        $user = User::factory()->create();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Appliance', 'icon' => null]);
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle', 'icon' => 'car']);

        $viewModel = new PossessionTypesViewModel(user: $user);

        $this->assertSame([Section::DEFAULT_ICON, 'car'], array_column($viewModel->possessionTypes(), 'icon'));
    }

    #[Test]
    public function it_says_when_the_account_has_no_type(): void
    {
        $user = User::factory()->create();
        PossessionType::factory()->create();

        $viewModel = new PossessionTypesViewModel(user: $user);

        $this->assertTrue($viewModel->isEmpty());
        $this->assertSame([], $viewModel->possessionTypes());
    }
}
