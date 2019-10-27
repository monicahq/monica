<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Task;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TaskTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $task = factory(Task::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($task->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $task = factory(Task::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($task->contact()->exists());
    }

    public function test_it_filters_by_completed_items()
    {
        $task = factory(Task::class)->create(['completed' => true]);
        $task = factory(Task::class)->create(['completed' => true]);
        $task = factory(Task::class)->create(['completed' => false]);
        $task = factory(Task::class)->create(['completed' => true]);

        $this->assertEquals(
            3,
            Task::completed()->count()
        );
    }

    public function test_it_filters_by_incomplete_items()
    {
        $task = factory(Task::class)->create(['completed' => false]);
        $task = factory(Task::class)->create(['completed' => true]);
        $task = factory(Task::class)->create(['completed' => true]);
        $task = factory(Task::class)->create(['completed' => true]);

        $this->assertEquals(
            1,
            Task::inProgress()->count()
        );
    }
}
