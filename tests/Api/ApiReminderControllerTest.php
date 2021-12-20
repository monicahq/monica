<?php

namespace Tests\Api;

use Carbon\Carbon;
use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiReminderControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonReminder = [
        'id',
        'object',
        'initial_date',
        'title',
        'description',
        'frequency_type',
        'frequency_number',
        'delible',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_gets_all_reminders()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $reminder1 = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $reminder2 = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
            'delible' => false,
        ]);

        $response = $this->json('GET', '/api/reminders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonReminder],
        ]);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminder1->id,
            'delible' => true,
        ]);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminder2->id,
            'delible' => false,
        ]);
    }

    /** @test */
    public function it_gets_all_the_reminders_of_a_contact()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $reminder1 = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $reminder2 = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/reminders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonReminder],
        ]);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminder1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'reminder',
            'id' => $reminder2->id,
        ]);
    }

    /** @test */
    public function it_cant_get_a_reminder_of_a_contact_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/reminders');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_gets_one_reminder()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $reminder1 = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $reminder2 = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/reminders/'.$reminder1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonReminder,
        ]);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminder1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'reminder',
            'id' => $reminder2->id,
        ]);
    }

    /** @test */
    public function it_cant_get_a_reminder_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/reminders/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_creates_a_reminder()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/reminders', [
            'contact_id' => $contact->id,
            'title' => 'the title',
            'initial_date' => '2018-05-01',
            'frequency_type' => 'one_time',
            'frequency_number' => 1,
            'description' => 'the description',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonReminder,
        ]);
        $reminderId = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminderId,
        ]);

        $this->assertGreaterThan(0, $reminderId);
        $this->assertDatabaseHas('reminders', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $reminderId,
            'title' => 'the title',
            'initial_date' => '2018-05-01',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);
    }

    /** @test */
    public function create_reminders_gets_an_error_if_fields_are_missing()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/reminders', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The initial date field is required.',
            'The frequency type field is required.',
            'The frequency number field is required.',
            'The title field is required.',
        ]);
    }

    /** @test */
    public function reminders_create_error_bad_account()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/reminders', [
            'contact_id' => $contact->id,
            'title' => 'the title',
            'initial_date' => '2018-05-01',
            'frequency_type' => 'one_time',
            'frequency_number' => 1,
            'description' => 'the description',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_updates_a_reminder()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/reminders/'.$reminder->id, [
            'contact_id' => $contact->id,
            'title' => 'the title',
            'initial_date' => '2018-05-01',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonReminder,
        ]);
        $reminder_id = $response->json('data.id');
        $this->assertEquals($reminder->id, $reminder_id);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminder_id,
            'title' => 'the title',
            'initial_date' => '2018-05-01T00:00:00Z',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);

        $this->assertGreaterThan(0, $reminder_id);
        $this->assertDatabaseHas('reminders', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $reminder_id,
            'title' => 'the title',
            'initial_date' => '2018-05-01 00:00:00',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);
    }

    /** @test */
    public function updating_reminder_generates_an_error()
    {
        $user = $this->signin();
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/reminders/'.$reminder->id, [
            'contact_id' => $reminder->contact_id,
        ]);

        $this->expectDataError($response, [
            'The initial date field is required.',
            'The frequency type field is required.',
            'The title field is required.',
        ]);
    }

    /** @test */
    public function reminders_update_error_bad_account()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();

        $contact = factory(Contact::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/reminders/'.$reminder->id, [
            'contact_id' => $contact->id,
            'title' => 'the title',
            'initial_date' => '2018-05-01',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_deletes_a_reminder()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('DELETE', '/api/reminders/'.$reminder->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('reminders', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $reminder->id,
        ]);
    }

    /** @test */
    public function reminders_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/reminders/0');

        $this->expectDataError($response, [
            'The selected reminder id is invalid.',
        ]);
    }

    /** @test */
    public function it_gets_all_upcoming_reminders()
    {
        $user = $this->signin();

        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        // add 2 reminders for the month of March
        $reminder1 = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'initial_date' => '2017-03-03 00:00:00',
        ]);
        $reminder1->schedule($user);

        $reminder2 = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'initial_date' => '2017-03-03 00:00:00',
            'delible' => false,
        ]);
        $reminder2->schedule($user);

        $response = $this->json('GET', '/api/reminders/upcoming/2');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonReminder],
        ]);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'reminder_id' => $reminder1->id,
            'delible' => true,
        ]);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'reminder_id' => $reminder2->id,
            'delible' => false,
        ]);
    }
}
