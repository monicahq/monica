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

    'accepted'             => 'Polje :attribute mora biti prihvaćeno.',
    'active_url'           => 'Polje :attribute nije ispravan URL.',
    'after'                => 'Polje :attribute mora biti datum nakon :date.',
    'after_or_equal'       => 'The :attribute must be a date after or equal to :date.',
    'alpha'                => 'Polje :attribute smije sadržavati samo slova.',
    'alpha_dash'           => 'Polje :attribute smije sadržavati samo slova, brojeve i crtice.',
    'alpha_num'            => 'Polje :attribute smije sadržavati samo slova i brojeve.',
    'array'                => 'Polje :attribute mora biti niz.',
    'before'               => 'Polje :attribute mora biti datum prije :date.',
    'before_or_equal'      => 'The :attribute must be a date before or equal to :date.',
    'between'              => [
        'numeric' => 'Polje :attribute mora biti između :min - :max.',
        'file'    => 'Polje :attribute mora biti između :min - :max kilobajta.',
        'string'  => 'Polje :attribute mora biti između :min - :max znakova.',
        'array'   => 'Polje :attribute mora imati između :min - :max stavki.',
    ],
    'boolean'              => 'Polje :attribute mora biti false ili true.',
    'confirmed'            => 'Potvrda polja :attribute se ne podudara.',
    'date'                 => 'Polje :attribute nije ispravan datum.',
    'date_format'          => 'Polje :attribute ne podudara s formatom :format.',
    'different'            => 'Polja :attribute i :other moraju biti različita.',
    'digits'               => 'Polje :attribute mora sadržavati :digits znamenki.',
    'digits_between'       => 'Polje :attribute mora imati između :min i :max znamenki.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => 'The :attribute field has a duplicate value.',
    'email'                => 'Polje :attribute mora biti ispravna e-mail adresa.',
    'exists'               => 'Odabrano polje :attribute nije ispravno.',
    'file'                 => 'The :attribute must be a file.',
    'filled'               => 'The :attribute field is required.',
    'image'                => 'Polje :attribute mora biti slika.',
    'in'                   => 'Odabrano polje :attribute nije ispravno.',
    'in_array'             => 'The :attribute field does not exist in :other.',
    'integer'              => 'Polje :attribute mora biti broj.',
    'ip'                   => 'Polje :attribute mora biti ispravna IP adresa.',
    'ipv4'                 => 'The :attribute must be a valid IPv4 address.',
    'ipv6'                 => 'The :attribute must be a valid IPv6 address.',
    'json'                 => 'The :attribute must be a valid JSON string.',
    'max'                  => [
        'numeric' => 'Polje :attribute mora biti manje od :max.',
        'file'    => 'Polje :attribute mora biti manje od :max kilobajta.',
        'string'  => 'Polje :attribute mora sadržavati manje od :max znakova.',
        'array'   => 'Polje :attribute ne smije imati više od :max stavki.',
    ],
    'mimes'                => 'Polje :attribute mora biti datoteka tipa: :values.',
    'mimetypes'            => 'Polje :attribute mora biti datoteka tipa: :values.',
    'min'                  => [
        'numeric' => 'Polje :attribute mora biti najmanje :min.',
        'file'    => 'Polje :attribute mora biti najmanje :min kilobajta.',
        'string'  => 'Polje :attribute mora sadržavati najmanje :min znakova.',
        'array'   => 'Polje :attribute mora sadržavati najmanje :min stavki.',
    ],
    'not_in'               => 'Odabrano polje :attribute nije ispravno.',
    'not_regex'            => 'The :attribute format is invalid.',
    'numeric'              => 'Polje :attribute mora biti broj.',
    'present'              => 'The :attribute field must be present.',
    'regex'                => 'Polje :attribute se ne podudara s formatom.',
    'required'             => 'Polje :attribute je obavezno.',
    'required_if'          => 'Polje :attribute je obavezno kada polje :other sadrži :value.',
    'required_unless'      => 'The :attribute field is required unless :other is in :values.',
    'required_with'        => 'Polje :attribute je obavezno kada postoji polje :values.',
    'required_with_all'    => 'Polje :attribute je obavezno kada postje polja :values.',
    'required_without'     => 'Polje :attribute je obavezno kada ne postoji polje :values.',
    'required_without_all' => 'Polje :attribute je obavezno kada nijedno od polja :values ne postoji.',
    'same'                 => 'Polja :attribute i :other se moraju podudarati.',
    'size'                 => [
        'numeric' => 'Polje :attribute mora biti :size.',
        'file'    => 'Polje :attribute mora biti :size kilobajta.',
        'string'  => 'Polje :attribute mora biti :size znakova.',
        'array'   => 'Polje :attribute mora sadržavati :size stavki.',
    ],
    'string'               => 'The :attribute must be a string.',
    'timezone'             => 'Polje :attribute mora biti ispravna vremenska zona.',
    'unique'               => 'Polje :attribute već postoji.',
    'uploaded'             => 'The :attribute failed to upload.',
    'url'                  => 'Polje :attribute nije ispravnog formata.',

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
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
