<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Task;
use App\Models\Contact\ContactFieldType;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Task\CreateTask;

class CreateTaskTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_task()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'title' => 'This is a title',
            'description' => 'This is a description',
        ];

        $taskService = new CreateTask;
        $task = $taskService->execute($request);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'contact_id' => $contact->id,
            'title' => 'This is a title',
            'description' => 'This is a description',
        ]);

        $this->assertInstanceOf(
            Task::class,
            $task
        );
    }

    public function test_it_stores_a_task_without_contact_id()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'title' => 'This is a title',
            'description' => 'This is a description',
        ];

        $taskService = new CreateTask;
        $task = $taskService->execute($request);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'contact_id' => null,
            'title' => 'This is a title',
            'description' => 'This is a description',
        ]);

        $this->assertInstanceOf(
            Task::class,
            $task
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
        ];

        $this->expectException(MissingParameterException::class);

        $createTask = new CreateTask;
        $task = $createTask->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'title' => 'This is a title',
            'description' => 'This is a description',
        ];

        $this->expectException(ModelNotFoundException::class);

        $createTask = (new CreateTask)->execute($request);
    }
}
