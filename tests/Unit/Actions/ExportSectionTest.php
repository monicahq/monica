<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ExportSection;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\ListingHasNothingToPublish;
use App\Helpers\StructureFile;
use App\Helpers\ValueListSnapshot;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
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

class ExportSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_one_section_down_as_a_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $file = new ExportSection(user: $user, template: $row->structureVersion->structure->owner, row: $row)->execute();

        $this->assertEquals('section', $file['monica']['format']);
        $this->assertEquals(StructureFile::FORMAT_VERSION, $file['monica']['format_version']);
        $this->assertEquals('Personal details', $file['section']['name']);
        $this->assertCount(1, $file['section']['fields']);
    }

    #[Test]
    public function it_says_which_template_the_section_came_out_of(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $file = new ExportSection(user: $user, template: $row->structureVersion->structure->owner, row: $row)->execute();

        $this->assertEquals(
            ['name' => 'Dunder Mifflin contact', 'version' => 1],
            $file['from_template'],
        );
    }

    #[Test]
    public function it_carries_the_lists_of_choices_the_fields_point_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = $this->publishedRow($user, withAValueList: true);

        $file = new ExportSection(user: $user, template: $row->structureVersion->structure->owner, row: $row)->execute();

        $this->assertArrayHasKey('branches', $file['value_lists']);
        $this->assertEquals('Scranton', $file['value_lists']['branches']['items'][0]['label']);
        $this->assertEquals('branches', $file['section']['fields'][0]['value_list_reference']);
    }

    #[Test]
    public function it_refuses_a_section_on_another_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = $this->publishedRow($user);
        $other = Template::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new ExportSection(user: $user, template: $other, row: $row)->execute();
    }

    #[Test]
    public function it_refuses_a_section_still_being_written(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $row->sectionVersion->status = VersionStatusEnum::Draft;
        $row->sectionVersion->save();

        $this->expectException(ListingHasNothingToPublish::class);

        new ExportSection(user: $user, template: $row->structureVersion->structure->owner, row: $row->fresh())->execute();
    }

    #[Test]
    public function it_logs_the_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        new ExportSection(user: $user, template: $row->structureVersion->structure->owner, row: $row)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionExported
                && $job->parameters === ['section' => 'Personal details', 'name' => 'Dunder Mifflin contact'];
        });
    }

    private function publishedRow(User $user, bool $withAValueList = false): StructureVersionSection
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
        ]);

        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $section = Section::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Personal details',
        ]);

        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Branch',
            'type' => $withAValueList ? FieldTypeEnum::Select : FieldTypeEnum::Text,
            'value_list_id' => $withAValueList ? $this->valueList($user)->id : null,
        ]);

        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        if ($withAValueList) {
            $sectionVersion->resolved_value_lists = array_values(ValueListSnapshot::of($sectionVersion));
        }

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->published_at = now();
        $sectionVersion->save();

        $section->published_version_id = $sectionVersion->id;
        $section->save();

        $version->status = VersionStatusEnum::Published;
        $version->published_at = now();
        $version->save();

        $template->structure->update(['published_version_id' => $version->id]);

        return $row->fresh();
    }

    private function valueList(User $user): ValueList
    {
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'key' => 'branches',
            'name' => 'Branches',
        ]);

        ValueListItem::factory()->create([
            'value_list_id' => $list->id,
            'label' => 'Scranton',
            'position' => 1,
        ]);

        return $list;
    }
}
