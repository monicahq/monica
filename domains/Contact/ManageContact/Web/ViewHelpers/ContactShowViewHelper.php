<?php

namespace App\Contact\ManageContact\Web\ViewHelpers;

use App\Models\User;
use App\Models\Module;
use App\Models\Contact;
use App\Models\TemplatePage;
use Illuminate\Support\Collection;
use App\Contact\ManageLoans\Web\ViewHelpers\ModuleLoanViewHelper;
use App\Contact\ManageNotes\Web\ViewHelpers\ModuleNotesViewHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Contact\ManageLabels\Web\ViewHelpers\ModuleLabelViewHelper;
use App\Contact\ManageAvatar\Web\ViewHelpers\ModuleAvatarViewHelper;
use App\Contact\ManageContactFeed\Web\ViewHelpers\ModuleFeedViewHelper;
use App\Contact\ManageReminders\Web\ViewHelpers\ModuleRemindersViewHelper;
use App\Contact\ManageJobInformation\Web\ViewHelpers\ModuleCompanyViewHelper;
use App\Contact\ManagePronouns\Web\ViewHelpers\ModuleGenderPronounViewHelper;
use App\Contact\ManageContactName\Web\ViewHelpers\ModuleContactNameViewHelper;
use App\Contact\ManageRelationships\Web\ViewHelpers\ModuleRelationshipViewHelper;
use App\Contact\ManageContactImportantDates\Web\ViewHelpers\ModuleImportantDatesViewHelper;

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
            'template_pages' => $templatesPagesCollection,
            'contact_information' => self::getContactInformation($templatePages, $contact, $user),
            'modules' => $firstPage ? self::modules($firstPage, $contact, $user) : [],
            'url' => [
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
            'template_pages' => self::getTemplatePagesList($templatePages, $contact, $templatePage),
            'contact_information' => self::getContactInformation($templatePages, $contact, $user),
            'modules' => self::modules($templatePage, $contact, $user),
            'url' => [
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

            $modulesCollection->push([
                'id' => $module->id,
                'type' => $module->type,
                'data' => $data,
            ]);
        }

        return $modulesCollection;
    }
}
