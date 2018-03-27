<?php

namespace Tests\Unit;

use App\Task;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TaskTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $task = factory('App\Task')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($task->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $account = factory('App\Account')->create([]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $task = factory('App\Task')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($task->contact()->exists());
    }

    public function test_it_filters_by_completed_items()
    {
        $task = factory('App\Task')->create(['completed' => true]);
        $task = factory('App\Task')->create(['completed' => true]);
        $task = factory('App\Task')->create(['completed' => false]);
        $task = factory('App\Task')->create(['completed' => true]);

        $this->assertEquals(
            3,
            Task::completed()->count()
        );
    }

    public function test_it_filters_by_incomplete_items()
    {
        $task = factory('App\Task')->create(['completed' => false]);
        $task = factory('App\Task')->create(['completed' => true]);
        $task = factory('App\Task')->create(['completed' => true]);
        $task = factory('App\Task')->create(['completed' => true]);

        $this->assertEquals(
            1,
            Task::inProgress()->count()
        );
    }
}
