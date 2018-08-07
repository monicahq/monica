<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Services\Contact\CreateShareableLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateShareableLinkTest extends TestCase
{
    //use DatabaseTransactions;

    public function test_it_creates_a_shareable_link()
    {
        $contact = factory(Contact::class)->create([]);
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'share_expire_at' => null,
            'shareable_link' => null,
        ]);

        $shareableService = new CreateShareableLink;
        $link = $shareableService->execute([
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue(
            strlen($link) == 240
        );

        $contact = Contact::find($contact->id);

        $this->assertEquals(
            '2017-01-04',
            $contact->share_expire_at->format('Y-m-d')
        );

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
            'share_expire_at' => null,
            'shareable_link' => null,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'contact_id' => 1,
        ];

        $this->expectException(\Exception::class);

        $shareableService = new CreateShareableLink;
        $link = $shareableService->execute($request);
    }

    public function test_it_throws_an_exception_if_model_is_not_found()
    {
        $request = [
            'contact_id' => 12343123,
            'account_id' => 12343123,
        ];

        $this->expectException(ModelNotFoundException::class);

        $shareableService = new CreateShareableLink;
        $link = $shareableService->execute($request);
    }
}
