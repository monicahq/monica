<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEntity;
use App\Enums\UserActionEnum;
use App\Exceptions\EntityNeedsAName;
use App\Exceptions\EntityTypeIsNotActive;
use App\Exceptions\VaultHasNoEntityDefault;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Entity;
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

class CreateEntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_record_down(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Companies']);
        $version = $this->publishedVersionFor($user->account, $type);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $version,
        )->execute();

        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertDatabaseHas('entities', [
            'id' => $entity->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'entity_type_id' => $type->id,
            'template_version_id' => $version->id,
            'name' => 'Dunder Mifflin',
            'sort_name' => 'dunder mifflin',
            'is_archived' => false,
        ]);
    }

    #[Test]
    public function it_starts_the_record_on_the_template_the_vault_defaults_to_for_that_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedVersionFor($user->account, $type);
        VaultDefaultTemplate::factory()->forEntity($type)->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
        ]);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
        )->execute();

        $this->assertEquals($version->id, $entity->template_version_id);
    }

    #[Test]
    public function it_refuses_a_vault_with_nothing_for_a_record_of_that_type_to_start_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        VaultDefaultTemplate::factory()->forEntity()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->expectException(VaultHasNoEntityDefault::class);

        new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Dunder Mifflin')->execute();
    }

    #[Test]
    public function it_counts_the_record_on_the_type_and_on_the_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 4]);
        $version = $this->publishedVersionFor($user->account, $type);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $version,
        )->execute();

        $this->assertSame(5, $type->fresh()->entity_count);
        $this->assertSame(1, $version->fresh()->record_count);
    }

    #[Test]
    public function it_gives_the_record_a_colour_to_draw_its_initials_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $this->publishedVersionFor($user->account, $type),
        )->execute();

        $this->assertContains($entity->avatar_colour, Entity::AVATAR_COLOURS);
    }

    #[Test]
    public function it_refuses_a_kind_of_record_that_is_no_longer_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'is_active' => false,
            'name_plural' => 'Companies',
        ]);

        $this->expectException(EntityTypeIsNotActive::class);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $this->publishedVersionFor($user->account, $type),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_record_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(EntityNeedsAName::class);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: '   ',
            templateVersion: $this->publishedVersionFor($user->account, $type),
        )->execute();
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: '<script>alert("Dunder")</script>Dunder Mifflin',
            templateVersion: $this->publishedVersionFor($user->account, $type),
        )->execute();

        $this->assertEquals('Dunder Mifflin', $entity->name);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateEntity(
            user: $user,
            vault: Vault::factory()->create(),
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $this->publishedVersionFor($user->account, $type),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_kind_of_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Dunder Mifflin')->execute();
    }

    #[Test]
    public function it_refuses_a_template_version_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $this->publishedVersionFor(Account::factory()->create(), $type),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_template_that_does_not_describe_an_entity(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $version,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_template_describing_another_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $another = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $this->publishedVersionFor($user->account, $another),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_version_that_is_still_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->forEntity($type)->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $version,
        )->execute();
    }

    #[Test]
    public function it_logs_the_creation(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Dunder Mifflin',
            templateVersion: $this->publishedVersionFor($user->account, $type),
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($entity)
                && $job->parameters === ['name' => 'Dunder Mifflin'],
        );
    }

    private function publishedVersionFor(Account $account, EntityType $entityType): TemplateVersion
    {
        $template = Template::factory()->forEntity($entityType)->create(['account_id' => $account->id]);

        return TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
    }
}
