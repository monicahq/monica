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

    'accepted'             => ':attribute musí být přijat.',
    'active_url'           => ':attribute není platná adresa URL.',
    'after'                => ':attribute musí být datum po :date.',
    'after_or_equal'       => 'The :attribute must be a date after or equal to :date.',
    'alpha'                => ':attribute smí obsahovat pouze písmena.',
    'alpha_dash'           => ':attribute smí obsahovat pouze písmena, číslice a pomlčky.',
    'alpha_num'            => ':attribute smí obsahovat pouze písmena a číslice.',
    'array'                => ':attribute musí být proměnná.',
    'before'               => ':attribute musí být datum před :date.',
    'before_or_equal'      => 'The :attribute must be a date before or equal to :date.',
    'between'              => [
        'numeric' => ':attribute musí být v rozmezí :min a :max.',
        'file'    => ':attribute musí být v rozmezí :min a :max kilobajtů.',
        'string'  => ':attribute musí být v rozmezí :min a :max znaků.',
        'array'   => ':attribute musí mít mezi :min a :max položek.',
    ],
    'boolean'              => 'Pole :attribute musí být true nebo false.',
    'confirmed'            => ':attribute ověření se neshoduje.',
    'date'                 => ':attribute není platné datum.',
    'date_format'          => ':attribute neodpovídá formátu :format.',
    'different'            => ':attribute a :other se nesmí shodovat.',
    'digits'               => ':attribute musí být :digits číslic.',
    'digits_between'       => ':attribute musí být v rozmezí :min a :max číslic.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => 'Pole :attribute obsahuje duplicitní hodnotu.',
    'email'                => ':attribute musí být platná emailová adresa.',
    'exists'               => 'Vybraný :attribute není platný.',
    'file'                 => 'The :attribute must be a file.',
    'filled'               => 'The :attribute field must have a value.',
    'image'                => ':attribute musí být obrázek.',
    'in'                   => 'Vybraný :attribute není platný.',
    'in_array'             => 'Pole :attribute neexistuje v :other.',
    'integer'              => ':attribute musí být celé číslo.',
    'ip'                   => ':attribute musí být platná IP adresa.',
    'ipv4'                 => 'The :attribute must be a valid IPv4 address.',
    'ipv6'                 => 'The :attribute must be a valid IPv6 address.',
    'json'                 => ':attribute musí být platný JSON řetězec.',
    'max'                  => [
        'numeric' => ':attribute nesmí být vyšší než :max.',
        'file'    => ':attribute nesmí být větší než :max kilobajtů.',
        'string'  => ':attribute nesmí být delší než :max znaků.',
        'array'   => ':attribute nesmí obsahovat více než :max položek.',
    ],
    'mimes'                => ':attribute musí být soubor typu: :values.',
    'mimetypes'            => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => ':attribute musí být alespoň :min.',
        'file'    => ':attribute musí být alespoň :min kilobajtů.',
        'string'  => ':attribute musí obsahovat alespoň :min znaků.',
        'array'   => ':attribute musí obsahovat alespoň :min položek.',
    ],
    'not_in'               => 'Vybraný :attribute není platný.',
    'not_regex'            => 'The :attribute format is invalid.',
    'numeric'              => ':attribute musí být číslice.',
    'present'              => ':attribute musí být dárek.',
    'regex'                => ':attribute formát není platný.',
    'required'             => 'Pole :attribute je vyžadováno.',
    'required_if'          => 'Pole :attribute je vyžadováno, když :other má hodnotu :value.',
    'required_unless'      => 'Pole :attribute je vyžadováno, pokud :other je v :values.',
    'required_with'        => 'Pole :attribute je vyžadováno, pokud :values je dárek.',
    'required_with_all'    => 'Pole :attribute je vyžadováno, pokud :values je dárek.',
    'required_without'     => 'Pole :attribute je vyžadováno, pokud :values není dárek.',
    'required_without_all' => 'Pole :attribute je vyžadováno, pokud není žádná z :values dárek.',
    'same'                 => ':attribute a :other se musí shodovat.',
    'size'                 => [
        'numeric' => ':attribute musí být :size.',
        'file'    => ':attribute musí být :size kilobajtů.',
        'string'  => ':attribute musí být :size znaků.',
        'array'   => ':attribute musí obsahovat :size položek.',
    ],
    'string'               => ':attribute musí být řetězec.',
    'timezone'             => ':attribute musí být platná zóna.',
    'unique'               => ':attribute je již použitý.',
    'uploaded'             => 'The :attribute failed to upload.',
    'url'                  => ':attribute formát není platný.',

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
