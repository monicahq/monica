<?php

/**
 * vCard Import Feature - Parse vCard File Tests
 *
 * @author Clément ABRAHAM <https://github.com/cabraham2>
 * @version 1.0.0
 * @created 2026-01-08
 * @updated 2026-01-08
 * @license MIT
 *
 * Unit tests for ParseVCardFile service.
 * Tests parsing of various vCard formats and field extraction.
 */

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\ParseVCardFile;
use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParseVCardFileTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_parses_a_simple_vcard()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "EMAIL;type=INTERNET;type=HOME:john@example.com\n".
            "TEL;type=CELL:+1234567890\n".
            "END:VCARD";

        $service = app(ParseVCardFile::class);
        $result = $service->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'file_content' => $vcardContent,
        ]);

        $this->assertCount(1, $result);
        $contact = $result->first();

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
        $this->assertEquals('John Doe', $contact['formatted_name']);
        $this->assertCount(1, $contact['emails']);
        $this->assertEquals('john@example.com', $contact['emails'][0]['value']);
        $this->assertCount(1, $contact['phones']);
        $this->assertEquals('+1234567890', $contact['phones'][0]['value']);
    }

    /** @test */
    public function it_parses_multiple_vcards()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "END:VCARD\n".
            "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Smith;Jane;;;\n".
            "FN:Jane Smith\n".
            "END:VCARD";

        $service = app(ParseVCardFile::class);
        $result = $service->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'file_content' => $vcardContent,
        ]);

        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('Jane', $result[1]['first_name']);
    }

    /** @test */
    public function it_parses_vcard_with_organization()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "ORG:Example Inc.\n".
            "TITLE:Software Engineer\n".
            "END:VCARD";

        $service = app(ParseVCardFile::class);
        $result = $service->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'file_content' => $vcardContent,
        ]);

        $contact = $result->first();
        $this->assertEquals('Example Inc.', $contact['organization']);
        $this->assertEquals('Software Engineer', $contact['title']);
    }

    /** @test */
    public function it_parses_vcard_with_birthday()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "BDAY:1990-05-15\n".
            "END:VCARD";

        $service = app(ParseVCardFile::class);
        $result = $service->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'file_content' => $vcardContent,
        ]);

        $contact = $result->first();
        $this->assertEquals('1990-05-15', $contact['birthday']);
    }

    /** @test */
    public function it_parses_vcard_with_multiple_emails_and_phones()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "EMAIL;type=INTERNET;type=HOME:personal@example.com\n".
            "EMAIL;type=INTERNET;type=WORK:work@example.com\n".
            "TEL;type=CELL:+1234567890\n".
            "TEL;type=HOME:+0987654321\n".
            "END:VCARD";

        $service = app(ParseVCardFile::class);
        $result = $service->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'file_content' => $vcardContent,
        ]);

        $contact = $result->first();
        $this->assertCount(2, $contact['emails']);
        $this->assertCount(2, $contact['phones']);
    }

    /** @test */
    public function it_handles_invalid_vcard_gracefully()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);

        $vcardContent = "INVALID VCARD CONTENT";

        $service = app(ParseVCardFile::class);
        $result = $service->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'file_content' => $vcardContent,
        ]);

        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_parses_vcard_with_address()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "ADR;type=HOME:;;123 Main St;Springfield;IL;62701;USA\n".
            "END:VCARD";

        $service = app(ParseVCardFile::class);
        $result = $service->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'file_content' => $vcardContent,
        ]);

        $contact = $result->first();
        $this->assertCount(1, $contact['addresses']);
        $this->assertEquals('123 Main St', $contact['addresses'][0]['street']);
        $this->assertEquals('Springfield', $contact['addresses'][0]['city']);
        $this->assertEquals('IL', $contact['addresses'][0]['region']);
        $this->assertEquals('62701', $contact['addresses'][0]['postal_code']);
        $this->assertEquals('USA', $contact['addresses'][0]['country']);
    }

    /** @test */
    public function it_parses_vcard_with_categories()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "CATEGORIES:Friends,Family\n".
            "END:VCARD";

        $service = app(ParseVCardFile::class);
        $result = $service->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'file_content' => $vcardContent,
        ]);

        $contact = $result->first();
        $this->assertCount(2, $contact['categories']);
        $this->assertContains('Friends', $contact['categories']);
        $this->assertContains('Family', $contact['categories']);
    }
}
