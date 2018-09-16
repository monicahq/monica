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

    'accepted'             => 'يجب أن يتم قبول :attribute.',
    'active_url'           => 'إن رابط :attribute غير صالح.',
    'after'                => 'يجب أن يكون تاريخ :attribute بعد :date.',
    'after_or_equal'       => 'يجب أن يكون تاريخ :attribute بعد أو مساوياً لـ:date.',
    'alpha'                => 'يجب أن يحتوي :attribute فقط على أحرف.',
    'alpha_dash'           => 'يجب أن يحتوي :attribute فقط على أحرف، و أرقام و شرطات.',
    'alpha_num'            => 'يجب أن يحتوي :attribute فقط على أحرف و أرقام.',
    'array'                => 'يجب أن يكون :attribute مرتباً.',
    'before'               => 'يجب أن يكون تاريخ :attribute قبل :date.',
    'before_or_equal'      => 'يجب أن يكون تاريخ :attribute قبل أو مساوياً لـ:date.',
    'between'              => [
        'numeric' => 'يجب أن يكون :attribute بين :min و :max.',
        'file'    => 'يجب أن يكون :attribute بين :min و :max كيلوبايت.',
        'string'  => 'يجب أن يكون :attribute بين :min و :max من الأحرف.',
        'array'   => 'يجب أن يكون لـ :attribute بين :min و :max من العناصر.',
    ],
    'boolean'              => 'حقل :attribute يجب أن يكون صحيحاً أو خاطئاً.',
    'confirmed'            => 'تأكيد :attribute غير متطابق.',
    'date'                 => 'إن تاريخ :attribute غير صالح.',
    'date_format'          => 'إن :attribute غير متطابق مع تنسيق :format.',
    'different'            => 'إن :attribute و :other يجب أن يكونا مختلفين.',
    'digits'               => 'يجب أن يحتوي :attribute على :digits رقماً.',
    'digits_between'       => 'يجب أن يكون :attribute بين :min و :max رقماً.',
    'dimensions'           => 'يحتوي :attribute على أبعاد غير صالحة للصور.',
    'distinct'             => 'يحتوي :attribute على قيمة مكررة.',
    'email'                => 'يجب أن يكون :attribute بريداً إلكتروني صالح.',
    'exists'               => 'إن :attribute غير صالح.',
    'file'                 => 'يجب أن يكون :attribute ملفاً.',
    'filled'               => 'يجب أن يحتوي حقل :attribute على قيمة.',
    'image'                => 'يجب أن تكون :attribute صورة.',
    'in'                   => 'إن :attribute غير صالح.',
    'in_array'             => 'حقل :attribute غير موجود في :other.',
    'integer'              => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'ip'                   => 'The :attribute must be a valid IP address.',
    'ipv4'                 => 'The :attribute must be a valid IPv4 address.',
    'ipv6'                 => 'The :attribute must be a valid IPv6 address.',
    'json'                 => 'The :attribute must be a valid JSON string.',
    'max'                  => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file'    => 'The :attribute may not be greater than :max kilobytes.',
        'string'  => 'The :attribute may not be greater than :max characters.',
        'array'   => 'The :attribute may not have more than :max items.',
    ],
    'mimes'                => 'The :attribute must be a file of type: :values.',
    'mimetypes'            => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => 'The :attribute must be at least :min.',
        'file'    => 'The :attribute must be at least :min kilobytes.',
        'string'  => 'The :attribute must be at least :min characters.',
        'array'   => 'The :attribute must have at least :min items.',
    ],
    'not_in'               => 'The selected :attribute is invalid.',
    'not_regex'            => 'The :attribute format is invalid.',
    'numeric'              => 'يجب أن يكون :attribute رقماً.',
    'present'              => 'The :attribute field must be present.',
    'regex'                => 'The :attribute format is invalid.',
    'required'             => 'The :attribute field is required.',
    'required_if'          => 'The :attribute field is required when :other is :value.',
    'required_unless'      => 'The :attribute field is required unless :other is in :values.',
    'required_with'        => 'The :attribute field is required when :values is present.',
    'required_with_all'    => 'The :attribute field is required when :values is present.',
    'required_without'     => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same'                 => 'The :attribute and :other must match.',
    'size'                 => [
        'numeric' => 'The :attribute must be :size.',
        'file'    => 'The :attribute must be :size kilobytes.',
        'string'  => 'The :attribute must be :size characters.',
        'array'   => 'The :attribute must contain :size items.',
    ],
    'string'               => 'The :attribute must be a string.',
    'timezone'             => 'يجب أن يكون :attribute منطقة صالحة.',
    'unique'               => 'لقد تم أخذ :attribute مسبقاً.',
    'uploaded'             => 'لقد فشل تحميل :attribute.',
    'url'                  => 'إن تنسيق :attribute غير صالح.',

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
