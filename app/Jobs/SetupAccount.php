<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Module;
use App\Models\Emotion;
use App\Models\Currency;
use App\Models\Template;
use App\Models\Information;
use App\Models\TemplatePage;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use App\Models\UserNotificationChannel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Settings\ManageGenders\Services\CreateGender;
use App\Settings\ManagePronouns\Services\CreatePronoun;
use App\Settings\ManageTemplates\Services\CreateModule;
use App\Settings\ManageTemplates\Services\CreateTemplate;
use App\Settings\ManageTemplates\Services\CreateTemplatePage;
use App\Settings\ManageAddressTypes\Services\CreateAddressType;
use App\Settings\ManagePetCategories\Services\CreatePetCategory;
use App\Settings\ManageTemplates\Services\AssociateModuleToTemplatePage;
use App\Settings\ManageRelationshipTypes\Services\CreateRelationshipGroupType;
use App\Settings\ManageNotificationChannels\Services\CreateUserNotificationChannel;
use App\Settings\ManageContactInformationTypes\Services\CreateContactInformationType;

class SetupAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The user instance.
     *
     * @var User
     */
    public User $user;

    /**
     * The template instance.
     *
     * @var Template
     */
    protected $template;

    /**
     * The position instance.
     *
     * @var int
     */
    protected int $position = 1;

    /**
     * Create a new job instance.
     *
     * @param  User  $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->populateCurrencies();
        $this->addNotificationChannel();
        $this->addTemplate();
        $this->addTemplatePageContactInformation();
        $this->addTemplatePageFeed();
        $this->addTemplatePageSocial();
        $this->addFirstInformation();
    }

    /**
     * Populate currencies in the account.
     *
     * @return void
     */
    private function populateCurrencies(): void
    {
        $currencies = Currency::get();
        foreach ($currencies as $currency) {
            $this->user->account->currencies()->attach($currency->id);
        }
    }

    /**
     * Add the first notification channel based on the email address of the user.
     *
     * @return void
     */
    private function addNotificationChannel(): void
    {
        $channel = (new CreateUserNotificationChannel)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('app.notification_channel_email'),
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => $this->user->email,
            'verify_email' => false,
            'preferred_time' => '09:00',
        ]);

        $channel->verified_at = Carbon::now();
        $channel->active = true;
        $channel->save();
    }

    /**
     * Add the first template.
     */
    private function addTemplate(): void
    {
        $request = [
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_template_name'),
        ];

        $this->template = (new CreateTemplate)->execute($request);
    }

    private function addTemplatePageContactInformation(): void
    {
        // the contact information page is automatically created when we
        // create the template
        $templatePageContact = TemplatePage::where('template_id', $this->template->id)
            ->where('type', TemplatePage::TYPE_CONTACT)
            ->first();

        // avatar
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_avatar'),
            'type' => Module::TYPE_AVATAR,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // names
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_names'),
            'type' => Module::TYPE_CONTACT_NAMES,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // important dates
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_important_dates'),
            'type' => Module::TYPE_IMPORTANT_DATES,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // gender/pronouns
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_gender_pronoun'),
            'type' => Module::TYPE_GENDER_PRONOUN,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // labels
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_labels'),
            'type' => Module::TYPE_LABELS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // companies
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_companies'),
            'type' => Module::TYPE_COMPANY,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageFeed(): void
    {
        $templatePageFeed = (new CreateTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_feed'),
            'can_be_deleted' => true,
        ]);
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_feed'),
            'type' => Module::TYPE_FEED,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageFeed->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageSocial(): void
    {
        $templatePageSocial = (new CreateTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_social'),
            'can_be_deleted' => true,
        ]);

        // Notes
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_notes'),
            'type' => Module::TYPE_NOTES,
            'can_be_deleted' => false,
            'pagination' => 3,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);

        // Reminders
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_reminders'),
            'type' => Module::TYPE_REMINDERS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);

        // Loans
        $module = (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_loans'),
            'type' => Module::TYPE_LOANS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);
    }

    private function addFirstInformation(): void
    {
        $this->addGenders();
        $this->addPronouns();
        $this->addGroupTypes();
        $this->addRelationshipTypes();
        $this->addAddressTypes();
        $this->addContactInformation();
        $this->addPetCategories();
        $this->addEmotions();
    }

    /**
     * Add the default genders in the account.
     */
    private function addGenders(): void
    {
        $types = collect([
            trans('account.gender_male'),
            trans('account.gender_female'),
            trans('account.gender_other'),
        ]);

        foreach ($types as $type) {
            $request = [
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'name' => $type,
            ];

            (new CreateGender)->execute($request);
        }
    }

    /**
     * Add the default pronouns in the account.
     */
    private function addPronouns(): void
    {
        $pronouns = collect([
            trans('account.pronoun_he_him'),
            trans('account.pronoun_she_her'),
            trans('account.pronoun_they_them'),
            trans('account.pronoun_per_per'),
            trans('account.pronoun_ve_ver'),
            trans('account.pronoun_xe_xem'),
            trans('account.pronoun_ze_hir'),
        ]);

        foreach ($pronouns as $pronoun) {
            $request = [
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'name' => $pronoun,
            ];

            (new CreatePronoun)->execute($request);
        }
    }

    /**
     * Add the default group types in the account.
     */
    private function addGroupTypes(): void
    {
    }

    private function addRelationshipTypes(): void
    {
        // Love type
        $group = (new CreateRelationshipGroupType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.relationship_type_love'),
        ]);

        DB::table('relationship_types')->insert([
            [
                'name' => trans('account.relationship_type_partner'),
                'name_reverse_relationship' => trans('account.relationship_type_partner'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_spouse'),
                'name_reverse_relationship' => trans('account.relationship_type_spouse'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_date'),
                'name_reverse_relationship' => trans('account.relationship_type_date'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_lover'),
                'name_reverse_relationship' => trans('account.relationship_type_lover'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_inlovewith'),
                'name_reverse_relationship' => trans('account.relationship_type_lovedby'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_lovedby'),
                'name_reverse_relationship' => trans('account.relationship_type_inlovewith'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_ex'),
                'name_reverse_relationship' => trans('account.relationship_type_ex'),
                'relationship_group_type_id' => $group->id,
            ],
        ]);

        // Family type
        $group = (new CreateRelationshipGroupType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.relationship_type_family'),
        ]);

        DB::table('relationship_types')->insert([
            [
                'name' => trans('account.relationship_type_parent'),
                'name_reverse_relationship' => trans('account.relationship_type_child'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_child'),
                'name_reverse_relationship' => trans('account.relationship_type_parent'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_sibling'),
                'name_reverse_relationship' => trans('account.relationship_type_sibling'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_grandparent'),
                'name_reverse_relationship' => trans('account.relationship_type_grandchild'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_grandchild'),
                'name_reverse_relationship' => trans('account.relationship_type_grandparent'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_uncle'),
                'name_reverse_relationship' => trans('account.relationship_type_nephew'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_nephew'),
                'name_reverse_relationship' => trans('account.relationship_type_uncle'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_cousin'),
                'name_reverse_relationship' => trans('account.relationship_type_cousin'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_godfather'),
                'name_reverse_relationship' => trans('account.relationship_type_godson'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_godson'),
                'name_reverse_relationship' => trans('account.relationship_type_godfather'),
                'relationship_group_type_id' => $group->id,
            ],
        ]);

        // Friend
        $group = (new CreateRelationshipGroupType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.relationship_type_friend_title'),
        ]);

        DB::table('relationship_types')->insert([
            [
                'name' => trans('account.relationship_type_friend'),
                'name_reverse_relationship' => trans('account.relationship_type_friend'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_bestfriend'),
                'name_reverse_relationship' => trans('account.relationship_type_bestfriend'),
                'relationship_group_type_id' => $group->id,
            ],
        ]);

        // Work
        $group = (new CreateRelationshipGroupType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.relationship_type_work'),
        ]);

        DB::table('relationship_types')->insert([
            [
                'name' => trans('account.relationship_type_colleague'),
                'name_reverse_relationship' => trans('account.relationship_type_colleague'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_boss'),
                'name_reverse_relationship' => trans('account.relationship_type_subordinate'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_subordinate'),
                'name_reverse_relationship' => trans('account.relationship_type_boss'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_mentor'),
                'name_reverse_relationship' => trans('account.relationship_type_protege'),
                'relationship_group_type_id' => $group->id,
            ],
            [
                'name' => trans('account.relationship_type_protege'),
                'name_reverse_relationship' => trans('account.relationship_type_mentor'),
                'relationship_group_type_id' => $group->id,
            ],
        ]);
    }

    private function addAddressTypes(): void
    {
        $addresses = collect([
            trans('account.address_type_home'),
            trans('account.address_type_secondary_residence'),
            trans('account.address_type_work'),
            trans('account.address_type_chalet'),
        ]);

        foreach ($addresses as $address) {
            (new CreateAddressType)->execute([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'name' => $address,
            ]);
        }
    }

    private function addContactInformation(): void
    {
        $information = (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_email'),
            'protocol' => 'mailto:',
        ]);
        $information->can_be_deleted = false;
        $information->type = 'email';
        $information->save();

        $information = (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_phone'),
            'protocol' => 'tel:',
        ]);
        $information->can_be_deleted = false;
        $information->type = 'phone';
        $information->save();

        (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_facebook'),
        ]);
        (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_twitter'),
        ]);
        (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_whatsapp'),
        ]);
        (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_telegram'),
        ]);
        (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_hangouts'),
        ]);
        (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_linkedin'),
        ]);
        (new CreateContactInformationType)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_instagram'),
        ]);
    }

    private function addPetCategories(): void
    {
        $categories = collect([
            trans('account.pets_dog'),
            trans('account.pets_cat'),
            trans('account.pets_bird'),
            trans('account.pets_fish'),
            trans('account.pets_hamster'),
            trans('account.pets_horse'),
            trans('account.pets_rabbit'),
            trans('account.pets_rat'),
            trans('account.pets_reptile'),
            trans('account.pets_small_animal'),
            trans('account.pets_other'),
        ]);

        foreach ($categories as $category) {
            (new CreatePetCategory)->execute([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'name' => $category,
            ]);
        }
    }

    private function addEmotions(): void
    {
        DB::table('emotions')->insert([
            [
                'account_id' => $this->user->account_id,
                'name' => trans('app.emotion_negative'),
                'type' => Emotion::TYPE_NEGATIVE,
            ],
            [
                'account_id' => $this->user->account_id,
                'name' => trans('app.emotion_neutral'),
                'type' => Emotion::TYPE_NEUTRAL,
            ],
            [
                'account_id' => $this->user->account_id,
                'name' => trans('app.emotion_positive'),
                'type' => Emotion::TYPE_POSITIVE,
            ],
        ]);
    }
}
