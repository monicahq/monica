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

    'accepted' => '您必須同意 :attribute。',
    'active_url' => ':attribute 不是一個有效的 URL 網址',
    'after' => ':attribute 必須是一個在 :date 之後的日期。',
    'after_or_equal' => ':attribute 必須是一個在 :date 或之後的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能由字母、數字、減號(-)和底線(_)組成。',
    'alpha_num' => ':attribute 只允許包含字母和數字',
    'array' => ':attribute 必須是個陣列。',
    'before' => ':attribute 必須在 :date 之前',
    'before_or_equal' => ':attribute 必須在 :date 或之前',
    'between' => [
        'numeric' => ':attribute 必須在 :min 和 :max 之間。',
        'file' => ':attribute 必須在 :min KB 到 :max KB 之間。',
        'string' => ':attribute 必須在 :min 到 :max 字元之間',
        'array' => ':attribute 必須在 :min 到 :max 個數目之間',
    ],
    'boolean' => ':attribute 欄位必須為 true 或 false。',
    'confirmed' => ':attribute 與確認專案不匹配',
    'date' => ':attribute 不是個有效日期',
    'date_equals' => ':attribute 必須要等於 :date。',
    'date_format' => ':attribute 不符合 :format 的格式',
    'different' => ':attribute 和 :other 不能相同。',
    'digits' => ':attribute 必須是 :digits 數字',
    'digits_between' => ':attribute 必須是 :min - :max 位數字。',
    'dimensions' => ':attribute 的圖片無效',
    'distinct' => '：屬性欄位具有重複值。',
    'email' => ':attribute 必須是一個有效的電子郵件地址。',
    'ends_with' => ':attribute 必須以 :values 為結尾。',
    'exists' => '選擇的 :attribute 無效',
    'file' => ':attribute 必須是個檔案',
    'filled' => ':attribute 欄位必須有一個值',
    'gt' => [
        'numeric' => ':attribute 必須大於 :value。',
        'file' => ':attribute 必須大於 :value KB。',
        'string' => ':attribute 必須多於 :value 個字元。',
        'array' => ':attribute 必須多於 :value 個元素。',
    ],
    'gte' => [
        'numeric' => ':attribute 必須大於或等於 :value。',
        'file' => ':attribute 必須大於或等於 :value KB。',
        'string' => ':attribute 必須多於或等於 :value 個字元。',
        'array' => ':attribute 必須多於或等於 :value 個元素。',
    ],
    'image' => ':attribute 必須是圖片。',
    'in' => '選擇的 :attribute 無效',
    'in_array' => ':attribute 不在 :other 中。',
    'integer' => ':attribute 必須是整數',
    'ip' => ':attribute 必須是一個有效的 IP 位址',
    'ipv4' => ':attribute 必須是一個有效的 IPv4 位址',
    'ipv6' => ':attribute 必須是一個有效的 IPv6 位址',
    'json' => '：屬性必須是有效的JSON字串。',
    'lt' => [
        'numeric' => ':attribute 必須小於 :value。',
        'file' => ':attribute 必須小於 :value KB。',
        'string' => ':attribute 必須少於 :value 個字元。',
        'array' => ':attribute 必須少於 :value 個元素。',
    ],
    'lte' => [
        'numeric' => ':attribute 必須小於或等於 :value。',
        'file' => ':attribute 必須小於或等於 :value KB。',
        'string' => ':attribute 必須少於或等於 :value 個字元。',
        'array' => ':attribute 必須少於或等於 :value 個元素。',
    ],
    'max' => [
        'numeric' => ':attribute 不大於 :max',
        'file' => ':attribute 不大於 :max kb',
        'string' => ':attribute 不大於 :max 字元',
        'array' => ':attribute 的數量不能超過 :max 個。',
    ],
    'mimes' => ':attribute 檔案類型必須是 :values。',
    'mimetypes' => ':attribute 檔案類型必須是 :values。',
    'min' => [
        'numeric' => ':attribute 最少是 :min',
        'file' => ':attribute 最小是 :min 千位元組',
        'string' => ':attribute 最少為 :min 個字元',
        'array' => ':attribute 至少為 :min 個',
    ],
    'not_in' => '選擇的 :attribute 無效',
    'not_regex' => ':attribute 格式無效',
    'numeric' => ':attribute 必須是數字。',
    'password' => '密碼錯誤',
    'present' => ':attribute 為必填項。',
    'regex' => ':attribute 格式不對',
    'required' => ':attribute 欄位必填',
    'required_if' => ':attribute 欄位在 :other 是 :value 時是必須的',
    'required_unless' => ':attribute 是必須的除非 :other 在 :values 中。',
    'required_with' => '當 :values 不存在時， :attribute 是必需的',
    'required_with_all' => '當 :values 存在時 :attribute 不能為空。',
    'required_without' => '當 :values 不存在時， :attribute 是必填的。',
    'required_without_all' => '當沒有任何 :values 存在時， :attribute 欄位為必填項。',
    'same' => ':attribute 和 :other 必需匹配',
    'size' => [
        'numeric' => ':attribute 必需是 :size',
        'file' => ':attribute 必需是 :size kb',
        'string' => ':attribute 必須包含 :size 個字元。',
        'array' => ':attribute 必須包含 :size 個項。',
    ],
    'starts_with' => ':attribute 必須以 :values 為開頭。',
    'string' => ':attribute 必須是一個字串。',
    'timezone' => ':attribute 必須是個有效的區域。',
    'unique' => ':attribute 已經被佔用',
    'uploaded' => ':attribute 上傳失敗.',
    'url' => ':attribute 格式不對',
    'uuid' => ':attribute 必須是有效的 UUID。',

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
