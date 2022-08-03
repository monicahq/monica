<?php

namespace App\Jobs;

use App\Models\Currency;
use App\Models\Emotion;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Module;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Settings\ManageActivityTypes\Services\CreateActivityType;
use App\Settings\ManageAddressTypes\Services\CreateAddressType;
use App\Settings\ManageCallReasons\Services\CreateCallReason;
use App\Settings\ManageCallReasons\Services\CreateCallReasonType;
use App\Settings\ManageContactInformationTypes\Services\CreateContactInformationType;
use App\Settings\ManageGenders\Services\CreateGender;
use App\Settings\ManageGroupTypes\Services\CreateGroupType;
use App\Settings\ManageGroupTypes\Services\CreateGroupTypeRole;
use App\Settings\ManageNotificationChannels\Services\CreateUserNotificationChannel;
use App\Settings\ManagePetCategories\Services\CreatePetCategory;
use App\Settings\ManagePronouns\Services\CreatePronoun;
use App\Settings\ManageRelationshipTypes\Services\CreateRelationshipGroupType;
use App\Settings\ManageTemplates\Services\AssociateModuleToTemplatePage;
use App\Settings\ManageTemplates\Services\CreateModule;
use App\Settings\ManageTemplates\Services\CreateTemplate;
use App\Settings\ManageTemplates\Services\CreateTemplatePage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SetupAccount implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        $this->addTemplatePageContact();
        $this->addTemplatePageSocial();
        $this->addTemplatePageLifeEvents();
        $this->addTemplatePageInformation();
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
        $channel = (new CreateUserNotificationChannel())->execute([
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

        $this->template = (new CreateTemplate())->execute($request);
    }

    private function addTemplatePageContactInformation(): void
    {
        // the contact information page is automatically created when we
        // create the template
        $templatePageContact = TemplatePage::where('template_id', $this->template->id)
            ->where('type', TemplatePage::TYPE_CONTACT)
            ->first();

        // avatar
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_avatar'),
            'type' => Module::TYPE_AVATAR,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // names
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_names'),
            'type' => Module::TYPE_CONTACT_NAMES,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // family summary
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_family_summary'),
            'type' => Module::TYPE_FAMILY_SUMMARY,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // important dates
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_important_dates'),
            'type' => Module::TYPE_IMPORTANT_DATES,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // gender/pronouns
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_gender_pronoun'),
            'type' => Module::TYPE_GENDER_PRONOUN,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // labels
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_labels'),
            'type' => Module::TYPE_LABELS,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // companies
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_companies'),
            'type' => Module::TYPE_COMPANY,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageFeed(): void
    {
        $templatePageFeed = (new CreateTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_feed'),
            'can_be_deleted' => true,
        ]);
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_feed'),
            'type' => Module::TYPE_FEED,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageFeed->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageContact(): void
    {
        $template = (new CreateTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_contact'),
            'can_be_deleted' => true,
        ]);

        // Addresses
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_addresses'),
            'type' => Module::TYPE_ADDRESSES,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $template->id,
            'module_id' => $module->id,
        ]);

        // Contact information
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_contact_information'),
            'type' => Module::TYPE_CONTACT_INFORMATION,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $template->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageSocial(): void
    {
        $templatePageSocial = (new CreateTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_social'),
            'can_be_deleted' => true,
        ]);

        // Relationships
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_relationships'),
            'type' => Module::TYPE_RELATIONSHIPS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);

        // Pets
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_pets'),
            'type' => Module::TYPE_PETS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);

        // Groups
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_groups'),
            'type' => Module::TYPE_GROUPS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageLifeEvents(): void
    {
        $templatePageSocial = (new CreateTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_life_events'),
            'can_be_deleted' => true,
        ]);

        // goals
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_goals'),
            'type' => Module::TYPE_GOALS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageInformation(): void
    {
        $templatePageInformation = (new CreateTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_information'),
            'can_be_deleted' => true,
        ]);

        // Documents
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_documents'),
            'type' => Module::TYPE_DOCUMENTS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Documents
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_photos'),
            'type' => Module::TYPE_PHOTOS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Notes
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_notes'),
            'type' => Module::TYPE_NOTES,
            'can_be_deleted' => false,
            'pagination' => 3,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Reminders
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_reminders'),
            'type' => Module::TYPE_REMINDERS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Loans
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_loans'),
            'type' => Module::TYPE_LOANS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Tasks
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_tasks'),
            'type' => Module::TYPE_TASKS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Calls
        $module = (new CreateModule())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_calls'),
            'type' => Module::TYPE_CALLS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
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
        $this->addCallReasonTypes();
        $this->addContactInformation();
        $this->addPetCategories();
        $this->addEmotions();
        $this->addActivityTypes();
        $this->addLifeEventCategories();
        $this->addGiftOccasions();
        $this->addGiftStates();
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

            (new CreateGender())->execute($request);
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

            (new CreatePronoun())->execute($request);
        }
    }

    /**
     * Add the default group types in the account.
     */
    private function addGroupTypes(): void
    {
        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.group_type_family'),
        ]);
        (new CreateGroupTypeRole())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'group_type_id' => $groupType->id,
            'label' => trans('account.group_type_family_role_parent'),
        ]);
        (new CreateGroupTypeRole())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'group_type_id' => $groupType->id,
            'label' => trans('account.group_type_family_role_child'),
        ]);

        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.group_type_couple_without_children'),
        ]);
        (new CreateGroupTypeRole())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'group_type_id' => $groupType->id,
            'label' => trans('account.group_type_couple_role'),
        ]);

        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.group_type_club'),
        ]);

        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.group_type_association'),
        ]);

        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.group_type_roomates'),
        ]);
    }

    private function addRelationshipTypes(): void
    {
        // Love type
        $group = (new CreateRelationshipGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.relationship_type_love'),
            'can_be_deleted' => false,
            'type' => RelationshipGroupType::TYPE_LOVE,
        ]);

        DB::table('relationship_types')->insert([
            [
                'name' => trans('account.relationship_type_partner'),
                'name_reverse_relationship' => trans('account.relationship_type_partner'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => false,
                'type' => RelationshipType::TYPE_LOVE,
            ],
            [
                'name' => trans('account.relationship_type_spouse'),
                'name_reverse_relationship' => trans('account.relationship_type_spouse'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => false,
                'type' => RelationshipType::TYPE_LOVE,
            ],
            [
                'name' => trans('account.relationship_type_date'),
                'name_reverse_relationship' => trans('account.relationship_type_date'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_lover'),
                'name_reverse_relationship' => trans('account.relationship_type_lover'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_inlovewith'),
                'name_reverse_relationship' => trans('account.relationship_type_lovedby'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_ex'),
                'name_reverse_relationship' => trans('account.relationship_type_ex'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
        ]);

        // Family type
        $group = (new CreateRelationshipGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.relationship_type_family'),
            'can_be_deleted' => false,
            'type' => RelationshipGroupType::TYPE_FAMILY,
        ]);

        DB::table('relationship_types')->insert([
            [
                'name' => trans('account.relationship_type_parent'),
                'name_reverse_relationship' => trans('account.relationship_type_child'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => false,
                'type' => RelationshipType::TYPE_CHILD,
            ],
            [
                'name' => trans('account.relationship_type_sibling'),
                'name_reverse_relationship' => trans('account.relationship_type_sibling'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_grandparent'),
                'name_reverse_relationship' => trans('account.relationship_type_grandchild'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_uncle'),
                'name_reverse_relationship' => trans('account.relationship_type_nephew'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_cousin'),
                'name_reverse_relationship' => trans('account.relationship_type_cousin'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_godfather'),
                'name_reverse_relationship' => trans('account.relationship_type_godson'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
        ]);

        // Friend
        $group = (new CreateRelationshipGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.relationship_type_friend_title'),
            'can_be_deleted' => true,
        ]);

        DB::table('relationship_types')->insert([
            [
                'name' => trans('account.relationship_type_friend'),
                'name_reverse_relationship' => trans('account.relationship_type_friend'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_bestfriend'),
                'name_reverse_relationship' => trans('account.relationship_type_bestfriend'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
        ]);

        // Work
        $group = (new CreateRelationshipGroupType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.relationship_type_work'),
            'can_be_deleted' => true,
        ]);

        DB::table('relationship_types')->insert([
            [
                'name' => trans('account.relationship_type_colleague'),
                'name_reverse_relationship' => trans('account.relationship_type_colleague'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_subordinate'),
                'name_reverse_relationship' => trans('account.relationship_type_boss'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
            ],
            [
                'name' => trans('account.relationship_type_mentor'),
                'name_reverse_relationship' => trans('account.relationship_type_protege'),
                'relationship_group_type_id' => $group->id,
                'can_be_deleted' => true,
                'type' => null,
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
            (new CreateAddressType())->execute([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'name' => $address,
            ]);
        }
    }

    private function addCallReasonTypes(): void
    {
        $type = (new CreateCallReasonType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.default_call_reason_types_personal'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_advice'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_say_hello'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_need_anything'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_respect'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_story'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_love'),
        ]);

        // business
        $type = (new CreateCallReasonType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.default_call_reason_types_business'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_business_purchases'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_business_partnership'),
        ]);
    }

    private function addContactInformation(): void
    {
        $information = (new CreateContactInformationType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_email'),
            'protocol' => 'mailto:',
        ]);
        $information->can_be_deleted = false;
        $information->type = 'email';
        $information->save();

        $information = (new CreateContactInformationType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_phone'),
            'protocol' => 'tel:',
        ]);
        $information->can_be_deleted = false;
        $information->type = 'phone';
        $information->save();

        (new CreateContactInformationType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_facebook'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_twitter'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_whatsapp'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_telegram'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_hangouts'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('account.contact_information_type_linkedin'),
        ]);
        (new CreateContactInformationType())->execute([
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
            (new CreatePetCategory())->execute([
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

    private function addActivityTypes(): void
    {
        $type = (new CreateActivityType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.activity_type_category_simple_activities'),
        ]);

        DB::table('activities')->insert([
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_just_hung_out'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_watched_movie_at_home'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_talked_at_home'),
            ],
        ]);

        $type = (new CreateActivityType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.activity_type_category_sport'),
        ]);

        DB::table('activities')->insert([
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_did_sport_activities_together'),
            ],
        ]);

        $type = (new CreateActivityType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.activity_type_category_food'),
        ]);

        DB::table('activities')->insert([
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_ate_at_his_place'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_went_bar'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_ate_at_home'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_picnicked'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_ate_restaurant'),
            ],
        ]);

        $type = (new CreateActivityType())->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'label' => trans('account.activity_type_category_cultural_activities'),
        ]);

        DB::table('activities')->insert([
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_went_theater'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_went_concert'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_went_play'),
            ],
            [
                'activity_type_id' => $type->id,
                'label' => trans('account.activity_type_went_museum'),
            ],
        ]);
    }

    private function addLifeEventCategories(): void
    {
        $categoryId = DB::table('life_event_categories')->insertGetId([
            'account_id' => $this->user->account_id,
            'label_translation_key' => 'account.default_life_event_category_travel_experiences',
            'can_be_deleted' => false,
            'type' => LifeEventCategory::TYPE_TRAVEL_EXPERIENCES,
        ]);

        DB::table('life_event_types')->insert([
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_activity',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_ACTIVITIES,
                'position' => 1,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_travel',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_TRAVEL,
                'position' => 2,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_sport',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_SPORT,
                'position' => 3,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_hobby',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_HOBBY,
                'position' => 4,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_instrument',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_INSTRUMENT,
                'position' => 5,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_language',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_LANGUAGE,
                'position' => 6,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_tattoo_or_piercing',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_TATTOO_OR_PIERCING,
                'position' => 7,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_license',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_LICENSE,
                'position' => 8,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_achievement_or_award',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_ACHIEVEMENT_OR_AWARD,
                'position' => 9,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_changed_beliefs',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_CHANGED_BELIEFS,
                'position' => 10,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_first_word',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_FIRST_WORD,
                'position' => 11,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_first_kiss',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_FIRST_KISS,
                'position' => 12,
            ],
        ]);

        $categoryId = DB::table('life_event_categories')->insertGetId([
            'account_id' => $this->user->account_id,
            'label_translation_key' => 'account.default_life_event_category_work_education',
            'can_be_deleted' => false,
            'type' => LifeEventCategory::TYPE_WORK_EDUCATION,
        ]);

        DB::table('life_event_types')->insert([
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_job',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_JOB,
                'position' => 1,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_retirement',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_RETIREMENT,
                'position' => 2,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_school',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_SCHOOL,
                'position' => 3,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_study_abroad',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_STUDY_ABROAD,
                'position' => 4,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_volunteer_work',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_VOLUNTEER_WORK,
                'position' => 5,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_published_book_or_paper',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_PUBLISHED_BOOK_OR_PAPER,
                'position' => 6,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_military_service',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_MILITARY_SERVICE,
                'position' => 7,
            ],
        ]);

        $categoryId = DB::table('life_event_categories')->insertGetId([
            'account_id' => $this->user->account_id,
            'label_translation_key' => 'account.default_life_event_category_family_relationships',
            'can_be_deleted' => false,
            'type' => LifeEventCategory::TYPE_FAMILY_RELATIONSHIPS,
        ]);

        DB::table('life_event_types')->insert([
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_first_met',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_FIRST_MET,
                'position' => 1,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_relationship',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_RELATIONSHIP,
                'position' => 2,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_engagement',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_ENGAGEMENT,
                'position' => 3,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_marriage',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_MARRIAGE,
                'position' => 4,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_anniversary',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_ANNIVERSARY,
                'position' => 5,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_expecting_a_baby',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_EXPECTING_A_BABY,
                'position' => 6,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_child',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_CHILD,
                'position' => 7,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_family_member',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_FAMILY_MEMBER,
                'position' => 8,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_pet',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_PET,
                'position' => 9,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_end_of_relationship',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_END_OF_RELATIONSHIP,
                'position' => 10,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_loss_of_a_loved_one',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_LOSS_OF_A_LOVED_ONE,
                'position' => 11,
            ],
        ]);

        $categoryId = DB::table('life_event_categories')->insertGetId([
            'account_id' => $this->user->account_id,
            'label_translation_key' => 'account.default_life_event_category_home_living',
            'can_be_deleted' => false,
            'type' => LifeEventCategory::TYPE_HOME_LIVING,
        ]);

        DB::table('life_event_types')->insert([
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_moved',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_MOVED,
                'position' => 1,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_bought_a_home',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_BOUGHT_A_HOME,
                'position' => 2,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_home_improvement',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_HOME_IMPROVEMENT,
                'position' => 3,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_holidays',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_HOLIDAYS,
                'position' => 4,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_vehicle',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_VEHICLE,
                'position' => 5,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_roommate',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_ROOMMATE,
                'position' => 6,
            ],
        ]);

        $categoryId = DB::table('life_event_categories')->insertGetId([
            'account_id' => $this->user->account_id,
            'label_translation_key' => 'account.default_life_event_category_health_wellness',
            'can_be_deleted' => false,
            'type' => LifeEventCategory::TYPE_HEALTH_WELLNESS,
        ]);

        DB::table('life_event_types')->insert([
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_overcame_an_illness',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_OVERCAME_AN_ILLNESS,
                'position' => 1,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_quit_a_habit',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_QUIT_A_HABIT,
                'position' => 2,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_new_eating_habits',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_NEW_EATING_HABITS,
                'position' => 3,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_weight_loss',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_WEIGHT_LOSS,
                'position' => 4,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_wear_glass_or_contact',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_WEAR_GLASS_OR_CONTACT,
                'position' => 5,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_broken_bone',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_BROKEN_BONE,
                'position' => 6,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_removed_braces',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_REMOVED_BRACES,
                'position' => 7,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_surgery',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_SURGERY,
                'position' => 8,
            ],
            [
                'life_event_category_id' => $categoryId,
                'label_translation_key' => 'account.default_life_event_type_dentist',
                'can_be_deleted' => false,
                'type' => LifeEventType::TYPE_DENTIST,
                'position' => 9,
            ],
        ]);
    }

    private function addGiftOccasions(): void
    {
        DB::table('gift_occasions')->insert([
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_occasion_birthday'),
                'position' => 1,
            ],
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_occasion_anniversary'),
                'position' => 2,
            ],
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_occasion_christmas'),
                'position' => 3,
            ],
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_occasion_just_because'),
                'position' => 4,
            ],
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_occasion_wedding'),
                'position' => 5,
            ],
        ]);
    }

    private function addGiftStates(): void
    {
        DB::table('gift_states')->insert([
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_state_idea'),
                'position' => 1,
            ],
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_state_searched'),
                'position' => 2,
            ],
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_state_found'),
                'position' => 3,
            ],
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_state_bought'),
                'position' => 4,
            ],
            [
                'account_id' => $this->user->account_id,
                'label' => trans('account.gift_state_offered'),
                'position' => 5,
            ],
        ]);
    }
}
