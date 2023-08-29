<?php

namespace Tests\Unit\Domains\Vault\ManageTasks\Web\ViewHelpers;

use App\Domains\Vault\ManageTasks\Web\ViewHelpers\VaultTasksIndexViewHelper;
use App\Models\Contact;
use App\Models\ContactTask;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class VaultTasksIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 1, 1));
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $task = ContactTask::factory()->create([
            'contact_id' => $contact->id,
            'completed' => false,
            'due_at' => '2021-01-01',
        ]);

        $collection = VaultTasksIndexViewHelper::data($vault, $user);

        $this->assertEquals(
            [
                0 => [
                    'id' => $task->id,
                    'label' => $task->label,
                    'due_at' => [
                        'formatted' => 'Jan 01, 2021',
                        'value' => '2021-01-01',
                        'is_late' => true,
                    ],
                    'url' => [
                        'toggle' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/tasks/'.$task->id.'/toggle',
                    ],
                ],
            ],
            $collection->toArray()[0]['tasks']->toArray()
        );
    }
}
