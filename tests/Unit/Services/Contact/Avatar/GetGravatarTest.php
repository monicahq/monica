<?php

namespace Tests\Unit\Services\Contact\Avatar;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use App\Services\Contact\Avatar\GetGravatar;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Avatar\GetGravatarURL;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetGravatarTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_gravatar_url()
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

        $request = [
            'contact_id' => $contact->id,
        ];

        $contact = app(GetGravatar::class)->execute($request);

        $this->assertNotNull(
            $contact->avatar_gravatar_url
        );
    }

    /** @test */
    public function it_get_gravatar_of_real_email()
    {
        $contact = factory(Contact::class)->create();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $contact->account->id,
        ]);
        factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'bademail',
        ]);
        factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'matt@wordpress.com',
        ]);

        $request = [
            'contact_id' => $contact->id,
        ];

        $contact = app(GetGravatar::class)->execute($request);

        $this->assertNotNull(
            $contact->avatar_gravatar_url
        );
    }

    /** @test */
    public function it_returns_an_url()
    {
        $request = [
            'email' => 'matt@wordpress.com',
            'size' => 400,
        ];

        $url = app(GetGravatarURL::class)->execute($request);

        $this->assertEquals(
            'https://www.gravatar.com/avatar/5bbc9048a99ec78cdbc227770e707efb.jpg?s=400&d=404&r=g',
            $url
        );
    }

    /** @test */
    public function it_returns_an_url_with_a_small_avatar_size()
    {
        $request = [
            'email' => 'matt@wordpress.com',
            'size' => 80,
        ];

        $url = app(GetGravatarURL::class)->execute($request);

        $this->assertEquals(
            'https://www.gravatar.com/avatar/5bbc9048a99ec78cdbc227770e707efb.jpg?s=80&d=404&r=g',
            $url
        );
    }

    /** @test */
    public function it_returns_an_url_with_a_default_avatar_size()
    {
        $request = [
            'email' => 'matt@wordpress.com',
        ];

        $url = app(GetGravatarURL::class)->execute($request);

        // should return an avatar of 200 px wide
        $this->assertEquals(
            'https://www.gravatar.com/avatar/5bbc9048a99ec78cdbc227770e707efb.jpg?s=200&d=404&r=g',
            $url
        );
    }

    /** @test */
    public function it_returns_null_if_no_avatar_is_found()
    {
        $request = [
            'email' => 'jlskjdfl@dskfjlsd.com',
        ];

        // should return an avatar of 200 px wide
        $this->assertNull(
            app(GetGravatarURL::class)->execute($request)
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'size' => 200,
        ];

        $this->expectException(ValidationException::class);
        $url = app(GetGravatarURL::class)->execute($request);
    }
}
