<?php

namespace Tests\Unit\Helpers;

use App\Helpers\AvatarHelper;
use App\Helpers\UserHelper;
use App\Models\Avatar;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_the_information_about_the_contact_of_the_user_in_the_vault(): void
    {
        $rachel = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $rachel->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'Keanu',
            'last_name' => 'Reeves',
        ]);
        $avatar = Avatar::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $contact->avatar_id = $avatar->id;
        $contact->save();
        $vault->users()->save($rachel, [
            'permission' => 1,
            'contact_id' => $contact->id,
        ]);

        $this->assertEquals(
            [
                'id' => $contact->id,
                'name' => 'Keanu Reeves',
                'avatar' => AvatarHelper::getSVG($contact),
            ],
            UserHelper::getInformationAboutContact($rachel, $vault)
        );
    }

    /** @test */
    public function it_returns_null_if_the_user_doesnt_have_a_contact_in_the_vault(): void
    {
        $rachel = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $rachel->account_id,
        ]);

        $this->assertNull(
            UserHelper::getInformationAboutContact($rachel, $vault)
        );
    }
}
