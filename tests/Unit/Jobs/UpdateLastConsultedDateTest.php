<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Jobs\UpdateLastConsultedDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateLastConsultedDateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_the_last_consulted_at_field_for_the_given_contact()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0, 'America/New_York'));

        $contact = factory(Contact::class)->create([
            'number_of_views' => 1,
        ]);

        dispatch(new UpdateLastConsultedDate($contact));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'last_consulted_at' => '2017-01-01 07:00:00',
            'number_of_views' => 2,
        ]);
    }
}
