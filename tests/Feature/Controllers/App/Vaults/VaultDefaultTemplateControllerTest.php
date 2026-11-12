<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\StructureAppliesToEnum;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultDefaultTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_what_a_new_contact_in_the_vault_starts_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $chosen = $this->publishedContactTemplate($user, 'Work contact');

        $response = $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);

        $response->assertRedirect(route('vaults.settings.index', $vault));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('vault_default_templates', [
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_accepts_a_version_the_template_has_moved_on_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Standard contact');
        $older = $template->structure->published_version_id;

        $newest = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
        ]);
        $template->structure->update(['published_version_id' => $newest->id]);

        $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [
            'structure_version_id' => $older,
        ])->assertRedirect(route('vaults.settings.index', $vault));

        $this->assertEquals($older, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->sole()->structure_version_id);
    }

    #[Test]
    public function it_refuses_a_version_of_a_template_describing_something_other_than_a_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->forEntity()->create([
            'account_id' => $user->account_id,
            'name' => 'Beet varieties',
        ]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [
            'structure_version_id' => $version->id,
        ])->assertSessionHasErrors('structure_version_id');

        $this->assertEquals(0, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
    }

    #[Test]
    public function it_refuses_a_template_belonging_to_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $michael->account_id]);
        $elsewhere = $this->publishedContactTemplate($dwight, 'Beet varieties');

        $this->actingAs($michael)->put(route('vaults.defaultTemplate.update', $vault), [
            'structure_version_id' => $elsewhere->structure->published_version_id,
        ])->assertSessionHasErrors('structure_version_id');

        $this->assertEquals(0, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
    }

    #[Test]
    public function it_refuses_a_request_with_no_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [])
            ->assertSessionHasErrors('structure_version_id');
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template;
    }
}
