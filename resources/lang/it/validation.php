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

    'accepted'             => ':attribute dev\'essere accettato.',
    'active_url'           => ':attribute non è una URL valida.',
    'after'                => ':attribute dev\'essere dopo il :date.',
    'after_or_equal'       => 'The :attribute must be a date after or equal to :date.',
    'alpha'                => ':attribute può contenere solamente lettere.',
    'alpha_dash'           => ':attribute può solo contenere lettere, numeri e trattini.',
    'alpha_num'            => ':attribute può solo contenere lettere e numeri.',
    'array'                => ':attribute dev\'essere un array.',
    'before'               => ':attribute dev\'essere prima del :date.',
    'before_or_equal'      => 'The :attribute must be a date before or equal to :date.',
    'between'              => [
        'numeric' => ':attribute dev\'essere tra :min e :max.',
        'file'    => ':attribute dev\'essere tra :min e :max kilobyte.',
        'string'  => ':attribute dev\'essere tra :min e :max caratteri.',
        'array'   => ':attribute deve avere tra :min e :max elementi.',
    ],
    'boolean'              => ':attribute dev\'essere vero o falso.',
    'confirmed'            => 'La conferma di :attribute non coincide.',
    'date'                 => ':attribute non è una data valida',
    'date_format'          => ':attribute non coincide con il formato :format.',
    'different'            => ':attribute e :other devono differire.',
    'digits'               => ':attribute dev\'essere di :digits cifre.',
    'digits_between'       => ':attribute dev\'essere tra :min e :max cifre.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => ':attribute ha un valore duplicato.',
    'email'                => ':attribute dev\'essere un\'email valida.',
    'exists'               => ':attribute selezionato invalido.',
    'file'                 => 'The :attribute must be a file.',
    'filled'               => 'The :attribute field must have a value.',
    'image'                => ':attribute dev\'essere un\'immagine.',
    'in'                   => 'selezione per :attribute non valida.',
    'in_array'             => ':attribute non esiste in :other.',
    'integer'              => ':attribute dev\'essere un intero.',
    'ip'                   => ':attribute dev\'essere un indirizzo IP valido.',
    'ipv4'                 => 'The :attribute must be a valid IPv4 address.',
    'ipv6'                 => 'The :attribute must be a valid IPv6 address.',
    'json'                 => ':attribute dev\'essere una sequenza JSON valida.',
    'max'                  => [
        'numeric' => ':attribute non può essere maggiore di :max.',
        'file'    => ':attribute non può pesare più di :max kilobyte.',
        'string'  => ':attribute non può avere più di :max caratteri.',
        'array'   => ':attribute non può avere più di :max elementi.',
    ],
    'mimes'                => ':attribute dev\'essere un file di tipo: :values.',
    'mimetypes'            => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => ':attribute dev\'essere almeno :min.',
        'file'    => ':attribute deve pesare almeno :min.',
        'string'  => ':attribute deve avere almeno :min caratteri.',
        'array'   => ':attribute deve avere almento :min elementi.',
    ],
    'not_in'               => 'selezione per :attribute non valida.',
    'not_regex'            => 'The :attribute format is invalid.',
    'numeric'              => ':attribute dev\'essere un numero.',
    'present'              => ':attribute dev\'essere presente.',
    'regex'                => 'formato di :attribute non valido.',
    'required'             => ':attribute non è facoltativo.',
    'required_if'          => ':attribute non è facoltativo se :other è :value.',
    'required_unless'      => ':attribute non è facoltativo a meno che :other non sia :values.',
    'required_with'        => ':attribute non è facoltativo se :values è presente.',
    'required_with_all'    => ':attribute non è facoltativo se :values è presente.',
    'required_without'     => ':attribute non è facoltativo se :values non è presente.',
    'required_without_all' => ':attribute non è facoltativo se nessuno dei seguenti valori è presente: :values.',
    'same'                 => ':attribute e :other devono coincidere.',
    'size'                 => [
        'numeric' => ':attribute dev\'essere :size.',
        'file'    => ':attribute deve pesare :size kilobyte.',
        'string'  => ':attribute dev\'essere lungo :size caratteri.',
        'array'   => ':attribute deve contenere :size elementi.',
    ],
    'string'               => ':attribute dev\'essere una sequenza.',
    'timezone'             => ':attribute dev\'essere un fuso orario valido.',
    'unique'               => ':attribute è già stato riservato.',
    'uploaded'             => 'The :attribute failed to upload.',
    'url'                  => 'formato di :attribute non valido.',

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
