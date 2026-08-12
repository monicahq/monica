<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SetVaultDefaultTemplate;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
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
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetVaultDefaultTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_which_structure_a_new_contact_starts_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedVersion(Template::factory()->create(['account_id' => $user->account_id]));

        $default = new SetVaultDefaultTemplate(
            user: $user,
            vault: $vault,
            templateVersion: $version,
        )->execute();

        $this->assertInstanceOf(VaultDefaultTemplate::class, $default);
        $this->assertDatabaseHas('vault_default_templates', [
            'id' => $default->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact->value,
            'entity_type_id' => null,
            'template_version_id' => $version->id,
        ]);
    }

    #[Test]
    public function it_reads_the_kind_of_record_off_the_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedVersion(Template::factory()->forEntity($entityType)->create(['account_id' => $user->account_id]));

        $default = new SetVaultDefaultTemplate(
            user: $user,
            vault: $vault,
            templateVersion: $version,
        )->execute();

        $this->assertEquals(TemplateAppliesToEnum::Entity, $default->applies_to);
        $this->assertEquals($entityType->id, $default->entity_type_id);
    }

    #[Test]
    public function it_replaces_the_default_already_there_for_that_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $second = TemplateVersion::factory()->published()->create(['template_id' => $template->id, 'version_number' => 2]);

        new SetVaultDefaultTemplate(user: $user, vault: $vault, templateVersion: $first)->execute();
        $default = new SetVaultDefaultTemplate(user: $user, vault: $vault, templateVersion: $second)->execute();

        $this->assertEquals($second->id, $default->template_version_id);
        $this->assertEquals(1, VaultDefaultTemplate::query()->count());
    }

    #[Test]
    public function it_keeps_a_default_per_kind_of_record_in_the_same_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        new SetVaultDefaultTemplate(
            user: $user,
            vault: $vault,
            templateVersion: $this->publishedVersion(Template::factory()->create(['account_id' => $user->account_id])),
        )->execute();

        new SetVaultDefaultTemplate(
            user: $user,
            vault: $vault,
            templateVersion: $this->publishedVersion(Template::factory()->forEntity($entityType)->create(['account_id' => $user->account_id])),
        )->execute();

        $this->assertEquals(2, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
    }

    #[Test]
    public function it_lets_two_vaults_start_the_same_kind_of_record_differently(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $family = Vault::factory()->create(['account_id' => $user->account_id]);
        $work = Vault::factory()->create(['account_id' => $user->account_id]);

        $detailed = new SetVaultDefaultTemplate(
            user: $user,
            vault: $family,
            templateVersion: $this->publishedVersion(Template::factory()->create(['account_id' => $user->account_id])),
        )->execute();

        $plain = new SetVaultDefaultTemplate(
            user: $user,
            vault: $work,
            templateVersion: $this->publishedVersion(Template::factory()->create(['account_id' => $user->account_id])),
        )->execute();

        $this->assertNotEquals($detailed->template_version_id, $plain->template_version_id);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new SetVaultDefaultTemplate(
            user: $user,
            vault: $vault,
            templateVersion: $this->publishedVersion(Template::factory()->create(['account_id' => $user->account_id])),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_template_version_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new SetVaultDefaultTemplate(user: $user, vault: $vault, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_that_is_still_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->expectException(InvalidArgumentException::class);

        new SetVaultDefaultTemplate(user: $user, vault: $vault, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_logs_the_change(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedVersion(Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Basic contact']));

        $default = new SetVaultDefaultTemplate(user: $user, vault: $vault, templateVersion: $version)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultDefaultTemplateSet
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($default)
                && $job->parameters === ['name' => 'Basic contact'],
        );
    }

    private function publishedVersion(Template $template): TemplateVersion
    {
        return TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
    }
}
