<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotFoundControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_page_for_an_address_that_leads_nowhere(): void
    {
        $response = $this->get('/dunder-mifflin-scranton');

        $response->assertNotFound();
        $response->assertSee(route('home.index'), escape: false);
        $response->assertSee(route('docs.index'), escape: false);
        $response->assertSee(config('monica.terms_url'), escape: false);
    }

    #[Test]
    public function it_shows_the_page_for_a_contact_reached_through_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->published()->create([
                'account_id' => $user->account_id,
            ]),
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);

        $response = $this->actingAs($user)->get(route('contacts.show', ['vault' => $elsewhere, 'contact' => $contact]));

        $response->assertNotFound();
        $response->assertDontSee('Dwight', escape: false);
    }

    #[Test]
    public function it_answers_json_to_an_api_address_that_leads_nowhere(): void
    {
        $response = $this->getJson('/api/v1/dunder-mifflin-scranton');

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
    }

    #[Test]
    public function it_shows_the_page_in_the_language_of_whoever_is_signed_in(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);

        $response = $this->actingAs($user)->get('/dunder-mifflin-scranton');

        $response->assertNotFound();
        $response->assertSee('Retour à l’accueil', escape: false);
    }
}
