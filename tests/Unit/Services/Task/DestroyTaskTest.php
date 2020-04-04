<?php

namespace Tests\Unit\Services\Task;

use Tests\TestCase;
use App\Models\Contact\Task;
use App\Models\Account\Account;
use App\Services\Task\DestroyTask;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyTaskTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_task()
    {
        $task = factory(Task::class)->create([]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);

        $request = [
            'account_id' => $task->account_id,
            'task_id' => $task->id,
        ];

        app(DestroyTask::class)->execute($request);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'task_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        app(DestroyTask::class)->execute($request);
    }

    /** @test */
    public function it_throws_a_task_doesnt_exist()
    {
        $task = factory(Task::class)->create([]);
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'task_id' => $task->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(DestroyTask::class)->execute($request);
    }
}
