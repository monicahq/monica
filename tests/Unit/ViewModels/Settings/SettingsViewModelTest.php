<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings;

use App\Enums\TimeFormatEnum;
use App\Models\EntityType;
use App\Models\Template;
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
        $this->assertSame('Signed in as Pam Beesly · pam.beesly@dundermifflin.com', $viewModel->signedInAs());
        $this->assertTrue($user->is($viewModel->user()));
    }

    #[Test]
    public function it_groups_the_rows_into_you_this_account_and_leaving(): void
    {
        $user = User::factory()->create();

        $sections = new SettingsViewModel(user: $user)->sections();

        $this->assertCount(3, $sections);
        $this->assertSame('You', $sections[0]['title']);
        $this->assertSame('This account', $sections[1]['title']);
        $this->assertNull($sections[2]['title']);

        $this->assertSame(
            ['Profile', 'Security and access', 'Preferences', 'Notifications'],
            array_column($sections[0]['rows'], 'title'),
        );

        $this->assertSame(
            ['Templates', 'Kinds of record', 'Relationship types', 'Value lists', 'Logs', 'Billing'],
            array_column($sections[1]['rows'], 'title'),
        );

        $this->assertSame(
            ['Export everything', 'Sign out'],
            array_column($sections[2]['rows'], 'title'),
        );
    }

    #[Test]
    public function it_leaves_the_url_of_a_screen_that_does_not_exist_yet_empty(): void
    {
        $user = User::factory()->create();

        $sections = new SettingsViewModel(user: $user)->sections();

        $urls = [];

        foreach ($sections as $section) {
            foreach ($section['rows'] as $row) {
                $urls[$row['title']] = $row['url'];
            }
        }

        $this->assertNull($urls['Notifications']);
        $this->assertNull($urls['Export everything']);
        $this->assertSame(route('settings.profile.index'), $urls['Profile']);
        $this->assertSame(route('settings.logs.index'), $urls['Logs']);
    }

    #[Test]
    public function it_marks_signing_out_as_something_that_is_sent_rather_than_followed(): void
    {
        $user = User::factory()->create();

        $sections = new SettingsViewModel(user: $user)->sections();

        $signOut = $sections[2]['rows'][1];

        $this->assertSame('Sign out', $signOut['title']);
        $this->assertSame('post', $signOut['method']);
        $this->assertSame(route('auth.login.destroy'), $signOut['url']);
    }

    #[Test]
    public function it_says_whether_two_factor_authentication_is_on(): void
    {
        $without = User::factory()->create();

        $this->assertSame(
            'Two-factor off',
            new SettingsViewModel(user: $without)->sections()[0]['rows'][1]['meta'],
        );

        $with = User::factory()->create([
            'two_factor_secret' => encrypt('DWIGHTSCHRUTE'),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->assertSame(
            'Two-factor on',
            new SettingsViewModel(user: $with)->sections()[0]['rows'][1]['meta'],
        );
    }

    #[Test]
    public function it_shows_the_language_and_the_clock_beside_the_preferences(): void
    {
        $user = User::factory()->create(['time_format' => TimeFormatEnum::TwelveHour]);

        $this->assertSame(
            'English · 12-hour',
            new SettingsViewModel(user: $user)->sections()[0]['rows'][2]['meta'],
        );
    }

    #[Test]
    public function it_counts_the_templates_and_the_entity_types_of_the_account(): void
    {
        $user = User::factory()->create();

        Template::factory()->create(['account_id' => $user->account_id]);
        EntityType::factory()->count(4)->create(['account_id' => $user->account_id]);

        $other = User::factory()->create();
        Template::factory()->count(7)->create(['account_id' => $other->account_id]);

        $rows = new SettingsViewModel(user: $user)->sections()[1]['rows'];

        $this->assertSame('1 template', $rows[0]['meta']);
        $this->assertSame('4 types', $rows[1]['meta']);
    }

    #[Test]
    public function it_sends_deleting_the_account_to_the_confirmation_on_the_profile_screen(): void
    {
        $user = User::factory()->create();

        $this->assertSame(
            route('settings.profile.index').'#delete-your-account',
            new SettingsViewModel(user: $user)->deleteUrl(),
        );
    }

    #[Test]
    public function it_leaves_billing_out_of_the_rows_on_a_self_hosted_instance(): void
    {
        config(['monica.self_hosted' => true]);

        $user = User::factory()->create();

        $sections = new SettingsViewModel(user: $user)->sections();

        $this->assertSame(
            ['Templates', 'Kinds of record', 'Relationship types', 'Value lists', 'Logs'],
            array_column($sections[1]['rows'], 'title'),
        );
    }
}
