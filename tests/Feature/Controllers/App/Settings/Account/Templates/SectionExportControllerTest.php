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

class SectionExportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_file_before_it_goes_anywhere(): void
    {
        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $this->actingAs($user)->get(route('settings.sectionExport.index', [$row->templateVersion->template, $row->id]))
            ->assertStatus(200)
            ->assertSee('personal-details.json')
            ->assertSee('&quot;format&quot;: &quot;section&quot;', false);
    }

    #[Test]
    public function it_hands_over_the_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $response = $this->actingAs($user)
            ->get(route('settings.sectionExport.show', [$row->templateVersion->template, $row->id]));

        $response->assertOk();
        $response->assertDownload('personal-details.json');

        $file = json_decode($response->streamedContent(), true);

        $this->assertEquals('section', $file['monica']['format']);
        $this->assertEquals('Personal details', $file['section']['name']);
        $this->assertEquals('Dunder Mifflin contact', $file['from_template']['name']);
    }

    #[Test]
    public function it_logs_the_download(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $this->actingAs($user)
            ->get(route('settings.sectionExport.show', [$row->templateVersion->template, $row->id]))
            ->streamedContent();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionExported;
        });
    }

    #[Test]
    public function it_does_not_hand_over_a_section_on_another_template(): void
    {
        $user = User::factory()->create();
        $row = $this->publishedRow($user);
        $other = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.sectionExport.show', [$other, $row->id]))
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_hand_over_a_section_in_another_account(): void
    {
        $row = $this->publishedRow(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('settings.sectionExport.show', [$row->templateVersion->template, $row->id]))
            ->assertNotFound();
    }

    private function publishedRow(User $user): TemplateVersionSection
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
            'slug' => 'dunder-mifflin-contact',
        ]);

        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $section = Section::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Personal details',
            'slug' => 'personal-details',
        ]);

        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Nickname']);

        $row = TemplateVersionSection::factory()->create([
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

        return $row->fresh();
    }
}
