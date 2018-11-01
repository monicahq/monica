<?php

namespace Tests\Unit\Services\Contact\Reminder;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Reminder\CreateReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_reminder_without_special_date_id()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
            'special_date_id' => null,
        ];

        $reminder = (new CreateReminder)->execute($request);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
        ]);

        $this->assertInstanceOf(
            Reminder::class,
            $reminder
        );
    }

    public function test_it_stores_a_reminder_with_special_date_id()
    {
        $contact = factory(Contact::class)->create([]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $contact->account->id,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
            'special_date_id' => $specialDate->id,
        ];

        $reminder = (new CreateReminder)->execute($request);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'special_date_id' => $specialDate->id,
        ]);

        $this->assertInstanceOf(
            Reminder::class,
            $reminder
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'date' => Carbon::now(),
        ];

        $this->expectException(MissingParameterException::class);

        $reminderService = (new CreateReminder)->execute($request);
    }

    public function test_it_throws_an_exception_if_ids_are_not_found()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
            'special_date_id' => null,
        ];

        $this->expectException(ModelNotFoundException::class);

        $reminder = (new CreateReminder)->execute($request);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
            'special_date_id' => 3,
        ];

        $this->expectException(ModelNotFoundException::class);

        $reminderService = (new CreateReminder)->execute($request);
    }

    public function test_it_throws_an_exception_if_frequency_type_is_not_right()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'date' => '2017-02-02',
            'frequency_type' => 'blabla',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
            'special_date_id' => null,
        ];

        $this->expectException(MissingParameterException::class);

        try {
            $reminderService = (new CreateReminder)->execute($request);
        } catch (MissingParameterException $e) {
            $this->assertEquals(['The selected frequency type is invalid.'], $e->errors);
            throw $e;
        }
    }
}
