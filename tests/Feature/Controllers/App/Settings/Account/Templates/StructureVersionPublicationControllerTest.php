<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionPublicationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_publishes_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $draft]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertEquals(VersionStatusEnum::Published, $draft->fresh()->status);
        $this->assertEquals($draft->id, $template->structure->fresh()->published_version_id);
    }

    #[Test]
    public function it_publishes_the_sections_it_carries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $draft->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $draft]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertEquals(VersionStatusEnum::Published, $sectionVersion->fresh()->status);
        $this->assertEquals(VersionStatusEnum::Published, $draft->fresh()->status);
    }

    #[Test]
    public function it_leaves_a_section_that_was_already_published_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $draft->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $draft]))
            ->assertRedirect();

        $this->assertEquals(1, $sectionVersion->fresh()->version_number);
        $this->assertEquals(VersionStatusEnum::Published, $draft->fresh()->status);
    }

    #[Test]
    public function it_records_what_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $second = StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $second]), [
            'change_note' => 'Added the allergies field',
        ])->assertRedirect();

        $this->assertEquals('Added the allergies field', $second->fresh()->change_note);
    }

    #[Test]
    public function it_explains_why_a_second_version_needs_a_note(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $second = StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $second]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(VersionStatusEnum::Draft, $second->fresh()->status);
    }

    #[Test]
    public function it_does_not_find_a_draft_of_another_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $elsewhere]))
            ->assertNotFound();

        $this->assertEquals(VersionStatusEnum::Draft, $elsewhere->fresh()->status);
    }
}
