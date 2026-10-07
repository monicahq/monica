<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactReminderFormControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_contact_page_on_the_form_to_add_a_reminder(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);

        $this->actingAs($user)
            ->get(route('contacts.reminderForm.new', ['vault' => $vault, 'contact' => $kevin]))
            ->assertOk()
            ->assertSee('What should we remind you about?');
    }

    #[Test]
    public function it_does_not_open_the_form_for_a_contact_through_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Kevin']);

        $this->actingAs($user)
            ->get(route('contacts.reminderForm.new', ['vault' => $elsewhere, 'contact' => $kevin]))
            ->assertNotFound();
    }
}
