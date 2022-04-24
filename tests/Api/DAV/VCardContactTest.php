<?php

namespace Tests\Api\DAV;

use Tests\ApiTestCase;
use Illuminate\Support\Str;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Sabre\VObject\PHPUnitAssertions;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VCardContactTest extends ApiTestCase
{
    use DatabaseTransactions, CardEtag, PHPUnitAssertions;

    /**
     * @group dav
     */
    public function test_carddav_get_one_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->get("/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf", [
            'HTTP_ACCEPT' => 'text/vcard; version=4.0',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-Sabre-Version');

        $this->assertVObjectEqualsVObject($this->getCard($contact, true), $response->getContent());
    }

    /**
     * @group dav
     */
    public function test_carddav_put_one_contact()
    {
        $user = $this->signin();

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/single_vcard_stub.vcf", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD"
        );

        $response->assertStatus(201);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * @group dav
     */
    public function test_carddav_put_one_contact_with_photo()
    {
        Storage::fake();

        $user = $this->signin();

        $image = Image::canvas(1, 1, '#fff')->encode('data-url');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/single_vcard_stub.vcf", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doe\nN:Doe;John;;;\nPHOTO:$image\nEND:VCARD"
        );

        $response->assertStatus(201);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $this->assertDatabaseHas('photos', [
            'account_id' => $user->account_id,
        ]);

        $photo = Photo::where(['account_id' => $user->account_id])->first();

        Storage::disk('public')->assertExists($photo->new_filename);
    }

    /**
     * @group dav
     */
    public function test_carddav_put_one_contact_with_photo_already_set()
    {
        $user = $this->signin();
        $photo = factory(Photo::class)->create([
            'account_id' => $user->account_id,
        ]);
        UploadedFile::fake()->image('file.jpg')->storeAs('', 'file.jpg');
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'avatar_source' => 'photo',
            'avatar_photo_id' => $photo->id,
            'uuid' => Str::uuid()->toString(),
        ]);

        $image = Image::canvas(1, 1, '#fff')->encode('data-url');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doe\nN:Doe;John;;;\nPHOTO:$image\nEND:VCARD"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'avatar_photo_id' => $photo->id,
        ]);
    }

    /**
     * @group dav
     */
    public function test_carddav_put_one_contact_with_photo_and_attributes()
    {
        Storage::fake();

        $user = $this->signin();

        $image = base64_encode(Image::canvas(1, 1, '#fff')->encode('jpg'));

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/single_vcard_stub.vcf", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nPHOTO;ENCODING=B;TYPE=JPEG:$image\nEND:VCARD"
        );

        $response->assertStatus(201);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $this->assertDatabaseHas('photos', [
            'account_id' => $user->account_id,
        ]);

        $photo = Photo::where(['account_id' => $user->account_id])->first();

        Storage::disk('public')->assertExists($photo->new_filename);
    }

    /**
     * @group dav
     */
    public function test_carddav_update_existing_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doex\nN:Doex;John;;;\nEND:VCARD"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'John',
            'last_name' => 'Doex',
        ]);
    }

    /**
     * @group dav
     */
    public function test_carddav_update_existing_contact_if_modified()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $filename = urlencode($contact->uuid.'.vcf');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            [
                'HTTP_If-Modified-Since' => $contact->updated_at->addDays(-1)->toRfc7231String(),
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doex\nN:Doex;John;;;\nEND:VCARD"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'John',
            'last_name' => 'Doex',
        ]);
    }

    /**
     * @group dav
     */
    public function test_carddav_update_existing_contact_if_modified_not_modified()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $filename = urlencode($contact->uuid.'.vcf');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            [
                'HTTP_If-Modified-Since' => $contact->updated_at->addDays(1)->toRfc7231String(),
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doex\nN:Doex;John;;;\nEND:VCARD"
        );

        // Not modified
        $response->assertStatus(304);

        $response->assertHeader('X-Sabre-Version');

        // see http://tools.ietf.org/html/rfc2616#section-10.3.5
        $response->assertHeaderMissing('Last-Modified');

        $response->assertHeaderMissing('ETag');
    }

    /**
     * @group dav
     */
    public function test_carddav_update_existing_contact_if_unmodified()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $filename = urlencode($contact->uuid.'.vcf');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            [
                'HTTP_If-Unmodified-Since' => $contact->updated_at->addDays(1)->toRfc7231String(),
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doex\nN:Doex;John;;;\nEND:VCARD"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'John',
            'last_name' => 'Doex',
        ]);
    }

    /**
     * @group dav
     */
    public function test_carddav_update_existing_contact_if_unmodified_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $filename = urlencode($contact->uuid.'.vcf');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            [
                'HTTP_If-Unmodified-Since' => $contact->updated_at->addDays(-1)->toRfc7231String(),
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doex\nN:Doex;John;;;\nEND:VCARD"
        );

        // PRECONDITION FAILED
        $response->assertStatus(412);

        $response->assertHeader('X-Sabre-Version');

        $sabreversion = \Sabre\DAV\Version::VERSION;
        $response->assertSee("<d:error xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\">
  <s:sabredav-version>{$sabreversion}</s:sabredav-version>
  <s:exception>Sabre\DAV\Exception\PreconditionFailed</s:exception>
  <s:message>An If-Unmodified-Since header was specified, but the entity has been changed since the specified date.</s:message>", false);
    }

    /**
     * @group dav
     */
    public function test_carddav_update_existing_contact_no_modify()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $filename = urlencode($contact->uuid.'.vcf');

        $response = $this->get("/dav/addressbooks/{$user->email}/contacts/{$filename}");
        $data = $response->getContent();

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            $data
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        //$response->assertHeader('ETag'); // etag no more sent
    }

    public function test_carddav_contacts_report_version4()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            '<card:addressbook-query xmlns:d="DAV:" xmlns:card="urn:ietf:params:xml:ns:carddav">
               <d:prop>
                 <d:getetag />
                 <card:address-data content-type="text/vcard" version="4.0" />
               </d:prop>
             </card:addressbook-query>'
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $vcard = mb_ereg_replace("\n", "&#13;\n", $this->getCard($contact));

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
        '<d:response>'.
          "<d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($contact)}&quot;</d:getetag>".
              "<card:address-data>{$vcard}</card:address-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus>', false);
    }

    public function test_carddav_contacts_report_version3()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            '<card:addressbook-query xmlns:d="DAV:" xmlns:card="urn:ietf:params:xml:ns:carddav">
               <d:prop>
                 <d:getetag />
                 <card:address-data content-type="text/vcard" version="3.0" />
               </d:prop>
             </card:addressbook-query>'
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $vcard = mb_ereg_replace('VERSION:4.0', 'VERSION:3.0', $this->getCard($contact));
        $vcard = mb_ereg_replace("\n", "&#13;\n", $vcard);

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
        '<d:response>'.
          "<d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($contact)}&quot;</d:getetag>".
              "<card:address-data>{$vcard}</card:address-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus>', false);
    }

    public function test_carddav_contacts_report_multiget()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
            ],
            "<card:addressbook-multiget xmlns:d=\"DAV:\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\">
               <d:prop>
                 <d:getetag />
                 <card:address-data content-type=\"text/vcard\" version=\"4.0\" />
               </d:prop>
               <d:href>/dav/addressbooks/{$user->email}/contacts/{$contact1->uuid}.vcf</d:href>
               <d:href>/dav/addressbooks/{$user->email}/contacts/{$contact2->uuid}.vcf</d:href>
             </card:addressbook-multiget>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $vcard1 = mb_ereg_replace("\n", "&#13;\n", $this->getCard($contact1));
        $vcard2 = mb_ereg_replace("\n", "&#13;\n", $this->getCard($contact2));

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
        '<d:response>'.
          "<d:href>/dav/addressbooks/{$user->email}/contacts/{$contact1->uuid}.vcf</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($contact1)}&quot;</d:getetag>".
              "<card:address-data>{$vcard1}</card:address-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>', false);
        $response->assertSee(
          '<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/contacts/{$contact2->uuid}.vcf</d:href>".
            '<d:propstat>'.
              '<d:prop>'.
                "<d:getetag>&quot;{$this->getEtag($contact2)}&quot;</d:getetag>".
                "<card:address-data>{$vcard2}</card:address-data>".
               '</d:prop>'.
               '<d:status>HTTP/1.1 200 OK</d:status>'.
             '</d:propstat>'.
            '</d:response>'.
          '</d:multistatus>', false);
    }

    /**
     * @group dav
     * @test
     */
    public function carddav_delete_one_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('DELETE', "/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf");

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseMissing('contacts', [
            'account_id' => $user->account_id,
            'id' => $contact->id,
            'deleted_at' => null,
        ]);
    }
}
