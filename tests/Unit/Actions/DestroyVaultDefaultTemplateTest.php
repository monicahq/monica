<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyVaultDefaultTemplate;
use App\Enums\UserActionEnum;
use App\Exceptions\VaultAlwaysHasAContactDefault;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyVaultDefaultTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_offering_a_structure_by_default_for_a_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $default = $this->entityDefault($user);

        new DestroyVaultDefaultTemplate(user: $user, default: $default)->execute();

        $this->assertModelMissing($default);
    }

    #[Test]
    public function it_refuses_to_take_away_the_template_new_contacts_start_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $default = VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->expectException(VaultAlwaysHasAContactDefault::class);

        new DestroyVaultDefaultTemplate(user: $user, default: $default)->execute();
    }

    #[Test]
    public function it_refuses_a_default_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $default = VaultDefaultTemplate::factory()->forEntity()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyVaultDefaultTemplate(user: $user, default: $default)->execute();
    }

    #[Test]
    public function it_logs_the_removal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $default = $this->entityDefault($user);

        new DestroyVaultDefaultTemplate(user: $user, default: $default)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultDefaultTemplateRemoved
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $default->vault_id
                && $job->isAbout($default)
                && $job->parameters === ['name' => 'Animal'],
        );
    }

    private function entityDefault(User $user): VaultDefaultTemplate
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->forEntity($entityType)->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        return VaultDefaultTemplate::factory()->forEntity($entityType)->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
        ]);
    }
}
