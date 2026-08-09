<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateSectionPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_up_the_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $first = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 1]);
        $second = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 2]);

        $this->actingAs($user)->put(route('settings.templateSectionPosition.update', [$template, $second]), ['position' => 1])
            ->assertRedirect();

        $this->assertEquals(1, $second->fresh()->position);
        $this->assertEquals(2, $first->fresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_that_is_not_a_number(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $row = TemplateVersionSection::factory()->create(['template_version_id' => $version->id]);

        $this->actingAs($user)->put(route('settings.templateSectionPosition.update', [$template, $row]), ['position' => 'first'])
            ->assertSessionHasErrors('position');
    }

    #[Test]
    public function it_explains_why_a_published_version_cannot_be_reordered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $row = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 1]);
        TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 2]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->actingAs($user)->put(route('settings.templateSectionPosition.update', [$template, $row->fresh()]), ['position' => 2])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(1, $row->fresh()->position);
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $michael->account_id]);
        $row = TemplateVersionSection::factory()->create();

        $this->actingAs($michael)->put(route('settings.templateSectionPosition.update', [$template, $row]), ['position' => 1])
            ->assertNotFound();
    }
}
