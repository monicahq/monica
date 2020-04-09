<?php

namespace Tests\Unit\Services\Account\Activity;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\Activity\AttachContactToActivity;

class AttachContactToActivityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_attaches_contacts()
    {
        $activity = factory(Activity::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);
        $contactC = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'contacts' => [$contactA->id, $contactB->id, $contactC->id],
        ];

        $activity = app(AttachContactToActivity::class)->execute($request);

        $this->assertDatabaseHas('activity_contact', [
            'activity_id' => $activity->id,
            'contact_id' => $contactA->id,
            'account_id' => $activity->account_id,
        ]);

        $this->assertDatabaseHas('activity_contact', [
            'activity_id' => $activity->id,
            'contact_id' => $contactB->id,
            'account_id' => $activity->account_id,
        ]);

        $this->assertDatabaseHas('activity_contact', [
            'activity_id' => $activity->id,
            'contact_id' => $contactC->id,
            'account_id' => $activity->account_id,
        ]);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $activity = factory(Activity::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'activity_id' => $activity->id,
            'contacts' => [$contactA->id],
        ];

        $this->expectException(ValidationException::class);
        app(AttachContactToActivity::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $activity = factory(Activity::class)->create([]);
        $account = factory(Account::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'activity_id' => $activity->id,
            'account_id' => $account->id,
            'contacts' => [$contactA->id],
        ];

        $this->expectException(ModelNotFoundException::class);
        app(AttachContactToActivity::class)->execute($request);
    }
}
