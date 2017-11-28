<?php

namespace Tests\Unit;

use App\Task;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TaskTest extends TestCase
{
    use DatabaseTransactions;

    public function testToggle()
    {
        $contact = factory(\App\Contact::class)->create();

        $task = factory(\App\Task::class)->make([
            'contact_id' => $contact->id,
            'status' => 'inprogress',
        ]);

        $this->assertEquals(
            'inprogress',
            $task->status
        );

        $task->toggle();

        $this->assertEquals(
            'completed',
            $task->status
        );

        $task->toggle();

        $this->assertEquals(
            'inprogress',
            $task->status
        );
    }
}
