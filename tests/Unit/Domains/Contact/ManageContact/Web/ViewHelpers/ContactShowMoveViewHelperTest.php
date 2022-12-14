<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactShowMoveViewHelper;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactShowMoveViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $user->vaults()->attach($vault->id, [
            'permission' => Vault::PERMISSION_EDIT,
            'contact_id' => Contact::factory()->create()->id,
        ]);
        $otherVault = Vault::factory()->create();
        $user->vaults()->attach($otherVault->id, [
            'permission' => Vault::PERMISSION_EDIT,
            'contact_id' => Contact::factory()->create()->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $otherVault->id,
        ]);

        $this->be($user);

        $array = ContactShowMoveViewHelper::data($contact, $user);
        $this->assertCount(3, $array);
        $this->assertEquals(
            [
                'name' => $contact->name,
            ],
            $array['contact']
        );
        $this->assertEquals(
            [
                'move' => env('APP_URL').'/vaults/'.$otherVault->id.'/contacts/'.$contact->id.'/move',
            ],
            $array['url']
        );
    }
}
