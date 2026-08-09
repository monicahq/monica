<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DuplicateTemplate;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DuplicateTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_copies_a_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $copy = new DuplicateTemplate(user: $user, template: $template)->execute();

        $this->assertEquals('Copy of Contact', $copy->name);
        $this->assertEquals('copy-of-contact', $copy->slug);
        $this->assertEquals($template->applies_to, $copy->applies_to);
        $this->assertFalse($copy->is_system);
    }

    #[Test]
    public function it_takes_the_name_and_the_slug_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $copy = new DuplicateTemplate(
            user: $user,
            template: $template,
            name: 'Contact, mine',
            slug: 'contact-mine',
        )->execute();

        $this->assertEquals('Contact, mine', $copy->name);
        $this->assertEquals('contact-mine', $copy->slug);
    }

    /**
     * A template the product supplied cannot be edited in place, and the copy
     * has to be an ordinary template of the user's from the first moment.
     */
    #[Test]
    public function it_copies_a_supplied_template_into_an_ordinary_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->supplied()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $copy = new DuplicateTemplate(user: $user, template: $template)->execute();

        $this->assertFalse($copy->is_system);
        $this->assertNull($copy->name_translation_key);
    }

    /**
     * Nothing is published on somebody's behalf: the copy has to be looked at
     * first, which is also the moment to notice what needs changing.
     */
    #[Test]
    public function it_leaves_the_copy_as_an_unpublished_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $copy = new DuplicateTemplate(user: $user, template: $template)->execute();
        $version = $copy->versions()->first();

        $this->assertNull($copy->published_version_id);
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals(VersionStatusEnum::Draft, $version->status);
        $this->assertNull($version->previous_version_id);
    }

    /**
     * A section is frozen and can sit in any number of template versions, so the copy points
     * at the same rows rather than at copies of them.
     */
    #[Test]
    public function it_carries_the_same_sections_rather_than_copies_of_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'title_override' => 'Where they live',
        ]);

        $before = $sectionVersion->usage_count;

        $copy = new DuplicateTemplate(user: $user, template: $template)->execute();
        $row = $copy->versions()->first()->assembly()->first();

        $this->assertEquals($sectionVersion->id, $row->section_version_id);
        $this->assertEquals('Where they live', $row->title_override);
        $this->assertEquals($before + 1, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_copies_the_published_version_rather_than_the_newest_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $published = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $published->id,
            'section_version_id' => $sectionVersion->id,
        ]);
        $published->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $template->update(['published_version_id' => $published->id]);

        TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 2]);

        $copy = new DuplicateTemplate(user: $user, template: $template->fresh())->execute();

        $this->assertEquals(1, $copy->versions()->first()->assembly()->count());
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DuplicateTemplate(user: $michael, template: $template)->execute();
    }

    #[Test]
    public function it_writes_the_copy_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        new DuplicateTemplate(user: $user, template: $template)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateDuplication
                && $job->parameters === ['source' => 'Contact', 'name' => 'Copy of Contact'];
        });
    }
}
