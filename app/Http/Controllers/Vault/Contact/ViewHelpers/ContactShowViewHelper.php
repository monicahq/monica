<?php

namespace App\Http\Controllers\Vault\Contact\ViewHelpers;

use App\Models\Module;
use App\Models\Contact;
use App\Models\TemplatePage;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Http\Controllers\Vault\Contact\Modules\ViewHelpers\ModuleNotesViewHelper;

class ContactShowViewHelper
{
    public static function data(Contact $contact): array
    {
        $templatePages = $contact->template->pages()->orderBy('position', 'asc')->get();

        // get the first page to display in this default page
        $firstPage = $templatePages->filter(function ($page) {
            return $page->type != TemplatePage::TYPE_CONTACT;
        })->first();

        return [
            'template_pages' => self::getTemplatePagesList($templatePages, $contact),
            'contact_information' => self::getContactInformation($templatePages, $contact),
            'modules' => $firstPage ? self::modules($firstPage, $contact) : [],
        ];
    }

    private static function getTemplatePagesList(EloquentCollection $templatePages, Contact $contact): Collection
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
                'selected' => $page->id === $templatePages->first()->id,
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

    private static function getContactInformation(EloquentCollection $templatePages, Contact $contact): array
    {
        $contactInformationPage = $templatePages->where('type', TemplatePage::TYPE_CONTACT)->first();

        return  [
        ];
    }

    /**
     * Get the modules list and data in the given page.
     */
    public static function modules(TemplatePage $page, Contact $contact): Collection
    {
        $modules = $page->modules()->orderBy('position', 'asc')->get();

        $modulesCollection = collect();
        foreach ($modules as $module) {
            if ($module->type == Module::TYPE_NOTES) {
                $data = ModuleNotesViewHelper::data($contact);
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
