<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateSectionVersionDraft;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateSectionVersionDraftTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_draft_from_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $published = SectionVersion::factory()->published()->create(['section_id' => $section->id]);

        $draft = new CreateSectionVersionDraft(user: $user, sectionVersion: $published)->execute();

        $this->assertInstanceOf(SectionVersion::class, $draft);
        $this->assertEquals(2, $draft->version_number);
        $this->assertEquals(VersionStatusEnum::Draft, $draft->status);
        $this->assertEquals($published->id, $draft->previous_version_id);
        $this->assertEquals(0, $draft->usage_count);
    }

    #[Test]
    public function it_copies_every_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $published = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $published->id, 'key' => 'street', 'label' => 'Street', 'position' => 1]);
        Field::factory()->create(['section_version_id' => $published->id, 'key' => 'city', 'label' => 'City', 'position' => 2]);

        $draft = new CreateSectionVersionDraft(user: $user, sectionVersion: $published)->execute();

        $this->assertEquals(['street', 'city'], $draft->fields->pluck('key')->all());
        $this->assertEquals(['Street', 'City'], $draft->fields->pluck('label')->all());
    }

    /**
     * A child copied before its repeater would find the old parent, and the two
     * versions would end up sharing a field.
     */
    #[Test]
    public function it_points_a_copied_field_at_its_new_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $published = SectionVersion::factory()->create(['section_id' => $section->id]);
        $repeater = Field::factory()->repeater()->create([
            'section_version_id' => $published->id,
            'key' => 'phones',
            'position' => 1,
        ]);
        Field::factory()->create([
            'section_version_id' => $published->id,
            'key' => 'number',
            'parent_field_id' => $repeater->id,
            'position' => 1,
        ]);

        $draft = new CreateSectionVersionDraft(user: $user, sectionVersion: $published)->execute();

        $newRepeater = $draft->fields()->where('key', 'phones')->first();
        $newChild = $draft->fields()->where('key', 'number')->first();

        $this->assertEquals($newRepeater->id, $newChild->parent_field_id);
        $this->assertNotEquals($repeater->id, $newChild->parent_field_id);
    }

    #[Test]
    public function it_leaves_the_published_version_and_its_fields_exactly_as_they_were(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $published = SectionVersion::factory()->create(['section_id' => $section->id]);
        $field = Field::factory()->create(['section_version_id' => $published->id, 'label' => 'Street']);
        $published->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        new CreateSectionVersionDraft(user: $user, sectionVersion: $published)->execute();

        $this->assertEquals('Street', $field->fresh()->label);
        $this->assertEquals(1, $published->fresh()->fields()->count());
    }

    #[Test]
    public function it_numbers_the_draft_after_the_highest_version_there_is(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $first = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 5]);

        $draft = new CreateSectionVersionDraft(user: $user, sectionVersion: $first)->execute();

        $this->assertEquals(6, $draft->version_number);
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = SectionVersion::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateSectionVersionDraft(user: $michael, sectionVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_draft_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $published = SectionVersion::factory()->published()->create(['section_id' => $section->id]);

        new CreateSectionVersionDraft(user: $user, sectionVersion: $published)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionVersionDrafted
                && $job->parameters === ['name' => 'Address', 'version' => 2];
        });
    }
}
