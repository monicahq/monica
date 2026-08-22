<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Models\Administrator;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MakeAdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_designates_the_first_administrator_of_an_instance(): void
    {
        $michael = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertDatabaseCount('administrators', 0);

        $this->artisan('monica:make-administrator', ['email' => 'michael.scott@dundermifflin.com'])
            ->expectsOutputToContain('Michael Scott now administers this instance.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('administrators', ['user_id' => $michael->id]);
    }

    #[Test]
    public function it_attributes_the_designation_to_the_console(): void
    {
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->artisan('monica:make-administrator', ['email' => 'michael.scott@dundermifflin.com']);

        $this->assertDatabaseHas('administrator_logs', [
            'user_id' => null,
            'user_email' => Log::SYSTEM,
            'action' => 'administrator_access_granted',
        ]);
    }

    #[Test]
    public function it_finds_the_address_whatever_case_it_is_typed_in(): void
    {
        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->artisan('monica:make-administrator', ['email' => 'Michael.Scott@DunderMifflin.com'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('administrators', ['user_id' => $michael->id]);
    }

    #[Test]
    public function it_refuses_an_address_belonging_to_nobody(): void
    {
        $this->artisan('monica:make-administrator', ['email' => 'nobody@dundermifflin.com'])
            ->expectsOutputToContain('Nobody on this instance uses the address nobody@dundermifflin.com.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('administrators', 0);
    }

    #[Test]
    public function it_refuses_a_capacity_already_held(): void
    {
        $michael = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->artisan('monica:make-administrator', ['email' => 'michael.scott@dundermifflin.com'])
            ->expectsOutputToContain('Michael Scott already administers this instance.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('administrators', 1);
    }
}
