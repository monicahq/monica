<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Account;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateDuplicateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_duplicates_a_template_into_one_of_its_own(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Contact',
            'slug' => 'contact',
        ]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/templates/{$template->uuid}/duplicate");

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.is_active', true);

        $copy = Template::query()->where('account_id', $user->account_id)->where('id', '!=', $template->id)->sole();

        $this->assertNotSame($template->uuid, $copy->uuid);
        $this->assertNotSame('contact', $copy->slug);
        $this->assertSame($response->json('data.id'), $copy->uuid);
    }

    #[Test]
    public function it_takes_a_name_and_a_slug_for_the_copy(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/duplicate", [
            'name' => 'Contact, expanded',
            'slug' => 'contact-expanded',
        ])
            ->assertCreated()
            ->assertJsonPath('data.attributes.name', 'Contact, expanded')
            ->assertJsonPath('data.attributes.slug', 'contact-expanded');
    }

    #[Test]
    public function it_returns_not_found_for_a_template_with_no_version_to_copy(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/duplicate")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_template_from_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/duplicate")->assertNotFound();
        $this->assertSame(1, Template::query()->where('account_id', $template->account_id)->count());
    }
}
