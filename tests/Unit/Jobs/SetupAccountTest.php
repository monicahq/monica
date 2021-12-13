<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\SetupAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SetupAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sets_an_account_up(): void
    {
        $regis = $this->createAdministrator();

        SetupAccount::dispatch($regis);

        $this->assertDatabaseHas('templates', [
            'account_id' => $regis->account_id,
            'name' => 'Default template',
        ]);

        $this->assertDatabaseHas('information', [
            'account_id' => $regis->account_id,
            'name' => trans('app.default_description_information'),
            'allows_multiple_entries' => false,
        ]);

        $this->assertDatabaseHas('information', [
            'account_id' => $regis->account_id,
            'name' => trans('app.default_gender_information_name'),
            'allows_multiple_entries' => false,
        ]);

        $this->assertDatabaseHas('information', [
            'account_id' => $regis->account_id,
            'name' => trans('app.default_birthdate_information'),
            'allows_multiple_entries' => false,
        ]);

        $this->assertDatabaseHas('information', [
            'account_id' => $regis->account_id,
            'name' => trans('app.default_address_information'),
            'allows_multiple_entries' => true,
        ]);

        $this->assertDatabaseHas('information', [
            'account_id' => $regis->account_id,
            'name' => trans('app.default_pet_information'),
            'allows_multiple_entries' => true,
        ]);

        $this->assertDatabaseHas('information', [
            'account_id' => $regis->account_id,
            'name' => trans('app.default_contact_information_information'),
            'allows_multiple_entries' => true,
        ]);

        $this->assertDatabaseHas('information', [
            'account_id' => $regis->account_id,
            'name' => trans('app.default_food_preferences_information'),
            'allows_multiple_entries' => false,
        ]);

        $this->assertDatabaseHas('information', [
            'account_id' => $regis->account_id,
            'name' => trans('app.default_how_we_met_information'),
            'allows_multiple_entries' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_description_information'),
            'type' => 'text',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_gender_information_name'),
            'type' => 'dropdown',
            'has_default_value' => true,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_birthdate_information'),
            'type' => 'date',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_address_label'),
            'type' => 'text',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_address_city'),
            'type' => 'text',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_address_province'),
            'type' => 'text',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_address_postal_code'),
            'type' => 'text',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_address_country'),
            'type' => 'text',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_pet_type'),
            'type' => 'dropdown',
            'has_default_value' => true,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_pet_name'),
            'type' => 'text',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_contact_information_type_attribute'),
            'type' => 'dropdown',
            'has_default_value' => true,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_contact_information_value'),
            'type' => 'text',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_food_preferences_information'),
            'type' => 'textarea',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_how_we_met_description'),
            'type' => 'textarea',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_how_we_met_contact'),
            'type' => 'contact',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attributes', [
            'name' => trans('app.default_how_we_met_date'),
            'type' => 'date',
            'has_default_value' => false,
        ]);

        $this->assertDatabaseHas('attribute_default_values', [
            'value' => trans('app.default_gender_man'),
        ]);

        $this->assertDatabaseHas('attribute_default_values', [
            'value' => trans('app.default_gender_woman'),
        ]);

        $this->assertDatabaseHas('attribute_default_values', [
            'value' => trans('app.default_gender_other'),
        ]);

        $this->assertDatabaseHas('attribute_default_values', [
            'value' => trans('app.default_pet_type_dog'),
        ]);

        $this->assertDatabaseHas('attribute_default_values', [
            'value' => trans('app.default_pet_type_cat'),
        ]);

        $this->assertDatabaseHas('attribute_default_values', [
            'value' => trans('app.default_contact_information_facebook'),
        ]);

        $this->assertDatabaseHas('attribute_default_values', [
            'value' => trans('app.default_contact_information_email'),
        ]);

        $this->assertDatabaseHas('attribute_default_values', [
            'value' => trans('app.default_contact_information_twitter'),
        ]);

        $this->assertDatabaseHas('relationship_group_types', [
            'name' => trans('account.relationship_type_love'),
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name' => trans('account.relationship_type_family'),
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name' => trans('account.relationship_type_work'),
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name' => trans('account.relationship_type_friend_title'),
        ]);

        $this->assertDatabaseHas('genders', [
            'account_id' => $regis->account_id,
            'name' => trans('account.gender_male'),
        ]);
        $this->assertDatabaseHas('genders', [
            'account_id' => $regis->account_id,
            'name' => trans('account.gender_other'),
        ]);
        $this->assertDatabaseHas('genders', [
            'account_id' => $regis->account_id,
            'name' => trans('account.gender_female'),
        ]);

        $this->assertDatabaseHas('pronouns', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pronoun_he_him'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pronoun_she_her'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pronoun_they_them'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pronoun_per_per'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pronoun_ve_ver'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pronoun_xe_xem'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pronoun_ze_hir'),
        ]);

        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_email'),
            'protocol' => 'mailto:',
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_phone'),
            'protocol' => 'tel:',
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_facebook'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_twitter'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_whatsapp'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_telegram'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_hangouts'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_linkedin'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.contact_information_type_instagram'),
        ]);

        $this->assertDatabaseHas('address_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.address_type_home'),
        ]);
        $this->assertDatabaseHas('address_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.address_type_secondary_residence'),
        ]);
        $this->assertDatabaseHas('address_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.address_type_work'),
        ]);
        $this->assertDatabaseHas('address_types', [
            'account_id' => $regis->account_id,
            'name' => trans('account.address_type_chalet'),
        ]);
    }
}
