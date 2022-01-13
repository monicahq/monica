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

    'accepted' => 'O campo :attribute deve ser aceito.',
    'active_url' => 'O campo :attribute deve conter uma URL válida.',
    'after' => 'O campo :attribute deve conter uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deverá conter uma data posterior ou igual a :date.',
    'alpha' => 'O campo :attribute deve conter apenas letras.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'O campo :attribute deve conter apenas letras e números .',
    'array' => 'O campo :attribute deve conter um array.',
    'before' => 'O campo :attribute deve conter uma data anterior a :date.',
    'before_or_equal' => 'O Campo :attribute deverá conter uma data anterior ou igual a :date.',
    'between' => [
        'numeric' => 'O campo :attribute deve conter um número entre :min e :max.',
        'file' => 'O campo :attribute deve conter um arquivo de :min a :max kilobytes.',
        'string' => 'O campo :attribute deve conter entre :min a :max caracteres.',
        'array' => 'O campo :attribute deve conter de :min a :max itens.',
    ],
    'boolean' => 'O campo :attribute deve conter o valor verdadeiro ou falso.',
    'confirmed' => 'A confirmação para o campo :attribute não coincide.',
    'date' => 'O campo :attribute não contém uma data válida.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'A data informada para o campo :attribute não respeita o formato :format.',
    'different' => 'Os campos :attribute e :other devem conter valores diferentes.',
    'digits' => 'O campo :attribute deve conter :digits dígitos.',
    'digits_between' => 'O campo :attribute deve conter entre :min a :max dígitos.',
    'dimensions' => 'O campo :attribute deverá conter uma dimensão de imagem válida.',
    'distinct' => 'O campo :attribute contém um valor duplicado.',
    'email' => 'O campo :attribute não contém um endereço de email válido.',
    'ends_with' => 'The :attribute must end with one of the following: :values.',
    'exists' => 'O valor selecionado para o campo :attribute é inválido.',
    'file' => 'O campo :attribute deverá conter um ficheiro.',
    'filled' => 'É obrigatória a indicação de um valor para o campo :attribute.',
    'gt' => [
        'numeric' => 'The :attribute must be greater than :value.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'string' => 'The :attribute must be greater than :value characters.',
        'array' => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal :value.',
        'file' => 'The :attribute must be greater than or equal :value kilobytes.',
        'string' => 'The :attribute must be greater than or equal :value characters.',
        'array' => 'The :attribute must have :value items or more.',
    ],
    'image' => 'O campo :attribute deve conter uma imagem.',
    'in' => 'O campo :attribute não contém um valor válido.',
    'in_array' => 'O campo :attribute não existe em :other.',
    'integer' => 'O campo :attribute deve conter um número inteiro.',
    'ip' => 'O campo :attribute deve conter um IP válido.',
    'ipv4' => 'O campo :attribute deverá conter um IPv4 válido.',
    'ipv6' => 'O campo :attribute deverá conter um IPv6 válido.',
    'json' => 'O campo :attribute deve conter uma string JSON válida.',
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute must be less than or equal :value.',
        'file' => 'The :attribute must be less than or equal :value kilobytes.',
        'string' => 'The :attribute must be less than or equal :value characters.',
        'array' => 'The :attribute must not have more than :value items.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode conter um valor superior a :max.',
        'file' => 'O campo :attribute não pode conter um arquivo com mais de :max kilobytes.',
        'string' => 'O campo :attribute não pode conter mais de :max caracteres.',
        'array' => 'O campo :attribute deve conter no máximo :max itens.',
    ],
    'mimes' => 'O campo :attribute deve conter um arquivo do tipo: :values.',
    'mimetypes' => 'O campo :attribute deverá conter um ficheiro do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute deve conter um número superior ou igual a :min.',
        'file' => 'O campo :attribute deve conter um arquivo com no mínimo :min kilobytes.',
        'string' => 'O campo :attribute deve conter no mínimo :min caracteres.',
        'array' => 'O campo :attribute deve conter no mínimo :min itens.',
    ],
    'not_in' => 'O campo :attribute contém um valor inválido.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => 'O campo :attribute deve conter um valor numérico.',
    'password' => 'The password is incorrect.',
    'present' => 'O campo :attribute deve estar presente.',
    'regex' => 'O formato do valor informado no campo :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_if' => 'O campo :attribute é obrigatório quando o valor do campo :other é igual a :value.',
    'required_unless' => 'O campo :attribute é obrigatório a menos que :other esteja presente em :values.',
    'required_with' => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'O campo :attribute é obrigatório quando :values não está presente.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum dos :values está presente.',
    'same' => 'Os campos :attribute e :other devem conter valores iguais.',
    'size' => [
        'numeric' => 'O campo :attribute deve conter o número :size.',
        'file' => 'O campo :attribute deve conter um arquivo com o tamanho de :size kilobytes.',
        'string' => 'O campo :attribute deve conter :size caracteres.',
        'array' => 'O campo :attribute deve conter :size itens.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values.',
    'string' => 'O campo :attribute deve ser uma string.',
    'timezone' => 'O campo :attribute deve conter um fuso horário válido.',
    'unique' => 'O valor informado para o campo :attribute já está em uso.',
    'uploaded' => 'O upload do ficheiro :attribute falhou.',
    'url' => 'O formato da URL informada para o campo :attribute é inválido.',
    'uuid' => 'The :attribute must be a valid UUID.',

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
            'rule-name' => 'mensagem-personalizada',
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
            'numeric' => '{field} may not be greater than {max}.',
            'string' => '{field} may not be greater than {max} characters.',
        ],
        'required' => '{field} is required.',
        'url' => '{field} is not a valid URL.',
    ],

];
