<?php

namespace App\Http\Controllers\Vault\Contact\ViewHelpers;

use App\Models\Contact;
use App\Models\TemplatePage;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ContactShowViewHelper
{
    public static function data(Contact $contact): array
    {
        $template = $contact->template;

        if ($template) {
            $templatePages = $template->pages()->orderBy('position', 'asc')->get();
        }

        return [
            'template_pages' => $template ? self::getTemplatePagesList($templatePages, $contact) : null,
            'contact_information' => $template ? self::getContactInformation($templatePages->first(), $contact) : null,
            'url' => [
            ],
        ];
    }

    private static function getTemplatePagesList(EloquentCollection $templatePages, Contact $contact): Collection
    {
        $pagesCollection = collect();
        foreach ($templatePages as $page) {
            if (! $page->can_be_deleted) {
                continue;
            }

            $pagesCollection->push([
                'id' => $page->id,
                'name' => $page->name,
                'url' => [
                    'show' => route('contact.page.show', [
                        'vault' => $contact->vault->id,
                        'contact' => $contact->id,
                        'page' => $page->id,
                    ]),
                ],
            ]);
        }

        return $pagesCollection;
    }

    private static function getContactInformation(TemplatePage $templatePage, Contact $contact): array
    {
        return  [
            'todo',
        ];
    }
}
