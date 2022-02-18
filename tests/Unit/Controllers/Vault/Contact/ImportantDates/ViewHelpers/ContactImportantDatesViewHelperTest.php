<?php

namespace Tests\Unit\Controllers\Vault\Contact\ImportantDates\ViewHelpers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Contact;
use App\Models\ContactDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Vault\Contact\ImportantDates\ViewHelpers\ContactImportantDatesViewHelper;

class ContactImportantDatesViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $date = ContactDate::factory()->create([
            'contact_id' => $contact->id,
            'date' => 1981,
        ]);

        $array = ContactImportantDatesViewHelper::data($contact, $user);

        $this->assertEquals(
            5,
            count($array)
        );

        $this->assertArrayHasKey('contact', $array);
        $this->assertArrayHasKey('dates', $array);
        $this->assertArrayHasKey('months', $array);
        $this->assertArrayHasKey('days', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'name' => $contact->getName($user),
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
        $date = ContactDate::factory()->create([
            'contact_id' => $contact->id,
            'date' => 1981,
        ]);

        $array = ContactImportantDatesViewHelper::dto($contact, $date, $user);

        $this->assertEquals(
            [
                'id' => $date->id,
                'label' => $date->label,
                'date' => '1981',
                'type' => 'birthdate',
                'age' => '41',
                'choice' => 'age',
                'completeDate' => '',
                'month' => '',
                'day' => '',
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/dates/'.$date->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/dates/'.$date->id,
                ],
            ],
            $array
        );
    }
}
