<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Domains\Contact\ManageAvatar\Web\ViewHelpers\ModuleAvatarViewHelper;
use App\Domains\Contact\ManageCalls\Web\ViewHelpers\ModuleCallsViewHelper;
use App\Domains\Contact\ManageContactAddresses\Web\ViewHelpers\ModuleContactAddressesViewHelper;
use App\Domains\Contact\ManageContactImportantDates\Web\ViewHelpers\ModuleImportantDatesViewHelper;
use App\Domains\Contact\ManageContactInformation\Web\ViewHelpers\ModuleContactInformationViewHelper;
use App\Domains\Contact\ManageContactName\Web\ViewHelpers\ModuleContactNameViewHelper;
use App\Domains\Contact\ManageDocuments\Web\ViewHelpers\ModuleDocumentsViewHelper;
use App\Domains\Contact\ManageGoals\Web\ViewHelpers\ModuleGoalsViewHelper;
use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupsViewHelper;
use App\Domains\Contact\ManageGroups\Web\ViewHelpers\ModuleGroupsViewHelper;
use App\Domains\Contact\ManageJobInformation\Web\ViewHelpers\ModuleCompanyViewHelper;
use App\Domains\Contact\ManageLabels\Web\ViewHelpers\ModuleLabelViewHelper;
use App\Domains\Contact\ManageLifeEvents\Web\ViewHelpers\ModuleLifeEventViewHelper;
use App\Domains\Contact\ManageLoans\Web\ViewHelpers\ModuleLoanViewHelper;
use App\Domains\Contact\ManageNotes\Web\ViewHelpers\ModuleNotesViewHelper;
use App\Domains\Contact\ManagePets\Web\ViewHelpers\ModulePetsViewHelper;
use App\Domains\Contact\ManagePhotos\Web\ViewHelpers\ModulePhotosViewHelper;
use App\Domains\Contact\ManagePronouns\Web\ViewHelpers\ModuleGenderPronounViewHelper;
use App\Domains\Contact\ManageQuickFacts\Web\ViewHelpers\ContactModuleQuickFactViewHelper;
use App\Domains\Contact\ManageRelationships\Web\ViewHelpers\ModuleFamilySummaryViewHelper;
use App\Domains\Contact\ManageRelationships\Web\ViewHelpers\ModuleRelationshipViewHelper;
use App\Domains\Contact\ManageReligion\Web\ViewHelpers\ModuleReligionViewHelper;
use App\Domains\Contact\ManageReminders\Web\ViewHelpers\ModuleRemindersViewHelper;
use App\Domains\Contact\ManageTasks\Web\ViewHelpers\ModuleContactTasksViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\ModulePostsViewHelper;
use App\Helpers\StorageHelper;
use App\Models\Contact;
use App\Models\Module;
use App\Models\TemplatePage;
use App\Models\User;
use App\Models\VaultQuickFactsTemplate;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ContactShowViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $templatePages = $contact->template->pages()->orderBy('position', 'asc')->get();

        // get the first page to display in this default page
        $firstPage = $templatePages->firstWhere('type', '!=', TemplatePage::TYPE_CONTACT);
        $templatesPagesCollection = self::getTemplatePagesList($templatePages, $contact, $firstPage);

        return [
            'contact_name' => ModuleContactNameViewHelper::data($contact, $user),
            'listed' => $contact->listed,
            'template_pages' => $templatesPagesCollection,
            'contact_information' => self::getContactInformation($templatePages, $contact, $user),
            'group_summary_information' => GroupsViewHelper::summary($contact),
            'quick_fact_template_entries' => self::quickFacts($contact),
            'modules' => $firstPage ? self::modules($firstPage, $contact, $user) : [],
            'avatar' => [
                'uploadcare' => StorageHelper::uploadcare(),
                'canUploadFile' => StorageHelper::canUploadFile($contact->vault->account),
                'hasFile' => $contact->avatar['type'] === 'url',
            ],
            'options' => [
                'can_be_archived' => $user->getContactInVault($contact->vault)->id !== $contact->id,
                'can_be_deleted' => $user->getContactInVault($contact->vault)->id !== $contact->id,
            ],
            'url' => [
                'toggle_archive' => route('contact.archive.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'update_avatar' => route('contact.avatar.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'destroy_avatar' => route('contact.avatar.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'update_template' => route('contact.blank', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'move_contact' => route('contact.move.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'destroy' => route('contact.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'download_vcard' => route('contact.vcard.download', [
                    'vault' => $contact->vault,
                    'contact' => $contact,
                ]),
            ],
        ];
    }

    public static function dataForTemplatePage(Contact $contact, User $user, TemplatePage $templatePage): array
    {
        $templatePages = $contact->template->pages()->orderBy('position', 'asc')->get();

        return [
            'contact_name' => ModuleContactNameViewHelper::data($contact, $user),
            'listed' => $contact->listed,
            'template_pages' => self::getTemplatePagesList($templatePages, $contact, $templatePage),
            'contact_information' => self::getContactInformation($templatePages, $contact, $user),
            'group_summary_information' => GroupsViewHelper::summary($contact),
            'quick_fact_template_entries' => self::quickFacts($contact),
            'modules' => self::modules($templatePage, $contact, $user),
            'avatar' => [
                'uploadcare' => StorageHelper::uploadcare(),
                'canUploadFile' => StorageHelper::canUploadFile($contact->vault->account),
                'hasFile' => $contact->avatar['type'] === 'url',
            ],
            'options' => [
                'can_be_archived' => $user->getContactInVault($contact->vault)->id !== $contact->id,
                'can_be_deleted' => $user->getContactInVault($contact->vault)->id !== $contact->id,
            ],
            'url' => [
                'toggle_archive' => route('contact.archive.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'update_avatar' => route('contact.avatar.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'destroy_avatar' => route('contact.avatar.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'update_template' => route('contact.blank', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'move_contact' => route('contact.move.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'destroy' => route('contact.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'download_vcard' => route('contact.vcard.download', [
                    'vault' => $contact->vault,
                    'contact' => $contact,
                ]),
            ],
        ];
    }

    /**
     * @param  EloquentCollection<int,TemplatePage>  $templatePages
     */
    private static function getTemplatePagesList(EloquentCollection $templatePages, Contact $contact, ?TemplatePage $currentTemplatePage = null): Collection
    {
        return $templatePages->filter(fn (TemplatePage $page) => $page->type !== TemplatePage::TYPE_CONTACT)
            ->map(fn (TemplatePage $page) => [
                'id' => $page->id,
                'name' => $page->name,
                'selected' => $currentTemplatePage ? $page->id === $currentTemplatePage->id : null,
                'url' => [
                    'show' => route('contact.page.show', [
                        'vault' => $contact->vault->id,
                        'contact' => $contact->id,
                        'slug' => $page->slug,
                    ]),
                ],
            ]);
    }

    /**
     * @param  EloquentCollection<int,TemplatePage>  $templatePages
     */
    private static function getContactInformation(EloquentCollection $templatePages, Contact $contact, User $user): Collection
    {
        $contactInformationPage = $templatePages->firstWhere('type', TemplatePage::TYPE_CONTACT);
        $modules = $contactInformationPage->modules()->orderBy('position', 'asc')->get();

        $modulesCollection = collect();
        foreach ($modules as $module) {
            $data = [];
            if ($module->type == Module::TYPE_CONTACT_NAMES) {
                $data = ModuleContactNameViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_AVATAR) {
                $data = ModuleAvatarViewHelper::data($contact);
            }

            if ($module->type == Module::TYPE_GENDER_PRONOUN) {
                $data = ModuleGenderPronounViewHelper::data($contact);
            }

            if ($module->type == Module::TYPE_IMPORTANT_DATES) {
                $data = ModuleImportantDatesViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_LABELS) {
                $data = ModuleLabelViewHelper::data($contact);
            }

            if ($module->type == Module::TYPE_COMPANY) {
                $data = ModuleCompanyViewHelper::data($contact);
            }

            if ($module->type == Module::TYPE_FAMILY_SUMMARY) {
                $data = ModuleFamilySummaryViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_RELIGIONS) {
                $data = ModuleReligionViewHelper::data($contact);
            }

            $modulesCollection->push([
                'id' => $module->id,
                'type' => $module->type,
                'data' => $data,
            ]);
        }

        return $modulesCollection;
    }

    /**
     * Get the modules list and data in the given page.
     */
    public static function modules(TemplatePage $page, Contact $contact, User $user): Collection
    {
        $modules = $page->modules()->orderBy('position', 'asc')->get();

        $modulesCollection = collect();
        foreach ($modules as $module) {
            $data = [];
            if ($module->type == Module::TYPE_NOTES) {
                $data = ModuleNotesViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_FEED) {
                // this module loads data asynchroniously
                // so it needs an URL to load the data from
                $data = route('contact.feed.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]);
            }

            if ($module->type == Module::TYPE_REMINDERS) {
                $data = ModuleRemindersViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_LOANS) {
                $data = ModuleLoanViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_RELATIONSHIPS) {
                $data = ModuleRelationshipViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_TASKS) {
                $data = ModuleContactTasksViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_CALLS) {
                $data = ModuleCallsViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_PETS) {
                $data = ModulePetsViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_GOALS) {
                $data = ModuleGoalsViewHelper::data($contact);
            }

            if ($module->type == Module::TYPE_ADDRESSES) {
                $data = ModuleContactAddressesViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_GROUPS) {
                $data = ModuleGroupsViewHelper::data($contact);
            }

            if ($module->type == Module::TYPE_CONTACT_INFORMATION) {
                $data = ModuleContactInformationViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_DOCUMENTS) {
                $data = ModuleDocumentsViewHelper::data($contact);
            }

            if ($module->type == Module::TYPE_PHOTOS) {
                $data = ModulePhotosViewHelper::data($contact);
            }

            if ($module->type == Module::TYPE_POSTS) {
                $data = ModulePostsViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_LIFE_EVENTS) {
                $data = ModuleLifeEventViewHelper::data($contact, $user);
            }

            $modulesCollection->push([
                'id' => $module->id,
                'type' => $module->type,
                'data' => $data,
            ]);
        }

        return $modulesCollection;
    }

    public static function quickFacts(Contact $contact): array
    {
        $quickFactsTemplateEntries = $contact
            ->vault
            ->quickFactsTemplateEntries()
            ->get()
            ->map(fn (VaultQuickFactsTemplate $template) => [
                'id' => $template->id,
                'label' => $template->label,
                'url' => [
                    'show' => route('contact.quick_fact.show', [
                        'vault' => $contact->vault->id,
                        'contact' => $contact->id,
                        'template' => $template->id,
                    ]),
                ],
            ]);

        // get the quick facts of the first template
        $firstTemplate = $contact
            ->vault
            ->quickFactsTemplateEntries()
            ->first();

        $quickFacts = $firstTemplate ? ContactModuleQuickFactViewHelper::data($contact, $firstTemplate) : null;

        return [
            'show_quick_facts' => $contact->show_quick_facts,
            'templates' => $quickFactsTemplateEntries,
            'quick_facts' => $quickFacts,
            'url' => [
                'toggle' => route('contact.quick_fact.toggle', [
                    'vault' => $contact->vault->id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}
