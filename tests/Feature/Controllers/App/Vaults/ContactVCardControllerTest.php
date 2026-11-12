<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactVCardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_the_contact_over_as_a_card(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $response = $this->actingAs($user)
            ->get(route('contacts.vcard.show', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'text/vcard; charset=utf-8');

        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString($contact->slug.'.vcf', (string) $response->headers->get('content-disposition'));

        $card = $response->streamedContent();

        $this->assertStringContainsString('BEGIN:VCARD', $card);
        $this->assertStringContainsString('N:Scott;Michael;;;', $card);
        $this->assertStringContainsString('UID:'.$contact->uuid, $card);
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
