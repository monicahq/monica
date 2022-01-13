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

    'accepted' => ':attribute باید پذیرفته شده باشد.',
    'active_url' => 'ویژگی :attribute معتبری نیست.',
    'after' => 'ویژگی باید در تاریخی بعد از تاریخ باشد.',
    'after_or_equal' => 'attribute باید یک تاریخ بعد باشد یا برابر باشد: date.',
    'alpha' => ':attribute باید فقط حروف الفبا باشد.',
    'alpha_dash' => ':attribute باید فقط حروف الفبا، اعداد، خط تیره و زیرخط باشد.',
    'alpha_num' => ':attribute باید فقط حروف الفبا و اعداد باشد.',
    'array' => ':attribute باید آرایه باشد.',
    'before' => 'ویژگی باید در تاریخی قبل از تاریخ باشد.',
    'before_or_equal' => 'attribute باید تاریخ قبل یا برابر باشد: date.',
    'between' => [
        'numeric' => 'مقدار :attribute باید بین :min و :max باشد.',
        'file' => ':attribute باید بین :min و :max کیلوبایت باشد.',
        'string' => 'ویژگی باید بین حداقل حداکثر کاراکتر باشد.',
        'array' => ':attribute باید بین :min و :max آیتم باشد.',
    ],
    'boolean' => 'مقدار :attribute باید true یا false باشد.',
    'confirmed' => ':attribute با فیلد تکرار مطابقت ندارد.',
    'date' => ':attribute یک تاریخ معتبر نیست.',
    'date_equals' => ':attribute باید برابر با تاریخ :date باشد.',
    'date_format' => ':attribute با الگوی :format مطابقت ندارد.',
    'different' => ':attribute و :other باید از یکدیگر متفاوت باشند.',
    'digits' => ':attribute باید :digits رقم باشد.',
    'digits_between' => 'تعداد ارقام :attribute باید بین :min و :max رقم باشد.',
    'dimensions' => 'attribute: ابعاد تصویر نامعتبر است.',
    'distinct' => 'فیلد attribute دارای مقدار تکراری است.',
    'email' => 'مقدار :attribute باید یک آدرس ایمیل معتبر باشد.',
    'ends_with' => 'فیلد :attribute باید با یکی از مقادیر زیر خاتمه یابد: :values',
    'exists' => ':attribute انتخاب شده نامعتبر است.',
    'file' => ':attribute باید یک عدد باشد.',
    'filled' => 'فیلد :attribute باید مقدار داشته باشد.',
    'gt' => [
        'numeric' => ':attribute باید بزرگتر از :value باشد.',
        'file' => ':attribute باید بزرگتر از :value کیلوبایت باشد.',
        'string' => ':attribute باید بیشتر از :value کاراکتر داشته باشد.',
        'array' => ':attribute باید بیشتر از :value آیتم داشته باشد.',
    ],
    'gte' => [
        'numeric' => ':attribute باید بزرگتر یا مساوی :value باشد.',
        'file' => ':attribute باید بزرگتر یا مساوی :value کیلوبایت باشد.',
        'string' => ':attribute باید بیشتر یا مساوی :value کاراکتر داشته باشد.',
        'array' => ':attribute باید بیشتر یا مساوی :value آیتم داشته باشد.',
    ],
    'image' => ':attribute باید یک تصویر معتبر باشد.',
    'in' => ':attribute انتخاب شده نامعتبر است.',
    'in_array' => 'مقدار :attribute در :other وجود ندارد.',
    'integer' => 'مقدار :attribute باید یک عدد باشد.',
    'ip' => 'مقدار :attribute باید یک آدرس IP معتبر باشد.',
    'ipv4' => ':attribute باید یک آدرس معتبر از نوع IPv4 باشد.',
    'ipv6' => ':attribute باید یک آدرس معتبر از نوع IPv6 باشد.',
    'json' => 'فیلد :attribute باید یک رشته از نوع JSON باشد.',
    'lt' => [
        'numeric' => ':attribute باید کوچکتر از :value باشد.',
        'file' => ':attribute باید کوچکتر از :value کیلوبایت باشد.',
        'string' => ':attribute باید کمتر از :value کاراکتر داشته باشد.',
        'array' => ':attribute باید کمتر از :value آیتم داشته باشد.',
    ],
    'lte' => [
        'numeric' => ':attribute باید کوچکتر یا مساوی :value باشد.',
        'file' => ':attribute باید کوچکتر یا مساوی :value کیلوبایت باشد.',
        'string' => ':attribute باید کمتر یا مساوی :value کاراکتر داشته باشد.',
        'array' => ':attribute باید کمتر یا مساوی :value آیتم داشته باشد.',
    ],
    'max' => [
        'numeric' => ':attribute نباید بزرگتر از :max باشد.',
        'file' => ':attribute نباید بزرگتر از :max کیلوبایت باشد.',
        'string' => ':attribute نباید بیشتر از :max کاراکتر داشته باشد.',
        'array' => 'ویژگی: ممکن است بیش از موارد حداکثر داشته باشد.',
    ],
    'mimes' => ':attribute باید یک فایل از نوع :values باشد.',
    'mimetypes' => ':attribute باید یک فایل از نوع :values باشد.',
    'min' => [
        'numeric' => ':attribute نباید کوچکتر از :min باشد.',
        'file' => 'حجم :attribute باید حداقل :min کیلوبایت باشد.',
        'string' => ':attribute حداقل باید دارای :min کاراکتر باشد.',
        'array' => ':attribute باید حداقل دارای :min آیتم باشد.',
    ],
    'not_in' => ':attribute انتخاب شده نامعتبر است.',
    'not_regex' => 'فرمت :attribute نامعتبر می‌باشد.',
    'numeric' => ':attribute باید یک عدد باشد.',
    'password' => 'کلمه عبور صحیح نیست.',
    'present' => ':attribute باید وجود داشته باشد.',
    'regex' => 'فرمت :attribute نامعتبر می‌باشد.',
    'required' => 'فیلد :attribute باید مقدار داشته باشد.',
    'required_if' => 'فیلد :attribute اجباری است تا زمانی که :other در :values باشد.',
    'required_unless' => 'فیلد :attribute اجباری است تا زمانی که :other در :values باشد.',
    'required_with' => 'فیلد :attribute اجباری است تا زمانی که :values وجود داشته باشد.',
    'required_with_all' => 'فیلد :attribute اجباری است تا زمانی که :values وجود داشته باشد.',
    'required_without' => 'فیلد :attribute اجباری است تا زمانی که :values وجود نداشته باشد.',
    'required_without_all' => 'در صورت عدم وجود هر یک از فیلدهای :values، فیلد :attribute الزامی است.',
    'same' => ':attribute و :other باید همانند هم باشند.',
    'size' => [
        'numeric' => ':attribute باید برابر با :size باشد.',
        'file' => 'حجم :attribute باید به اندازه :size کیلوبایت باشد.',
        'string' => ':attribute باید برابر با :size کاراکتر باشد.',
        'array' => ':attribute باید شامل :size آیتم باشد.',
    ],
    'starts_with' => 'فیلد :attribute باید با یکی از مقادیر زیر شروع شود: :values',
    'string' => 'فیلد :attribute باید متن باشد.',
    'timezone' => 'فیلد :attribute باید یک منطقه زمانی معتبر باشد.',
    'unique' => ':attribute قبلا انتخاب شده است.',
    'uploaded' => ':attribute آپلود نشد.',
    'url' => 'فرمت :attribute نامعتبر می‌باشد.',
    'uuid' => 'فیلد :attribute باید یک UUID معتبر باشد.',

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
            'numeric' => '{field} نباید بزرگتر از  {max} باشد.',
            'string' => '{field} نباید بزرگتر از  {max} کارکتر باشد.',
        ],
        'required' => '{field} الزامیست.',
        'url' => '{field} یک URL معتبر نیست.',
    ],

];
