<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadableAddressPreferenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function with_the_preference_on_every_address_the_product_builds_names_the_record(): void
    {
        [$user, $vault, $contact] = $this->account(readable: true);

        $this->assertSame(
            "/vaults/{$vault->slug}/contacts/{$contact->slug}",
            parse_url(route('contacts.show', ['vault' => $vault, 'contact' => $contact]), PHP_URL_PATH),
        );

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertStatus(200)
            ->assertSee("/vaults/{$vault->slug}/contacts/{$contact->slug}", escape: false);
    }

    #[Test]
    public function with_the_preference_off_every_address_the_product_builds_carries_the_identifier(): void
    {
        [$user, $vault, $contact] = $this->account(readable: false);

        $this->assertSame(
            "/vaults/{$vault->uuid}/contacts/{$contact->uuid}",
            parse_url(route('contacts.show', ['vault' => $vault, 'contact' => $contact]), PHP_URL_PATH),
        );

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertStatus(200)
            ->assertSee("/vaults/{$vault->uuid}/contacts/{$contact->uuid}", escape: false)
            ->assertDontSee("/vaults/{$vault->slug}/contacts/{$contact->slug}", escape: false);
    }

    #[Test]
    public function an_address_saved_while_it_was_on_still_answers_once_it_is_off(): void
    {
        [$user, $vault, $contact] = $this->account(readable: false);

        $this->actingAs($user)->get(url("/vaults/{$vault->slug}/contacts/{$contact->slug}"))->assertStatus(200);
        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/{$contact->uuid}"))->assertStatus(200);
    }

    #[Test]
    public function an_address_saved_while_it_was_off_still_answers_once_it_is_on(): void
    {
        [$user, $vault, $contact] = $this->account(readable: true);

        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/{$contact->uuid}"))->assertStatus(200);
        $this->actingAs($user)->get(url("/vaults/{$vault->slug}/contacts/{$contact->slug}"))->assertStatus(200);
    }

    #[Test]
    public function the_api_reports_the_readable_name_whatever_the_preference_says(): void
    {
        [, $vault, $contact] = $this->account(readable: false);

        $this->assertSame('jim-halpert', $contact->slug);
        $this->assertNotSame('', $vault->slug);
    }

    #[Test]
    public function turning_it_off_changes_what_is_built_and_nothing_else(): void
    {
        [$user, $vault, $contact] = $this->account(readable: true);

        $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'uses_readable_addresses' => '0',
        ])->assertRedirect(route('settings.preferences.index'));

        Account::forgetAddressPreferences();

        $this->assertStringContainsString($vault->uuid, route('vaults.show', $vault));

        $this->actingAs($user)->get(url("/vaults/{$vault->slug}/contacts/{$contact->slug}"))->assertStatus(200);
        $this->assertSame('jim-halpert', $contact->fresh()->slug);
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function account(bool $readable): array
    {
        $user = User::factory()->create(['uses_readable_addresses' => $readable]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);

        Account::forgetAddressPreferences();

        return [$user, $vault, $contact];
    }
}
