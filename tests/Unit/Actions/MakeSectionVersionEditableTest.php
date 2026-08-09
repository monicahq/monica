<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MakeSectionVersionEditable;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MakeSectionVersionEditableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The ordinary case: a section somebody has just added to their own draft,
     * which nothing else is carrying.
     */
    #[Test]
    public function it_hands_back_a_draft_nobody_else_carries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->used(1)->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $result = new MakeSectionVersionEditable(user: $user, row: $row)->execute();

        $this->assertTrue($result->is($sectionVersion));
        $this->assertEquals(1, SectionVersion::query()->where('section_id', $sectionVersion->section_id)->count());
    }

    #[Test]
    public function it_gives_a_published_section_a_new_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->published()->used(1)->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $result = new MakeSectionVersionEditable(user: $user, row: $row)->execute();

        $this->assertFalse($result->is($sectionVersion));
        $this->assertEquals(VersionStatusEnum::Draft, $result->status);
        $this->assertEquals(2, $result->version_number);
        $this->assertEquals($sectionVersion->id, $result->previous_version_id);
    }

    /**
     * A draft a second template is carrying is as untouchable as a published
     * one: editing it would change what that other template asks for.
     */
    #[Test]
    public function it_gives_a_shared_draft_a_new_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->used(2)->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $result = new MakeSectionVersionEditable(user: $user, row: $row)->execute();

        $this->assertFalse($result->is($sectionVersion));
    }

    #[Test]
    public function it_points_the_template_at_the_new_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->published()->used(1)->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $result = new MakeSectionVersionEditable(user: $user, row: $row)->execute();

        $this->assertEquals($result->id, $row->fresh()->section_version_id);
        $this->assertEquals(1, $result->fresh()->usage_count);
        $this->assertEquals(0, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_copies_the_fields_into_the_new_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->used(1)->create(['account_id' => $user->account_id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'key' => 'street', 'label' => 'Street']);
        $sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $result = new MakeSectionVersionEditable(user: $user, row: $row)->execute();

        $this->assertEquals(['street'], $result->fields->pluck('key')->all());
        $this->assertEquals(['Street'], $result->fields->pluck('label')->all());
    }

    /**
     * The published version keeps everything it had, which is the whole reason
     * for making a copy rather than writing to it.
     */
    #[Test]
    public function it_leaves_the_published_version_untouched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->used(1)->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Street']);
        $sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new MakeSectionVersionEditable(user: $user, row: $row)->execute();

        $this->assertEquals(VersionStatusEnum::Published, $sectionVersion->fresh()->status);
        $this->assertEquals('Street', $field->fresh()->label);
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $row = TemplateVersionSection::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new MakeSectionVersionEditable(user: $michael, row: $row)->execute();
    }
}
