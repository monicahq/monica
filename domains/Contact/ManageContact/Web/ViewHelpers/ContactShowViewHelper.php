<?php

namespace App\Contact\ManageContact\Web\ViewHelpers;

use App\Contact\ManageAvatar\Web\ViewHelpers\ModuleAvatarViewHelper;
use App\Contact\ManageCalls\Web\ViewHelpers\ModuleCallsViewHelper;
use App\Contact\ManageContactAddresses\Web\ViewHelpers\ModuleContactAddressesViewHelper;
use App\Contact\ManageContactFeed\Web\ViewHelpers\ModuleFeedViewHelper;
use App\Contact\ManageContactImportantDates\Web\ViewHelpers\ModuleImportantDatesViewHelper;
use App\Contact\ManageContactInformation\Web\ViewHelpers\ModuleContactInformationViewHelper;
use App\Contact\ManageContactName\Web\ViewHelpers\ModuleContactNameViewHelper;
use App\Contact\ManageDocuments\Web\ViewHelpers\ModuleDocumentsViewHelper;
use App\Contact\ManageGoals\Web\ViewHelpers\ModuleGoalsViewHelper;
use App\Contact\ManageGroups\Web\ViewHelpers\GroupsViewHelper;
use App\Contact\ManageGroups\Web\ViewHelpers\ModuleGroupsViewHelper;
use App\Contact\ManageJobInformation\Web\ViewHelpers\ModuleCompanyViewHelper;
use App\Contact\ManageLabels\Web\ViewHelpers\ModuleLabelViewHelper;
use App\Contact\ManageLoans\Web\ViewHelpers\ModuleLoanViewHelper;
use App\Contact\ManageNotes\Web\ViewHelpers\ModuleNotesViewHelper;
use App\Contact\ManagePets\Web\ViewHelpers\ModulePetsViewHelper;
use App\Contact\ManagePhotos\Web\ViewHelpers\ModulePhotosViewHelper;
use App\Contact\ManagePronouns\Web\ViewHelpers\ModuleGenderPronounViewHelper;
use App\Contact\ManageRelationships\Web\ViewHelpers\ModuleFamilySummaryViewHelper;
use App\Contact\ManageRelationships\Web\ViewHelpers\ModuleRelationshipViewHelper;
use App\Contact\ManageReminders\Web\ViewHelpers\ModuleRemindersViewHelper;
use App\Contact\ManageTasks\Web\ViewHelpers\ModuleContactTasksViewHelper;
use App\Models\Contact;
use App\Models\Module;
use App\Models\TemplatePage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ContactShowViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $templatePages = $contact->template->pages()->orderBy('position', 'asc')->get();

        // get the first page to display in this default page
        $firstPage = $templatePages->filter(function ($page) {
            return $page->type != TemplatePage::TYPE_CONTACT;
        })->first();

        if ($firstPage) {
            $templatesPagesCollection = self::getTemplatePagesList($templatePages, $contact, $firstPage);
        } else {
            $templatesPagesCollection = self::getTemplatePagesList($templatePages, $contact);
        }

        return [
            'contact_name' => ModuleContactNameViewHelper::data($contact, $user),
            'listed' => $contact->listed,
            'template_pages' => $templatesPagesCollection,
            'contact_information' => self::getContactInformation($templatePages, $contact, $user),
            'group_summary_information' => GroupsViewHelper::summary($contact),
            'modules' => $firstPage ? self::modules($firstPage, $contact, $user) : [],
            'options' => [
                'can_be_archived' => $user->getContactInVault($contact->vault)->id !== $contact->id,
                'can_be_deleted' => $user->getContactInVault($contact->vault)->id !== $contact->id,
            ],
            'url' => [
                'toggle_archive' => route('contact.archive.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'update_template' => route('contact.blank', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'destroy' => route('contact.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
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
            'modules' => self::modules($templatePage, $contact, $user),
            'options' => [
                'can_be_archived' => $user->getContactInVault($contact->vault)->id !== $contact->id,
                'can_be_deleted' => $user->getContactInVault($contact->vault)->id !== $contact->id,
            ],
            'url' => [
                'toggle_archive' => route('contact.archive.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'update_template' => route('contact.blank', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'destroy' => route('contact.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    private static function getTemplatePagesList(EloquentCollection $templatePages, Contact $contact, TemplatePage $currentTemplatePage = null): Collection
    {
        $templatePages = $templatePages->filter(function ($page) {
            return $page->type != TemplatePage::TYPE_CONTACT;
        });

        $pagesCollection = collect();
        foreach ($templatePages as $page) {
            if ($page->type == TemplatePage::TYPE_CONTACT) {
                continue;
            }

            $pagesCollection->push([
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

        return $pagesCollection;
    }

    private static function getContactInformation(EloquentCollection $templatePages, Contact $contact, User $user): Collection
    {
        $contactInformationPage = $templatePages->where('type', TemplatePage::TYPE_CONTACT)->first();
        $modules = $contactInformationPage->modules()->orderBy('position', 'asc')->get();

        $modulesCollection = collect();
        foreach ($modules as $module) {
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
            if ($module->type == Module::TYPE_NOTES) {
                $data = ModuleNotesViewHelper::data($contact, $user);
            }

            if ($module->type == Module::TYPE_FEED) {
                $data = ModuleFeedViewHelper::data($contact, $user);
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
                $data = ModuleGoalsViewHelper::data($contact, $user);
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

            $modulesCollection->push([
                'id' => $module->id,
                'type' => $module->type,
                'data' => $data,
            ]);
        }

        return $modulesCollection;
    }
}
