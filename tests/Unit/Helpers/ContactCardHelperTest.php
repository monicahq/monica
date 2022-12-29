<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ContactCardHelper;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactCardHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_date_formatted_according_to_user_preferences(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 1, 1));

        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $type = ContactImportantDateType::factory()->create([
            'vault_id' => $contact->vault_id,
            'internal_type' => ContactImportantDate::TYPE_BIRTHDATE,
        ]);
        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'contact_important_date_type_id' => $type->id,
            'day' => 29,
            'month' => 10,
            'year' => 1981,
        ]);

        $array = ContactCardHelper::data($contact);

        $this->assertEquals(
            $contact->id,
            $array['id']
        );

        $this->assertEquals(
            $contact->name,
            $array['name']
        );

        $this->assertEquals(
            40,
            $array['age']
        );

        $this->assertEquals(
            env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id,
            $array['url']
        );

        $this->assertCount(0, $array['groups']->toArray());
    }
}
