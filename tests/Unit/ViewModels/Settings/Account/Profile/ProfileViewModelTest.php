<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Profile;

use App\Models\User;
use App\ViewModels\Settings\Account\Profile\ProfileViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_details_of_the_user(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
            'email' => 'stanley.hudson@dundermifflin.com',
        ]);

        $viewModel = new ProfileViewModel(user: $user);

        $this->assertEquals([
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
            'email' => 'stanley.hudson@dundermifflin.com',
        ], $viewModel->details());
    }

    #[Test]
    public function it_gives_the_name_the_user_goes_by(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);

        $viewModel = new ProfileViewModel(user: $user);

        $this->assertEquals('Jim Halpert', $viewModel->name());
        $this->assertTrue($user->is($viewModel->user()));
    }

    #[Test]
    public function it_knows_whether_there_is_a_photo(): void
    {
        $withPhoto = new ProfileViewModel(user: User::factory()->create(['photo_path' => 'photos/1/abc']));
        $withoutPhoto = new ProfileViewModel(user: User::factory()->create(['photo_path' => null]));

        $this->assertTrue($withPhoto->hasPhoto());
        $this->assertFalse($withoutPhoto->hasPhoto());
    }

    #[Test]
    public function it_offers_an_upload_only_while_file_upload_is_on_for_the_account(): void
    {
        $jim = User::factory()->create();
        $dwight = User::factory()->create();
        $dwight->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->assertTrue(new ProfileViewModel(user: $jim)->uploadsAreOn());
        $this->assertFalse(new ProfileViewModel(user: $dwight)->uploadsAreOn());
    }

    #[Test]
    public function it_says_how_long_ago_the_profile_was_saved(): void
    {
        $user = User::factory()->create(['last_saved_at' => now()->subDays(2)]);

        $viewModel = new ProfileViewModel(user: $user);

        $this->assertEquals('2 days ago', $viewModel->lastSavedAt());
    }

    #[Test]
    public function it_gives_nothing_when_the_profile_was_never_saved(): void
    {
        $user = User::factory()->create(['last_saved_at' => null]);

        $viewModel = new ProfileViewModel(user: $user);

        $this->assertNull($viewModel->lastSavedAt());
    }

    #[Test]
    public function it_gives_the_email(): void
    {
        $user = User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);

        $viewModel = new ProfileViewModel(user: $user);

        $this->assertEquals('dwight.schrute@dundermifflin.com', $viewModel->email());
    }
}
