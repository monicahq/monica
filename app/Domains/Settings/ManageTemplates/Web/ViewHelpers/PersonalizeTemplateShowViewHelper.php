<?php

namespace App\Domains\Settings\ManageTemplates\Web\ViewHelpers;

use App\Models\Template;
use App\Models\TemplatePage;

class PersonalizeTemplateShowViewHelper
{
    public static function data(Template $template): array
    {
        // get all the template pages EXCEPT the one about contact information
        // we can't delete this one, or rename it, as we need it to display
        // a contact page
        $templatePages = $template->pages()
            ->orderBy('position', 'asc')
            ->whereNull('type')
            ->get();

        $collection = collect();
        foreach ($templatePages as $templatePage) {
            $collection->push(self::dtoTemplatePage($template, $templatePage));
        }

        $contactInformationTemplatePage = $template->pages()
            ->firstWhere('type', 'contact_information');

        return [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
            ],
            'template_page_contact_information' => $contactInformationTemplatePage ? self::dtoTemplatePage($template, $contactInformationTemplatePage) : null,
            'template_pages' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'templates' => route('settings.personalize.template.index'),
                'template_page_store' => route('settings.personalize.template.template_page.store', [
                    'template' => $template->id,
                ]),
            ],
        ];
    }

    public static function dtoTemplatePage(Template $template, TemplatePage $page): array
    {
        return [
            'id' => $page->id,
            'name' => $page->name,
            'position' => $page->position,
            'can_be_deleted' => $page->can_be_deleted,
            'url' => [
                'show' => route('settings.personalize.template.template_page.show', [
                    'template' => $template->id,
                    'page' => $page->id,
                ]),
                'update' => route('settings.personalize.template.template_page.update', [
                    'template' => $template->id,
                    'page' => $page->id,
                ]),
                'order' => route('settings.personalize.template.template_page.order.update', [
                    'template' => $template->id,
                    'page' => $page->id,
                ]),
                'destroy' => route('settings.personalize.template.template_page.destroy', [
                    'template' => $template->id,
                    'page' => $page->id,
                ]),
            ],
        ];
    }
}
