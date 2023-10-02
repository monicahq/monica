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

        $this->assertDatabaseHas('account_currency', [
            'currency_id' => $currency->id,
            'account_id' => $user->account_id,
        ]);
        $this->assertEquals(
            164,
            Currency::count()
        );

        $this->assertDatabaseHas('user_notification_channels', [
            'user_id' => $user->id,
            'label' => 'Email address',
            'type' => 'email',
            'content' => $user->email,
            'active' => true,
        ]);

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Default template',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'name_translation_key' => 'Contact information',
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('template_pages', [
            'name_translation_key' => 'Life & goals',
            'can_be_deleted' => true,
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Notes',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Contact name',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Avatar',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Contact feed',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Gender and pronoun',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Labels',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Reminders',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Loans',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Job information',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Religions',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Tasks',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Calls',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Pets',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Goals',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Life',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Family summary',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Addresses',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Groups',
        ]);
        $this->assertDatabaseHas('modules', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Posts',
        ]);

        $this->assertDatabaseHas('relationship_group_types', [
            'name_translation_key' => 'Love',
            'can_be_deleted' => false,
            'type' => RelationshipGroupType::TYPE_LOVE,
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name_translation_key' => 'Family',
            'can_be_deleted' => false,
            'type' => RelationshipGroupType::TYPE_FAMILY,
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name_translation_key' => 'Work',
            'can_be_deleted' => true,
        ]);
        $this->assertDatabaseHas('relationship_group_types', [
            'name_translation_key' => 'Friend',
            'can_be_deleted' => true,
        ]);

        $this->assertDatabaseHas('genders', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Male',
        ]);
        $this->assertDatabaseHas('genders', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Other',
        ]);
        $this->assertDatabaseHas('genders', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Female',
        ]);

        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'he/him',
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'she/her',
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'they/them',
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'per/per',
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name_translation_key' => 've/ver',
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'xe/xem',
        ]);
        $this->assertDatabaseHas('pronouns', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'ze/hir',
        ]);

        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Email address',
            'protocol' => 'mailto:',
        ]);
        $this->assertDatabaseHas('contact_information_types', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Phone',
            'protocol' => 'tel:',
        ]);

        $this->assertDatabaseHas('address_types', [
            'account_id' => $user->account_id,
            'name_translation_key' => '🏡 Home',
        ]);

        $this->assertDatabaseHas('pet_categories', [
            'account_id' => $user->account_id,
            'name_translation_key' => 'Dog',
        ]);

        $this->assertDatabaseHas('call_reason_types', [
            'account_id' => $user->account_id,
            'label_translation_key' => 'Personal',
        ]);
        $this->assertDatabaseHas('call_reason_types', [
            'account_id' => $user->account_id,
            'label_translation_key' => 'Business',
        ]);

        $this->assertDatabaseHas('gift_occasions', [
            'label_translation_key' => 'Birthday',
            'position' => 1,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'label_translation_key' => 'Anniversary',
            'position' => 2,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'label_translation_key' => 'Christmas',
            'position' => 3,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'label_translation_key' => 'Just because',
            'position' => 4,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'label_translation_key' => 'Wedding',
            'position' => 5,
        ]);

        $this->assertDatabaseHas('religions', [
            'translation_key' => 'Christian',
            'position' => 1,
        ]);
    }
}
