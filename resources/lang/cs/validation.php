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

    'accepted' => ':attribute musí být přijat.',
    'active_url' => ':attribute není platnou URL adresou.',
    'after' => ':attribute musí být datum po :date.',
    'after_or_equal' => ':attribute musí být datum :date nebo pozdější.',
    'alpha' => ':attribute může obsahovat pouze písmena.',
    'alpha_dash' => ':attribute může obsahovat pouze písmena, číslice, pomlčky a podtržítka. České znaky (á, é, í, ó, ú, ů, ž, š, č, ř, ď, ť, ň) nejsou podporovány.',
    'alpha_num' => ':attribute může obsahovat pouze písmena a číslice.',
    'array' => ':attribute musí být pole.',
    'before' => ':attribute musí být datum před :date.',
    'before_or_equal' => 'Datum :attribute musí být před nebo rovno :date.',
    'between' => [
        'numeric' => ':attribute musí být hodnota mezi :min a :max.',
        'file' => ':attribute musí být větší než :min a menší než :max Kilobytů.',
        'string' => ':attribute musí být delší než :min a kratší než :max znaků.',
        'array' => ':attribute musí obsahovat nejméně :min a nesmí obsahovat více než :max prvků.',
    ],
    'boolean' => ':attribute musí být true nebo false',
    'confirmed' => ':attribute nebylo odsouhlaseno.',
    'date' => ':attribute musí být platné datum.',
    'date_equals' => ':attribute musí být datum shodné s :date.',
    'date_format' => ':attribute není platný formát data podle :format.',
    'different' => ':attribute a :other se musí lišit.',
    'digits' => ':attribute musí být :digits pozic dlouhé.',
    'digits_between' => ':attribute musí být dlouhé nejméně :min a nejvíce :max pozic.',
    'dimensions' => ':attribute má neplatné rozměry.',
    'distinct' => ':attribute má duplicitní hodnotu.',
    'email' => ':attribute není platný formát.',
    'ends_with' => ':attribute musí končit jednou z následujících hodnot: :values',
    'exists' => 'Zvolená hodnota pro :attribute není platná.',
    'file' => ':attribute musí být soubor.',
    'filled' => ':attribute musí být vyplněno.',
    'gt' => [
        'numeric' => ':attribute musí být větší než :value.',
        'file' => 'Velikost souboru :attribute musí být větší než :value kB.',
        'string' => 'Počet znaků :attribute musí být větší :value.',
        'array' => 'Pole :attribute musí mít více prvků než :value.',
    ],
    'gte' => [
        'numeric' => ':attribute musí být větší nebo rovno :value.',
        'file' => 'Velikost souboru :attribute musí být větší nebo rovno :value kB.',
        'string' => 'Počet znaků :attribute musí být větší nebo rovno :value.',
        'array' => 'Pole :attribute musí mít :value prvků nebo více.',
    ],
    'image' => ':attribute musí být obrázek.',
    'in' => 'Zvolená hodnota pro :attribute je neplatná.',
    'in_array' => ':attribute není obsažen v :other.',
    'integer' => ':attribute musí být celé číslo.',
    'ip' => ':attribute musí být platnou IP adresou.',
    'ipv4' => ':attribute musí být platná IPv4 adresa.',
    'ipv6' => ':attribute musí být platná IPv6 adresa.',
    'json' => ':attribute musí být platný JSON řetězec.',
    'lt' => [
        'numeric' => ':attribute musí být menší než :value.',
        'file' => 'Velikost souboru :attribute musí být menší než :value kB.',
        'string' => ':attribute musí obsahovat méně než :value znaků.',
        'array' => ':attribute by měl obsahovat méně než :value položek.',
    ],
    'lte' => [
        'numeric' => ':attribute musí být menší nebo rovno než :value.',
        'file' => 'Velikost souboru :attribute musí být menší než :value kB.',
        'string' => ':attribute nesmí být delší než :value znaků.',
        'array' => ':attribute by měl obsahovat maximálně :value položek.',
    ],
    'max' => [
        'numeric' => ':attribute musí být nižší než :max.',
        'file' => ':attribute musí být menší než :max Kilobytů.',
        'string' => ':attribute musí být kratší než :max znaků.',
        'array' => ':attribute nesmí obsahovat více než :max prvků.',
    ],
    'mimes' => ':attribute musí být jeden z následujících datových typů :values.',
    'mimetypes' => ':attribute musí být jeden z následujících datových typů :values.',
    'min' => [
        'numeric' => ':attribute musí být větší než :min.',
        'file' => ':attribute musí být větší než :min Kilobytů.',
        'string' => ':attribute musí být delší než :min znaků.',
        'array' => ':attribute musí obsahovat více než :min prvků.',
    ],
    'not_in' => 'Zvolená hodnota pro :attribute je neplatná.',
    'not_regex' => ':attribute musí být regulární výraz.',
    'numeric' => ':attribute musí být číslo.',
    'password' => 'The password is incorrect.',
    'present' => ':attribute musí být vyplněno.',
    'regex' => ':attribute nemá správný formát.',
    'required' => ':attribute musí být vyplněno.',
    'required_if' => ':attribute musí být vyplněno pokud :other je :value.',
    'required_unless' => ':attribute musí být vyplněno dokud :other je v :values.',
    'required_with' => ':attribute musí být vyplněno pokud :values je vyplněno.',
    'required_with_all' => ':attribute musí být vyplněno pokud :values je zvoleno.',
    'required_without' => ':attribute musí být vyplněno pokud :values není vyplněno.',
    'required_without_all' => ':attribute musí být vyplněno pokud není žádné z :values zvoleno.',
    'same' => ':attribute a :other se musí shodovat.',
    'size' => [
        'numeric' => ':attribute musí být přesně :size.',
        'file' => ':attribute musí mít přesně :size Kilobytů.',
        'string' => ':attribute musí být přesně :size znaků dlouhý.',
        'array' => ':attribute musí obsahovat právě :size prvků.',
    ],
    'starts_with' => ':attribute musí začínat jednou z následujících hodnot: :values',
    'string' => ':attribute musí být řetězec znaků.',
    'timezone' => ':attribute musí být platná časová zóna.',
    'unique' => ':attribute musí být unikátní.',
    'uploaded' => 'Nahrávání :attribute se nezdařilo.',
    'url' => 'Formát :attribute je neplatný.',
    'uuid' => ':attribute musí být validní UUID.',

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
