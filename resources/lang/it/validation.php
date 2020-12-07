<?php

/**
 * ⚠️ Edition not allowed except for 'en' language.
 *
 * @see https://github.com/monicahq/monica/blob/master/docs/contribute/translate.md for translations.
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

    'accepted' => ':attribute deve essere accettato.',
    'active_url' => ':attribute non è un URL valido.',
    'after' => ':attribute deve essere una data successiva al :date.',
    'after_or_equal' => ':attribute deve essere una data successiva o uguale al :date.',
    'alpha' => ':attribute può contenere solo lettere.',
    'alpha_dash' => ':attribute può contenere solo lettere, numeri e trattini.',
    'alpha_num' => ':attribute può contenere solo lettere e numeri.',
    'array' => ':attribute deve essere un array.',
    'before' => ':attribute deve essere una data precedente al :date.',
    'before_or_equal' => ':attribute deve essere una data precedente o uguale al :date.',
    'between' => [
        'numeric' => ':attribute deve trovarsi tra :min - :max.',
        'file' => ':attribute deve trovarsi tra :min - :max kilobyte.',
        'string' => ':attribute deve trovarsi tra :min - :max caratteri.',
        'array' => ':attribute deve avere tra :min - :max elementi.',
    ],
    'boolean' => 'Il campo :attribute deve essere vero o falso.',
    'confirmed' => 'Il campo di conferma per :attribute non coincide.',
    'date' => ':attribute non è una data valida.',
    'date_equals' => ':attribute deve essere una data e uguale a :date.',
    'date_format' => ':attribute non coincide con il formato :format.',
    'different' => ':attribute e :other devono essere differenti.',
    'digits' => ':attribute deve essere di :digits cifre.',
    'digits_between' => ':attribute deve essere tra :min e :max cifre.',
    'dimensions' => 'Le dimensioni dell\'immagine di :attribute non sono valide.',
    'distinct' => ':attribute contiene un valore duplicato.',
    'email' => ':attribute non è valido.',
    'ends_with' => ':attribute deve finire con uno dei seguenti valori: :values',
    'exists' => ':attribute selezionato non è valido.',
    'file' => ':attribute deve essere un file.',
    'filled' => 'Il campo :attribute deve contenere un valore.',
    'gt' => [
        'numeric' => ':attribute deve essere maggiore di :value.',
        'file' => ':attribute deve essere maggiore di :value kilobyte.',
        'string' => ':attribute deve contenere più di :value caratteri.',
        'array' => ':attribute deve contenere più di :value elementi.',
    ],
    'gte' => [
        'numeric' => ':attribute deve essere uguale o maggiore di :value.',
        'file' => ':attribute deve essere uguale o maggiore di :value kilobyte.',
        'string' => ':attribute deve contenere un numero di caratteri uguale o maggiore di :value.',
        'array' => ':attribute deve contenere un numero di elementi uguale o maggiore di :value.',
    ],
    'image' => ':attribute deve essere un\'immagine.',
    'in' => ':attribute selezionato non è valido.',
    'in_array' => 'Il valore del campo :attribute non esiste in :other.',
    'integer' => ':attribute deve essere un numero intero.',
    'ip' => ':attribute deve essere un indirizzo IP valido.',
    'ipv4' => ':attribute deve essere un indirizzo IPv4 valido.',
    'ipv6' => ':attribute deve essere un indirizzo IPv6 valido.',
    'json' => ':attribute deve essere una stringa JSON valida.',
    'lt' => [
        'numeric' => ':attribute deve essere minore di :value.',
        'file' => ':attribute deve essere minore di :value kilobyte.',
        'string' => ':attribute deve contenere meno di :value caratteri.',
        'array' => ':attribute deve contenere meno di :value elementi.',
    ],
    'lte' => [
        'numeric' => ':attribute deve essere minore o uguale a :value.',
        'file' => ':attribute deve essere minore o uguale a :value kilobyte.',
        'string' => ':attribute deve contenere un numero di caratteri minore o uguale a :value.',
        'array' => ':attribute deve contenere un numero di elementi minore o uguale a :value.',
    ],
    'max' => [
        'numeric' => ':attribute non può essere superiore a :max.',
        'file' => ':attribute non può essere superiore a :max kilobyte.',
        'string' => ':attribute non può contenere più di :max caratteri.',
        'array' => ':attribute non può avere più di :max elementi.',
    ],
    'mimes' => ':attribute deve essere del tipo: :values.',
    'mimetypes' => ':attribute deve essere del tipo: :values.',
    'min' => [
        'numeric' => ':attribute deve essere almeno :min.',
        'file' => ':attribute deve essere almeno di :min kilobyte.',
        'string' => ':attribute deve contenere almeno :min caratteri.',
        'array' => ':attribute deve avere almeno :min elementi.',
    ],
    'not_in' => 'Il valore selezionato per :attribute non è valido.',
    'not_regex' => 'Il formato di :attribute non è valido.',
    'numeric' => ':attribute deve essere un numero.',
    'password' => 'La password non è corretta.',
    'present' => 'Il campo :attribute deve essere presente.',
    'regex' => 'Il formato del campo :attribute non è valido.',
    'required' => 'Il campo :attribute è richiesto.',
    'required_if' => 'Il campo :attribute è richiesto quando :other è :value.',
    'required_unless' => 'Il campo :attribute è richiesto a meno che :other sia in :values.',
    'required_with' => 'Il campo :attribute è richiesto quando :values è presente.',
    'required_with_all' => 'Il campo :attribute è richiesto quando :values sono presenti.',
    'required_without' => 'Il campo :attribute è richiesto quando :values non è presente.',
    'required_without_all' => 'Il campo :attribute è richiesto quando nessuno di :values è presente.',
    'same' => ':attribute e :other devono coincidere.',
    'size' => [
        'numeric' => ':attribute deve essere :size.',
        'file' => ':attribute deve essere :size kilobyte.',
        'string' => ':attribute deve contenere :size caratteri.',
        'array' => ':attribute deve contenere :size elementi.',
    ],
    'starts_with' => ':attribute deve iniziare con uno dei seguenti: :values',
    'string' => ':attribute deve essere una stringa.',
    'timezone' => ':attribute deve essere una zona valida.',
    'unique' => ':attribute è stato già utilizzato.',
    'uploaded' => ':attribute non è stato caricato.',
    'url' => 'Il formato del campo :attribute non è valido.',
    'uuid' => ':attribute deve essere un UUID valido.',

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
