<?php

namespace Tests\Unit\Domains\Contact\ManageReminders\Web\ViewHelpers;

use App\Domains\Contact\ManageReminders\Web\ViewHelpers\ModuleRemindersViewHelper;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModuleRemindersViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        ContactReminder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $array = ModuleRemindersViewHelper::data($contact, $user);

        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('reminders', $array);
        $this->assertArrayHasKey('days', $array);
        $this->assertArrayHasKey('months', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/reminders',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $reminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'reminder',
            'day' => 29,
            'month' => 10,
            'year' => 1981,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'frequency_number' => 1,
        ]);

        $array = ModuleRemindersViewHelper::dtoReminder($contact, $reminder, $user);

        $this->assertEquals(
            [
                'id' => $reminder->id,
                'label' => 'reminder',
                'date' => 'Oct 29, 1981',
                'type' => 'one_time',
                'frequency_number' => 1,
                'day' => 29,
                'month' => 10,
                'choice' => 'full_date',
                'reminder_choice' => 'one_time',
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/reminders/'.$reminder->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/reminders/'.$reminder->id,
                ],
            ],
            $array
        );
    }
}
