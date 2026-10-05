<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SwitchFlagCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function refusals(): array
    {
        return [
            'an undeclared flag' => [['flag' => 'dundie-awards', 'state' => 'on', '--every-account' => true], 'No flag is named dundie-awards.'],
            'a state other than on or off' => [['flag' => 'example-capability', 'state' => 'maybe', '--every-account' => true], 'The state is on or off, not maybe.'],
            'no account named' => [['flag' => 'example-capability', 'state' => 'on'], 'Name one account with --email, or every account with --every-account.'],
            'both ways named' => [['flag' => 'example-capability', 'state' => 'on', '--email' => 'pam.beesly@dundermifflin.com', '--every-account' => true], 'Name one account with --email, or every account with --every-account.'],
            'an address nobody uses' => [['flag' => 'example-capability', 'state' => 'on', '--email' => 'toby.flenderson@dundermifflin.com'], 'Nobody on this instance uses the address toby.flenderson@dundermifflin.com.'],
        ];
    }

    #[Test]
    public function it_activates_a_flag_for_one_account_as_the_console(): void
    {
        $pam = User::factory()->create([
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'email' => 'pam.beesly@dundermifflin.com',
        ]);

        $this->artisan('monica:switch-flag', ['flag' => 'example-capability', 'state' => 'on', '--email' => 'pam.beesly@dundermifflin.com'])
            ->expectsOutputToContain('example-capability is now on for Pam Beesly.')
            ->assertExitCode(0);

        Feature::flushCache();

        $this->assertTrue($pam->account->features()->active('example-capability'));
        $this->assertDatabaseHas('administrator_logs', [
            'user_id' => null,
            'user_email' => Log::SYSTEM,
            'action' => 'flag_activated',
            'account_id' => $pam->account_id,
        ]);
    }

    #[Test]
    public function it_deactivates_a_flag_for_every_account(): void
    {
        $pam = User::factory()->create();
        $pam->account->features()->activate('example-capability');

        $this->artisan('monica:switch-flag', ['flag' => 'example-capability', 'state' => 'off', '--every-account' => true])
            ->expectsOutputToContain('example-capability is now off for every account.')
            ->assertExitCode(0);

        Feature::flushCache();

        $this->assertFalse($pam->account->features()->active('example-capability'));
        $this->assertDatabaseHas('administrator_logs', ['action' => 'flag_deactivated_for_every_account']);
    }

    #[Test]
    public function it_finds_the_address_whatever_case_it_is_typed_in(): void
    {
        $pam = User::factory()->create(['email' => 'pam.beesly@dundermifflin.com']);

        $this->artisan('monica:switch-flag', ['flag' => 'example-capability', 'state' => 'on', '--email' => 'Pam.Beesly@DunderMifflin.com'])
            ->assertExitCode(0);

        Feature::flushCache();

        $this->assertTrue($pam->account->features()->active('example-capability'));
    }

    /** @param  array<string, mixed>  $arguments */
    #[Test]
    #[DataProvider('refusals')]
    public function it_refuses_what_it_cannot_switch(array $arguments, string $reason): void
    {
        User::factory()->create(['email' => 'pam.beesly@dundermifflin.com']);

        $this->artisan('monica:switch-flag', $arguments)
            ->expectsOutputToContain($reason)
            ->assertExitCode(1);

        $this->assertDatabaseCount('administrator_logs', 0);
    }
}
