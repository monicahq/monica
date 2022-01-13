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

    'accepted' => ':attribute må bli godtatt.',
    'active_url' => ':attribute er ikke en gyldig URL.',
    'after' => ':attribute må være en dato etter :date.',
    'after_or_equal' => ':attribute må tidligst være datoen :date.',
    'alpha' => ':attribute kan kun inneholde bokstaver.',
    'alpha_dash' => ':attribute kan bare inneholde bokstaver, tall, bindestrek, og understrek.',
    'alpha_num' => ':attribute kan bare inneholde tall og bokstaver.',
    'array' => ':attribute må være en matrise.',
    'before' => ':attribute må være en dato tidligere enn :date.',
    'before_or_equal' => ':attribute må være en dato før eller lik :date.',
    'between' => [
        'numeric' => ':attribute må være mellom :min og :max.',
        'file' => ':attribute må være mellom :min og :max kilobytes.',
        'string' => ':attribute må være mellom :min og :max tegn.',
        'array' => ':attribute må være mellom :min og :max elementer.',
    ],
    'boolean' => ':attribute må være sann eller usann.',
    'confirmed' => ':attribute bekreftelsen stemmer ikke overens.',
    'date' => ':attribute er ikke en gyldig dato.',
    'date_equals' => ':attribute må være en dato samsvarende med :date.',
    'date_format' => ':attribute samsvarer ikke med formatet :format.',
    'different' => ':attribute og :other må være forskjellige.',
    'digits' => 'Attributtet :attribute må være :digits sifre.',
    'digits_between' => ':attribute må være mellom :min og :max sifre.',
    'dimensions' => ':attribute har ugyldig bildestørrelse.',
    'distinct' => ':attribute har en duplikatverdi.',
    'email' => ':attribute må være en gyldig e-postadresse.',
    'ends_with' => ':attribute må avsluttes med en av følgende: :values.',
    'exists' => 'Valgt :attribute er ugyldig.',
    'file' => ':attribute må være en fil.',
    'filled' => ':attribute må inneholde en verdi.',
    'gt' => [
        'numeric' => ':attribute må være større enn :value.',
        'file' => ':attribute må være større enn :value kilobytes.',
        'string' => ':attribute må ha flere enn :value tegn.',
        'array' => ':attribute må inneholde flere enn :value elementer.',
    ],
    'gte' => [
        'numeric' => ':attribute må være større enn eller samsvarende med :value.',
        'file' => ':attribute må være større enn eller samsvarende med :value kilobytes.',
        'string' => ':attribute må være større enn eller samsvarende med :value tegn.',
        'array' => ':attribute må være :value elementer eller mer.',
    ],
    'image' => ':attribute må være et bilde.',
    'in' => 'Valgt :attribute er ugyldig.',
    'in_array' => ':attribute feltet finnes ikke i :other.',
    'integer' => ':attribute må være ett helt tall.',
    'ip' => ':attribute må være en gyldig IP-adresse.',
    'ipv4' => ':attribute må være en gyldig IPv4-adresse.',
    'ipv6' => ':attribute må være en gyldig IPv6-adresse.',
    'json' => ':attribute må være en gyldig JSON-streng.',
    'lt' => [
        'numeric' => ':attribute må være mindre enn :value.',
        'file' => ':attribute må være mindre enn :value kilobytes.',
        'string' => ':attribute må være færre enn :value tegn.',
        'array' => ':attribute må ha færre enn :value elementer.',
    ],
    'lte' => [
        'numeric' => ':attribute må være mindre enn eller samsvarende med :value.',
        'file' => ':attribute må være mindre enn eller samsvarende med :value kilobytes.',
        'string' => ':attribute må være mindre enn eller tilsvarende :value tegn.',
        'array' => ':attribute må ikke inneholde flere enn :value elementer.',
    ],
    'max' => [
        'numeric' => ':attribute kan ikke være større enn :max.',
        'file' => ':attribute kan ikke være større enn :max kilobytes.',
        'string' => ':attribute kan ikke være større enn :max tegn.',
        'array' => ':attribute kan ikke inneholde mer enn :max elementer.',
    ],
    'mimes' => ':attribute må være av filtypen: :values.',
    'mimetypes' => ':attribute må være av filtypen: :values.',
    'min' => [
        'numeric' => ':attribute må være minst :min.',
        'file' => ':attribute må være minimum :min kilobytes.',
        'string' => ':attribute må ha minst :min tegn.',
        'array' => ':attribute må inneholde :min elementer.',
    ],
    'not_in' => 'Valgt :attribute er ugyldig.',
    'not_regex' => 'Formatet er ugyldig (:attribute).',
    'numeric' => ':attribute må være et tall.',
    'password' => 'Passordet er feil.',
    'present' => ':attribute må finnes.',
    'regex' => 'Formatet er ugyldig (:attribute).',
    'required' => ':attribute feltet er påkrevd.',
    'required_if' => ':attribute er påkrevd når :oher er :value.',
    'required_unless' => ':attribute feltet er påkrevd med mindre :other er i :values.',
    'required_with' => ':attribute er påkrevd når :values er tilstede.',
    'required_with_all' => ':attribute er påkrevd når :values er tilstede.',
    'required_without' => ':attribute kreves når ingen av :values er til stede.',
    'required_without_all' => ':attribute er påkrevd når ingen av :values er tilstede.',
    'same' => ':attribute og :other må være like.',
    'size' => [
        'numeric' => ':attribute må være :size.',
        'file' => ':attribute må være :size kilobytes.',
        'string' => ':attribute må være :size tegn.',
        'array' => ':attribute må inneholde :size elementer.',
    ],
    'starts_with' => ':attribute må begynne med en av de følgende: :values.',
    'string' => ':attribute må være en tekst.',
    'timezone' => ':attribute må være en gyldig tidssone.',
    'unique' => ':attribute er allerede brukt.',
    'uploaded' => ':attribute opplasting feilet.',
    'url' => 'Formatet er ugyldig (:attribute).',
    'uuid' => ':attribute må være en gyldig UUID.',

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
            'numeric' => '{field} kan ikke være større enn {max}.',
            'string' => '{field} må ikke være lengre enn {max} tegn.',
        ],
        'required' => '{field} er påkrevd.',
        'url' => '{field} er ikke en gyldig URL.',
    ],

];
