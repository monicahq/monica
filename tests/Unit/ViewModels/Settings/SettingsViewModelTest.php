<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings;

use App\Models\User;
use App\ViewModels\Settings\SettingsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_who_is_signed_in(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'email' => 'pam.beesly@dundermifflin.com',
        ]);

        $viewModel = new SettingsViewModel(user: $user);

        $this->assertSame('Pam Beesly', $viewModel->name());
        $this->assertTrue($user->is($viewModel->user()));
    }

    #[Test]
    public function it_groups_the_rows_into_you_this_account_and_leaving(): void
    {
        $user = User::factory()->create();

        $sections = new SettingsViewModel(user: $user)->sections();

        $this->assertCount(3, $sections);
        $this->assertNull($sections[2]['title']);

        $this->assertCount(4, $sections[0]['rows']);

        $this->assertCount(8, $sections[1]['rows']);

    }

    #[Test]
    public function it_leaves_billing_out_of_the_rows_on_a_self_hosted_instance(): void
    {
        config(['monica.self_hosted' => true]);

        $user = User::factory()->create();

        $sections = new SettingsViewModel(user: $user)->sections();

        $this->assertCount(7, $sections[1]['rows']);
    }
}
