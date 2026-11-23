<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContactPolicy
{
    public function create(User $user): Response
    {
        $limit = $user->account->contactLimit();

        if ($limit === null) {
            return Response::allow();
        }

        if ($user->account->contactCount() < $limit) {
            return Response::allow();
        }

        return Response::deny(trans_choice('The free plan holds :count contact across every vault, and this account already has it. Full access lifts the ceiling.|The free plan holds :count contacts across every vault, and this account already has them. Full access lifts the ceiling.', $limit));
    }

    public function delete(User $user, Contact $contact): Response
    {
        if ($contact->is_me) {
            return Response::deny(__('This is the contact that represents you, and it stays. Everything on the record is written from its point of view.'));
        }

        return Response::allow();
    }

    public function demote(User $user, Contact $contact): Response
    {
        if ($contact->is_me) {
            return Response::deny(__('This is the contact that represents you, and it stays a full contact.'));
        }

        if ($contact->is_archived) {
            return Response::deny(__(':name is archived, so they cannot become a partial contact.', ['name' => $contact->name]));
        }

        return Response::allow();
    }
}
