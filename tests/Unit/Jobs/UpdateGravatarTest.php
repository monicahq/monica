<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Jobs\Avatars\UpdateGravatar as UpdateGravatarJob;

class UpdateGravatarTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_update_gravatar()
    {
        $contact = factory(Contact::class)->create();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $contact->account->id,
        ]);
        factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'matt@wordpress.com',
        ]);

        (new UpdateGravatarJob($contact))->handle();

        $contact->refresh();

        $this->assertNotNull(
            $contact->avatar_gravatar_url
        );
    }
}
