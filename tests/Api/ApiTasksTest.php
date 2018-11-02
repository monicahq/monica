<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Task;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiTasksTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonTask = [
        'id',
        'object',
        'title',
        'description',
        'completed',
        'completed_at',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_tasks_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $task1 = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $task2 = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/tasks');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonTask],
        ]);
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $task1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $task2->id,
        ]);
    }

    public function test_tasks_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $task1 = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $task2 = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/tasks');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonTask],
        ]);
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $task1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'task',
            'id' => $task2->id,
        ]);
    }

    public function test_tasks_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/tasks');

        $this->expectNotFound($response);
    }

    public function test_tasks_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $task1 = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $task2 = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/tasks/'.$task1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonTask,
        ]);
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $task1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'task',
            'id' => $task2->id,
        ]);
    }

    public function test_tasks_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/tasks/0');

        $this->expectNotFound($response);
    }

    public function test_tasks_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/tasks', [
            'contact_id' => $contact->id,
            'title' => 'the task',
            'completed' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonTask,
        ]);
        $task_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $task_id,
        ]);

        $this->assertGreaterThan(0, $task_id);
        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $task_id,
            'title' => 'the task',
            'completed' => false,
        ]);
    }

    public function test_tasks_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/tasks', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The title field is required.',
            'The completed field is required.',
        ]);
    }

    public function test_tasks_create_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/tasks', [
            'contact_id' => $contact->id,
            'title' => 'the task',
            'completed' => false,
        ]);

        $this->expectNotFound($response);
    }

    public function test_tasks_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/tasks/'.$task->id, [
            'contact_id' => $contact->id,
            'title' => 'the task',
            'completed' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonTask,
        ]);
        $task_id = $response->json('data.id');
        $this->assertEquals($task->id, $task_id);
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $task_id,
        ]);

        $this->assertGreaterThan(0, $task_id);
        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $task_id,
            'title' => 'the task',
            'completed' => false,
        ]);
    }

    public function test_tasks_update_error()
    {
        $user = $this->signin();
        $task = factory(Task::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/tasks/'.$task->id, [
            'contact_id' => $task->contact_id,
        ]);

        $this->expectDataError($response, [
            'The title field is required.',
            'The completed field is required.',
        ]);
    }

    public function test_tasks_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/tasks/'.$task->id, [
            'contact_id' => $contact->id,
            'title' => 'the task',
            'completed' => false,
        ]);

        $this->expectNotFound($response);
    }

    public function test_tasks_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $task->id,
        ]);

        $response = $this->json('DELETE', '/api/tasks/'.$task->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $task->id,
        ]);
    }

    public function test_tasks_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/tasks/0');

        $this->expectNotFound($response);
    }
}
