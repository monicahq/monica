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

    'accepted' => ':attribute harus diterima.',
    'active_url' => ':atrribute bukan sebuah URL yang valid.',
    'after' => ':attribute harus merupakan tanggal setelah :date.',
    'after_or_equal' => ':attribute harus merupakan sebuah tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya dapat berisi huruf, angka, tanda hubung dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'array' => ':attribute harus merupakan array.',
    'before' => ':attribute harus merupakan seubah tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus merupakan sebuah tanggal sebelum atau sama dengan :date.',
    'between' => [
        'numeric' => 'attribute: harus berada antara :min dan :max.',
        'file' => 'attribute: harus berada antara :min dan :max kilobytes.',
        'string' => 'attribute: harus berada antara :min dan :max karakter.',
        'array' => ':attribute harus memiliki item antara :min dan :max.',
    ],
    'boolean' => 'Baris :attribute harus true atau false.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':attribute bukan sebuah tanggal yang valid.',
    'date_equals' => ':attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => ':attribute tidak cocok dengan format :format.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus berupa digit :digits.',
    'digits_between' => ':attribute harus berada diantara :min dan :max digit.',
    'dimensions' => ':attribute memiliki dimensi gambar tidak valid.',
    'distinct' => 'Baris :attribute memiliki sebuah nilai duplikat.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'ends_with' => ':attribute harus diakhiri dengan salah satu dari hal berikut: :values.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa sebuah berkas.',
    'filled' => 'Baris :attribute harus memiliki sebuah nilai.',
    'gt' => [
        'numeric' => ':attribute harus lebih besar dari :value.',
        'file' => ':attribute harus lebih besar dari :value kilobytes.',
        'string' => ':attribute harus lebih besar dari :value karakter.',
        'array' => ':attribute harus lebih besar dari :value item.',
    ],
    'gte' => [
        'numeric' => ':attribute harus lebih besar dari atau sama dengan :value.',
        'file' => ':attribute harus lebih besar dari atau sama dengan :value kilobytes.',
        'string' => ':attribute harus lebih besar dari atau sama dengan :value karakter.',
        'array' => ':attribute harus memiliki item :value atau lebih.',
    ],
    'image' => ':attribute harus berupa sebuah gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => 'Baris :attribute tidak tersedia di :other.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'ip' => ':attribute harus berupa sebuah alamat IP yang valid.',
    'ipv4' => ':attribute harus berupa sebuah alamat IPv4 yang valid.',
    'ipv6' => ':attribute harus berupa sebuah alamat IPv6 yang valid.',
    'json' => ':attribute harus berupa sebuah string JSON yang valid.',
    'lt' => [
        'numeric' => ':attribute harus kurang dari :value.',
        'file' => ':attribute harus kurang dari :value kilobytes.',
        'string' => ':attribute harus kurang dari :value karakter.',
        'array' => ':attribute harus kurang dari :value item.',
    ],
    'lte' => [
        'numeric' => ':attribute harus kurang dari atau sama dengan :value.',
        'file' => ':attribute harus kurang dari atau sama dengan :value kilobytes.',
        'string' => ':attribute harus kurang dari atau sama dengan :value karakter.',
        'array' => ':attribute harus kurang dari atau sama dengan :value item.',
    ],
    'max' => [
        'numeric' => ':attribute tidak boleh lebih besar dari :max.',
        'file' => ':attribute tidak boleh lebih besar dari :max kilobytes.',
        'string' => ':attribute tidak boleh lebih besar dari :max karakter.',
        'array' => ':attribute tidak boleh lebih besar dari :max item.',
    ],
    'mimes' => ':attribute harus berupa sebuah berkas berjenis :values.',
    'mimetypes' => ':attribute harus berupa sebuah berkas berjenis :values.',
    'min' => [
        'numeric' => ':attribute harus setidaknya :min.',
        'file' => ':attribute harus setidaknya :min kilobytes.',
        'string' => ':attribute harus setidaknya :min karakter.',
        'array' => ':attribute harus setidaknya :min item.',
    ],
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak benar.',
    'numeric' => ':attribute harus berupa angka.',
    'password' => 'Kata sandi tidak benar.',
    'present' => 'Baris :attribute harus tersedia.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Baris :attribute diperlukan.',
    'required_if' => 'Baris :attribute diperlukan ketika :other adalah :value.',
    'required_unless' => 'Baris :attribute diperlukan kecuali :other di dalam :values.',
    'required_with' => 'Baris :attribute diperlukan ketika :values tersedia.',
    'required_with_all' => 'Baris :attribute diperlukan ketika :values tersedia.',
    'required_without' => 'Baris :attribute diperlukan ketika :values tidak tersedia.',
    'required_without_all' => 'Baris :attribute diperlukan ketika tidak ada dari :values tersedia.',
    'same' => ':attribute dan :other harus cocok.',
    'size' => [
        'numeric' => ':attribute harus :size.',
        'file' => ':attribute harus :size kilobytes.',
        'string' => ':attribute harus :size karakter.',
        'array' => ':attribute harus :size item.',
    ],
    'starts_with' => ':attribute harus dimulai dengan salah satu dari berikut: :values.',
    'string' => ':attribute harus berupa sebuah string.',
    'timezone' => ':attribute harus berupa sebuah zona yang valid.',
    'unique' => ':attribute telah diambil/dipakai.',
    'uploaded' => ':attribute gagal mengunggah.',
    'url' => 'Format :attribute tidak valid.',
    'uuid' => ':attribute harus berupa sebuah UUID yang valid.',

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
            'numeric' => '{field} tidak boleh lebih dari {max}.',
            'string' => '{field} tidak boleh lebih dari {max} karakter.',
        ],
        'required' => '{field} diperlukan.',
        'url' => '{field} bukan sebuah alamat URL yang valid.',
    ],

];
