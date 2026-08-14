<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateBuilderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_sections_and_the_fields(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Paper stock']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('Paper stock')
            ->assertSee('Weight');
    }

    #[Test]
    public function it_opens_the_field_the_address_names(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.field', [
            'template' => $template,
            'page' => $version->pages()->sole()->key,
            'field' => $field->id,
        ]))
            ->assertStatus(200)
            ->assertSee(__('Delete the field'));
    }

    /**
     * The bug this replaced: opening a field from a second tab used to send the
     * screen back to the first one, because the address said nothing about
     * which page was open.
     */
    #[Test]
    public function it_stays_on_the_page_a_field_was_opened_from(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);
        $this->sectionOn($user, $version, $version->pages()->firstOrFail(), 'Paper stock');
        $row = $this->sectionOn($user, $version, $events, 'Jobs');
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'account_id' => $user->account_id,
            'label' => 'Employer',
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.field', [
            'template' => $template,
            'page' => $events->key,
            'field' => $field->id,
        ]))
            ->assertStatus(200)
            ->assertSee('Employer')
            ->assertSee('Jobs')
            ->assertDontSee('Paper stock');
    }

    #[Test]
    public function it_offers_sharing_the_template_and_its_sections_once_they_are_published(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertDontSee(__('Share this template'))
            ->assertDontSee(__('Share this section'));

        $sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $template->update(['published_version_id' => $version->id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertSee(__('Share this template'))
            ->assertSee(__('Share this section'));
    }

    #[Test]
    public function it_says_a_template_with_no_field_open_has_none(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee(__('Pick a field on the left to edit it. What you change applies to this draft only.'));
    }

    #[Test]
    public function it_shows_the_pages_as_tabs(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('General')
            ->assertSee('Life events')
            ->assertSee('Add a page')
            ->assertSee('This page has nothing on it yet');
    }

    /**
     * The card asking for a name has to come back holding what was typed, and
     * the builder around it carries a form editing a field with a label of its
     * own, so the two do not share an error bag.
     */
    #[Test]
    public function it_reopens_the_card_that_asks_for_a_page_name_when_it_was_rejected(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)
            ->from(route('settings.templateBuilder.index', $template))
            ->post(route('settings.templatePages.create', $template), ['label' => '']);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('x-data="{ open: true }"', false);
    }

    #[Test]
    public function it_shows_only_the_sections_of_the_page_named_in_the_query_string(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);
        $this->sectionOn($user, $version, $version->pages()->firstOrFail(), 'Paper stock');
        $this->sectionOn($user, $version, $events, 'Jobs');

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('Paper stock')
            ->assertDontSee('Jobs');

        $this->actingAs($user)->get(route('settings.templateBuilder.index', ['template' => $template, 'page' => $events->key]))
            ->assertStatus(200)
            ->assertSee('Jobs')
            ->assertDontSee('Paper stock');
    }

    #[Test]
    public function it_falls_back_to_the_first_page_when_the_one_asked_for_is_unknown(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $this->sectionOn($user, $version, $version->pages()->firstOrFail(), 'Paper stock');

        $this->actingAs($user)->get(route('settings.templateBuilder.index', ['template' => $template, 'page' => 'nothing-answers-to-this']))
            ->assertStatus(200)
            ->assertSee('Paper stock');
    }

    #[Test]
    public function it_offers_to_move_a_section_only_once_there_is_another_page(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $this->sectionOn($user, $version, $version->pages()->firstOrFail(), 'Paper stock');

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertDontSee('Move to');

        TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('Move to');
    }

    /**
     * A template whose only version was thrown away still has to open, with the
     * way forward on the screen rather than a crash.
     */
    #[Test]
    public function it_opens_a_template_that_has_no_version_at_all(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee(__('Start a draft'));
    }

    #[Test]
    public function it_does_not_show_the_template_of_another_account(): void
    {
        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->actingAs($michael)->get(route('settings.templateBuilder.index', $template))->assertNotFound();
    }

    #[Test]
    public function it_turns_a_visitor_away(): void
    {
        $template = Template::factory()->create();

        $this->get(route('settings.templateBuilder.index', $template))->assertRedirect(route('auth.login.new'));
    }

    private function sectionOn(User $user, TemplateVersion $version, TemplateVersionPage $page, string $name): TemplateVersionSection
    {
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $name]);

        return TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $page->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);
    }
}
