<?php

namespace App\Domains\Settings\ManagePostTemplates\Web\ViewHelpers;

use App\Models\Account;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;

class PersonalizePostTemplateViewHelper
{
    public static function data(Account $account): array
    {
        $postTemplates = $account->postTemplates()
            ->orderBy('position', 'asc')
            ->with('postTemplateSections')
            ->get();

        $collection = collect();
        foreach ($postTemplates as $postTemplate) {
            $collection->push(self::dto($postTemplate));
        }

        return [
            'post_templates' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'store' => route('settings.personalize.post_templates.store'),
            ],
        ];
    }

    public static function dto(PostTemplate $postTemplate): array
    {
        $postTemplateSections = $postTemplate->postTemplateSections()
            ->orderBy('position', 'asc')
            ->get()
            ->map(function (PostTemplateSection $postTemplateSection) use ($postTemplate) {
                return self::dtoPostTemplateSection($postTemplate, $postTemplateSection);
            });

        return [
            'id' => $postTemplate->id,
            'label' => $postTemplate->label,
            'position' => $postTemplate->position,
            'can_be_deleted' => $postTemplate->can_be_deleted,
            'post_template_sections' => $postTemplateSections,
            'url' => [
                'store' => route('settings.personalize.post_templates.section.store', [
                    'postTemplate' => $postTemplate->id,
                ]),
                'position' => route('settings.personalize.post_templates.order.update', [
                    'postTemplate' => $postTemplate->id,
                ]),
                'update' => route('settings.personalize.post_templates.update', [
                    'postTemplate' => $postTemplate->id,
                ]),
                'destroy' => route('settings.personalize.post_templates.destroy', [
                    'postTemplate' => $postTemplate->id,
                ]),
            ],
        ];
    }

    public static function dtoPostTemplateSection(PostTemplate $postTemplate, PostTemplateSection $postTemplateSection): array
    {
        return [
            'id' => $postTemplateSection->id,
            'label' => $postTemplateSection->label,
            'position' => $postTemplateSection->position,
            'can_be_deleted' => $postTemplateSection->can_be_deleted,
            'post_template_id' => $postTemplate->id,
            'url' => [
                'position' => route('settings.personalize.post_templates.section.order.update', [
                    'postTemplate' => $postTemplate->id,
                    'section' => $postTemplateSection->id,
                ]),
                'update' => route('settings.personalize.post_templates.section.update', [
                    'postTemplate' => $postTemplate->id,
                    'section' => $postTemplateSection->id,
                ]),
                'destroy' => route('settings.personalize.post_templates.section.destroy', [
                    'postTemplate' => $postTemplate->id,
                    'section' => $postTemplateSection->id,
                ]),
            ],
        ];
    }
}
