<?php

namespace Tests\Unit\Services\Account\Settings;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Settings\DestroyAccount;
use App\Services\Account\Settings\ArchiveAllContacts;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArchiveAllContactsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_archives_all_the_contacts_in_an_account()
    {
        $user = factory(User::class)->create([]);
        factory(Contact::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        $request = [
            'account_id' => $user->account_id,
        ];

        $result = app(ArchiveAllContacts::class)->execute($request);

        $this->assertTrue($result);

        $this->assertDatabaseHas('contacts', [
            'is_active' => 0,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);
        app(DestroyAccount::class)->execute($request);
    }
}
