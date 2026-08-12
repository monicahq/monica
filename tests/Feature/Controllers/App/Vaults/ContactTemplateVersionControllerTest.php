<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTemplateVersionControllerTest extends TestCase
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
            ->assertSessionHas('status', 'Michael Scott is on version 2 now.');

        $this->assertSame($published->id, $michael->refresh()->template_version_id);
    }

    #[Test]
    public function the_banner_is_offered_on_the_contact_page_and_goes_once_it_is_applied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->template($user);
        $michael = $this->contact($user, $template->versions()->firstOrFail());
        $this->publish($template, 2);

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Version 2 of the Friend template is ready')
            ->assertSee('Apply version 2');

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]));

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertDontSee('Apply version 2');
    }

    /**
     * The way back from the version history, which is the same act as applying
     * and therefore the same route: it names the version instead of leaving the
     * choice to the template.
     */
    #[Test]
    public function it_puts_the_contact_back_on_a_version_it_names(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->template($user);
        $first = $template->versions()->firstOrFail();
        $michael = $this->contact($user, $first);
        $second = $this->publish($template, 2);
        $michael->update(['template_version_id' => $second->id]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'template_version_id' => $first->id,
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertSessionHas('status', 'Michael Scott is on version 1 now.');

        $this->assertSame($first->id, $michael->refresh()->template_version_id);
    }

    #[Test]
    public function it_refuses_a_version_nobody_has_published(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->template($user);
        $michael = $this->contact($user, $template->versions()->firstOrFail());

        $draft = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 2]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'template_version_id' => $draft->id,
            ])
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_version_that_has_been_retired(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->template($user);
        $first = $template->versions()->firstOrFail();
        $michael = $this->contact($user, $first);
        $second = $this->publish($template, 2);
        $michael->update(['template_version_id' => $second->id]);
        $first->update(['status' => VersionStatusEnum::Retired]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'template_version_id' => $first->id,
            ])
            ->assertNotFound();

        $this->assertSame($second->id, $michael->refresh()->template_version_id);
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
                'template_version_id' => $elsewhere->published_version_id,
            ])
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_when_the_template_has_published_nothing_newer(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $michael = $this->contact($user, $template->versions()->firstOrFail());
        $template->update(['published_version_id' => null]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertNotFound();
    }

    #[Test]
    public function a_contact_in_another_vault_cannot_be_reached(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $michael = $this->contact($user, $template->versions()->firstOrFail());
        $this->publish($template, 2);

        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $elsewhere, 'contact' => $michael]))
            ->assertNotFound();
    }

    #[Test]
    public function a_contact_in_another_account_cannot_be_reached(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $template = $this->template($stranger);
        $theirs = $this->contact($stranger, $template->versions()->firstOrFail());
        $this->publish($template, 2);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $theirs->vault, 'contact' => $theirs]))
            ->assertNotFound();
    }

    private function template(User $user): Template
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Friend']);

        $version = TemplateVersion::factory()->published()->create([
            'template_id' => $template->id,
            'version_number' => 1,
        ]);

        $template->published_version_id = $version->id;
        $template->save();

        return $template->refresh();
    }

    private function publish(Template $template, int $number): TemplateVersion
    {
        $published = TemplateVersion::factory()->published()->create([
            'template_id' => $template->id,
            'version_number' => $number,
            'change_note' => 'Ways to connect is its own section now.',
            'previous_version_id' => $template->published_version_id,
        ]);

        $template->update(['published_version_id' => $published->id]);

        return $published;
    }

    private function contact(User $user, TemplateVersion $version): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'template_version_id' => $version->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
