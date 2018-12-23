<?php

namespace Tests\Api\Carddav;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CarddavContactTest extends ApiTestCase
{
    use DatabaseTransactions;

    /**
     * @group carddav
     */
    public function test_carddav_get_one_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->get("/carddav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf");

        $response->assertStatus(200);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('PRODID:-//Sabre//Sabre VObject');
        $response->assertSee('FN:John Doe');
        $response->assertSee('N:Doe;John;;;');
        $response->assertSee('GENDER:O;');
    }

    /**
     * @group carddav
     */
    public function test_carddav_put_one_contact()
    {
        $user = $this->signin();

        $response = $this->call('PUT', "/carddav/addressbooks/{$user->email}/contacts/single_vcard_stub.vcf", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD"
        );

        $response->assertStatus(201);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * @group carddav
     */
    public function test_carddav_update_existing_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $filename = urlencode($contact->uuid.'.vcf');

        $response = $this->call('PUT', "/carddav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doex\nN:Doex;John;;;\nEND:VCARD"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'first_name' => 'John',
            'last_name' => 'Doex',
        ]);
    }

    /**
     * @group carddav
     */
    public function test_carddav_update_existing_contact_no_modify()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $filename = urlencode($contact->uuid.'.vcf');

        $response = $this->get("/carddav/addressbooks/{$user->email}/contacts/{$filename}");
        $data = $response->getContent();

        $response = $this->call('PUT', "/carddav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            $data
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeader('ETag');
    }

    public function test_carddav_contacts_report()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('REPORT', "/carddav/addressbooks/{$user->email}/contacts/", [], [], [],
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

        $peopleurl = route('people.show', $contact);
        $sabreversion = \Sabre\VObject\Version::VERSION;

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav">'.
        '<d:response>'.
          "<d:href>/carddav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              '<d:getetag>&quot;');
        $response->assertSee('&quot;</d:getetag>'.
              "<card:address-data>BEGIN:VCARD&#13;\n".
        "VERSION:4.0&#13;\n".
        "PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN&#13;\n".
        "UID:{$contact->uuid}&#13;\n".
        "SOURCE:{$peopleurl}&#13;\n".
        "FN:John Doe&#13;\n".
        "N:Doe;John;;;&#13;\n".
        "GENDER:O;&#13;\n".
        "END:VCARD&#13;\n".
               '</card:address-data>'.
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus>');
    }

    public function test_carddav_contacts_report_multiget()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('REPORT', "/carddav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
            ],
            "<card:addressbook-multiget xmlns:d=\"DAV:\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\">
               <d:prop>
                 <d:getetag />
                 <card:address-data content-type=\"text/vcard\" version=\"4.0\" />
               </d:prop>
               <d:href>/carddav/addressbooks/{$user->email}/contacts/{$contact1->uuid}.vcf</d:href>
               <d:href>/carddav/addressbooks/{$user->email}/contacts/{$contact2->uuid}.vcf</d:href>
             </card:addressbook-multiget>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $people1url = route('people.show', $contact1);
        $people2url = route('people.show', $contact2);
        $sabreversion = \Sabre\VObject\Version::VERSION;

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav">'.
        '<d:response>'.
          "<d:href>/carddav/addressbooks/{$user->email}/contacts/{$contact1->uuid}.vcf</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              '<d:getetag>&quot;');
        $response->assertSee('&quot;</d:getetag>'.
              "<card:address-data>BEGIN:VCARD&#13;\n".
        "VERSION:4.0&#13;\n".
        "PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN&#13;\n".
        "UID:{$contact1->uuid}&#13;\n".
        "SOURCE:{$people1url}&#13;\n".
        "FN:John Doe&#13;\n".
        "N:Doe;John;;;&#13;\n".
        "GENDER:O;&#13;\n".
        "END:VCARD&#13;\n".
               '</card:address-data>'.
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>');
        $response->assertSee(
          '<d:response>'.
            "<d:href>/carddav/addressbooks/{$user->email}/contacts/{$contact2->uuid}.vcf</d:href>".
            '<d:propstat>'.
              '<d:prop>'.
                '<d:getetag>&quot;');
        $response->assertSee('&quot;</d:getetag>'.
                "<card:address-data>BEGIN:VCARD&#13;\n".
          "VERSION:4.0&#13;\n".
          "PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN&#13;\n".
          "UID:{$contact2->uuid}&#13;\n".
          "SOURCE:{$people2url}&#13;\n".
          "FN:John Doe&#13;\n".
          "N:Doe;John;;;&#13;\n".
          "GENDER:O;&#13;\n".
          "END:VCARD&#13;\n".
                 '</card:address-data>'.
               '</d:prop>'.
               '<d:status>HTTP/1.1 200 OK</d:status>'.
             '</d:propstat>'.
            '</d:response>'.
          '</d:multistatus>');
    }
}
