<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\CsvToVCard;
use Tests\TestCase;

class CsvToVCardTest extends TestCase
{
    /** @test */
    public function it_converts_minimal_row()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertStringContainsString('FN:John Doe', $vcard);
        $this->assertStringContainsString('N:Doe;John;;;', $vcard);
        $this->assertStringContainsString('BEGIN:VCARD', $vcard);
        $this->assertStringContainsString('END:VCARD', $vcard);
        $this->assertStringContainsString('VERSION:3.0', $vcard);
    }

    /** @test */
    public function it_converts_with_all_aliases_for_first_name()
    {
        $aliases = ['first_name', 'firstname', 'given_name', 'givenname'];
        foreach ($aliases as $alias) {
            $vcard = CsvToVCard::convert([
                $alias => 'Jane',
                'last_name' => 'Smith',
            ]);
            $this->assertStringContainsString('FN:Jane Smith', $vcard, "Alias '$alias' failed");
        }
    }

    /** @test */
    public function it_converts_with_all_aliases_for_last_name()
    {
        $aliases = ['last_name', 'lastname', 'family_name', 'familyname', 'surname'];
        foreach ($aliases as $alias) {
            $vcard = CsvToVCard::convert([
                'first_name' => 'Jane',
                $alias => 'Smith',
            ]);
            $this->assertStringContainsString('FN:Jane Smith', $vcard, "Alias '$alias' failed");
        }
    }

    /** @test */
    public function it_uses_unknown_as_name_when_both_names_missing()
    {
        $vcard = CsvToVCard::convert([
            'email' => 'test@example.com',
        ]);

        $this->assertStringContainsString('FN:Unknown', $vcard);
    }

    /** @test */
    public function it_converts_email()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertStringContainsString('EMAIL:john@example.com', $vcard);
    }

    /** @test */
    public function it_converts_email_with_aliases()
    {
        $aliases = ['email', 'email_address', 'emailaddress'];
        foreach ($aliases as $alias) {
            $vcard = CsvToVCard::convert([
                'first_name' => 'John',
                $alias => 'john@example.com',
            ]);
            $this->assertStringContainsString('EMAIL:john@example.com', $vcard, "Alias '$alias' failed");
        }
    }

    /** @test */
    public function it_converts_phone()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'phone' => '+1234567890',
        ]);

        $this->assertStringContainsString('TEL:+1234567890', $vcard);
    }

    /** @test */
    public function it_converts_phone_with_aliases()
    {
        $aliases = ['phone', 'telephone', 'phone_number', 'phonenumber', 'tel'];
        foreach ($aliases as $alias) {
            $vcard = CsvToVCard::convert([
                'first_name' => 'John',
                $alias => '+1234567890',
            ]);
            $this->assertStringContainsString('TEL:+1234567890', $vcard, "Alias '$alias' failed");
        }
    }

    /** @test */
    public function it_converts_full_address()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'street' => '123 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
            'country' => 'USA',
        ]);

        $this->assertStringContainsString('ADR:;;123 Main St;Springfield;IL;62701;USA', $vcard);
    }

    /** @test */
    public function it_converts_partial_address()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'city' => 'Springfield',
            'country' => 'USA',
        ]);

        $this->assertStringContainsString('ADR:;;;Springfield;;;USA', $vcard);
    }

    /** @test */
    public function it_skips_address_when_all_fields_empty()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
        ]);

        $this->assertStringNotContainsString('ADR:', $vcard);
    }

    /** @test */
    public function it_converts_birthday()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'birthday' => '1990-05-15',
        ]);

        $this->assertStringContainsString('BDAY:1990-05-15', $vcard);
    }

    /** @test */
    public function it_converts_birthday_with_aliases()
    {
        $aliases = ['birthday', 'birth_date', 'birthdate', 'dob', 'date_of_birth'];
        foreach ($aliases as $alias) {
            $vcard = CsvToVCard::convert([
                'first_name' => 'John',
                $alias => '1990-05-15',
            ]);
            $this->assertStringContainsString('BDAY:1990-05-15', $vcard, "Alias '$alias' failed");
        }
    }

    /** @test */
    public function it_handles_various_date_formats()
    {
        $dates = [
            '1990-05-15' => '1990-05-15',
            '05/15/1990' => '1990-05-15',
            '15 May 1990' => '1990-05-15',
            'May 15, 1990' => '1990-05-15',
        ];

        foreach ($dates as $input => $expected) {
            $vcard = CsvToVCard::convert([
                'first_name' => 'John',
                'birthday' => $input,
            ]);
            $this->assertStringContainsString("BDAY:$expected", $vcard, "Date '$input' failed");
        }
    }

    /** @test */
    public function it_converts_company()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'company' => 'Acme Inc',
        ]);

        $this->assertStringContainsString('ORG:Acme Inc', $vcard);
    }

    /** @test */
    public function it_converts_company_with_aliases()
    {
        $aliases = ['company', 'organization', 'org'];
        foreach ($aliases as $alias) {
            $vcard = CsvToVCard::convert([
                'first_name' => 'John',
                $alias => 'Acme Inc',
            ]);
            $this->assertStringContainsString('ORG:Acme Inc', $vcard, "Alias '$alias' failed");
        }
    }

    /** @test */
    public function it_converts_job_title()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'job_title' => 'Engineer',
        ]);

        $this->assertStringContainsString('TITLE:Engineer', $vcard);
    }

    /** @test */
    public function it_converts_notes()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'notes' => 'Some notes here',
        ]);

        $this->assertStringContainsString('NOTE:Some notes here', $vcard);
    }

    /** @test */
    public function it_is_case_insensitive()
    {
        $vcard = CsvToVCard::convert([
            'FIRST_NAME' => 'John',
            'LAST_NAME' => 'Doe',
            'EMAIL' => 'john@example.com',
        ]);

        $this->assertStringContainsString('FN:John Doe', $vcard);
        $this->assertStringContainsString('EMAIL:john@example.com', $vcard);
    }

    /** @test */
    public function it_ignores_unknown_columns()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'unknown_column' => 'whatever',
            'another_random' => 'data',
        ]);

        $this->assertStringContainsString('FN:John', $vcard);
    }

    /** @test */
    public function it_trims_whitespace_from_values()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => '  John  ',
            'email' => '  john@example.com  ',
        ]);

        $this->assertStringContainsString('FN:John', $vcard);
        $this->assertStringContainsString('EMAIL:john@example.com', $vcard);
    }

    /** @test */
    public function it_validates_row_with_name()
    {
        $errors = CsvToVCard::validate([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_validates_row_with_only_first_name()
    {
        $errors = CsvToVCard::validate([
            'first_name' => 'John',
        ]);

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_validates_row_with_only_last_name()
    {
        $errors = CsvToVCard::validate([
            'last_name' => 'Doe',
        ]);

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_fails_validation_when_name_missing()
    {
        $errors = CsvToVCard::validate([
            'email' => 'test@example.com',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Missing name', $errors[0]);
    }

    /** @test */
    public function it_fails_validation_with_invalid_email()
    {
        $errors = CsvToVCard::validate([
            'first_name' => 'John',
            'email' => 'not-an-email',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid email', $errors[0]);
    }

    /** @test */
    public function it_fails_validation_with_invalid_phone()
    {
        $errors = CsvToVCard::validate([
            'first_name' => 'John',
            'phone' => '12',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid phone', $errors[0]);
    }

    /** @test */
    public function it_passes_validation_with_valid_phone()
    {
        $errors = CsvToVCard::validate([
            'first_name' => 'John',
            'phone' => '+1 (555) 123-4567',
        ]);

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_passes_validation_with_valid_email()
    {
        $errors = CsvToVCard::validate([
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_returns_multiple_validation_errors()
    {
        $errors = CsvToVCard::validate([
            'email' => 'not-an-email',
            'phone' => '12',
        ]);

        $this->assertCount(3, $errors);
    }

    /** @test */
    public function it_converts_middle_name()
    {
        $vcard = CsvToVCard::convert([
            'first_name' => 'John',
            'middle_name' => 'Michael',
            'last_name' => 'Doe',
        ]);

        $this->assertStringContainsString('N:Doe;John;Michael;;', $vcard);
    }

    /** @test */
    public function it_handles_empty_row()
    {
        $vcard = CsvToVCard::convert([]);

        $this->assertStringContainsString('FN:Unknown', $vcard);
    }

    /** @test */
    public function it_validates_empty_row()
    {
        $errors = CsvToVCard::validate([]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Missing name', $errors[0]);
    }
}
