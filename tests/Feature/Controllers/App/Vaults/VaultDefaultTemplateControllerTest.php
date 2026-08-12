<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
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
            'template_version_id' => $chosen->published_version_id,
        ]);

        $response->assertRedirect(route('vaults.settings.index', $vault));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('vault_default_templates', [
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact->value,
            'template_version_id' => $chosen->published_version_id,
        ]);
    }

    #[Test]
    public function it_replaces_the_template_the_vault_was_pointing_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $standard = $this->publishedContactTemplate($user, 'Standard contact');
        $work = $this->publishedContactTemplate($user, 'Work contact');

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $standard->published_version_id,
        ]);

        $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [
            'template_version_id' => $work->published_version_id,
        ]);

        $default = VaultDefaultTemplate::query()->where('vault_id', $vault->id)->sole();

        $this->assertEquals($work->published_version_id, $default->template_version_id);
    }

    #[Test]
    public function it_accepts_a_version_the_template_has_moved_on_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Standard contact');
        $older = $template->published_version_id;

        $newest = TemplateVersion::factory()->published()->create([
            'template_id' => $template->id,
            'version_number' => 2,
        ]);
        $template->update(['published_version_id' => $newest->id]);

        $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [
            'template_version_id' => $older,
        ])->assertRedirect(route('vaults.settings.index', $vault));

        $this->assertEquals($older, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->sole()->template_version_id);
    }

    #[Test]
    public function it_refuses_a_version_nobody_has_published(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Standard contact');

        $draft = TemplateVersion::factory()->create([
            'template_id' => $template->id,
            'version_number' => 2,
        ]);

        $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [
            'template_version_id' => $draft->id,
        ])->assertSessionHasErrors('template_version_id');

        $this->assertEquals(0, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
    }

    #[Test]
    public function it_refuses_a_version_of_a_template_describing_something_other_than_a_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'applies_to' => TemplateAppliesToEnum::Entity,
            'name' => 'Beet varieties',
        ]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [
            'template_version_id' => $version->id,
        ])->assertSessionHasErrors('template_version_id');

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
            'template_version_id' => $elsewhere->published_version_id,
        ])->assertSessionHasErrors('template_version_id');

        $this->assertEquals(0, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
    }

    #[Test]
    public function it_refuses_a_request_with_no_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('vaults.defaultTemplate.update', $vault), [])
            ->assertSessionHasErrors('template_version_id');
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $elsewhere = Vault::factory()->create();
        $chosen = $this->publishedContactTemplate($michael, 'Work contact');

        $this->actingAs($michael)->put(route('vaults.defaultTemplate.update', $elsewhere), [
            'template_version_id' => $chosen->published_version_id,
        ])->assertNotFound();
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $template->published_version_id = $version->id;
        $template->save();

        return $template;
    }
}
