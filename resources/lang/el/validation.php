<?php

/**
 * ⚠️ Editing not allowed except for 'en' language.
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

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute is not a valid date.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The :attribute must be a valid email address.',
    'ends_with' => 'Το :attribute πρέπει να τελειώνει με μια απο τις ακόλουθες τιμές: :values.',
    'exists' => 'Το επιλεγμένο :attribute δεν είναι έγκυρο.',
    'file' => 'Tο :attribute πρέπει να είναι αρχείο.',
    'filled' => 'To πεδίο :attribute πρέπει να έχει τιμή.',
    'gt' => [
        'numeric' => 'Το :attribute πρέπει να είναι μεγαλύτερο από :value.',
        'file' => 'To :attribute πρέπει να είναι μεγαλύτερο από :value kilobytes.',
        'string' => 'Tο :attribute πρέπει να έχει περισσότερους από :value χαρακτήρες.',
        'array' => 'Το :attribute πρέπει να περιέχει περισσότερα από :value αντικείμενα.',
    ],
    'gte' => [
        'numeric' => 'Το :attribute πρέπει να είναι μεγαλύτερο ή ίσο από :value.',
        'file' => 'Το :attribute πρέπει να είναι μεγαλύτερο ή ίσο με :value kilobytes.',
        'string' => 'To :attribute πρέπει να είναι μεγαλύτερο ή ίσο από :value χαρακτήρες.',
        'array' => 'Το :attribute πρέπει να έχει :value αντικείμενα ή παραπάνω.',
    ],
    'image' => 'Tο :attribute πρέπει να είναι εικόνα.',
    'in' => 'Το επιλεγμένο :attribute δεν είναι έγκυρο.',
    'in_array' => 'Το πεδίο :attribute δεν υπάρχει σε :other.',
    'integer' => 'To :attribute πρέπει να είναι ακέραιος αριθμός.',
    'ip' => 'Το :attribute πρέπει να είναι έγκυρη διεύθυνση IP.',
    'ipv4' => 'Το :attribute πρέπει να είναι μια έγκυρη διεύθυνση IPv4.',
    'ipv6' => 'Το :attribute πρέπει να είναι μια έγκυρη IPv6 διεύθυνση.',
    'json' => 'Το :attribute πρέπει να είναι μια έγκυρη συμβολοσειρά JSON.',
    'lt' => [
        'numeric' => 'Το :attribute πρέπει να είναι μικρότερο του :value.',
        'file' => 'To :attribute πρέπει να είναι μικρότερο από :value kilobytes.',
        'string' => 'To :attribute πρέπει να είναι μικρότερο από :value kilobytes.',
        'array' => 'Tο :attribute πρέπει να έχει λιγότερα από :value αντικείμενα.',
    ],
    'lte' => [
        'numeric' => 'Το :attribute πρέπει να είναι μικρότερο ή ίσο του :value.',
        'file' => 'Το :attribute πρέπει να είναι μικρότερο ή ίσο του :value kilobytes.',
        'string' => 'Το :attribute πρέπει να είναι μικρότερο ή ίσο του :value kilobytes.',
        'array' => 'Tο :attribute δεν πρέπει να έχει περισσότερα από :value αντικείμενα.',
    ],
    'max' => [
        'numeric' => 'Tο :attribute δεν μπορεί να είναι μεγαλύτερο από :max.',
        'file' => 'To :attribute δεν μπορεί να είναι μεγαλύτερο από :max kilobytes.',
        'string' => 'Το :attribute δεν μπορεί να είναι μεγαλύτερο από :max χαρακτήρες.',
        'array' => 'Το :attribute δεν μπορεί να περιέχει περισσότερα από :max αντικείμενα.',
    ],
    'mimes' => 'Το :attribute πρέπει να είναι ένα αρχείου τύπου: :values.',
    'mimetypes' => 'Το :attribute πρέπει να είναι ένα αρχείου τύπου: :values.',
    'min' => [
        'numeric' => 'To :attribute πρέπει να είναι τουλάχιστον :min.',
        'file' => 'To :attribute πρέπει να είναι τουλάχιστον :min kilobytes.',
        'string' => 'Το :attribute πρέπει να είναι τουλάχιστον :min χαρακτήρες.',
        'array' => 'The :attribute must have at least :min items.',
    ],
    'not_in' => 'Το επιλεγμένο :attribute δεν είναι έγκυρο.',
    'not_regex' => 'Η μορφή του :attribute δεν είναι έγκυρη.',
    'numeric' => 'To :attribute πρέπει να είναι αριθμός.',
    'password' => 'Ο κωδικός πρόσβασης είναι εσφαλμένος.',
    'present' => 'Tο πεδίο :attribute δεν πρέπει να παραλείπεται.',
    'regex' => 'Η μορφή του :attribute δεν είναι έγκυρη.',
    'required' => 'Το πεδίο :attribute είναι υποχρεωτικό.',
    'required_if' => 'Το πεδίο :attribute είναι απαραίτητο όταν η τιμή του :other είναι :value.',
    'required_unless' => 'Το πεδίο :attribute είναι απαραίτητο εκτός αν το :other περιέχεται στα :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'Το πεδίο :attribute είναι υποχρεωτικό όταν κανένα από τα :values δεν εμφανίζονται.',
    'same' => 'Το :attribute και :other πρέπει να ταιριάζουν.',
    'size' => [
        'numeric' => 'Το :attribute πρέπει να είναι :size.',
        'file' => 'Το :attribute πρέπει να είναι :size kilobytes.',
        'string' => 'Το :attribute πρέπει να είναι :size χαρακτήρες.',
        'array' => 'Το :attribute πρέπει να περιέχει :size αντικείμενα.',
    ],
    'starts_with' => 'Το :attribute πρέπει να αρχίζει με μια από τις ακόλουθες τιμές: :values.',
    'string' => 'Το :attribute πρέπει να είναι κείμενο.',
    'timezone' => 'Το :attribute πρέπει να είναι μία έγκυρη ζώνη.',
    'unique' => 'Το :attribute δεν είναι διαθέσιμο.',
    'uploaded' => 'Το :attribute απέτυχε να μεταφορτωθεί.',
    'url' => 'Η μορφή του :attribute δεν είναι έγκυρη.',
    'uuid' => 'Tο :attribute πρέπει να είναι ένα έγκυρο UUID.',

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
            'numeric' => '{field} may not be greater than {max}.',
            'string' => '{field} may not be greater than {max} characters.',
        ],
        'required' => '{field} is required.',
        'url' => '{field} is not a valid URL.',
    ],

];
