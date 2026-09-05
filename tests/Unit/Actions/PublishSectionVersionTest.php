<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\PublishSectionVersion;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishSectionVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_freezes_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);

        new PublishSectionVersion(user: $user, sectionVersion: $version)->execute();

        $version->refresh();

        $this->assertEquals(VersionStatusEnum::Published, $version->status);
        $this->assertNotNull($version->published_at);
        $this->assertEquals($version->id, $section->fresh()->published_version_id);
    }

    #[Test]
    public function it_takes_a_copy_of_the_lists_the_fields_read_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'name' => 'Genders', 'key' => 'genders']);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => 'female',
            'label' => 'Female',
            'position' => 1,
        ]);
        Field::factory()->choice($list)->create(['section_version_id' => $version->id]);

        new PublishSectionVersion(user: $user, sectionVersion: $version)->execute();

        $snapshot = $version->fresh()->resolved_value_lists;

        $this->assertEquals('genders', $snapshot[$list->id]['key']);
        $this->assertEquals('Genders', $snapshot[$list->id]['name']);
        $this->assertEquals($item->id, $snapshot[$list->id]['items'][0]['id']);
        $this->assertEquals('Female', $snapshot[$list->id]['items'][0]['label']);
    }

    #[Test]
    public function it_copies_only_the_lists_the_fields_point_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $used = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueList::factory()->create(['account_id' => $user->account_id]);
        Field::factory()->choice($used)->create(['section_version_id' => $version->id]);

        new PublishSectionVersion(user: $user, sectionVersion: $version)->execute();

        $this->assertCount(1, $version->fresh()->resolved_value_lists);
    }

    #[Test]
    public function it_copies_nothing_when_no_field_offers_choices(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $version->id]);

        new PublishSectionVersion(user: $user, sectionVersion: $version)->execute();

        $this->assertEquals([], $version->fresh()->resolved_value_lists);
    }

    #[Test]
    public function it_keeps_a_supplied_label_as_a_translation_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $list = ValueList::factory()->create([
            'account_id' => $user->account_id,
            'name' => null,
            'name_translation_key' => 'Genders',
        ]);
        ValueListItem::factory()->supplied()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
        ]);
        Field::factory()->choice($list)->create(['section_version_id' => $version->id]);

        new PublishSectionVersion(user: $user, sectionVersion: $version)->execute();

        $snapshot = $version->fresh()->resolved_value_lists;

        $this->assertNull($snapshot[$list->id]['name']);
        $this->assertEquals('Genders', $snapshot[$list->id]['name_translation_key']);
        $this->assertNull($snapshot[$list->id]['items'][0]['label']);
        $this->assertNotNull($snapshot[$list->id]['items'][0]['label_translation_key']);
    }

    #[Test]
    public function it_asks_for_a_change_note_from_the_second_version_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $first = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $second = SectionVersion::factory()->create([
            'section_id' => $section->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        new PublishSectionVersion(user: $user, sectionVersion: $second)->execute();
    }

    #[Test]
    public function it_records_what_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $first = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $second = SectionVersion::factory()->create([
            'section_id' => $section->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        new PublishSectionVersion(
            user: $user,
            sectionVersion: $second,
            changeNote: 'Added the field about allergies',
        )->execute();

        $this->assertEquals('Added the field about allergies', $second->fresh()->change_note);
    }

    #[Test]
    public function it_refuses_to_publish_something_that_is_not_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->published()->create(['section_id' => $section->id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new PublishSectionVersion(user: $user, sectionVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = SectionVersion::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new PublishSectionVersion(user: $michael, sectionVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_publication_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);

        new PublishSectionVersion(user: $user, sectionVersion: $version)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionVersionPublished
                && $job->parameters === ['name' => 'Address', 'version' => 1];
        });
    }
}
