<?php

namespace Tests\Unit\Services\Contact\Reminder;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Reminder\UpdateReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_reminder()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $user = factory(User::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'initial_date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ]);

        $request = [
            'contact_id' => $reminder->contact->id,
            'account_id' => $reminder->contact->account_id,
            'reminder_id' => $reminder->id,
            'initial_date' => '2017-10-01',
            'frequency_type' => 'month',
            'frequency_number' => 1,
            'title' => 'title',
        ];

        $reminder = app(UpdateReminder::class)->execute($request);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'contact_id' => $reminder->contact->id,
            'account_id' => $reminder->contact->account_id,
            'initial_date' => '2017-10-01',
        ]);

        $this->assertInstanceOf(
            Reminder::class,
            $reminder
        );

        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->contact->account_id,
            'planned_date' => '2017-10-01',
            'nature' => 'reminder',
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'initial_date' => Carbon::now(),
        ];

        $this->expectException(ValidationException::class);

        app(UpdateReminder::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_frequency_type_is_not_right()
    {
        $reminder = factory(Reminder::class)->create([
            'initial_date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ]);

        $request = [
            'contact_id' => $reminder->contact->id,
            'account_id' => $reminder->contact->account_id,
            'reminder_id' => $reminder->id,
            'initial_date' => '2017-02-02',
            'frequency_type' => 'blabla',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ];

        $this->expectException(ValidationException::class);

        try {
            app(UpdateReminder::class)->execute($request);
        } catch (ValidationException $e) {
            $this->assertEquals(['The selected frequency type is invalid.'], $e->validator->errors()->all());
            throw $e;
        }
    }
}
