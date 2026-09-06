<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateVersionPagePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_page_and_renumbers_the_version_from_one(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        $first = $version->pages()->sole();
        $second = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'position' => 2]);
        $third = TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'position' => 3]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$third->uuid}/position", ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 1);

        $this->assertSame(1, $third->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);
    }

    #[Test]
    public function it_puts_a_page_asked_for_a_place_past_the_end_last(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        $first = $version->pages()->sole();
        TemplateVersionPage::factory()->create(['template_version_id' => $version->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$first->uuid}/position", ['position' => 99])
            ->assertOk();

        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_only_page_of_a_version_where_it_is(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        $page = $version->pages()->sole();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}/position", ['position' => 5])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 1);
    }

    #[Test]
    public function it_validates_the_position(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        $page = $version->pages()->sole();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}/position", ['position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_a_page_of_a_template_from_another_account(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        $page = $version->pages()->sole();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}/position", ['position' => 1])
            ->assertNotFound();
    }
}
