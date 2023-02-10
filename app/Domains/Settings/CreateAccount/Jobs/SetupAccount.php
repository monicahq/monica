<?php

namespace App\Domains\Settings\CreateAccount\Jobs;

use App\Domains\Settings\ManageAddressTypes\Services\CreateAddressType;
use App\Domains\Settings\ManageCallReasons\Services\CreateCallReason;
use App\Domains\Settings\ManageCallReasons\Services\CreateCallReasonType;
use App\Domains\Settings\ManageContactInformationTypes\Services\CreateContactInformationType;
use App\Domains\Settings\ManageGenders\Services\CreateGender;
use App\Domains\Settings\ManageGroupTypes\Services\CreateGroupType;
use App\Domains\Settings\ManageGroupTypes\Services\CreateGroupTypeRole;
use App\Domains\Settings\ManageNotificationChannels\Services\CreateUserNotificationChannel;
use App\Domains\Settings\ManagePetCategories\Services\CreatePetCategory;
use App\Domains\Settings\ManagePostTemplates\Services\CreatePostTemplate;
use App\Domains\Settings\ManagePostTemplates\Services\CreatePostTemplateSection;
use App\Domains\Settings\ManagePronouns\Services\CreatePronoun;
use App\Domains\Settings\ManageRelationshipTypes\Services\CreateRelationshipGroupType;
use App\Domains\Settings\ManageTemplates\Services\AssociateModuleToTemplatePage;
use App\Domains\Settings\ManageTemplates\Services\CreateModule;
use App\Domains\Settings\ManageTemplates\Services\CreateTemplate;
use App\Domains\Settings\ManageTemplates\Services\CreateTemplatePage;
use App\Interfaces\ServiceInterface;
use App\Models\Currency;
use App\Models\Emotion;
use App\Models\Module;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\UserNotificationChannel;
use App\Services\QueuableService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SetupAccount extends QueuableService implements ServiceInterface
{
    /**
     * The template instance.
     *
     * @var Template
     */
    protected $template;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
        ];
    }

    /**
     * Execute the service.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

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
            $this->account()->currencies()->attach($currency->id);
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
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('app.notification_channel_email'),
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => $this->author->email,
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
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.default_template_name'),
        ];

        $this->template = (new CreateTemplate())->execute($request);
    }

    private function addTemplatePageContactInformation(): void
    {
        // the contact information page is automatically created when we
        // create the template
        $templatePageContact = $this->template->pages()
            ->where('type', TemplatePage::TYPE_CONTACT)
            ->first();

        // avatar
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_avatar'),
            'type' => Module::TYPE_AVATAR,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // names
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_names'),
            'type' => Module::TYPE_CONTACT_NAMES,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // family summary
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_family_summary'),
            'type' => Module::TYPE_FAMILY_SUMMARY,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // important dates
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_important_dates'),
            'type' => Module::TYPE_IMPORTANT_DATES,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // gender/pronouns
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_gender_pronoun'),
            'type' => Module::TYPE_GENDER_PRONOUN,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // labels
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_labels'),
            'type' => Module::TYPE_LABELS,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // companies
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_companies'),
            'type' => Module::TYPE_COMPANY,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);

        // religions
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_religions'),
            'type' => Module::TYPE_RELIGIONS,
            'can_be_deleted' => false,
            'reserved_to_contact_information' => true,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageContact->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageFeed(): void
    {
        $templatePageFeed = (new CreateTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_feed'),
            'can_be_deleted' => true,
        ]);
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_feed'),
            'type' => Module::TYPE_FEED,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageFeed->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageContact(): void
    {
        $template = (new CreateTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_contact'),
            'can_be_deleted' => true,
        ]);

        // Addresses
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_addresses'),
            'type' => Module::TYPE_ADDRESSES,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $template->id,
            'module_id' => $module->id,
        ]);

        // Contact information
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_contact_information'),
            'type' => Module::TYPE_CONTACT_INFORMATION,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $template->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageSocial(): void
    {
        $templatePageSocial = (new CreateTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_social'),
            'can_be_deleted' => true,
        ]);

        // Relationships
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_relationships'),
            'type' => Module::TYPE_RELATIONSHIPS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);

        // Pets
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_pets'),
            'type' => Module::TYPE_PETS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);

        // Groups
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_groups'),
            'type' => Module::TYPE_GROUPS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageLifeEvents(): void
    {
        $templatePageSocial = (new CreateTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_life_events'),
            'can_be_deleted' => true,
        ]);

        // life events
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_life_events'),
            'type' => Module::TYPE_LIFE_EVENTS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);

        // goals
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_goals'),
            'type' => Module::TYPE_GOALS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageSocial->id,
            'module_id' => $module->id,
        ]);
    }

    private function addTemplatePageInformation(): void
    {
        $templatePageInformation = (new CreateTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_information'),
            'can_be_deleted' => true,
        ]);

        // Documents
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_documents'),
            'type' => Module::TYPE_DOCUMENTS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Documents
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_photos'),
            'type' => Module::TYPE_PHOTOS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Notes
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_notes'),
            'type' => Module::TYPE_NOTES,
            'can_be_deleted' => false,
            'pagination' => 3,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Reminders
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_reminders'),
            'type' => Module::TYPE_REMINDERS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Loans
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_loans'),
            'type' => Module::TYPE_LOANS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Tasks
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_tasks'),
            'type' => Module::TYPE_TASKS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Calls
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_calls'),
            'type' => Module::TYPE_CALLS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'template_id' => $this->template->id,
            'template_page_id' => $templatePageInformation->id,
            'module_id' => $module->id,
        ]);

        // Posts
        $module = (new CreateModule())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('app.module_posts'),
            'type' => Module::TYPE_POSTS,
            'can_be_deleted' => false,
        ]);
        (new AssociateModuleToTemplatePage())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
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
        $this->addGiftOccasions();
        $this->addGiftStates();
        $this->addPostTemplates();
        $this->addReligions();
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
                'account_id' => $this->author->account_id,
                'author_id' => $this->author->id,
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
                'account_id' => $this->author->account_id,
                'author_id' => $this->author->id,
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
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('account.group_type_family'),
        ]);
        (new CreateGroupTypeRole())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'group_type_id' => $groupType->id,
            'label' => trans('account.group_type_family_role_parent'),
        ]);
        (new CreateGroupTypeRole())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'group_type_id' => $groupType->id,
            'label' => trans('account.group_type_family_role_child'),
        ]);

        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('account.group_type_couple_without_children'),
        ]);
        (new CreateGroupTypeRole())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'group_type_id' => $groupType->id,
            'label' => trans('account.group_type_couple_role'),
        ]);

        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('account.group_type_club'),
        ]);

        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('account.group_type_association'),
        ]);

        $groupType = (new CreateGroupType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('account.group_type_roomates'),
        ]);
    }

    private function addRelationshipTypes(): void
    {
        // Love type
        $group = (new CreateRelationshipGroupType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
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
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
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
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
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
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
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
                'account_id' => $this->author->account_id,
                'author_id' => $this->author->id,
                'name' => $address,
            ]);
        }
    }

    private function addCallReasonTypes(): void
    {
        $type = (new CreateCallReasonType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('account.default_call_reason_types_personal'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_advice'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_say_hello'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_need_anything'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_respect'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_story'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_personal_love'),
        ]);

        // business
        $type = (new CreateCallReasonType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('account.default_call_reason_types_business'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_business_purchases'),
        ]);
        (new CreateCallReason())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'call_reason_type_id' => $type->id,
            'label' => trans('account.default_call_reason_business_partnership'),
        ]);
    }

    private function addContactInformation(): void
    {
        $information = (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('account.contact_information_type_email'),
            'protocol' => 'mailto:',
        ]);
        $information->can_be_deleted = false;
        $information->type = 'email';
        $information->save();

        $information = (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('account.contact_information_type_phone'),
            'protocol' => 'tel:',
        ]);
        $information->can_be_deleted = false;
        $information->type = 'phone';
        $information->save();

        (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('account.contact_information_type_facebook'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('account.contact_information_type_twitter'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('account.contact_information_type_whatsapp'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('account.contact_information_type_telegram'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('account.contact_information_type_hangouts'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'name' => trans('account.contact_information_type_linkedin'),
        ]);
        (new CreateContactInformationType())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
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
                'account_id' => $this->author->account_id,
                'author_id' => $this->author->id,
                'name' => $category,
            ]);
        }
    }

    private function addEmotions(): void
    {
        DB::table('emotions')->insert([
            [
                'account_id' => $this->author->account_id,
                'name' => trans('app.emotion_negative'),
                'type' => Emotion::TYPE_NEGATIVE,
            ],
            [
                'account_id' => $this->author->account_id,
                'name' => trans('app.emotion_neutral'),
                'type' => Emotion::TYPE_NEUTRAL,
            ],
            [
                'account_id' => $this->author->account_id,
                'name' => trans('app.emotion_positive'),
                'type' => Emotion::TYPE_POSITIVE,
            ],
        ]);
    }

    private function addGiftOccasions(): void
    {
        DB::table('gift_occasions')->insert([
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_occasion_birthday'),
                'position' => 1,
            ],
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_occasion_anniversary'),
                'position' => 2,
            ],
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_occasion_christmas'),
                'position' => 3,
            ],
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_occasion_just_because'),
                'position' => 4,
            ],
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_occasion_wedding'),
                'position' => 5,
            ],
        ]);
    }

    private function addGiftStates(): void
    {
        DB::table('gift_states')->insert([
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_state_idea'),
                'position' => 1,
            ],
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_state_searched'),
                'position' => 2,
            ],
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_state_found'),
                'position' => 3,
            ],
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_state_bought'),
                'position' => 4,
            ],
            [
                'account_id' => $this->author->account_id,
                'label' => trans('account.gift_state_offered'),
                'position' => 5,
            ],
        ]);
    }

    private function addPostTemplates(): void
    {
        // default template
        $postTemplate = (new CreatePostTemplate())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('settings.personalize_post_templates_default_template'),
            'can_be_deleted' => false,
        ]);

        (new CreatePostTemplateSection())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'post_template_id' => $postTemplate->id,
            'label' => trans('settings.personalize_post_templates_default_template_section'),
            'can_be_deleted' => false,
        ]);

        // inspirational template
        $postTemplate = (new CreatePostTemplate())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'label' => trans('settings.personalize_post_templates_default_template_inspirational'),
            'can_be_deleted' => true,
        ]);

        (new CreatePostTemplateSection())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'post_template_id' => $postTemplate->id,
            'label' => trans('settings.personalize_post_templates_default_template_section_grateful'),
            'can_be_deleted' => true,
        ]);
        (new CreatePostTemplateSection())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'post_template_id' => $postTemplate->id,
            'label' => trans('settings.personalize_post_templates_default_template_section_daily_affirmation'),
            'can_be_deleted' => true,
        ]);
        (new CreatePostTemplateSection())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'post_template_id' => $postTemplate->id,
            'label' => trans('settings.personalize_post_templates_default_template_section_better'),
            'can_be_deleted' => true,
        ]);
        (new CreatePostTemplateSection())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'post_template_id' => $postTemplate->id,
            'label' => trans('settings.personalize_post_templates_default_template_section_day'),
            'can_be_deleted' => true,
        ]);
        (new CreatePostTemplateSection())->execute([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'post_template_id' => $postTemplate->id,
            'label' => trans('settings.personalize_post_templates_default_template_section_three_things'),
            'can_be_deleted' => true,
        ]);
    }

    private function addReligions(): void
    {
        DB::table('religions')->insert([
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_christianity',
                'position' => 1,
            ],
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_islam',
                'position' => 2,
            ],
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_hinduism',
                'position' => 3,
            ],
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_buddhism',
                'position' => 4,
            ],
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_shintoism',
                'position' => 5,
            ],
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_taoism',
                'position' => 6,
            ],
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_sikhism',
                'position' => 7,
            ],
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_judaism',
                'position' => 8,
            ],
            [
                'account_id' => $this->author->account_id,
                'translation_key' => 'account.religion_atheism',
                'position' => 9,
            ],
        ]);
    }
}
