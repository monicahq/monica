<?php

namespace Tests\Unit\Services\Contact\Reminder;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Reminder\CreateReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_recurring_reminder()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $user = factory(User::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'initial_date' => '2017-02-01',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ];

        $reminder = app(CreateReminder::class)->execute($request);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
        ]);

        $this->assertInstanceOf(
            Reminder::class,
            $reminder
        );

        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'account_id' => $contact->account_id,
            'planned_date' => '2017-02-01',
            'nature' => 'reminder',
        ]);
    }

    public function test_it_stores_a_one_time_reminder()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $user = factory(User::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'initial_date' => '2017-02-01',
            'frequency_type' => 'one_time',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ];

        $reminder = app(CreateReminder::class)->execute($request);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
        ]);

        $this->assertInstanceOf(
            Reminder::class,
            $reminder
        );

        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'account_id' => $contact->account_id,
            'planned_date' => '2017-02-01',
            'nature' => 'reminder',
        ]);
    }

    public function test_it_stores_a_reminder_for_each_user_of_an_account()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $account = factory(Account::class)->create([]);
        $userA = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $userB = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'initial_date' => '2017-02-01',
            'frequency_type' => 'one_time',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ];

        $reminder = app(CreateReminder::class)->execute($request);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
        ]);

        $this->assertInstanceOf(
            Reminder::class,
            $reminder
        );

        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'account_id' => $contact->account_id,
            'planned_date' => '2017-02-01',
            'nature' => 'reminder',
            'user_id' => $userA->id,
        ]);
        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'account_id' => $contact->account_id,
            'planned_date' => '2017-02-01',
            'nature' => 'reminder',
            'user_id' => $userB->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'initial_date' => now(),
        ];

        $this->expectException(ValidationException::class);

        $reminderService = app(CreateReminder::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_ids_are_not_found()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'initial_date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ];

        $this->expectException(ModelNotFoundException::class);

        $reminder = app(CreateReminder::class)->execute($request);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'initial_date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ];

        $this->expectException(ModelNotFoundException::class);

        $reminderService = app(CreateReminder::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_frequency_type_is_not_right()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'initial_date' => '2017-02-02',
            'frequency_type' => 'blabla',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ];

        $this->expectException(ValidationException::class);

        try {
            $reminderService = app(CreateReminder::class)->execute($request);
        } catch (ValidationException $e) {
            $this->assertEquals(['The selected frequency type is invalid.'], $e->validator->errors()->all());
            throw $e;
        }
    }
}
