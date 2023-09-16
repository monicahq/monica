<?php

namespace Tests\Unit\Domains\Contact\ManageContactImportantDates\Web\ViewHelpers;

use App\Domains\Contact\ManageContactImportantDates\Web\ViewHelpers\ModuleImportantDatesViewHelper;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleImportantDatesViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'day' => 29,
            'month' => 10,
            'year' => 1981,
        ]);

        $array = ModuleImportantDatesViewHelper::data($contact, $user);

        $this->assertEquals(
            2,
            count($array)
        );

        $this->assertArrayHasKey('dates', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $date->id,
                    'label' => $date->label,
                    'date' => 'Oct 29, 1981',
                    'type' => 'Birthdate',
                    'age' => 36,
                ],
            ],
            $array['dates']->toArray()
        );

        $this->assertEquals(
            [
                'edit' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/dates',
            ],
            $array['url']
        );
    }
}
