<?php

/**
 * ⚠️ Editing not allowed except for 'en' language.
 *
 * @see https://github.com/monicahq/monica/blob/main/docs/contribute/translate.md for translations.
 */

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

    'accepted' => ':attribute måste accepteras.',
    'active_url' => ':attribute är inte en giltig URL.',
    'after' => ':attribute måste vara ett datum efter :date.',
    'after_or_equal' => ':attribute måste vara ett datum efter eller lika med :date.',
    'alpha' => ':attribute får endast innehålla bokstäver.',
    'alpha_dash' => ':attribute får endast innehålla bokstäver, siffror, bindestreck och understreck.',
    'alpha_num' => ':attribute får endast innehålla bokstäver och siffror.',
    'array' => ':attribute måste vara en lista med värden.',
    'before' => ':attribute måste vara ett datum före :date.',
    'before_or_equal' => ':attribute måste vara ett datum före eller lika med :date.',
    'between' => [
        'numeric' => ':attribute måste vara mellan :min och :max.',
        'file' => ':attribute måste vara mellan :min och :max kilobyte.',
        'string' => ':attribute måste vara mellan :min och :max tecken.',
        'array' => ':attribute måste vara mellan :min och :max föremål.',
    ],
    'boolean' => ':attribute måste vara sant eller falskt.',
    'confirmed' => ':attribute bekräftelsen matchar inte.',
    'date' => ':attribute är inte ett giltigt datum.',
    'date_equals' => ':attribute måste vara ett datum efter :date.',
    'date_format' => ':attribute matchar inte formatet :format.',
    'different' => ':attribute och :other måste vara olika.',
    'digits' => ':attributet måste vara :digits siffror.',
    'digits_between' => ':attribute måste vara mellan :min och :max siffror.',
    'dimensions' => ':attribute har ogiltiga bilddimensioner.',
    'distinct' => 'Fältet :attribute har ett dubbelt värde.',
    'email' => ':attribute måste vara en giltig e-postadress.',
    'ends_with' => ':attribute måste sluta med något av följande: :values.',
    'exists' => 'Valt värde för :attribute är ogiltigt.',
    'file' => ':attribute måste vara en fil.',
    'filled' => ':attribute fältet måste ha ett värde.',
    'gt' => [
        'numeric' => ':attribute måste vara större än :value.',
        'file' => ':attribute måste vara större än :value kilobytes.',
        'string' => ':attribute måste vara större än :value tecken.',
        'array' => ':attribute måste ha mer än :value objekt.',
    ],
    'gte' => [
        'numeric' => ':attribute måste vara större än eller lika :value.',
        'file' => ':attribute måste vara större än eller lika med :value kilobytes.',
        'string' => ':attribute måste vara större än eller lika med :value tecken.',
        'array' => ':attribute måste ha :value objekt eller mer.',
    ],
    'image' => ':attribute måste vara en bild.',
    'in' => 'Valt värde för :attribute är ogiltigt.',
    'in_array' => ':attribute fältet existerar inte i :other.',
    'integer' => ':attribute måste vara ett heltal.',
    'ip' => ':attribute måste vara en giltig IP-adress.',
    'ipv4' => ':attribute måste vara en giltig IPv4-adress.',
    'ipv6' => ':attribute måste vara en giltig IPv6-adress.',
    'json' => ':attribute måste vara en giltig JSON-sträng.',
    'lt' => [
        'numeric' => ':attribute måste vara mindre än :value.',
        'file' => ':attribute måste vara mindre än :value kilobytes.',
        'string' => ':attribute måste vara mindre än :value tecken.',
        'array' => ':attribute måste ha mindre än :value objekt.',
    ],
    'lte' => [
        'numeric' => ':attribute måste vara mindre än eller lika :value.',
        'file' => ':attribute måste vara mindre än eller lika med :value kilobytes.',
        'string' => ':attribute måste vara mindre än eller lika med :value tecken.',
        'array' => ':attribute får inte ha mer än :value objekt.',
    ],
    'max' => [
        'numeric' => ':attribute får inte vara större än :max.',
        'file' => ':attribute får inte vara större än :max kilobyte.',
        'string' => ':attribute får inte vara större än :max tecken.',
        'array' => ':attribute får inte ha mer än :max objekt.',
    ],
    'mimes' => ':attribute måste vara en fil av typ: :values.',
    'mimetypes' => ':attribute måste vara en fil av typ: :values.',
    'min' => [
        'numeric' => ':attribute måste vara minst :min.',
        'file' => ':attribute måste vara minst :min kilobyte.',
        'string' => ':attribute måste innehålla minst :min tecken.',
        'array' => ':attribute måste innehålla minst :min objekt.',
    ],
    'not_in' => 'Det valda :attribute är ogiltigt.',
    'not_regex' => ':attribute format är ogiltigt.',
    'numeric' => ':attribute måste vara ett tal.',
    'password' => 'Lösenordet är felaktigt.',
    'present' => 'Fältet :attribute måste vara närvarande.',
    'regex' => ':attribute format är ogiltigt.',
    'required' => 'Fältet :attribute är obligatoriskt.',
    'required_if' => 'Fältet :attribute är obligatoriskt när :other är :value.',
    'required_unless' => ':attribute är obligatoriskt om inte :other finns i :values.',
    'required_with' => ':attribute fältet är obligatoriskt när :values är angivet.',
    'required_with_all' => 'Fältet :attribute är obligatoriskt när :values är presenterade.',
    'required_without' => 'Fältet :attribute är obligatoriskt när :values inte visas.',
    'required_without_all' => ':attribute är obligatirskt när ingen av :values finns.',
    'same' => ':attribute och :other måste matcha.',
    'size' => [
        'numeric' => ':attribute måste vara :size.',
        'file' => ':attribute måste vara :size kilobyte.',
        'string' => ':attribute måste vara :size tecken.',
        'array' => ':attribute måste innehålla :size objekt.',
    ],
    'starts_with' => ':attribute måste börja med något av följande: :values.',
    'string' => ':attribute måste vara en sträng.',
    'timezone' => ':attribute måste vara en giltig zon.',
    'unique' => ':attribute har redan tagits.',
    'uploaded' => ':attribute kunde inte laddas upp.',
    'url' => ':attribute format är ogiltigt.',
    'uuid' => ':attribute måste vara ett giltigt UUID.',

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

    'vue' => [
        'max' => [
            'numeric' => '{field} may not be greater than {max}.',
            'string' => '{field} may not be greater than {max} characters.',
        ],
        'required' => '{field} is required.',
        'url' => '{field} is not a valid URL.',
    ],

];
