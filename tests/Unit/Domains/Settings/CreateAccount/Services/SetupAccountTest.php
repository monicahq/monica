<?php

namespace Tests\Unit\Domains\Settings\CreateAccount\Services;

use App\Domains\Settings\CreateAccount\Jobs\SetupAccount;
use App\Models\Currency;
use App\Models\RelationshipGroupType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SetupAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sets_an_account_up(): void
    {
        Mail::fake();
        $user = $this->createAdministrator();

        $request = [
            'account_id' => $user->account->id,
            'author_id' => $user->id,
        ];

        SetupAccount::dispatchSync($request);

        $currency = Currency::first();

        $this->assertDatabaseHas('account_currencies', [
            'currency_id' => $currency->id,
            'account_id' => $user->account_id,
        ]);
        $this->assertEquals(
            164,
            Currency::count()
        );

        $this->assertDatabaseHas('user_notification_channels', [
            'user_id' => $user->id,
            'label' => trans('app.notification_channel_email'),
            'type' => 'email',
            'content' => $user->email,
            'active' => true,
        ]);

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'name' => 'Default template',
        ]);
        $this->assertDatabaseHas('template_pages', [
            'name' => trans('app.default_template_page_contact_information'),
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'name' => trans('app.default_template_page_life_events'),
            'can_be_deleted' => true,
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_notes'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_names'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_avatar'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_feed'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_gender_pronoun'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_labels'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_reminders'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_loans'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_companies'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_religions'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_tasks'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_calls'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_pets'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_goals'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_family_summary'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_addresses'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name' => trans('app.module_groups'),
        ]);

        $this->assertDatabaseHas('relationship_group_types', [
            'name' => trans('account.relationship_type_love'),
            'can_be_deleted' => false,
            'type' => RelationshipGroupType::TYPE_LOVE,
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name' => trans('account.relationship_type_family'),
            'can_be_deleted' => false,
            'type' => RelationshipGroupType::TYPE_FAMILY,
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name' => trans('account.relationship_type_work'),
            'can_be_deleted' => true,
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name' => trans('account.relationship_type_friend_title'),
            'can_be_deleted' => true,
        ]);

        $this->assertDatabaseHas('genders', [
            'account_id' => $user->account_id,
            'name' => trans('account.gender_male'),
        ]);
        $this->assertDatabaseHas('genders', [
            'account_id' => $user->account_id,
            'name' => trans('account.gender_other'),
        ]);
        $this->assertDatabaseHas('genders', [
            'account_id' => $user->account_id,
            'name' => trans('account.gender_female'),
        ]);

        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name' => trans('account.pronoun_he_him'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name' => trans('account.pronoun_she_her'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name' => trans('account.pronoun_they_them'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name' => trans('account.pronoun_per_per'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name' => trans('account.pronoun_ve_ver'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name' => trans('account.pronoun_xe_xem'),
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name' => trans('account.pronoun_ze_hir'),
        ]);

        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_email'),
            'protocol' => 'mailto:',
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_phone'),
            'protocol' => 'tel:',
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_facebook'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_twitter'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_whatsapp'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_telegram'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_hangouts'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_linkedin'),
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.contact_information_type_instagram'),
        ]);

        $this->assertDatabaseHas('address_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.address_type_home'),
        ]);
        $this->assertDatabaseHas('address_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.address_type_secondary_residence'),
        ]);
        $this->assertDatabaseHas('address_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.address_type_work'),
        ]);
        $this->assertDatabaseHas('address_types', [
            'account_id' => $user->account_id,
            'name' => trans('account.address_type_chalet'),
        ]);

        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_dog'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_cat'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_bird'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_fish'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_hamster'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_horse'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_rabbit'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_rat'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_reptile'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_small_animal'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name' => trans('account.pets_other'),
        ]);

        $this->assertDatabaseHas('call_reason_types', [
            'account_id' => $user->account_id,
            'label' => trans('account.default_call_reason_types_personal'),
        ]);
        $this->assertDatabaseHas('call_reason_types', [
            'account_id' => $user->account_id,
            'label' => trans('account.default_call_reason_types_business'),
        ]);
        $this->assertDatabaseHas('call_reasons', [
            'label' => trans('account.default_call_reason_business_purchases'),
        ]);
        $this->assertDatabaseHas('call_reasons', [
            'label' => trans('account.default_call_reason_business_partnership'),
        ]);
        $this->assertDatabaseHas('call_reasons', [
            'label' => trans('account.default_call_reason_personal_advice'),
        ]);
        $this->assertDatabaseHas('call_reasons', [
            'label' => trans('account.default_call_reason_personal_say_hello'),
        ]);
        $this->assertDatabaseHas('call_reasons', [
            'label' => trans('account.default_call_reason_personal_need_anything'),
        ]);
        $this->assertDatabaseHas('call_reasons', [
            'label' => trans('account.default_call_reason_personal_respect'),
        ]);
        $this->assertDatabaseHas('call_reasons', [
            'label' => trans('account.default_call_reason_personal_story'),
        ]);
        $this->assertDatabaseHas('call_reasons', [
            'label' => trans('account.default_call_reason_personal_love'),
        ]);

        $this->assertDatabaseHas('life_event_categories', [
            'label_translation_key' => 'account.default_life_event_category_work_education',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'label_translation_key' => 'account.default_life_event_category_family_relationships',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'label_translation_key' => 'account.default_life_event_category_home_living',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'label_translation_key' => 'account.default_life_event_category_travel_experiences',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'label_translation_key' => 'account.default_life_event_category_health_wellness',
            'can_be_deleted' => false,
        ]);

        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_job',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_retirement',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_school',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_study_abroad',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_volunteer_work',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_published_book_or_paper',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_military_service',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_first_met',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_relationship',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_engagement',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_marriage',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_anniversary',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_expecting_a_baby',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_child',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_family_member',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_pet',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_end_of_relationship',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_loss_of_a_loved_one',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_moved',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_bought_a_home',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_home_improvement',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_holidays',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_vehicle',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_roommate',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_overcame_an_illness',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_quit_a_habit',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_eating_habits',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_weight_loss',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_wear_glass_or_contact',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_broken_bone',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_removed_braces',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_surgery',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_dentist',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_sport',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_hobby',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_instrument',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_language',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_tattoo_or_piercing',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_new_license',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_travel',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_achievement_or_award',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_changed_beliefs',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_first_word',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'label_translation_key' => 'account.default_life_event_type_first_kiss',
            'can_be_deleted' => false,
        ]);

        $this->assertDatabaseHas('gift_occasions', [
            'label' => 'Birthday',
            'position' => 1,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'label' => 'Anniversary',
            'position' => 2,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'label' => 'Christmas',
            'position' => 3,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'label' => 'Just because',
            'position' => 4,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'label' => 'Wedding',
            'position' => 5,
        ]);

        $this->assertDatabaseHas('gift_states', [
            'label' => trans('account.gift_state_idea'),
            'position' => 1,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'label' => trans('account.gift_state_searched'),
            'position' => 2,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'label' => trans('account.gift_state_found'),
            'position' => 3,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'label' => trans('account.gift_state_bought'),
            'position' => 4,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'label' => trans('account.gift_state_offered'),
            'position' => 5,
        ]);

        $this->assertDatabaseHas('post_templates', [
            'label' => trans('settings.personalize_post_templates_default_template'),
            'position' => 1,
        ]);
        $this->assertDatabaseHas('post_templates', [
            'label' => trans('settings.personalize_post_templates_default_template_inspirational'),
            'position' => 2,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'label' => trans('settings.personalize_post_templates_default_template_section'),
            'position' => 1,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'label' => trans('settings.personalize_post_templates_default_template_section_grateful'),
            'position' => 1,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'label' => trans('settings.personalize_post_templates_default_template_section_daily_affirmation'),
            'position' => 2,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'label' => trans('settings.personalize_post_templates_default_template_section_better'),
            'position' => 3,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'label' => trans('settings.personalize_post_templates_default_template_section_day'),
            'position' => 4,
        ]);
        $this->assertDatabaseHas('post_template_sections', [
            'label' => trans('settings.personalize_post_templates_default_template_section_three_things'),
            'position' => 5,
        ]);

        $this->assertDatabaseHas('religions', [
            'translation_key' => 'account.religion_christian',
            'position' => 1,
        ]);
        $this->assertDatabaseHas('religions', [
            'translation_key' => 'account.religion_islam',
            'position' => 2,
        ]);
        $this->assertDatabaseHas('religions', [
            'translation_key' => 'account.religion_hinduism',
            'position' => 3,
        ]);
    }
}
