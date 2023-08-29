<?php

namespace Tests\Unit\Domains\Contact\ManageTasks\Web\ViewHelpers;

use App\Domains\Contact\ManageTasks\Web\ViewHelpers\ModuleContactTasksViewHelper;
use App\Models\Contact;
use App\Models\ContactTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModuleContactTasksViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $task = ContactTask::factory()->create([
            'contact_id' => $contact->id,
        ]);
        ContactTask::factory()->create([
            'contact_id' => $contact->id,
            'completed' => true,
        ]);

        $array = ModuleContactTasksViewHelper::data($contact, $user);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('tasks', $array);
        $this->assertArrayHasKey('completed_tasks_count', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            1,
            $array['completed_tasks_count']
        );

        $this->assertEquals(
            [
                'completed' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/tasks/completed',
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/tasks',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $task = ContactTask::factory()->create([
            'contact_id' => $contact->id,
            'due_at' => '2018-01-01',
        ]);

        $collection = ModuleContactTasksViewHelper::dtoTask($contact, $task, $user);

        $this->assertEquals(
            [
                'id' => $task->id,
                'label' => $task->label,
                'description' => $task->description,
                'completed' => false,
                'completed_at' => null,
                'due_at' => [
                    'formatted' => 'Jan 01, 2018',
                    'value' => '2018-01-01',
                    'is_late' => false,
                ],
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/tasks/'.$task->id,
                    'toggle' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/tasks/'.$task->id.'/toggle',
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/tasks/'.$task->id,
                ],
            ],
            $collection
        );
    }
}
