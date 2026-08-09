<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateVersionPublicationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_publishes_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $draft]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertEquals(VersionStatusEnum::Published, $draft->fresh()->status);
        $this->assertEquals($draft->id, $template->fresh()->published_version_id);
    }

    /**
     * A template version cannot be frozen while it points at a section somebody
     * is still editing, and the user has no separate screen for a section, so
     * the sections go with it.
     */
    #[Test]
    public function it_publishes_the_sections_it_carries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $draft->id,
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
        $draft = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $draft->id,
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
        $first = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $second = TemplateVersion::factory()->create([
            'template_id' => $template->id,
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
        $first = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $second = TemplateVersion::factory()->create([
            'template_id' => $template->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $second]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(VersionStatusEnum::Draft, $second->fresh()->status);
    }

    #[Test]
    public function it_explains_why_a_published_version_cannot_be_published_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->actingAs($user)->put(route('settings.templateVersionPublication.update', [$template, $published]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();
        $draft = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($michael)->put(route('settings.templateVersionPublication.update', [$template, $draft]))
            ->assertNotFound();
    }
}
