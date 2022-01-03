<?php

namespace Tests\Unit\Services\Account\Settings;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Account\ActivityType;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Settings\ResetAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Account\Activity\Activity\CreateActivity;

class ResetAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_resets_an_account()
    {
        // populate the account with fake contacts and activities
        $user = factory(User::class)->create();
        $contacts = factory(Contact::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $request = [
            'account_id' => $user->account_id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
            'contacts' => $contacts->map(function ($contact) {
                return $contact->id;
            })->toArray(),
        ];

        app(CreateActivity::class)->execute($request);

        $request = [
            'account_id' => $user->account_id,
        ];

        app(ResetAccount::class)->handle($request);

        $this->assertDatabaseMissing('contacts', [
            'account_id' => $user->account_id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseMissing('activities', [
            'account_id' => $user->account_id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);
        app(ResetAccount::class)->handle($request);
    }
}
