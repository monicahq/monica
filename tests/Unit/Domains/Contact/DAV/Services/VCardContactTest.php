<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class VCardContactTest extends TestCase
{
    use CardEtag, DatabaseTransactions, PHPUnitAssertions;

    /**
     * @test
     *
     * @group dav
     */
    public function test_carddav_get_one_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);

        $response = $this->get("/dav/addressbooks/{$user->email}/$vaultname/{$contact->id}.vcf", [
            'HTTP_ACCEPT' => 'text/vcard; version=4.0',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-Sabre-Version');

        $this->assertVObjectEqualsVObject($this->getCard($contact, true), $response->getContent());
    }

    /**
     * @test
     *
     * @group dav
     */
    public function test_carddav_put_one_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vaultname = rawurlencode($vault->name);
        $uuid = (string) Str::orderedUuid();

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/$vaultname/$uuid.vcf", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:4.0\nUID:$uuid\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD"
        );

        $response->assertStatus(201);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'id' => $uuid,
            'vault_id' => $vault->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * @test
     *
     * @group dav
     */
    public function test_carddav_update_existing_contact_base()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/$vaultname/{$contact->id}.vcf", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:4.0\nFN:John Doex\nN:Doex;John;;;\nEND:VCARD"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'first_name' => 'John',
            'last_name' => 'Doex',
        ]);
    }

    /**
     * @test
     *
     * @group dav
     */
    public function test_carddav_update_existing_contact_if_modified()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $filename = urlencode($contact->id.'.vcf');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/$vaultname/$filename", [], [], [],
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
            'vault_id' => $vault->id,
            'first_name' => 'John',
            'last_name' => 'Doex',
        ]);
    }

    /**
     * @test
     *
     * @group dav
     */
    public function test_carddav_update_existing_contact_if_modified_not_modified()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $filename = urlencode($contact->id.'.vcf');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/$vaultname/$filename", [], [], [],
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
     * @test
     *
     * @group dav
     */
    public function test_carddav_update_existing_contact_if_unmodified()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $filename = urlencode($contact->id.'.vcf');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/$vaultname/$filename", [], [], [],
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
            'vault_id' => $vault->id,
            'first_name' => 'John',
            'last_name' => 'Doex',
        ]);
    }

    /**
     * @test
     *
     * @group dav
     */
    public function test_carddav_update_existing_contact_if_unmodified_error()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $filename = urlencode($contact->id.'.vcf');

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/$vaultname/$filename", [], [], [],
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
     * @test
     *
     * @group dav
     */
    public function test_carddav_update_existing_contact_no_modify()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $filename = urlencode($contact->id.'.vcf');

        $response = $this->get("/dav/addressbooks/{$user->email}/$vaultname/$filename");
        $data = $response->getContent();

        $response = $this->call('PUT', "/dav/addressbooks/{$user->email}/$vaultname/$filename", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            $data
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        // $response->assertHeader('ETag'); // etag no more sent
    }

    public function test_carddav_contacts_report_version4()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/$vaultname/", [], [], [],
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

        $response->assertSee('<d:response>'.
          "<d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact->id}.vcf</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($contact)}&quot;</d:getetag>".
              "<card:address-data>{$vcard}</card:address-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>', false);
    }

    public function test_carddav_contacts_report_version3()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/$vaultname/", [], [], [],
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

        $response->assertSee('<d:response>'.
          "<d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact->id}.vcf</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($contact)}&quot;</d:getetag>".
              "<card:address-data>{$vcard}</card:address-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>', false);
    }

    public function test_carddav_contacts_report_multiget()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $vaultname = rawurlencode($vault->name);
        $contact1 = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $contact2 = Contact::factory()->random()->create(['vault_id' => $vault->id]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/$vaultname/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
            ],
            "<card:addressbook-multiget xmlns:d=\"DAV:\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\">
               <d:prop>
                 <d:getetag />
                 <card:address-data content-type=\"text/vcard\" version=\"4.0\" />
               </d:prop>
               <d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact1->id}.vcf</d:href>
               <d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact2->id}.vcf</d:href>
             </card:addressbook-multiget>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $vcard1 = mb_ereg_replace("\n", "&#13;\n", $this->getCard($contact1));
        $vcard2 = mb_ereg_replace("\n", "&#13;\n", $this->getCard($contact2));

        $response->assertSee('<d:response>'.
          "<d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact1->id}.vcf</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($contact1)}&quot;</d:getetag>".
              "<card:address-data>{$vcard1}</card:address-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact2->id}.vcf</d:href>".
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
     *
     * @test
     */
    public function carddav_delete_one_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vaultname = rawurlencode($vault->name);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);

        $response = $this->call('DELETE', "/dav/addressbooks/{$user->email}/$vaultname/{$contact->id}.vcf");

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseMissing('contacts', [
            'vault_id' => $vault->id,
            'id' => $contact->id,
            'deleted_at' => null,
        ]);
    }
}
