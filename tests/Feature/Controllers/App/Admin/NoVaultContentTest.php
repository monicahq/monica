<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoVaultContentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function no_screen_of_the_admin_space_shows_what_was_written_in_a_vault(): void
    {
        $dwight = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);

        $vault = Vault::factory()->create([
            'account_id' => $dwight->account_id,
            'name' => 'Schrute Farms',
        ]);

        $mose = Contact::factory()->create([
            'account_id' => $dwight->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Mose',
            'last_name' => 'Schrute',
        ]);

        FieldValue::factory()->create([
            'account_id' => $dwight->account_id,
            'vault_id' => $vault->id,
            'valuable_type' => Contact::class,
            'valuable_id' => $mose->id,
            'value_string' => 'Beet farm on Route 6',
        ]);

        File::factory()->create([
            'account_id' => $dwight->account_id,
            'vault_id' => $vault->id,
            'original_name' => 'beet-harvest-1987.pdf',
        ]);

        $michael = $this->administrator();

        foreach ([
            route('admin.accounts.index', ['query' => 'dwight']),
            route('admin.accounts.show', ['account' => $dwight->account_id]),
            route('admin.logs.index', ['account' => $dwight->account_id]),
            route('admin.moderation.index'),
            route('admin.search.index'),
            route('admin.billing.index'),
        ] as $url) {
            $response = $this->actingAs($michael)->get($url);

            $response->assertStatus(200);
            $response->assertDontSee('Mose', escape: false);
            $response->assertDontSee('Beet farm on Route 6', escape: false);
            $response->assertDontSee('beet-harvest-1987.pdf', escape: false);
            $response->assertDontSee('Schrute Farms', escape: false);
        }
    }

    #[Test]
    public function no_screen_of_the_admin_space_links_anywhere_near_a_vault(): void
    {
        $dwight = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $dwight->account_id]);
        $mose = Contact::factory()->create([
            'account_id' => $dwight->account_id,
            'vault_id' => $vault->id,
        ]);

        $michael = $this->administrator();

        foreach ([
            route('admin.accounts.index'),
            route('admin.accounts.show', ['account' => $dwight->account_id]),
        ] as $url) {
            $response = $this->actingAs($michael)->get($url);

            $response->assertDontSee('/vaults', escape: false);
            $response->assertDontSee(route('vaults.show', ['vault' => $vault->id]), escape: false);
            $response->assertDontSee(route('contacts.show', ['vault' => $vault->id, 'contact' => $mose->id]), escape: false);
        }
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
