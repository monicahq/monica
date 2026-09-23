<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\VaultColourEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use DOMDocument;
use DOMXPath;
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
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Paper stock']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('Paper stock')
            ->assertSee('Weight');
    }

    #[Test]
    public function it_says_that_a_section_and_a_field_can_be_dragged(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $response->assertStatus(200);
        $response->assertSee('data-row-id="'.$field->id.'"', escape: false);
        $response->assertSee('data-position-form="'.$field->id.'"', escape: false);
        $response->assertSee(route('settings.fieldPosition.update', [$template, $row->id, $field->id]), escape: false);
    }

    #[Test]
    public function it_hides_the_grip_from_a_screen_reader(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $html = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))->getContent();

        preg_match_all('/<span[^>]*cursor-grab[^>]*>/', $html, $grips);

        $this->assertNotEmpty($grips[0], 'No grip was drawn at all.');

        foreach ($grips[0] as $grip) {
            $this->assertStringContainsString('aria-hidden="true"', $grip);
        }
    }

    #[Test]
    public function it_asks_a_repeater_which_end_it_collects_at_and_asks_nothing_else(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $repeater = Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ]);
        $plain = Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Weight',
            'type' => FieldTypeEnum::Number,
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $onTheRepeater = $this->actingAs($user)->get(route('settings.templateBuilder.field', [
            'template' => $template, 'page' => $version->pages->first()->key, 'field' => $repeater->id,
        ]));

        $onTheRepeater->assertStatus(200);
        $onTheRepeater->assertSee('New repetitions');
        $onTheRepeater->assertSee('New ones at the top');
        $onTheRepeater->assertSee('New ones at the bottom');

        $onThePlainField = $this->actingAs($user)->get(route('settings.templateBuilder.field', [
            'template' => $template, 'page' => $version->pages->first()->key, 'field' => $plain->id,
        ]));

        $onThePlainField->assertStatus(200);
        $onThePlainField->assertDontSee('New repetitions');
    }

    #[Test]
    public function it_marks_a_repeater_on_the_list_only_when_it_collects_at_the_front(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $repeater = Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertDontSee('New ones at the top');

        Field::query()->whereKey($repeater->id)->update(['repetition_order' => RepetitionOrderEnum::NewestFirst->value]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertSee('New ones at the top');
    }

    #[Test]
    public function it_opens_the_field_the_address_names(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
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

    #[Test]
    public function it_stays_on_the_page_a_field_was_opened_from(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'label' => 'Life events']);
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
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertDontSee(__('Share on marketplace'))
            ->assertDontSee(__('More actions'))
            ->assertDontSee(__('Share this section'));

        $sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $template->structure->update(['published_version_id' => $version->id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertSee(__('Share on marketplace'))
            ->assertSee(__('More actions'))
            ->assertSee(__('Share this section'));
    }

    #[Test]
    public function it_says_a_template_with_no_field_open_has_none(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee(__('Pick a field on the left to edit it. What you change applies to this draft only.'));
    }

    #[Test]
    public function it_shows_the_pages_as_tabs(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'label' => 'Life events']);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('General')
            ->assertSee('Life events')
            ->assertSee('Add a page')
            ->assertSee('This page has nothing on it yet');
    }

    #[Test]
    public function it_reopens_the_card_that_asks_for_a_page_name_when_it_was_rejected(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)
            ->from(route('settings.templateBuilder.index', $template))
            ->post(route('settings.templatePages.create', $template), ['label' => '']);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('x-data="{ open: true }"', false);
    }

    #[Test]
    public function it_offers_to_move_a_section_only_once_there_is_another_page(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $this->sectionOn($user, $version, $version->pages()->firstOrFail(), 'Paper stock');

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertDontSee('Move to');

        StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'label' => 'Life events']);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('Move to');
    }

    #[Test]
    public function it_opens_a_template_that_has_no_version_at_all(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee(__('Create a new version'));
    }

    #[Test]
    public function it_offers_the_version_history_once_the_template_has_more_than_one_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $response->assertStatus(200);
        $response->assertSee('Version history');
        $response->assertSee('href="'.route('settings.templateVersions.index', $template).'"', escape: false);
    }

    private function sectionOn(User $user, StructureVersion $version, StructureVersionPage $page, string $name): StructureVersionSection
    {
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $name]);

        return StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $page->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);
    }

    #[Test]
    public function it_offers_the_mark_picker_on_the_add_block(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $response->assertStatus(200);
        $response->assertSee('name="icon"', escape: false);
        $response->assertSee('name="colour"', escape: false);
        $response->assertSee('aria-controls="section-new-mark-picker"', escape: false);
    }

    #[Test]
    public function it_names_every_glyph_and_colour_in_the_picker_so_they_can_be_heard(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $this->assertSame(35, mb_substr_count($response->getContent(), 'aria-pressed="icon ==='));
        $this->assertSame(1, mb_substr_count($response->getContent(), 'aria-pressed="! icon'));
        $response->assertSee('>No icon<', escape: false);

        foreach (VaultColourEnum::cases() as $colour) {
            $response->assertSee(">{$colour->label()}<", escape: false);
        }

        $response->assertSee('>Neutral<', escape: false);
    }

    #[Test]
    public function it_draws_the_picker_only_where_the_version_can_be_changed(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $published->id]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template->fresh()));

        $response->assertStatus(200);
        $response->assertDontSee('aria-controls="section-new-mark-picker"', escape: false);
    }

    #[Test]
    public function it_offers_the_mark_picker_on_each_section_as_well_as_the_add_block(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Health']);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $response->assertSee('aria-controls="section-new-mark-picker"', escape: false);
        $response->assertSee("aria-controls=\"section-{$row->id}-mark-picker\"", escape: false);
        $response->assertSee('No other template carries this section, so the mark stays here.', escape: false);
    }

    #[Test]
    public function it_never_leaves_the_mark_as_the_only_difference_between_two_sections(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        foreach ([['Health', 'health', '#4f8a49'], ['Work', 'briefcase', '#2f7f86']] as [$name, $icon, $colour]) {
            $section = Section::factory()->marked($icon, $colour)->create([
                'account_id' => $user->account_id,
                'name' => $name,
            ]);
            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $response->assertSee('>Health<', escape: false);
        $response->assertSee('>Work<', escape: false);
        $this->assertGreaterThanOrEqual(2, mb_substr_count($response->getContent(), 'aria-hidden="true"'));
    }

    #[Test]
    public function it_pins_the_mark_picker_out_of_the_flow_so_the_section_card_cannot_clip_it(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $response->assertSee('x-anchor.bottom-start.offset.6="$refs.trigger"', escape: false);
        $response->assertDontSee('absolute top-full', escape: false);
    }

    #[Test]
    public function it_closes_the_mark_picker_when_a_save_is_on_its_way(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $response->assertSee('@ajax:before.window="open = false"', escape: false);
    }

    #[Test]
    public function it_leaves_exactly_one_glyph_showing_in_the_tile_before_alpine_boots(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->marked('profile', '#3f5fd0')->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);

        $content = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))->getContent();

        foreach ($this->tilesIn($content) as $where => $visible) {
            $this->assertCount(1, $visible, "The {$where} tile shows ".count($visible).' glyphs.');
        }

        $this->assertSame(
            ["icon === 'profile'", '! icon'],
            array_values(array_map(fn (array $visible): string => $visible[0], $this->tilesIn($content))),
        );
    }

    #[Test]
    public function it_picks_a_colour_from_a_solid_swatch_rather_than_from_the_pale_pair_it_draws(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        foreach (VaultColourEnum::cases() as $colour) {
            $response->assertSee("background: {$colour->value}", escape: false);
        }
    }

    /** @return array<string, list<string>> */
    private function tilesIn(string $content): array
    {
        $tiles = [];
        $segments = explode('x-data="{ tints:', $content);
        array_shift($segments);

        foreach ($segments as $index => $segment) {
            $tile = mb_substr($segment, 0, (int) mb_strpos($segment, 'sr-only'));

            preg_match_all(
                '/<span\s+x-show="(icon === \'[a-z]+\'|! icon)"\s*(style="display: none")?/',
                $tile,
                $matches,
                PREG_SET_ORDER,
            );

            $tiles["tile {$index}"] = array_values(array_map(
                fn (array $match): string => $match[1],
                array_filter($matches, fn (array $match): bool => ($match[2] ?? '') === ''),
            ));
        }

        return $tiles;
    }

    #[Test]
    public function it_keeps_the_mark_picker_open_while_a_glyph_and_a_colour_are_being_picked(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);

        $content = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))->getContent();

        $this->assertCount(2, $this->markTriggersIn($content));

        foreach ($this->markTriggersIn($content) as $where => $trigger) {
            $this->assertStringContainsString('@click.stop="open = ! open"', $trigger, "The {$where} tile does not stop its own click.");
            $this->assertStringNotContainsString('@click.outside', $trigger, "The {$where} tile shuts the panel on the first pick.");
        }

        foreach ($this->panelsIn($content) as $where => $panel) {
            $this->assertStringContainsString('@click.outside="open = false"', $panel, "The {$where} panel does not close on an outside click.");
        }
    }

    #[Test]
    public function it_posts_the_stored_mark_even_before_alpine_has_bound_the_fields(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->marked('profile', '#3f5fd0')->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template));

        $response->assertSee('name="icon" value="profile" :value="icon"', escape: false);
        $response->assertSee('name="colour" value="#3f5fd0" :value="colour"', escape: false);
    }

    /** @return array<string, string> */
    private function markTriggersIn(string $content): array
    {
        preg_match_all('/<button\\b[^>]*aria-controls="([^"]+)-mark-picker"[^>]*>/s', $content, $matches, PREG_SET_ORDER);

        $triggers = [];

        foreach ($matches as $match) {
            $triggers[$match[1]] = $match[0];
        }

        return $triggers;
    }

    /** @return array<string, string> */
    private function panelsIn(string $content): array
    {
        preg_match_all('/<div\s+x-cloak\s+x-show="open"[^>]*id="([^"]+)-mark-picker"/s', $content, $matches, PREG_SET_ORDER);

        $panels = [];

        foreach ($matches as $match) {
            $panels[$match[1]] = $match[0];
        }

        return $panels;
    }

    #[Test]
    public function it_declares_every_ref_in_the_scope_that_reaches_for_it(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->marked('profile', '#3f5fd0')->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);

        $content = $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))->getContent();

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($content);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $unreachable = [];

        foreach ($xpath->query('//*[@*[contains(., "$refs.")]]') as $consumer) {
            $scope = $xpath->query('ancestor-or-self::*[@x-data][1]', $consumer)->item(0);

            foreach ($consumer->attributes as $attribute) {
                preg_match_all('/\$refs\.([A-Za-z0-9_]+)/', $attribute->value, $matches);

                foreach ($matches[1] as $ref) {
                    $declared = $xpath->query("//*[@x-ref='{$ref}']");
                    $reachable = false;

                    foreach ($declared as $element) {
                        if ($xpath->query('ancestor::*[@x-data][1]', $element)->item(0)?->isSameNode($scope) === true) {
                            $reachable = true;
                        }
                    }

                    if (! $reachable) {
                        $unreachable[] = $ref;
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($unreachable)));
    }
}
