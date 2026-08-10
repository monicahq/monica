<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyVault;
use App\Actions\RestoreVault;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_a_deleted_vault_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'position' => 2,
        ]);
        $vault->delete();

        $result = new RestoreVault(user: $user, vault: $vault)->execute();

        $this->assertInstanceOf(Vault::class, $result);
        $this->assertNotSoftDeleted('vaults', ['id' => $vault->id]);
        $this->assertSame(2, $vault->refresh()->position);
    }

    #[Test]
    public function it_does_not_bring_the_vault_back_as_the_one_records_land_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'position' => 1,
        ]);
        $next = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        new DestroyVault(user: $user, vault: $vault)->execute();
        new RestoreVault(user: $user, vault: $vault)->execute();

        $this->assertFalse($vault->refresh()->is_default);
        $this->assertTrue($next->refresh()->is_default);
    }

    #[Test]
    public function it_brings_back_the_contacts_that_left_with_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->used(1)->create();
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'template_version_id' => $version->id]);
        $creed = Contact::factory()->create(['vault_id' => $vault->id]);
        $creed->delete();

        new DestroyVault(user: $user, vault: $vault)->execute();
        new RestoreVault(user: $user, vault: $vault)->execute();

        $this->assertNotSoftDeleted('contacts', ['id' => $dwight->id]);
        $this->assertSoftDeleted('contacts', ['id' => $creed->id]);
        $this->assertEquals(1, $version->refresh()->record_count);
    }

    #[Test]
    public function it_takes_the_default_back_when_it_was_the_last_vault_of_the_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        new DestroyVault(user: $user, vault: $vault)->execute();
        new RestoreVault(user: $user, vault: $vault)->execute();

        $this->assertTrue($vault->refresh()->is_default);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $vault->delete();

        $this->expectException(ModelNotFoundException::class);

        new RestoreVault(user: $user, vault: $vault)->execute();
    }

    #[Test]
    public function it_logs_the_restoration(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Schrute Farms',
        ]);
        $vault->delete();

        new RestoreVault(user: $user, vault: $vault)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultRestoration
                && $job->account->id === $user->account_id
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Schrute Farms'],
        );
    }
}
