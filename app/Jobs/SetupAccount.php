<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Module;
use App\Models\Template;
use App\Models\Attribute;
use App\Models\Information;
use App\Models\TemplatePage;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Account\ManageGenders\CreateGender;
use App\Services\Account\ManageTemplate\CreateModule;
use App\Services\Account\ManagePronouns\CreatePronoun;
use App\Services\Account\ManageTemplate\CreateTemplate;
use App\Services\Account\ManageTemplate\CreateAttribute;
use App\Services\Account\ManageTemplate\CreateInformation;
use App\Services\Account\ManageTemplate\CreateTemplatePage;
use App\Services\Account\ManageAddressTypes\CreateAddressType;
use App\Services\Account\ManagePetCategories\CreatePetCategory;
use App\Services\Account\ManageTemplate\AddDefaultValueToAttribute;
use App\Services\Account\ManageTemplate\AssociateModuleToTemplatePage;
use App\Services\Account\ManageTemplate\AssociateInformationToTemplate;
use App\Services\Account\ManageRelationshipTypes\CreateRelationshipGroupType;
use App\Services\Account\ManageContactInformationTypes\CreateContactInformationType;

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
     * The template page instance about the contact.
     *
     * @var TemplatePage
     */
    protected $templatePageContact;

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
        $this->addTemplate();
        $this->addTemplatePages();
        $this->addModules();
        $this->addFirstInformation();
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

    /**
     * Add the template pages.
     */
    private function addTemplatePages(): void
    {
        $request = [
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_contact_information'),
            'can_be_deleted' => false,
            'type' => TemplatePage::TYPE_CONTACT,
        ];
        $this->templatePageContact = (new CreateTemplatePage)->execute($request);

        $request = [
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'name' => trans('app.default_template_page_social'),
            'can_be_deleted' => true,
        ];
        (new CreateTemplatePage)->execute($request);
    }

    /**
     * Add the default modules.
     *
     * @return void
     */
    private function addModules(): void
    {
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
            'template_page_id' => $this->templatePageContact->id,
            'module_id' => $module->id,
        ]);

        (new CreateModule)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.module_notes'),
            'type' => Module::TYPE_NOTES,
            'can_be_deleted' => false,
        ]);
    }

    /**
     * Add the first information in the account, like gender, birthdate,...
     */
    private function addFirstInformation(): void
    {
        $this->addDescriptionField();
        $this->addGenderInformation();
        $this->addBirthdateInformation();
        $this->addAddressField();
        $this->addPetField();
        $this->addContactInformationField();
        $this->addFoodPreferences();
        $this->addHowWeMet();
        $this->addGenders();
        $this->addPronouns();
        $this->addGroupTypes();
        $this->addRelationshipTypes();
        $this->addAddressTypes();
        $this->addContactInformation();
        $this->addPetCategories();
    }

    /**
     * Add the description information.
     */
    private function addDescriptionField(): void
    {
        $information = (new CreateInformation)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_description_information'),
            'allows_multiple_entries' => false,
        ]);

        $this->associateToTemplate($information);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_description_information'),
            'type' => 'text',
        ]);
    }

    /**
     * Add the gender information.
     */
    private function addGenderInformation(): void
    {
        $information = (new CreateInformation)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_gender_information_name'),
            'allows_multiple_entries' => false,
        ]);

        $this->associateToTemplate($information);

        $attribute = (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_gender_information_name'),
            'type' => 'dropdown',
            'has_default_value' => true,
        ]);

        $this->addDefaultValue($attribute, trans('app.default_gender_man'));
        $this->addDefaultValue($attribute, trans('app.default_gender_woman'));
        $this->addDefaultValue($attribute, trans('app.default_gender_other'));
    }

    /**
     * Add the birthdate information.
     */
    private function addBirthdateInformation(): void
    {
        $information = (new CreateInformation)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_birthdate_information'),
            'allows_multiple_entries' => false,
        ]);

        $this->associateToTemplate($information);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_birthdate_information'),
            'type' => 'date',
            'has_default_value' => false,
        ]);
    }

    /**
     * Add the address field information.
     */
    private function addAddressField(): void
    {
        $information = (new CreateInformation)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_address_information'),
            'allows_multiple_entries' => true,
        ]);

        $this->associateToTemplate($information);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_address_label'),
            'type' => 'text',
        ]);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_address_city'),
            'type' => 'text',
        ]);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_address_province'),
            'type' => 'text',
        ]);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_address_postal_code'),
            'type' => 'text',
        ]);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_address_country'),
            'type' => 'text',
        ]);
    }

    /**
     * Add the pet field information.
     */
    private function addPetField(): void
    {
        $information = (new CreateInformation)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_pet_information'),
            'allows_multiple_entries' => true,
        ]);

        $this->associateToTemplate($information);

        $attribute = (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_pet_type'),
            'type' => 'dropdown',
            'has_default_value' => true,
        ]);

        $this->addDefaultValue($attribute, trans('app.default_pet_type_dog'));
        $this->addDefaultValue($attribute, trans('app.default_pet_type_cat'));

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_pet_name'),
            'type' => 'text',
        ]);
    }

    /**
     * Add the contact information panel.
     */
    private function addContactInformationField(): void
    {
        $information = (new CreateInformation)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_contact_information_information'),
            'allows_multiple_entries' => true,
        ]);

        $this->associateToTemplate($information);

        $attribute = (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_contact_information_type_attribute'),
            'type' => 'dropdown',
            'has_default_value' => true,
        ]);

        $this->addDefaultValue($attribute, trans('app.default_contact_information_facebook'));
        $this->addDefaultValue($attribute, trans('app.default_contact_information_email'));
        $this->addDefaultValue($attribute, trans('app.default_contact_information_twitter'));

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_contact_information_value'),
            'type' => 'text',
        ]);
    }

    /**
     * Add the food preferences panel.
     */
    private function addFoodPreferences(): void
    {
        $information = (new CreateInformation)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_food_preferences_information'),
            'allows_multiple_entries' => false,
        ]);

        $this->associateToTemplate($information);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_food_preferences_information'),
            'type' => 'textarea',
        ]);
    }

    /**
     * Add how you met panel.
     */
    private function addHowWeMet(): void
    {
        $information = (new CreateInformation)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'name' => trans('app.default_how_we_met_information'),
            'allows_multiple_entries' => false,
        ]);

        $this->associateToTemplate($information);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_how_we_met_description'),
            'type' => 'textarea',
        ]);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_how_we_met_contact'),
            'type' => 'contact',
        ]);

        (new CreateAttribute)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'information_id' => $information->id,
            'name' => trans('app.default_how_we_met_date'),
            'type' => 'date',
        ]);
    }

    /**
     * Add a default value to an attribute.
     *
     * @param  Attribute  $attribute
     * @param  string  $name
     */
    private function addDefaultValue(Attribute $attribute, string $name): void
    {
        $request = [
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'attribute_id' => $attribute->id,
            'value' => $name,
        ];

        (new AddDefaultValueToAttribute)->execute($request);
    }

    /**
     * Associate the information to the template.
     *
     * @param  Information  $information
     */
    private function associateToTemplate(Information $information): void
    {
        (new AssociateInformationToTemplate)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'template_id' => $this->template->id,
            'information_id' => $information->id,
            'position' => $this->position,
        ]);

        $this->position++;
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
}
