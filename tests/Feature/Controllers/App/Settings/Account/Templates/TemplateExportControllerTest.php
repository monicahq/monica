<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateExportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_file_before_it_goes_anywhere(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $this->actingAs($user)->get(route('settings.templateExport.index', $template))
            ->assertStatus(200)
            ->assertSee('dunder-mifflin-contact-v1.json')
            ->assertSee('&quot;format&quot;: &quot;template&quot;', false)
            ->assertSee('Nickname');
    }

    #[Test]
    public function it_hands_over_the_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $response = $this->actingAs($user)->get(route('settings.templateExport.show', $template));

        $response->assertOk();
        $response->assertDownload('dunder-mifflin-contact-v1.json');

        $file = json_decode($response->streamedContent(), true);

        $this->assertEquals('template', $file['monica']['format']);
        $this->assertEquals('Dunder Mifflin contact', $file['template']['name']);
    }

    #[Test]
    public function it_logs_the_download_and_not_the_reading(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $this->actingAs($user)->get(route('settings.templateExport.index', $template));

        Queue::assertNotPushed(LogUserAction::class);

        $this->actingAs($user)->get(route('settings.templateExport.show', $template))->streamedContent();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateExported;
        });
    }

    #[Test]
    public function it_is_not_offered_for_a_template_nobody_has_published(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.templateExport.index', $template))->assertNotFound();
        $this->actingAs($user)->get(route('settings.templateExport.show', $template))->assertNotFound();
    }

    #[Test]
    public function it_does_not_hand_over_a_template_in_another_account(): void
    {
        $template = $this->publishedTemplate(User::factory()->create());

        $this->actingAs(User::factory()->create())->get(route('settings.templateExport.show', $template))
            ->assertNotFound();
    }

    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
            'slug' => 'dunder-mifflin-contact',
        ]);

        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Personal details']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Nickname']);

        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->published_at = now();
        $sectionVersion->save();

        $section->published_version_id = $sectionVersion->id;
        $section->save();

        $version->status = VersionStatusEnum::Published;
        $version->published_at = now();
        $version->save();

        $template->published_version_id = $version->id;
        $template->save();

        return $template->fresh();
    }
}
