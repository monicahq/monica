<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateTemplate;
use App\Actions\DestroyTemplate;
use App\Enums\DefaultTemplateEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_template_nothing_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);

        new DestroyTemplate(user: $user, template: $template)->execute();

        $this->assertSoftDeleted($template);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateDeletion
                && $job->parameters === ['name' => 'Contact'];
        });
    }

    #[Test]
    public function it_refuses_while_records_still_point_at_one_of_its_versions(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->used(214)->create(['structure_id' => $template->structure->id]);

        $this->expectException(AuthorizationException::class);

        new DestroyTemplate(user: $user, template: $template)->execute();
    }

    #[Test]
    public function it_refuses_the_last_template_for_contacts(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        new DestroyTemplate(user: $user, template: $template)->execute();
    }

    #[Test]
    public function it_does_not_release_the_slug_of_a_deleted_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();

        new DestroyTemplate(user: $user, template: $template)->execute();

        $again = new CreateTemplate(user: $user, name: 'Contact')->execute();

        $this->assertEquals('contact-2', $again->slug);
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyTemplate(user: $michael, template: $template)->execute();
    }

    #[Test]
    public function it_moves_the_vault_default_to_basic_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $basicContact = Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::BasicContact->value]);
        Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::Essentials->value]);
        $template = Template::factory()->published()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $default = VaultDefaultTemplate::factory()->create(['vault_id' => $vault->id, 'structure_version_id' => $template->structure->published_version_id]);

        new DestroyTemplate(user: $user, template: $template)->execute();

        $this->assertDatabaseHas('vault_default_templates', [
            'id' => $default->id,
            'vault_id' => $vault->id,
            'structure_version_id' => $basicContact->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_moves_the_vault_default_to_the_next_supplied_template_when_basic_contact_is_deleted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::FullPortrait->value]);
        $essentials = Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::Essentials->value]);
        $basicContact = Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::BasicContact->value]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $default = VaultDefaultTemplate::factory()->create(['vault_id' => $vault->id, 'structure_version_id' => $basicContact->structure->published_version_id]);

        new DestroyTemplate(user: $user, template: $basicContact)->execute();

        $this->assertDatabaseHas('vault_default_templates', [
            'id' => $default->id,
            'structure_version_id' => $essentials->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_takes_the_vault_default_with_it_when_no_template_of_its_kind_is_left(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->forEntity()->published()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $default = VaultDefaultTemplate::factory()->create([
            'vault_id' => $vault->id,
            'applies_to' => 'entity',
            'entity_type_id' => $template->structure->entity_type_id,
            'structure_version_id' => $template->structure->published_version_id,
        ]);
        $another = VaultDefaultTemplate::factory()->create(['vault_id' => $vault->id]);

        new DestroyTemplate(user: $user, template: $template)->execute();

        $this->assertModelMissing($default);
        $this->assertModelExists($another);
    }
}
