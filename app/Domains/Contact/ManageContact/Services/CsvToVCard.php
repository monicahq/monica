<?php

namespace App\Domains\Contact\ManageContact\Services;

class CsvToVCard
{
    public static function convert(array $row): string
    {
        $row = array_change_key_case($row, CASE_LOWER);

        $first = static::pick($row, ['first_name', 'firstname', 'given_name', 'givenname']);
        $last = static::pick($row, ['last_name', 'lastname', 'family_name', 'familyname', 'surname']);
        $middle = static::pick($row, ['middle_name', 'middlename', 'middle']);

        $name = ($first ?: '').' '.($last ?: '');
        $name = trim($name) ?: 'Unknown';

        $vcard = "BEGIN:VCARD\nVERSION:3.0\n";
        $vcard .= "FN:$name\n";
        $vcard .= 'N:'.($last ?: '').';'.($first ?: '').';'.($middle ?: '').";;\n";

        $email = static::pick($row, ['email', 'email_address', 'emailaddress']);
        if ($email) {
            $vcard .= "EMAIL:$email\n";
        }

        $phone = static::pick($row, ['phone', 'telephone', 'phone_number', 'phonenumber', 'tel']);
        if ($phone) {
            $vcard .= "TEL:$phone\n";
        }

        $parts = [];
        $parts[] = static::pick($row, ['street', 'address', 'address1', 'address_line_1']) ?: '';
        $parts[] = static::pick($row, ['city']) ?: '';
        $parts[] = static::pick($row, ['state', 'province']) ?: '';
        $parts[] = static::pick($row, ['zip', 'postal_code', 'postalcode', 'postcode']) ?: '';
        $parts[] = static::pick($row, ['country']) ?: '';

        if (implode('', $parts) !== '') {
            for ($i = count($parts) - 1; $i >= 0; $i--) {
                if ($parts[$i] === '' && $i > 0) {
                    unset($parts[$i]);
                } else {
                    break;
                }
            }
            $vcard .= 'ADR:;;'.implode(';', $parts)."\n";
        }

        $birthday = static::pick($row, ['birthday', 'birth_date', 'birthdate', 'dob', 'date_of_birth']);
        if ($birthday) {
            $date = date_parse($birthday);
            if ($date['error_count'] === 0 && $date['year']) {
                $y = $date['year'];
                $m = str_pad($date['month'] ?: 1, 2, '0', STR_PAD_LEFT);
                $d = str_pad($date['day'] ?: 1, 2, '0', STR_PAD_LEFT);
                $vcard .= "BDAY:$y-$m-$d\n";
            }
        }

        $company = static::pick($row, ['company', 'organization', 'org']);
        if ($company) {
            $vcard .= "ORG:$company\n";
        }

        $title = static::pick($row, ['job_title', 'jobtitle', 'title']);
        if ($title) {
            $vcard .= "TITLE:$title\n";
        }

        $notes = static::pick($row, ['notes', 'note']);
        if ($notes) {
            $vcard .= "NOTE:$notes\n";
        }

        $vcard .= 'END:VCARD';

        return $vcard;
    }

    public static function validate(array $row): array
    {
        $row = array_change_key_case($row, CASE_LOWER);
        $errors = [];

        $first = static::pick($row, ['first_name', 'firstname', 'given_name', 'givenname']);
        $last = static::pick($row, ['last_name', 'lastname', 'family_name', 'familyname', 'surname']);

        if (! $first && ! $last) {
            $errors[] = 'Missing name: at least one of first_name or last_name is required.';
        }

        $email = static::pick($row, ['email', 'email_address', 'emailaddress']);
        if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format: $email";
        }

        $phone = static::pick($row, ['phone', 'telephone', 'phone_number', 'phonenumber', 'tel']);
        if ($phone) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($digits) < 5) {
                $errors[] = "Invalid phone number: $phone";
            }
        }

        return $errors;
    }

    private static function pick(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                return trim((string) $row[$key]);
            }
        }

        return null;
    }
}
