<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_the_contact_on_another_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $michael = $this->contact($user, $this->template($user, 'Friend'));
        $coworker = $this->template($user, 'Coworker');

        $this->actingAs($user)
            ->put(route('contacts.template.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'template' => $coworker->uuid,
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertSessionHas('status', 'Michael Scott uses the Coworker template now.');

        $this->assertSame($coworker->structure->published_version_id, $michael->refresh()->structure_version_id);
    }

    #[Test]
    public function it_shows_why_a_template_is_refused(): void
    {
        $user = User::factory()->create();
        $friend = $this->template($user, 'Friend');
        $michael = $this->contact($user, $friend);
        $retired = $this->template($user, 'Coworker');
        $retired->update(['is_active' => false]);

        $this->actingAs($user)
            ->from(route('contacts.edit', ['vault' => $michael->vault, 'contact' => $michael]))
            ->put(route('contacts.template.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'template' => $retired->uuid,
            ])
            ->assertRedirect(route('contacts.edit', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertSessionHasErrors(['template' => 'This template is turned off, so no contact can be put on it.']);

        $this->assertSame($friend->structure->published_version_id, $michael->refresh()->structure_version_id);
    }

    #[Test]
    public function it_requires_a_template(): void
    {
        $user = User::factory()->create();
        $michael = $this->contact($user, $this->template($user, 'Friend'));

        $this->actingAs($user)
            ->put(route('contacts.template.update', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertSessionHasErrors('template');
    }

    #[Test]
    public function it_answers_not_found_for_a_template_of_another_account(): void
    {
        $user = User::factory()->create();
        $michael = $this->contact($user, $this->template($user, 'Friend'));
        $elsewhere = $this->template(User::factory()->create(), 'Coworker');

        $this->actingAs($user)
            ->put(route('contacts.template.update', ['vault' => $michael->vault, 'contact' => $michael]), [
                'template' => $elsewhere->uuid,
            ])
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_through_another_vault(): void
    {
        $user = User::factory()->create();
        $michael = $this->contact($user, $this->template($user, 'Friend'));
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('contacts.template.update', ['vault' => $otherVault, 'contact' => $michael]), [
                'template' => $this->template($user, 'Coworker')->uuid,
            ])
            ->assertNotFound();
    }

    private function template(User $user, string $name): Template
    {
        return Template::factory()->published()->create(['account_id' => $user->account_id, 'name' => $name])->refresh();
    }

    private function contact(User $user, Template $template): Contact
    {
        return Contact::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $template->structure->published_version_id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
