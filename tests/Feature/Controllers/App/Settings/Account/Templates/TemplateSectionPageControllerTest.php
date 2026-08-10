<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateSectionPageControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_to_another_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $general = $version->pages()->firstOrFail();
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id]);
        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $general->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.templateSectionPage.update', [$template, $row]), [
            'page' => $events->uuid,
        ]);

        $response->assertRedirect(route('settings.templateBuilder.index', ['template' => $template, 'page' => $events->key]));
        $response->assertSessionHas('status');
        $this->assertEquals($events->id, $row->fresh()->template_version_page_id);
    }

    #[Test]
    public function it_refuses_a_page_on_another_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $row = TemplateVersionSection::factory()->create(['template_version_id' => $version->id]);
        $elsewhere = TemplateVersion::factory()->create(['account_id' => $user->account_id])->pages()->sole();

        $this->actingAs($user)->put(route('settings.templateSectionPage.update', [$template, $row]), [
            'page' => $elsewhere->uuid,
        ])->assertSessionHasErrors('page');

        $this->assertNotEquals($elsewhere->id, $row->fresh()->template_version_page_id);
    }

    #[Test]
    public function it_refuses_to_move_a_section_on_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $events = TemplateVersionPage::factory()->create(['template_version_id' => $version->id]);
        $row = TemplateVersionSection::factory()->create(['template_version_id' => $version->id]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->actingAs($user)->put(route('settings.templateSectionPage.update', [$template, $row]), [
            'page' => $events->uuid,
        ])->assertSessionHas('error');

        $this->assertNotEquals($events->id, $row->fresh()->template_version_page_id);
    }

    #[Test]
    public function it_does_not_find_a_section_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);
        $theirs = TemplateVersionSection::factory()->create();

        $this->actingAs($user)->put(route('settings.templateSectionPage.update', [$template, $theirs]), [
            'page' => $theirs->templateVersion->pages()->sole()->uuid,
        ])->assertNotFound();
    }
}
