<?php

namespace Tests\Api;

use Carbon\Carbon;
use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRemindersTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonReminder = [
        'id',
        'object',
        'title',
        'description',
        'frequency_type',
        'frequency_number',
        'last_triggered_date',
        'next_expected_date',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_reminders_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $reminder1 = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $reminder2 = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/reminders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonReminder],
        ]);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminder1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminder2->id,
        ]);
    }

    public function test_reminders_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $reminder1 = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $reminder2 = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
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

    public function test_reminders_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/reminders');

        $this->expectNotFound($response);
    }

    public function test_reminders_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $reminder1 = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $reminder2 = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
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

    public function test_reminders_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/reminders/0');

        $this->expectNotFound($response);
    }

    public function test_reminders_create()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/reminders', [
            'contact_id' => $contact->id,
            'title' => 'the title',
            'next_expected_date' => '2018-05-01',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonReminder,
        ]);
        $reminder_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'reminder',
            'id' => $reminder_id,
            'title' => 'the title',
            'next_expected_date' => '2018-05-01T00:00:00Z',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);

        $this->assertGreaterThan(0, $reminder_id);
        $this->assertDatabaseHas('reminders', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $reminder_id,
            'title' => 'the title',
            'next_expected_date' => '2018-05-01 00:00:00',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);
    }

    public function test_reminders_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/reminders', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The title field is required.',
            'The description field is required.',
            'The next expected date field is required.',
            'The frequency type field is required.',
        ]);
    }

    public function test_reminders_create_error_bad_account()
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
            'next_expected_date' => '2018-05-01',
            'frequency_type' => 'one_time',
            'description' => 'the description',
        ]);

        $this->expectNotFound($response);
    }

    public function test_reminders_update()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/reminders/'.$reminder->id, [
            'contact_id' => $contact->id,
            'title' => 'the title',
            'next_expected_date' => '2018-05-01',
            'frequency_type' => 'day',
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
            'next_expected_date' => '2018-05-01T00:00:00Z',
            'frequency_type' => 'day',
            'description' => 'the description',
        ]);

        $this->assertGreaterThan(0, $reminder_id);
        $this->assertDatabaseHas('reminders', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $reminder_id,
            'title' => 'the title',
            'next_expected_date' => '2018-05-01 00:00:00',
            'frequency_type' => 'day',
            'description' => 'the description',
        ]);
    }

    public function test_reminders_update_error()
    {
        $user = $this->signin();
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/reminders/'.$reminder->id, [
            'contact_id' => $reminder->contact_id,
        ]);

        $this->expectDataError($response, [
            'The title field is required.',
            'The description field is required.',
            'The next expected date field is required.',
            'The frequency type field is required.',
        ]);
    }

    public function test_reminders_update_error_bad_account()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/reminders/'.$reminder->id, [
            'contact_id' => $contact->id,
            'title' => 'the title',
            'next_expected_date' => '2018-05-01',
            'frequency_type' => 'day',
            'description' => 'the description',
        ]);

        $this->expectNotFound($response);
    }

    public function test_reminders_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('reminders', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $reminder->id,
        ]);

        $response = $this->json('DELETE', '/api/reminders/'.$reminder->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('reminders', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $reminder->id,
        ]);
    }

    public function test_reminders_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/reminders/0');

        $this->expectNotFound($response);
    }
}
