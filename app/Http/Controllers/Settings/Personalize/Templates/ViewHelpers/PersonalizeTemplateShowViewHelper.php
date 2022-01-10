<?php

namespace App\Http\Controllers\Settings\Personalize\Templates\ViewHelpers;

use App\Models\Template;
use App\Models\TemplatePage;

class PersonalizeTemplateShowViewHelper
{
    public static function data(Template $template): array
    {
        $templatePages = $template->pages()
            ->orderBy('position', 'asc')
            ->get();

        $collection = collect();
        foreach ($templatePages as $templatePage) {
            $collection->push(self::dtoTemplatePage($template, $templatePage));
        }

        return [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
            ],
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
            'url' => [
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
