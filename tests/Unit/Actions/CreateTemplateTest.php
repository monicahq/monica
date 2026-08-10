<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateTemplate;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_describes_what_a_record_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new CreateTemplate(
            user: $user,
            name: 'Contact',
            description: 'The one every vault starts with',
        )->execute();

        $this->assertInstanceOf(Template::class, $template);

        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'account_id' => $user->account_id,
            'applies_to' => TemplateAppliesToEnum::Contact->value,
            'entity_type_id' => null,
            'name' => 'Contact',
            'description' => 'The one every vault starts with',
            'slug' => 'contact',
            'is_system' => false,
            'is_active' => true,
            'published_version_id' => null,
            'position' => 1,
        ]);
    }

    /**
     * A template with no version at all is a name nothing can be done with.
     */
    #[Test]
    public function it_starts_the_template_with_an_empty_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();

        $this->assertDatabaseHas('template_versions', [
            'template_id' => $template->id,
            'account_id' => $user->account_id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft->value,
            'previous_version_id' => null,
            'record_count' => 0,
        ]);
    }

    /**
     * A version with no page has nowhere to put a section.
     */
    #[Test]
    public function it_starts_the_draft_with_the_page_the_product_supplies(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();
        $version = $template->versions()->sole();

        $this->assertDatabaseHas('template_version_pages', [
            'account_id' => $user->account_id,
            'template_version_id' => $version->id,
            'key' => 'general',
            'label' => null,
            'label_translation_key' => 'General',
            'position' => 1,
        ]);

        $this->assertEquals('General', $version->pages()->sole()->label);
    }

    #[Test]
    public function it_names_the_kind_of_record_it_describes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        $template = new CreateTemplate(
            user: $user,
            name: 'Animal',
            appliesTo: TemplateAppliesToEnum::Entity,
            entityType: $entityType,
        )->execute();

        $this->assertEquals($entityType->id, $template->entity_type_id);
    }

    #[Test]
    public function it_refuses_to_describe_an_entity_without_saying_which_kind(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        new CreateTemplate(
            user: $user,
            name: 'Animal',
            appliesTo: TemplateAppliesToEnum::Entity,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_kind_of_record_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $entityType = EntityType::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateTemplate(
            user: $michael,
            name: 'Animal',
            appliesTo: TemplateAppliesToEnum::Entity,
            entityType: $entityType,
        )->execute();
    }

    #[Test]
    public function it_puts_a_new_template_at_the_end_of_the_picker(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'position' => 4]);

        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();

        $this->assertSame(5, $template->position);
    }

    #[Test]
    public function it_takes_the_slug_it_is_given_rather_than_one_made_from_the_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new CreateTemplate(user: $user, name: 'Meeting notes', slug: 'meeting')->execute();

        $this->assertEquals('meeting', $template->slug);
    }

    #[Test]
    public function it_counts_up_when_the_slug_it_wants_is_taken(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $first = new CreateTemplate(user: $user, name: 'Contact')->execute();
        $second = new CreateTemplate(user: $user, name: 'Contact')->execute();

        $this->assertEquals('contact', $first->slug);
        $this->assertEquals('contact-2', $second->slug);
    }

    #[Test]
    public function it_writes_the_template_in_the_language_of_whoever_made_it(): void
    {
        Queue::fake();

        $user = User::factory()->create(['locale' => 'fr_FR']);

        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();

        $this->assertEquals('fr_FR', $template->locale);
    }

    #[Test]
    public function it_strips_html_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new CreateTemplate(user: $user, name: '<b>Contact</b>')->execute();

        $this->assertEquals('Contact', $template->name);
    }

    #[Test]
    public function it_writes_the_creation_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateTemplate(user: $user, name: 'Contact')->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job) use ($user): bool {
            return $job->action === UserActionEnum::TemplateCreation
                && $job->parameters === ['name' => 'Contact']
                && $job->user->is($user);
        });
    }
}
