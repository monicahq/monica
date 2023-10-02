<?php

namespace App\Domains\Settings\ManageTemplates\Web\ViewHelpers;

use App\Models\Account;
use App\Models\Template;

class PersonalizeTemplateIndexViewHelper
{
    public static function data(Account $account): array
    {
        $templates = $account->templates()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Template $template) => self::dtoTemplate($template));

        return [
            'templates' => $templates,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'template_store' => route('settings.personalize.template.store'),
            ],
        ];
    }

    public static function dtoTemplate(Template $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'can_be_deleted' => $template->can_be_deleted,
            'url' => [
                'show' => route('settings.personalize.template.show', [
                    'template' => $template->id,
                ]),
                'update' => route('settings.personalize.template.update', [
                    'template' => $template->id,
                ]),
                'destroy' => route('settings.personalize.template.destroy', [
                    'template' => $template->id,
                ]),
            ],
        ];
    }
}
