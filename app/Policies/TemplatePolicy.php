<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StructureAppliesToEnum;
use App\Models\Template;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TemplatePolicy
{
    /**
     * Whether the template may be deleted.
     *
     * One a record still renders with stays, since that record is drawn with it.
     * The last contact template stays too: a new vault starts its owner's contact
     * from one, so without it no vault could be created again.
     */
    public function delete(User $user, Template $template): Response
    {
        if ((int) $template->versions()->sum('record_count') > 0) {
            return Response::deny(__('The template still renders records and stays.'));
        }

        if ($template->structure?->applies_to !== StructureAppliesToEnum::Contact) {
            return Response::allow();
        }

        if (Template::startingPointFor(accountId: $template->account_id, appliesTo: StructureAppliesToEnum::Contact, exceptTemplateId: $template->id) === null) {
            return Response::deny(__('This is the last template for contacts, and a new vault needs one, so it stays.'));
        }

        return Response::allow();
    }
}
