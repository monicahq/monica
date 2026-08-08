<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateVault;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(
            user: $user,
            name: 'Dunder Mifflin',
            description: 'Everybody at the Scranton branch',
            colour: '#AA151B',
        )->execute();

        $this->assertInstanceOf(Vault::class, $vault);
        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
            'colour' => '#aa151b',
            'position' => 1,
            'contact_count' => 0,
        ]);
    }

    #[Test]
    public function it_makes_the_first_vault_of_an_account_the_one_records_land_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $first = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();
        $second = new CreateVault(user: $user, name: 'Schrute Farms')->execute();

        $this->assertTrue($first->is_default);
        $this->assertFalse($second->is_default);
    }

    #[Test]
    public function it_puts_a_new_vault_at_the_end_of_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'position' => 4]);

        $vault = new CreateVault(user: $user, name: 'Schrute Farms')->execute();

        $this->assertSame(5, $vault->position);
    }

    #[Test]
    public function it_counts_only_the_vaults_of_the_account_when_placing_a_new_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Vault::factory()->create(['position' => 9]);

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        $this->assertSame(1, $vault->position);
        $this->assertTrue($vault->is_default);
    }

    #[Test]
    public function it_gives_a_vault_a_colour_when_nobody_picks_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        $this->assertEquals(Vault::DEFAULT_COLOUR, $vault->colour);
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(
            user: $user,
            name: '<script>alert("Dwight")</script>Schrute Farms',
            description: '<b>Beets</b>',
        )->execute();

        $this->assertEquals('Schrute Farms', $vault->name);
        $this->assertEquals('Beets', $vault->description);
    }

    #[Test]
    public function it_leaves_the_description_empty_when_there_is_none(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin', description: '  ')->execute();

        $this->assertNull($vault->description);
    }

    #[Test]
    public function it_logs_the_creation(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->loggable->is($vault)
                && $job->parameters === ['name' => 'Dunder Mifflin'],
        );
    }
}
