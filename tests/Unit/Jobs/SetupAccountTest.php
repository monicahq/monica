<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Models\Currency;
use App\Jobs\SetupAccount;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SetupAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sets_an_account_up(): void
    {
        Mail::fake();
        $regis = $this->createAdministrator();

        SetupAccount::dispatch($regis);

        $currency = Currency::first();

        $this->assertDatabaseHas('account_currencies', [
            'currency_id' => $currency->id,
            'account_id' => $regis->account_id,
        ]);
        $this->assertEquals(
            164,
            Currency::count()
        );

        $this->assertDatabaseHas('user_notification_channels', [
            'user_id' => $regis->id,
            'label' => trans('app.notification_channel_email'),
            'type' => 'email',
            'content' => $regis->email,
            'active' => true,
        ]);

        $this->assertDatabaseHas('templates', [
            'account_id' => $regis->account_id,
            'name' => 'Default template',
        ]);
        $this->assertDatabaseHas('template_pages', [
            'name' => trans('app.default_template_page_contact_information'),
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_notes'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_names'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_avatar'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_feed'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_gender_pronoun'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_labels'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_reminders'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_loans'),
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $regis->account_id,
            'name' => trans('app.module_companies'),
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

        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_dog'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_cat'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_bird'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_fish'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_hamster'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_horse'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_rabbit'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_rat'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_reptile'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_small_animal'),
        ]);
        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $regis->account_id,
            'name' => trans('account.pets_other'),
        ]);
    }
}
