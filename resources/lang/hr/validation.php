<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Polje :attribute mora biti prihvaćeno.',
    'active_url' => 'Polje :attribute nije ispravan URL.',
    'after' => 'Polje :attribute mora biti datum nakon :date.',
    'after_or_equal' => 'Polje :attribute mora biti datum veći ili jednak :date.',
    'alpha' => 'Polje :attribute smije sadržavati samo slova.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'Polje :attribute smije sadržavati samo slova i brojeve.',
    'array' => 'Polje :attribute mora biti niz.',
    'before' => 'Polje :attribute mora biti datum prije :date.',
    'before_or_equal' => 'Polje :attribute mora biti datum manji ili jednak :date.',
    'between' => [
        'numeric' => 'Polje :attribute mora biti između :min - :max.',
        'file' => 'Polje :attribute mora biti između :min - :max kilobajta.',
        'string' => 'Polje :attribute mora biti između :min - :max znakova.',
        'array' => 'Polje :attribute mora imati između :min - :max stavki.',
    ],
    'boolean' => 'Polje :attribute mora biti false ili true.',
    'confirmed' => 'Potvrda polja :attribute se ne podudara.',
    'date' => 'Polje :attribute nije ispravan datum.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'Polje :attribute ne podudara s formatom :format.',
    'different' => 'Polja :attribute i :other moraju biti različita.',
    'digits' => 'Polje :attribute mora sadržavati :digits znamenki.',
    'digits_between' => 'Polje :attribute mora imati između :min i :max znamenki.',
    'dimensions' => 'Polje :attribute ima neispravne dimenzije slike.',
    'distinct' => 'Polje :attribute ima dupliciranu vrijednost.',
    'email' => 'Polje :attribute mora biti ispravna e-mail adresa.',
    'ends_with' => 'The :attribute must end with one of the following: :values.',
    'exists' => 'Odabrano polje :attribute nije ispravno.',
    'file' => 'Polje :attribute mora biti datoteka.',
    'filled' => 'The :attribute field is required.',
    'gt' => [
        'numeric' => 'The :attribute must be greater than :value.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'string' => 'The :attribute must be greater than :value characters.',
        'array' => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal :value.',
        'file' => 'The :attribute must be greater than or equal :value kilobytes.',
        'string' => 'The :attribute must be greater than or equal :value characters.',
        'array' => 'The :attribute must have :value items or more.',
    ],
    'image' => 'Polje :attribute mora biti slika.',
    'in' => 'Odabrano polje :attribute nije ispravno.',
    'in_array' => 'Polje :attribute ne postoji u :other.',
    'integer' => 'Polje :attribute mora biti broj.',
    'ip' => 'Polje :attribute mora biti ispravna IP adresa.',
    'ipv4' => 'Polje :attribute mora biti ispravna IPv4 adresa.',
    'ipv6' => 'Polje :attribute mora biti ispravna IPv6 adresa.',
    'json' => 'Polje :attribute mora biti ispravan JSON string.',
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute must be less than or equal :value.',
        'file' => 'The :attribute must be less than or equal :value kilobytes.',
        'string' => 'The :attribute must be less than or equal :value characters.',
        'array' => 'The :attribute must not have more than :value items.',
    ],
    'max' => [
        'numeric' => 'Polje :attribute mora biti manje od :max.',
        'file' => 'Polje :attribute mora biti manje od :max kilobajta.',
        'string' => 'Polje :attribute mora sadržavati manje od :max znakova.',
        'array' => 'Polje :attribute ne smije imati više od :max stavki.',
    ],
    'mimes' => 'Polje :attribute mora biti datoteka tipa: :values.',
    'mimetypes' => 'Polje :attribute mora biti datoteka tipa: :values.',
    'min' => [
        'numeric' => 'Polje :attribute mora biti najmanje :min.',
        'file' => 'Polje :attribute mora biti najmanje :min kilobajta.',
        'string' => 'Polje :attribute mora sadržavati najmanje :min znakova.',
        'array' => 'Polje :attribute mora sadržavati najmanje :min stavki.',
    ],
    'not_in' => 'Odabrano polje :attribute nije ispravno.',
    'not_regex' => 'Format polja :attribute je neispravan.',
    'numeric' => 'Polje :attribute mora biti broj.',
    'password' => 'The password is incorrect.',
    'present' => 'Polje :attribute mora biti prisutno.',
    'regex' => 'Polje :attribute se ne podudara s formatom.',
    'required' => 'Polje :attribute je obavezno.',
    'required_if' => 'Polje :attribute je obavezno kada polje :other sadrži :value.',
    'required_unless' => 'Polje :attribute je obavezno osim :other je u :values.',
    'required_with' => 'Polje :attribute je obavezno kada postoji polje :values.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'Polje :attribute je obavezno kada ne postoji polje :values.',
    'required_without_all' => 'Polje :attribute je obavezno kada nijedno od polja :values ne postoji.',
    'same' => 'Polja :attribute i :other se moraju podudarati.',
    'size' => [
        'numeric' => 'Polje :attribute mora biti :size.',
        'file' => 'Polje :attribute mora biti :size kilobajta.',
        'string' => 'Polje :attribute mora biti :size znakova.',
        'array' => 'Polje :attribute mora sadržavati :size stavki.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values.',
    'string' => 'Polje :attribute mora biti string.',
    'timezone' => 'Polje :attribute mora biti ispravna vremenska zona.',
    'unique' => 'Polje :attribute već postoji.',
    'uploaded' => 'Polje :attribute nije uspešno učitano.',
    'url' => 'Polje :attribute nije ispravnog formata.',
    'uuid' => 'The :attribute must be a valid UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
