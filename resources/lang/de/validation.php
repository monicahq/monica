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

    'accepted' => ':attribute muss akzeptiert werden.',
    'active_url' => ':attribute keine gültige URL.',
    'after' => ':attribute muss ein Datum nach :date sein.',
    'after_or_equal' => 'Das :attribute muss ein Datum nach oder gleich :date sein.',
    'alpha' => ':attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => ':attribute darf nur aus Buchstaben, Zahlen, Binde- und Unterstrichen bestehen.',
    'alpha_num' => ':attribute darf nur Buchstaben und Nummern enthalten.',
    'array' => ':attribute muss ein Array sein.',
    'before' => ':attribute muss ein Datum vor :date sein.',
    'before_or_equal' => 'Das :attribute muss ein Datum vor oder gleich :date sein.',
    'between' => [
        'numeric' => ':attribute muss zwischen :min und :max liegen.',
        'file' => ':attribute muss zwischen :min und :max Kilobyte liegen.',
        'string' => ':attribute muss zwischen :min und :max Zeichen liegen.',
        'array' => ':attribute muss zwischen :min und :max Elemente haben.',
    ],
    'boolean' => 'Das :attribute Feld muss Wahr oder Falsch sein.',
    'confirmed' => 'Die :attribute Bestätigung stimmt nicht überein.',
    'date' => ':attribute ist kein gültiges Datum.',
    'date_equals' => ':attribute muss ein Datum gleich :date sein.',
    'date_format' => ':attribute stimmt nicht mit dem Format :format überein.',
    'different' => ':attribute und :other müssen sich unterscheiden.',
    'digits' => ':attribute müssen :digits Ziffern sein.',
    'digits_between' => ':attribute muss zwischen :min und :max Ziffern liegen.',
    'dimensions' => 'Das :attribute hat ungültige Bilddimensionen.',
    'distinct' => 'Das :attribute Feld hat einen doppelten Wert.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'ends_with' => ':attribute muss eine der folgenden Endungen aufweisen: :values.',
    'exists' => ':attribute ist ungültig.',
    'file' => 'Das :attribute muss eine Datei sein.',
    'filled' => 'Das :attribute Feld muss einen Wert haben.',
    'gt' => [
        'numeric' => ':attribute muss größer als :value sein.',
        'file' => ':attribute muss größer als :value Kilobytes sein.',
        'string' => ':attribute muss länger als :value Zeichen sein.',
        'array' => ':attribute muss mehr als :value Elemente haben.',
    ],
    'gte' => [
        'numeric' => ':attribute muss größer oder gleich :value sein.',
        'file' => ':attribute muss größer oder gleich :value Kilobytes sein.',
        'string' => ':attribute muss mindestens :value Zeichen lang sein.',
        'array' => ':attribute muss mindestens :value Elemente haben.',
    ],
    'image' => ':attribute muss ein Bild sein.',
    'in' => ':attribute ist ungültig.',
    'in_array' => 'Das :attribute Feld existiert nicht in :other.',
    'integer' => ':attribute muss eine Ganzzahl sein.',
    'ip' => ':attribute muss eine gültige IP-Adresse sein.',
    'ipv4' => ':attribute muss eine gültige IPv4 Adresse sein.',
    'ipv6' => ':attribute muss eine gültige IPv6 Adresse sein.',
    'json' => ':attribute muss eine gültige JSON-Zeichenfolge sein.',
    'lt' => [
        'numeric' => ':attribute muss kleiner als :value sein.',
        'file' => ':attribute muss kleiner als :value Kilobytes sein.',
        'string' => ':attribute muss kürzer als :value Zeichen sein.',
        'array' => ':attribute muss weniger als :value Elemente haben.',
    ],
    'lte' => [
        'numeric' => ':attribute muss kleiner oder gleich :value sein.',
        'file' => ':attribute muss kleiner oder gleich :value Kilobytes sein.',
        'string' => ':attribute darf maximal :value Zeichen lang sein.',
        'array' => ':attribute darf maximal :value Elemente haben.',
    ],
    'max' => [
        'numeric' => ':attribute darf nicht größer als :max sein.',
        'file' => ':attribute darf nicht größer als :max Kilobytes sein.',
        'string' => ':attribute darf nicht größer als :max Zeichen sein.',
        'array' => ':attribute darf nicht mehr als :max Elemente haben.',
    ],
    'mimes' => ':attribute muss vom typ: :values sein.',
    'mimetypes' => ':attribute muss den Dateityp :values haben.',
    'min' => [
        'numeric' => ':attribute muss mindestens :min sein.',
        'file' => ':attribute muss mindestens :min Kilobytes sein.',
        'string' => ':attribute muss mindestens :min Zeichen haben.',
        'array' => ':attribute muss mindestens :min Elemente haben.',
    ],
    'not_in' => ':attribute ist ungültig.',
    'not_regex' => 'Das Format von :attribute ist ungültig.',
    'numeric' => ':attribute muss eine Zahl sein.',
    'password' => 'Das Passwort ist falsch.',
    'present' => 'Das :attribute Feld muss vorhanden sein.',
    'regex' => 'Das :attribute Format ist ungültig.',
    'required' => 'Das :attribute Feld ist ein Pflichtfeld.',
    'required_if' => ':attribute ist Pflicht, wenn :other :value ist.',
    'required_unless' => ':attribute ist Pflicht, außer :other ist in :values.',
    'required_with' => ':attribute ist Pflicht, wenn :values vorhanden ist.',
    'required_with_all' => ':attribute muss ausgefüllt werden, wenn :values ausgefüllt wurde.',
    'required_without' => ':attribute ist Pflicht, wenn :values nicht vorhanden ist.',
    'required_without_all' => ':attribute ist Pflicht, wenn keiner der folgenden Werte vorhandne ist :values.',
    'same' => ':attribute und :other müssen übereinstimmen.',
    'size' => [
        'numeric' => ':attribute muss :size sein.',
        'file' => ':attribute muss :size Kilobytes sein.',
        'string' => ':attribute muss :size Zeichen sein.',
        'array' => ':attribute muss :size Elemente enthalten.',
    ],
    'starts_with' => ':attribute muss mit einem der folgenden Anfänge aufweisen: :values.',
    'string' => ':attribute muss eine Zeichenkette sein.',
    'timezone' => ':attribute muss eine gültige Zone sein.',
    'unique' => ':attribute muss einzigartig sein.',
    'uploaded' => ':attribute konnte nicht hochgeladen werden.',
    'url' => ':attribute hat ein ungültiges Format.',
    'uuid' => ':attribute muss ein UUID sein.',

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
            'numeric' => '{field} darf nicht größer sein als {max}.',
            'string' => '{field} darf nicht mehr als {max} Zeichen enthalten.',
        ],
        'required' => '{field} ist erforderlich.',
        'url' => '{field} ist keine gültige URL.',
    ],

];
