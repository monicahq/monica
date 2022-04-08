<?php

namespace App\Settings\ManageTemplates\Web\ViewHelpers;

use App\Models\Module;
use App\Models\TemplatePage;

class PersonalizeTemplatePageShowViewHelper
{
    public static function data(TemplatePage $templatePage): array
    {
        $allModules = $templatePage->template->account->modules()
            ->orderBy('name', 'asc')
            ->get();

        $modulesInPage = $templatePage->modules()
            ->orderBy('position', 'asc')
            ->get();

        $modulesInAccountCollection = collect();
        foreach ($allModules as $module) {
            $modulesInAccountCollection->push([
                'id' => $module->id,
                'name' => $module->name,
                'already_used' => $modulesInPage->contains($module),
                'url' => [
                    'destroy' => route('settings.personalize.template.template_page.module.destroy', [
                        'template' => $templatePage->template->id,
                        'page' => $templatePage->id,
                        'module' => $module->id,
                    ]),
                ],
            ]);
        }

        $modulesIntemplateCollection = collect();
        foreach ($modulesInPage as $module) {
            $modulesIntemplateCollection->push(self::dtoModule($templatePage, $module));
        }

        return [
            'page' => [
                'id' => $templatePage->id,
                'name' => $templatePage->name,
            ],
            'modules' => $modulesIntemplateCollection,
            'modules_in_account' => $modulesInAccountCollection,
            'url' => [
                'store' => route('settings.personalize.template.template_page.module.store', [
                    'template' => $templatePage->template->id,
                    'page' => $templatePage->id,
                ]),
            ],
        ];
    }

    public static function dtoModule(TemplatePage $templatePage, Module $module): array
    {
        return [
            'id' => $module->id,
            'name' => $module->name,
            'position' => $module->position,
            'url' => [
                'position' => route('settings.personalize.template.template_page.module.order.update', [
                    'template' => $templatePage->template->id,
                    'page' => $templatePage->id,
                    'module' => $module->id,
                ]),
                'destroy' => route('settings.personalize.template.template_page.module.destroy', [
                    'template' => $templatePage->template->id,
                    'page' => $templatePage->id,
                    'module' => $module->id,
                ]),
            ],
        ];
    }
}
