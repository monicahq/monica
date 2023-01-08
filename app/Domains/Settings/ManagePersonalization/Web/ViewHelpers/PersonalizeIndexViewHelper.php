<?php

namespace App\Domains\Settings\ManagePersonalization\Web\ViewHelpers;

class PersonalizeIndexViewHelper
{
    public static function data(): array
    {
        return [
            'url' => [
                'settings' => route('settings.index'),
                'back' => route('settings.index'),
                'manage_relationships' => route('settings.personalize.relationship.index'),
                'manage_genders' => route('settings.personalize.gender.index'),
                'manage_pronouns' => route('settings.personalize.pronoun.index'),
                'manage_address_types' => route('settings.personalize.address_type.index'),
                'manage_pet_categories' => route('settings.personalize.pet_category.index'),
                'manage_contact_information_types' => route('settings.personalize.contact_information_type.index'),
                'manage_templates' => route('settings.personalize.template.index'),
                'manage_modules' => route('settings.personalize.module.index'),
                'manage_currencies' => route('settings.personalize.currency.index'),
                'manage_call_reasons' => route('settings.personalize.call_reasons.index'),
                'manage_gift_occasions' => route('settings.personalize.gift_occasions.index'),
                'manage_gift_states' => route('settings.personalize.gift_states.index'),
                'manage_group_types' => route('settings.personalize.group_types.index'),
                'manage_post_templates' => route('settings.personalize.post_templates.index'),
                'manage_religions' => route('settings.personalize.religions.index'),
            ],
        ];
    }
}
