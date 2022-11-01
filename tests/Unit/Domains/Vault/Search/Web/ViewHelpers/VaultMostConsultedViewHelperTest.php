<?php

namespace Tests\Unit\Domains\Vault\Search\Web\ViewHelpers;

use App\Domains\Vault\Search\Web\ViewHelpers\VaultMostConsultedViewHelper;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VaultMostConsultedViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_five_most_consulted_contacts(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'regis',
            'last_name' => 'troyat',
        ]);
        $mostViewedContact = Contact::factory()->create([
            'first_name' => 'alexis',
            'last_name' => 'troyat',
            'vault_id' => $contact->vault_id,
        ]);
        $user = User::factory()->create();
        DB::table('contact_vault_user')->insert([
            'contact_id' => $contact->id,
            'vault_id' => $contact->vault_id,
            'user_id' => $user->id,
            'number_of_views' => 1,
        ]);
        DB::table('contact_vault_user')->insert([
            'contact_id' => $mostViewedContact->id,
            'vault_id' => $contact->vault_id,
            'user_id' => $user->id,
            'number_of_views' => 4,
        ]);

        $collection = VaultMostConsultedViewHelper::data($contact->vault, $user);

        $this->assertEquals(
            [
                0 => [
                    'id' => $mostViewedContact->id,
                    'name' => 'alexis troyat',
                    'url' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$mostViewedContact->id,
                ],
                1 => [
                    'id' => $contact->id,
                    'name' => 'regis troyat',
                    'url' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
                ],
            ],
            $collection->toArray()
        );
    }
}
