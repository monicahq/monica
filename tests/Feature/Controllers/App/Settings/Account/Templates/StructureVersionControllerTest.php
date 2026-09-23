<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_draft_from_the_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $published->id]);

        $this->actingAs($user)->post(route('settings.templateVersions.create', $template))
            ->assertRedirect()
            ->assertSessionHas('status');

        $draft = $template->versions()->where('status', VersionStatusEnum::Draft)->firstOrFail();

        $this->assertEquals(2, $draft->version_number);
        $this->assertEquals($published->id, $draft->previous_version_id);
        $this->assertEquals(VersionStatusEnum::Published, $published->fresh()->status);
    }

    #[Test]
    public function it_throws_a_draft_away(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->delete(route('settings.templateVersions.destroy', [$template, $draft]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertModelMissing($draft);
    }

    #[Test]
    public function it_explains_why_a_published_version_cannot_be_thrown_away(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->delete(route('settings.templateVersions.destroy', [$template, $published]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertModelExists($published);
    }

    #[Test]
    public function it_does_not_find_a_draft_of_another_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->delete(route('settings.templateVersions.destroy', [$template, $elsewhere]))
            ->assertNotFound();

        $this->assertModelExists($elsewhere);
    }
}
