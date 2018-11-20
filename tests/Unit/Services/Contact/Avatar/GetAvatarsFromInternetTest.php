<?php

namespace Tests\Unit\Services\Contact\url;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactFieldType;
use App\Models\Contact\ContactField;
use App\Services\Contact\Avatar\GetAvatarsFromInternet;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetAvatarsFromInternetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_a_contact_object_with_avatars()
    {
        $contact = factory(Contact::class)->create([]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $contact->account->id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'matt@wordpress.com',
        ]);

        $request = [
            'contact_id' => $contact->id,
        ];

        $avatarService = new GetAvatarsFromInternet;
        $contact = $avatarService->execute($request);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );

        $this->assertNotNull(
            $contact->avatar_adorable_url
        );

        $this->assertNotNull(
            $contact->avatar_gravatar_url
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'size' => 200,
        ];

        $this->expectException(MissingParameterException::class);

        $avatarService = new GetAvatarsFromInternet;
        $contact = $avatarService->execute($request);
    }
}
