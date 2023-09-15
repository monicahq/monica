<?php

namespace Tests\Unit\Domains\Contact\ManageContactImportantDates\Web\ViewHelpers;

use App\Domains\Contact\ManageContactImportantDates\Web\ViewHelpers\ContactImportantDatesViewHelper;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactImportantDatesViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'day' => 29,
            'month' => 10,
            'year' => 1981,
        ]);

        $array = ContactImportantDatesViewHelper::data($contact, $user);

        $this->assertEquals(
            6,
            count($array)
        );

        $this->assertArrayHasKey('contact', $array);
        $this->assertArrayHasKey('dates', $array);
        $this->assertArrayHasKey('months', $array);
        $this->assertArrayHasKey('days', $array);
        $this->assertArrayHasKey('date_types', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'name' => $contact->name,
            ],
            $array['contact']
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/dates',
                'contact' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 1, 1));
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'day' => 29,
            'month' => 10,
            'year' => 1981,
        ]);

        $array = ContactImportantDatesViewHelper::dto($contact, $date, $user);

        $this->assertEquals(
            [
                'id' => $date->id,
                'label' => $date->label,
                'date' => 'Oct 29, 1981',
                'type' => [
                    'id' => $date->contactImportantDateType->id,
                    'label' => 'Birthdate',
                ],
                'age' => '40',
                'choice' => 'full_date',
                'completeDate' => '1981-10-29',
                'month' => 10,
                'day' => 29,
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/dates/'.$date->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/dates/'.$date->id,
                ],
            ],
            $array
        );
    }
}
