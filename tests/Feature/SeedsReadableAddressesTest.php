<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Vault;
use Database\Seeders\ExampleAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeedsReadableAddressesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_seeded_vault_and_contact_carries_a_readable_address(): void
    {
        $this->seed(ExampleAccountSeeder::class);

        $vaults = Vault::query()->get();
        $contacts = Contact::query()->get();

        $this->assertGreaterThan(0, $vaults->count());
        $this->assertGreaterThan(0, $contacts->count());

        foreach ($vaults as $vault) {
            $this->assertNotSame('', (string) $vault->slug, "The vault {$vault->name} has no readable address.");
        }

        foreach ($contacts as $contact) {
            $this->assertNotSame('', (string) $contact->slug, "The contact {$contact->name} has no readable address.");
        }
    }

    #[Test]
    public function no_two_contacts_of_one_vault_share_a_readable_address(): void
    {
        $this->seed(ExampleAccountSeeder::class);

        Contact::query()->get()->groupBy('vault_id')->each(function ($contacts): void {
            $slugs = $contacts->pluck('slug');

            $this->assertCount($slugs->unique()->count(), $slugs);
        });
    }
}
