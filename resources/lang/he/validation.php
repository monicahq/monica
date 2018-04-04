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

    'accepted'             => ':attribute חייב להתקבל.',
    'active_url'           => ':attribute אינה כתובת תקנית.',
    'after'                => ':attribute חייב להיות תאריך לאחר :date.',
    'after_or_equal'       => 'The :attribute must be a date after or equal to :date.',
    'alpha'                => ':attribute יכול להכיל אותיות בלבד.',
    'alpha_dash'           => ':attribute יכול להכיל רק אותיות, מספרים ומינוסים.',
    'alpha_num'            => ':attribute יכול להכיל אותיות ומספרים בלבד.',
    'array'                => ':attribute חייב להיות מערך.',
    'before'               => ':attribute חייב להיות תאריך לפני :date.',
    'before_or_equal'      => 'The :attribute must be a date before or equal to :date.',
    'between'              => [
        'numeric' => ':attribute חייב להיות בין :min לבין :max.',
        'file'    => ':attribute חייב להיות בין :min לבין :max קילובתים.',
        'string'  => ':attribute חייב להיות בין :min לבין :max תווים.',
        'array'   => ':attribute חייב להיות בין :min לבין :max פריטים.',
    ],
    'boolean'              => 'השדה :attribute חייב להיות אמת או שקר.',
    'confirmed'            => 'האימות של :attribute לא תואם.',
    'date'                 => ':attribute אינו תאריך תקני.',
    'date_format'          => ':attribute לא תואם את המבנה :format.',
    'different'            => ':attribute וגם :other חייבים להיות שונים זה מזה.',
    'digits'               => ':attribute חייב להיות באורך :digits ספרות.',
    'digits_between'       => ':attribute חייב להיות בין :min ל־:max ספרות.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => 'לשדה :attribute יש ערך כפול.',
    'email'                => ':attribute חייב להיות כתובת דוא״ל תקנית.',
    'exists'               => ':attribute הנבחר שגוי.',
    'file'                 => 'The :attribute must be a file.',
    'filled'               => 'The :attribute field must have a value.',
    'image'                => ':attribute חייב להיות תמונה.',
    'in'                   => ':attribute הנבחר שגוי.',
    'in_array'             => 'השדה :attribute לא קיים תחת :other.',
    'integer'              => ':attribute חייב להיות מספר שלם וחיובי.',
    'ip'                   => ':attribute חייב להיות כתובת IP תקנית.',
    'ipv4'                 => 'The :attribute must be a valid IPv4 address.',
    'ipv6'                 => 'The :attribute must be a valid IPv6 address.',
    'json'                 => ':attribute חייב להיות מחרוזת JSON תקנית.',
    'max'                  => [
        'numeric' => ':attribute לא יכול להיות יותר גדול מאשר :max.',
        'file'    => ':attribute לא יכול להיות גדול מ־:max קילובתים.',
        'string'  => ':attribute לא יכול להיות גדול מ־:max תווים.',
        'array'   => 'תחת :attribute לא יכולים להיות יותר מ־:max פריטים.',
    ],
    'mimes'                => ':attribute חייב להיות קובץ מסוג: :values.',
    'mimetypes'            => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => ':attribute חייב להיות לפחות :min.',
        'file'    => ':attribute חייב להיות בגודל של לפחות :min קילובתים.',
        'string'  => ':attribute חייב להיות באורך של לפחות :min תווים.',
        'array'   => 'תחת :attribute חייבים להיות לפחות :min פריטים.',
    ],
    'not_in'               => ':attribute הנבחר שגוי.',
    'not_regex'            => 'The :attribute format is invalid.',
    'numeric'              => ':attribute חייב להיות מספר.',
    'present'              => 'השדה :attribute חייב להיות נוכח.',
    'regex'                => 'המבנה :attribute שגוי.',
    'required'             => 'השדה :attribute נחוץ.',
    'required_if'          => 'השדה :attribute נחוץ כאשר :other הוא :value.',
    'required_unless'      => 'השדה :attribute נחוץ אלמלא :other קיים בתוך :values.',
    'required_with'        => 'השדה :attribute נחוץ כאשר :values קיימים.',
    'required_with_all'    => 'השדה :attribute נחוץ כאשר :values קיימים.',
    'required_without'     => 'השדה :attribute נחוץ כאשר :values אינם קיימים.',
    'required_without_all' => 'השדה :attribute נחוץ כאשר אף אחד מבין :values קיים.',
    'same'                 => ':attribute וגם :other חייבים להיות תואמים.',
    'size'                 => [
        'numeric' => ':attribute חייב להיות בגודל :size.',
        'file'    => ':attribute חייב להיות בגודל של :size קילובתים.',
        'string'  => ':attribute חייב להיות באורך של :size תווים.',
        'array'   => ':attribute חייב להכיל :size פריטים.',
    ],
    'string'               => ':attribute חייב להיות קובץ.',
    'timezone'             => ':attribute חייב להיות אזור תקני.',
    'unique'               => ':attribute כבר תפוס.',
    'uploaded'             => 'The :attribute failed to upload.',
    'url'                  => 'התבנית :attribute שגויה.',

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
