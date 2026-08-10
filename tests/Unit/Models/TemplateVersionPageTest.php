<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateVersionPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account_and_a_template_version(): void
    {
        $page = TemplateVersionPage::factory()->create();

        $this->assertTrue($page->account()->exists());
        $this->assertTrue($page->templateVersion()->exists());
        $this->assertInstanceOf(Account::class, $page->account);
        $this->assertInstanceOf(TemplateVersion::class, $page->templateVersion);
    }

    #[Test]
    public function it_holds_the_sections_placed_on_it_in_order(): void
    {
        $version = TemplateVersion::factory()->create();
        $page = $version->pages()->firstOrFail();

        $second = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $page->id,
            'position' => 2,
        ]);
        $first = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $page->id,
            'position' => 1,
        ]);

        $this->assertEquals([$first->id, $second->id], $page->assembly->pluck('id')->all());
    }

    #[Test]
    public function it_reads_by_its_translation_key_until_somebody_names_it(): void
    {
        $supplied = TemplateVersion::factory()->create()->pages()->firstOrFail();
        $named = TemplateVersionPage::factory()->create(['label' => 'Health']);

        $this->assertEquals('General', $supplied->label);
        $this->assertEquals('Health', $named->label);
    }

    #[Test]
    public function it_is_reachable_through_the_template_it_sits_on(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $version->id]);
        $elsewhere = TemplateVersionPage::factory()->create();

        $this->assertTrue($page->isReachableThrough($user, $template));
        $this->assertFalse($elsewhere->isReachableThrough($user, $template));
    }

    #[Test]
    public function it_refuses_to_be_added_to_a_published_template_version(): void
    {
        $version = TemplateVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        TemplateVersionPage::factory()->create(['template_version_id' => $version->id]);
    }

    #[Test]
    public function it_refuses_to_be_renamed_once_the_template_version_is_published(): void
    {
        $version = TemplateVersion::factory()->create();
        $page = $version->pages()->firstOrFail();
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $page->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $page->label = 'Health';
        $page->save();
    }

    #[Test]
    public function it_refuses_to_be_taken_off_a_published_template_version(): void
    {
        $version = TemplateVersion::factory()->create();
        $page = $version->pages()->firstOrFail();
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $page->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $page->delete();
    }
}
