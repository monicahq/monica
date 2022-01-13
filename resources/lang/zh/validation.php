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

    'accepted' => '您必须同意 :attribute。',
    'active_url' => ':attribute 不是一个有效的URL网址',
    'after' => ':attribute 必须是一个在 :date 之后的日期。',
    'after_or_equal' => ':attribute 必须是一个在 :date 或之后的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能由字母、数字、短划线(-)和下划线(_)组成。',
    'alpha_num' => ':attribute 只允许包含字母和数字',
    'array' => ':attribute 必须是个数组。',
    'before' => ':attribute 必须在 :date 之前',
    'before_or_equal' => ':attribute 必须在 :date 或之前',
    'between' => [
        'numeric' => ':attribute 必须在 :min 和 :max 之间。',
        'file' => ':attribute 必须在 :min 千字节到 :max 千字节之间。',
        'string' => ':attribute 必须在 :min 到 :max 字符之间',
        'array' => ':attribute 必须在 :min 到 :max 个数目之间',
    ],
    'boolean' => ':attribute 字段必须为 true 或 false。',
    'confirmed' => ':attribute 与确认项目不匹配',
    'date' => ':attribute 不是个有效日期',
    'date_equals' => ':attribute 必须要等于 :date。',
    'date_format' => ':attribute 不符合 :format 的格式',
    'different' => ':attribute 和 :other 不能相同。',
    'digits' => ':attribute 必须是 :digits 数字',
    'digits_between' => ':attribute 必须是 :min - :max 位数字。',
    'dimensions' => ':attribute 的图片无效',
    'distinct' => '：属性字段具有重复值。',
    'email' => ':attribute 必须是一个有效的电子邮件地址。',
    'ends_with' => ':attribute 必须以 :values 为结尾。',
    'exists' => '选择的 :attribute 无效',
    'file' => ':attribute 必须是个文件',
    'filled' => ':attribute 字段必须有一个值',
    'gt' => [
        'numeric' => ':attribute 必须大于 :value。',
        'file' => ':attribute 必须大于 :value KB。',
        'string' => ':attribute 必须多于 :value 个字符。',
        'array' => ':attribute 必须多于 :value 个元素。',
    ],
    'gte' => [
        'numeric' => ':attribute 必须大于或等于 :value。',
        'file' => ':attribute 必须大于或等于 :value KB。',
        'string' => ':attribute 必须多于或等于 :value 个字符。',
        'array' => ':attribute 必须多于或等于 :value 个元素。',
    ],
    'image' => ':attribute 必须是图片。',
    'in' => '选择的 :attribute 无效',
    'in_array' => ':attribute 不在 :other 中。',
    'integer' => ':attribute 必须是整数',
    'ip' => ':attribute 必须是一个有效的 IP 地址',
    'ipv4' => ':attribute 必须是一个有效的 IPv4 地址',
    'ipv6' => ':attribute 必须是一个有效的 IPv6 地址',
    'json' => '：属性必须是有效的JSON字符串。',
    'lt' => [
        'numeric' => ':attribute 必须小于 :value。',
        'file' => ':attribute 必须小于 :value KB。',
        'string' => ':attribute 必须少于 :value 个字符。',
        'array' => ':attribute 必须少于 :value 个元素。',
    ],
    'lte' => [
        'numeric' => ':attribute 必须小于或等于 :value。',
        'file' => ':attribute 必须小于或等于 :value KB。',
        'string' => ':attribute 必须少于或等于 :value 个字符。',
        'array' => ':attribute 必须少于或等于 :value 个元素。',
    ],
    'max' => [
        'numeric' => ':attribute 不大于 :max',
        'file' => ':attribute 不大于 :max kb',
        'string' => ':attribute 不大于 :max 字符',
        'array' => ':attribute 的数量不能超过 :max 个。',
    ],
    'mimes' => ':attribute 文件类型必须是 :values。',
    'mimetypes' => ':attribute 文件类型必须是 :values。',
    'min' => [
        'numeric' => ':attribute 最少是 :min',
        'file' => ':attribute 最小是 :min 千字节',
        'string' => ':attribute 最少为 :min个字符',
        'array' => ':attribute 至少为 :min 个',
    ],
    'not_in' => '选择的 :attribute 无效',
    'not_regex' => ':attribute 格式无效',
    'numeric' => ':attribute 必须是数字。',
    'password' => '密码错误',
    'present' => ':attribute 为必填项。',
    'regex' => ':attribute 格式不对',
    'required' => ':attribute 字段必填',
    'required_if' => ':attribute 字段在 :other 是 :value 时是必须的',
    'required_unless' => ':attribute 是必须的除非 :other 在 :values 中。',
    'required_with' => '当 :values 不存在时， :attribute 是必需的',
    'required_with_all' => '当 :values 存在时 :attribute 不能为空。',
    'required_without' => '当 :values 不存在时， :attribute 是必填的。',
    'required_without_all' => '当没有任何 :values 存在时， :attribute 字段为必填项。',
    'same' => ':attribute 和 :other 必需匹配',
    'size' => [
        'numeric' => ':attribute 必需是 :size',
        'file' => ':attribute 必需是 :size kb',
        'string' => ':attribute 必须包含 :size 个字符。',
        'array' => ':attribute 必须包含 :size 个项。',
    ],
    'starts_with' => ':attribute 必须以 :values 为开头。',
    'string' => ':attribute 必须是一个字符串。',
    'timezone' => ':attribute 必须是个有效的区域。',
    'unique' => ':attribute 已经被占用',
    'uploaded' => ':attribute上传失败.',
    'url' => ':attribute 格式不对',
    'uuid' => ':attribute 必须是有效的 UUID。',

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
            'rule-name' => '自定义消息',
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
            'numeric' => '{field} 不能大于 {max}',
            'string' => '{field} 不能超过 {max} 个字符',
        ],
        'required' => '{field} 必填',
        'url' => '{field} 不是一个有效的URL地址',
    ],

];
