<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\VersionStatusEnum;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateBuilderViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_draft_rather_than_the_published_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $published->id]);
        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertTrue($viewModel->version()->is($draft));
        $this->assertTrue($viewModel->isEditable());
    }

    #[Test]
    public function it_opens_a_published_template_read_only(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $published->id]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertFalse($viewModel->isEditable());
    }

    #[Test]
    public function it_offers_sharing_only_on_the_version_the_template_stands_behind(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $published->id]);

        $this->assertTrue(new TemplateBuilderViewModel(user: $user, template: $template->fresh())->isShareable());

        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);

        $this->assertFalse(new TemplateBuilderViewModel(user: $user, template: $template->fresh())->isShareable());
    }

    #[Test]
    public function it_does_not_offer_sharing_a_template_nobody_has_published(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertFalse($viewModel->isShareable());
    }

    #[Test]
    public function it_lists_the_sections_with_their_fields(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'label' => 'Weight',
            'help_text' => 'In kilograms',
            'type' => FieldTypeEnum::Number,
        ]);

        $sections = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();

        $this->assertCount(1, $sections);
        $this->assertEquals('Weight', $sections[0]['fields'][0]['label']);
        $this->assertEquals('In kilograms', $sections[0]['fields'][0]['help']);
    }

    #[Test]
    public function it_shows_what_a_repeater_asks_for_inside_it_rather_than_beside_it(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $repeater = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'label' => 'Document',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ]);
        $child = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'label' => 'File',
            'type' => FieldTypeEnum::File,
            'parent_field_id' => $repeater->id,
        ]);

        $sections = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();

        $this->assertCount(1, $sections[0]['fields']);
        $this->assertEquals($repeater->id, $sections[0]['fields'][0]['id']);

        $this->assertCount(1, $sections[0]['fields'][0]['children']);
        $this->assertEquals($child->id, $sections[0]['fields'][0]['children'][0]['id']);
        $this->assertEquals('File', $sections[0]['fields'][0]['children'][0]['label']);
        $this->assertEquals([], $sections[0]['fields'][0]['children'][0]['children']);
    }

    #[Test]
    public function it_shows_the_heading_this_template_gave_the_section(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $row->update(['title_override' => 'Where they live']);

        $sections = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();

        $this->assertEquals('Where they live', $sections[0]['name']);
    }

    #[Test]
    public function it_warns_that_a_shared_section_will_be_copied(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);

        $alone = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();
        $this->assertFalse($alone[0]['willBeCopied']);

        $row->sectionVersion->update(['usage_count' => 2]);

        $shared = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();
        $this->assertTrue($shared[0]['willBeCopied']);
    }

    #[Test]
    public function it_opens_the_field_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'label' => 'Weight']);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $field->id);

        $this->assertEquals('Weight', $viewModel->selectedField()['label']);
        $this->assertEquals($row->id, $viewModel->selectedField()['rowId']);
        $this->assertTrue($viewModel->sections()[0]['fields'][0]['isSelected']);
    }

    #[Test]
    public function it_says_what_an_open_relation_points_at(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $contact = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'type' => FieldTypeEnum::Relation,
            'relation_target' => RelationTargetEnum::Contact,
        ]);
        $animals = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals', 'is_active' => false]);
        $entity = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'type' => FieldTypeEnum::Relation,
            'relation_target' => RelationTargetEnum::Entity,
            'relation_entity_type_id' => $animals->id,
        ]);

        $toSomebody = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $contact->id)->selectedField();
        $toAnAnimal = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $entity->id)->selectedField();

        $this->assertTrue($toSomebody['isRelation']);
        $this->assertFalse($toSomebody['relationTargetIsInactive']);
        $this->assertEquals('Animals', $toAnAnimal['relationTargetLabel']);
        $this->assertTrue($toAnAnimal['relationTargetIsInactive']);
    }

    #[Test]
    public function it_offers_to_add_a_field_inside_an_open_repeater_of_a_draft(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $caregivers = Field::factory()->create(['section_version_id' => $row->section_version_id, 'type' => FieldTypeEnum::Repeater]);
        $weight = Field::factory()->create(['section_version_id' => $row->section_version_id, 'type' => FieldTypeEnum::Number]);

        $repeater = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $caregivers->id)->selectedField();
        $number = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $weight->id)->selectedField();

        $this->assertEquals(route('settings.childFields.new', [$template, $row, $caregivers]), $repeater['addInsideUrl']);
        $this->assertNull($number['addInsideUrl']);
    }

    #[Test]
    public function it_gives_the_kinds_of_file_an_open_field_takes_as_one_line(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'type' => FieldTypeEnum::Image,
            'options' => ['accepted_mime_types' => ['image/png', 'image/jpeg']],
        ]);

        $selected = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $field->id)->selectedField();

        $this->assertTrue($selected['holdsAFile']);
        $this->assertEquals('image/png, image/jpeg', $selected['acceptedMimeTypes']);
    }

    #[Test]
    public function it_ignores_a_field_that_is_not_on_this_template(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $elsewhere = Field::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $elsewhere->id);

        $this->assertNull($viewModel->selectedField());
    }

    #[Test]
    public function it_ignores_a_field_from_another_account(): void
    {
        $michael = User::factory()->create();
        [$template] = $this->templateCarryingASection($michael);
        $theirs = Field::factory()->create();

        $viewModel = new TemplateBuilderViewModel(user: $michael, template: $template->fresh(), selectedFieldId: $theirs->id);

        $this->assertNull($viewModel->selectedField());
    }

    #[Test]
    public function it_lists_the_pages_of_the_version_with_the_fields_on_each(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'label' => 'Life events']);
        Field::factory()->count(2)->create(['section_version_id' => $row->section_version_id]);

        $pages = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->pages();

        $this->assertCount(2, $pages);
        $this->assertEquals(['General', 'Life events'], array_column($pages, 'label'));
        $this->assertEquals([2, 0], array_column($pages, 'fieldCount'));
        $this->assertTrue($pages[0]['isSelected']);
        $this->assertFalse($pages[1]['isSelected']);
    }

    #[Test]
    public function it_opens_the_page_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'label' => 'Life events']);

        $viewModel = new TemplateBuilderViewModel(
            user: $user,
            template: $template->fresh(),
            selectedPageKey: $events->key,
        );

        $this->assertTrue($viewModel->selectedPage()->is($events));
        $this->assertEquals([], $viewModel->sections());
    }

    #[Test]
    public function it_falls_back_to_the_first_page_when_the_one_asked_for_is_not_on_this_version(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $elsewhere = StructureVersionPage::factory()->create();

        $viewModel = new TemplateBuilderViewModel(
            user: $user,
            template: $template->fresh(),
            selectedPageKey: $elsewhere->key,
        );

        $this->assertEquals('General', $viewModel->selectedPage()->label);
        $this->assertCount(1, $viewModel->sections());
    }

    #[Test]
    public function it_shows_only_the_sections_of_the_page_that_is_open(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'label' => 'Life events']);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Jobs']);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $events->id,
            'section_version_id' => SectionVersion::factory()->create(['section_id' => $section->id])->id,
        ]);

        $onGeneral = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->sections();
        $onEvents = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedPageKey: $events->key)->sections();

        $this->assertEquals(['Address'], array_column($onGeneral, 'name'));
        $this->assertEquals(['Jobs'], array_column($onEvents, 'name'));
    }

    #[Test]
    public function it_names_the_page_of_a_field_that_is_open_from_another_tab(): void
    {
        $user = User::factory()->create();
        [$template, $row] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'label' => 'Life events']);
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'account_id' => $user->account_id,
            'label' => 'Street',
            'type' => FieldTypeEnum::Text,
        ]);

        $fromItsOwnPage = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedFieldId: $field->id);
        $fromAnother = new TemplateBuilderViewModel(
            user: $user,
            template: $template->fresh(),
            selectedFieldId: $field->id,
            selectedPageKey: $events->key,
        );

        $this->assertEquals('Address', $fromItsOwnPage->selectedField()['sectionName']);
        $this->assertEquals('General › Address', $fromAnother->selectedField()['sectionName']);
    }

    #[Test]
    public function it_refuses_to_offer_the_removal_of_the_last_page(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $viewModel = new TemplateBuilderViewModel(user: $user, template: $template->fresh());

        $this->assertFalse($viewModel->canRemovePage());
    }

    #[Test]
    public function it_refuses_to_offer_the_removal_of_a_page_still_holding_sections(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id]);

        $onGeneral = new TemplateBuilderViewModel(user: $user, template: $template->fresh());
        $onEvents = new TemplateBuilderViewModel(user: $user, template: $template->fresh(), selectedPageKey: $events->key);

        $this->assertFalse($onGeneral->canRemovePage());
        $this->assertTrue($onEvents->canRemovePage());
    }

    #[Test]
    public function it_offers_the_other_pages_a_section_could_move_to(): void
    {
        $user = User::factory()->create();
        [$template] = $this->templateCarryingASection($user);
        $version = $template->versions()->sole();
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'label' => 'Life events']);

        $others = new TemplateBuilderViewModel(user: $user, template: $template->fresh())->otherPages();

        $this->assertEquals([['id' => $events->id, 'uuid' => $events->uuid, 'label' => 'Life events']], $others);
    }

    #[Test]
    public function it_has_a_history_only_once_the_template_has_more_than_one_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        $this->assertFalse(new TemplateBuilderViewModel(user: $user, template: $template->fresh())->hasHistory());

        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);

        $this->assertTrue(new TemplateBuilderViewModel(user: $user, template: $template->fresh())->hasHistory());
    }

    /** @return array{0: Template, 1: StructureVersionSection} */
    private function templateCarryingASection(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'status' => VersionStatusEnum::Draft]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->used(1)->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$template, $row];
    }
}
