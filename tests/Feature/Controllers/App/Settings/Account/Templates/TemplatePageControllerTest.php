<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplatePageControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_page_to_the_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $response = $this->actingAs($user)->post(route('settings.templatePages.create', $template), [
            'label' => 'Life events',
        ]);

        $page = TemplateVersionPage::query()->where('label', 'Life events')->sole();

        $response->assertRedirect(route('settings.templateBuilder.index', ['template' => $template, 'page' => $page->key]));
        $response->assertSessionHas('status');
        $this->assertEquals($version->id, $page->template_version_id);
        $this->assertEquals('life-events', $page->key);
    }

    #[Test]
    public function it_refuses_a_label_the_template_already_reads(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $response = $this->actingAs($user)->post(route('settings.templatePages.create', $template), [
            'label' => 'General',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, TemplateVersionPage::query()->count());
    }

    #[Test]
    public function it_refuses_a_page_on_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->actingAs($user)->post(route('settings.templatePages.create', $template), ['label' => 'Life events'])
            ->assertSessionHas('error');

        $this->assertEquals(1, TemplateVersionPage::query()->count());
    }

    #[Test]
    public function it_refuses_a_page_with_no_label(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)->post(route('settings.templatePages.create', $template), ['label' => ''])
            ->assertSessionHasErrors('label', errorBag: 'newPage');
    }

    #[Test]
    public function it_renames_a_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'label' => 'Life events']);

        $response = $this->actingAs($user)->put(route('settings.templatePages.update', [$template, $page]), [
            'label' => 'Milestones',
        ]);

        $response->assertRedirect(route('settings.templateBuilder.index', ['template' => $template, 'page' => $page->key]));
        $this->assertEquals('Milestones', $page->fresh()->label);
    }

    #[Test]
    public function it_keeps_the_key_when_the_label_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $version->id,
            'key' => 'life-events',
            'label' => 'Life events',
        ]);

        $this->actingAs($user)->put(route('settings.templatePages.update', [$template, $page]), ['label' => 'Milestones']);

        $this->assertEquals('life-events', $page->fresh()->key);
    }

    #[Test]
    public function it_removes_a_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $version->id]);

        $response = $this->actingAs($user)->delete(route('settings.templatePages.destroy', [$template, $page]));

        $response->assertRedirect(route('settings.templateBuilder.index', $template));
        $response->assertSessionHas('status');
        $this->assertModelMissing($page);
    }

    #[Test]
    public function it_keeps_a_page_that_still_holds_sections(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $version->id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $page->id,
        ]);

        $this->actingAs($user)->delete(route('settings.templatePages.destroy', [$template, $page]))
            ->assertSessionHas('error');

        $this->assertModelExists($page);
    }

    #[Test]
    public function it_keeps_the_last_page_of_a_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $page = $version->pages()->sole();

        $this->actingAs($user)->delete(route('settings.templatePages.destroy', [$template, $page]))
            ->assertSessionHas('error');

        $this->assertModelExists($page);
    }

    #[Test]
    public function it_does_not_find_a_page_of_another_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);
        $elsewhere = TemplateVersion::factory()->create(['account_id' => $user->account_id])->pages()->sole();

        $this->actingAs($user)->put(route('settings.templatePages.update', [$template, $elsewhere]), ['label' => 'Milestones'])
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_page_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);
        $theirs = TemplateVersion::factory()->create()->pages()->sole();

        $this->actingAs($user)->delete(route('settings.templatePages.destroy', [$template, $theirs]))
            ->assertNotFound();
    }

    #[Test]
    public function it_keeps_somebody_who_is_not_signed_in_out(): void
    {
        $template = Template::factory()->create();

        $this->post(route('settings.templatePages.create', $template), ['label' => 'Life events'])
            ->assertRedirect(route('auth.login.new'));
    }
}
