<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Task;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiTaskControllerTest extends ApiTestCase
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

    /** @test */
    public function it_gets_all_the_tasks()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task1 = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task2 = factory(Task::class)->create([
            'account_id' => $user->account_id,
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

    /** @test */
    public function it_gets_all_the_tasks_of_a_contact()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task1 = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task2 = factory(Task::class)->create([
            'account_id' => $user->account_id,
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

    /** @test */
    public function it_cant_get_the_tasks_of_a_contact_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/tasks');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_gets_a_specific_task()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task1 = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $task2 = factory(Task::class)->create([
            'account_id' => $user->account_id,
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

    /** @test */
    public function it_cant_get_a_task_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/tasks/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_create_a_task_associated_to_a_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/tasks', [
            'contact_id' => $contact->id,
            'title' => 'the task',
            'description' => 'description',
            'completed' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonTask,
        ]);
        $taskId = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $taskId,
        ]);

        $this->assertGreaterThan(0, $taskId);
        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $taskId,
            'title' => 'the task',
            'completed' => false,
        ]);
    }

    /** @test */
    public function it_create_a_task_not_associated_to_a_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/tasks', [
            'contact_id' => $contact->id,
            'title' => 'the task',
            'description' => 'description',
            'completed' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonTask,
        ]);
        $taskId = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $taskId,
        ]);

        $this->assertGreaterThan(0, $taskId);
        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $taskId,
            'title' => 'the task',
            'completed' => false,
        ]);
    }

    /** @test */
    public function creating_a_task_triggers_invalid_parameter_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/tasks', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The title field is required.',
        ]);
    }

    /** @test */
    public function creating_a_task_with_a_wrong_account_id_triggers_an_error()
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

    /** @test */
    public function it_updates_a_task()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
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
        $taskId = $response->json('data.id');
        $this->assertEquals($task->id, $taskId);
        $response->assertJsonFragment([
            'object' => 'task',
            'id' => $taskId,
        ]);

        $this->assertGreaterThan(0, $taskId);
        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $taskId,
            'title' => 'the task',
            'completed' => false,
        ]);
    }

    /** @test */
    public function updating_a_task_with_missing_parameters_triggers_an_error()
    {
        $user = $this->signin();
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/tasks/'.$task->id, [
            'contact_id' => $task->contact_id,
        ]);

        $this->expectDataError($response, [
            'The title field is required.',
            'The completed field is required.',
        ]);
    }

    /** @test */
    public function updating_a_task_with_wrong_account_triggers_an_error()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $task = factory(Task::class)->create([]);

        $response = $this->json('PUT', '/api/tasks/'.$task->id, [
            'contact_id' => $contact->id,
            'title' => 'the task',
            'completed' => false,
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_deletes_a_task()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('DELETE', '/api/tasks/'.$task->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tasks', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $task->id,
        ]);
    }

    /** @test */
    public function it_cant_delete_a_task_if_wrong_task_id()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/tasks/0');

        $this->expectNotFound($response);
    }
}
