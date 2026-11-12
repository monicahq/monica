<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactStructureVersionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_the_contact_on_the_version_that_is_published_now(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->template($user);
        $michael = $this->contact($user, $template->versions()->firstOrFail());
        $published = $this->publish($template, 2);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertRedirect(route('contacts.show', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertSessionHas('status');

        $this->assertSame($published->id, $michael->refresh()->structure_version_id);
    }

    #[Test]
    public function it_puts_the_contact_back_on_a_version_it_names(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->template($user);
        $first = $template->versions()->firstOrFail();
        $michael = $this->contact($user, $first);
        $second = $this->publish($template, 2);
        $michael->update(['structure_version_id' => $second->id]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'structure_version_id' => $first->id,
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertSessionHas('status');

        $this->assertSame($first->id, $michael->refresh()->structure_version_id);
    }

    #[Test]
    public function it_refuses_a_version_nobody_has_published(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->template($user);
        $michael = $this->contact($user, $template->versions()->firstOrFail());

        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'structure_version_id' => $draft->id,
            ])
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_version_of_another_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $michael = $this->contact($user, $this->template($user)->versions()->firstOrFail());

        $elsewhere = $this->template($user);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'structure_version_id' => $elsewhere->structure->published_version_id,
            ])
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_when_the_template_has_published_nothing_newer(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $michael = $this->contact($user, $template->versions()->firstOrFail());
        $template->structure->update(['published_version_id' => null]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertNotFound();
    }

    private function template(User $user): Template
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Friend']);

        $version = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 1,
        ]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template->refresh();
    }

    private function publish(Template $template, int $number): StructureVersion
    {
        $published = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => $number,
            'change_note' => 'Ways to connect is its own section now.',
            'previous_version_id' => $template->structure->published_version_id,
        ]);

        $template->structure->update(['published_version_id' => $published->id]);

        return $published;
    }

    private function contact(User $user, StructureVersion $version): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $version->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
