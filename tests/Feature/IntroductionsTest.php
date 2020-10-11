<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IntroductionsTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_display_introductions_screen()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->get("/people/{$contact->hashID()}/introductions/edit");

        $response->assertStatus(200);
        $response->assertSee("How did you meet {$contact->first_name}");
    }

    public function test_it_update_introductions()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->post("/people/{$contact->hashID()}/introductions/update", [
            'first_met_additional_info' => 'info',
            'is_first_met_date_known' => 'known',
            'first_met_year' => 2006,
            'first_met_month' => 1,
            'first_met_day' => 2,
            'addReminder' => 'on',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect("/people/{$contact->hashID()}");

        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => Contact::find($contact->id)->first_met_special_date_id,
            'is_age_based' => false,
            'is_year_unknown' => false,
            'date' => '2006-01-02',
        ]);
    }
}
