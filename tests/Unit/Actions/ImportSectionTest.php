<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ImportSection;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFile;
use App\Helpers\ValueListSnapshot;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_the_section_the_file_describes_on_the_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template, $page] = $this->draft($user);

        $row = new ImportSection(user: $user, template: $template, page: $page, raw: $file)->execute();

        $this->assertInstanceOf(StructureVersionSection::class, $row);
        $this->assertEquals($page->id, $row->structure_version_page_id);
        $this->assertEquals('Personal details', $row->sectionVersion->section->name);
        $this->assertCount(3, $row->sectionVersion->fields);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job) use ($template): bool {
            return $job->action === UserActionEnum::SectionImported
                && $job->isAbout($template)
                && $job->parameters === ['section' => 'Personal details', 'name' => $template->name];
        });
    }

    #[Test]
    public function it_lands_frozen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template, $page] = $this->draft($user);

        $row = new ImportSection(user: $user, template: $template, page: $page, raw: $file)->execute();

        $this->assertEquals(VersionStatusEnum::Published, $row->sectionVersion->status);
        $this->assertEquals(1, $row->sectionVersion->version_number);
        $this->assertEquals(1, $row->sectionVersion->usage_count);
        $this->assertEquals($row->section_version_id, $row->sectionVersion->section->published_version_id);
    }

    #[Test]
    public function it_lands_at_the_end_of_the_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template, $page] = $this->draft($user);

        StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => $page->structure_version_id,
            'structure_version_page_id' => $page->id,
            'position' => 4,
        ]);

        $row = new ImportSection(user: $user, template: $template, page: $page, raw: $file)->execute();

        $this->assertEquals(5, $row->position);
    }

    #[Test]
    public function it_points_at_the_list_of_choices_the_account_already_curates(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template, $page] = $this->draft($user);
        $lists = ValueList::query()->where('account_id', $user->account_id)->count();

        $row = new ImportSection(user: $user, template: $template, page: $page, raw: $file)->execute();

        $this->assertEquals($lists, ValueList::query()->where('account_id', $user->account_id)->count());
        $this->assertEquals(
            ValueList::query()->where('account_id', $user->account_id)->where('key', ValueListEnum::PhoneLabels->value)->value('id'),
            $row->sectionVersion->fields->firstWhere('key', 'label')->value_list_id,
        );
    }

    #[Test]
    public function it_refuses_a_file_holding_a_whole_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = StructureFile::encode(StructureFile::ofTemplate($this->publishedTemplate($user)));
        [$template, $page] = $this->draft($user);

        $this->expectException(StructureFileIsNotReadable::class);

        new ImportSection(user: $user, template: $template, page: $page, raw: $file)->execute();
    }

    #[Test]
    public function it_refuses_a_version_nobody_can_change(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        $file = StructureFile::encode(StructureFile::ofSection($template->publishedVersion->assembly->first()));
        $page = $template->publishedVersion->pages()->firstOrFail();

        $this->expectException(PublishedVersionIsFrozen::class);

        new ImportSection(user: $user, template: $template, page: $page, raw: $file)->execute();
    }

    #[Test]
    public function it_refuses_a_template_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [, $page] = $this->draft($user);

        $this->expectException(ModelNotFoundException::class);

        new ImportSection(user: $user, template: Template::factory()->create(), page: $page, raw: $file)->execute();
    }

    #[Test]
    public function it_refuses_a_page_that_is_not_on_this_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template] = $this->draft($user);
        [, $elsewhere] = $this->draft($user);

        $this->expectException(ModelNotFoundException::class);

        new ImportSection(user: $user, template: $template, page: $elsewhere, raw: $file)->execute();
    }

    #[Test]
    public function it_writes_nothing_at_all_when_the_file_is_refused(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $page] = $this->draft($user);
        $sections = Section::query()->count();

        try {
            new ImportSection(user: $user, template: $template, page: $page, raw: 'Michael Scott')->execute();
        } catch (StructureFileIsNotReadable) {
        }

        $this->assertEquals($sections, Section::query()->count());
    }

    private function exportedSection(User $user): string
    {
        $template = $this->publishedTemplate($user);

        return StructureFile::encode(StructureFile::ofSection($template->publishedVersion->assembly->first()));
    }

    /** @return array{Template, StructureVersionPage} */
    private function draft(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $version = StructureVersion::factory()->create([
            'account_id' => $user->account_id,
            'structure_id' => $template->structure->id,
        ]);

        return [$template, $version->pages()->firstOrFail()];
    }

    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
        ]);

        $version = StructureVersion::factory()->create([
            'account_id' => $user->account_id,
            'structure_id' => $template->structure->id,
        ]);

        $section = Section::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Personal details',
        ]);

        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $user->account_id,
            'section_id' => $section->id,
        ]);

        $list = ValueList::factory()->supplied(ValueListEnum::PhoneLabels)->create([
            'account_id' => $user->account_id,
        ]);

        ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Mobile',
        ]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'key' => 'nickname',
            'label' => 'Nickname',
            'position' => 1,
        ]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'type' => FieldTypeEnum::Select,
            'key' => 'label',
            'label' => 'Label',
            'position' => 2,
            'value_list_id' => $list->id,
        ]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'key' => 'number',
            'label' => 'Number',
            'position' => 3,
        ]);

        StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages()->firstOrFail()->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->resolved_value_lists = ValueListSnapshot::of($sectionVersion);
        $sectionVersion->published_at = now();
        $sectionVersion->save();

        $section->published_version_id = $sectionVersion->id;
        $section->save();

        $version->status = VersionStatusEnum::Published;
        $version->published_at = now();
        $version->save();

        $template->structure->update(['published_version_id' => $version->id]);

        return $template->fresh();
    }
}
