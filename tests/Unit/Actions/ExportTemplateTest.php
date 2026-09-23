<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ExportTemplate;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\ListingHasNothingToPublish;
use App\Helpers\StructureFile;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_template_down_as_a_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $file = new ExportTemplate(user: $user, template: $template)->execute();

        $this->assertEquals('template', $file['monica']['format']);
        $this->assertEquals(StructureFile::FORMAT_VERSION, $file['monica']['format_version']);
        $this->assertEquals('Dunder Mifflin contact', $file['template']['name']);
        $this->assertArrayHasKey('exported_at', $file);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateExported
                && $job->parameters === ['name' => 'Dunder Mifflin contact'];
        });
    }

    #[Test]
    public function it_carries_the_pages_the_sections_and_the_fields(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $file = new ExportTemplate(user: $user, template: $template)->execute();

        $pages = $file['template']['pages'];

        $this->assertCount(1, $pages);
        $this->assertCount(1, $pages[0]['sections']);
        $this->assertEquals('Personal details', $pages[0]['sections'][0]['section']['name']);
        $this->assertEquals('Nickname', $pages[0]['sections'][0]['section']['fields'][0]['label']);
    }

    #[Test]
    public function it_refuses_a_template_nobody_has_published(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ListingHasNothingToPublish::class);

        new ExportTemplate(user: $user, template: $template)->execute();
    }

    #[Test]
    public function it_refuses_a_template_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new ExportTemplate(user: $user, template: $template)->execute();
    }

    private function publishedTemplate(User $user): Template
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
            'label' => 'Nickname',
        ]);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
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

        $template->structure->update(['published_version_id' => $version->id]);

        return $template->fresh();
    }
}
