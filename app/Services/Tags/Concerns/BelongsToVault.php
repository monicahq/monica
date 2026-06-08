<?php

namespace App\Services\Tags\Concerns;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait BelongsToVault
{
    protected function vaultId(): string
    {
        return Auth::user()->vault_id;
    }

    protected function ensureTagBelongsToVault(Tag $tag): void
    {
        if ($tag->vault_id !== $this->vaultId()) {
            throw new NotFoundHttpException;
        }
    }

    protected function ensureContactBelongsToVault(Contact $contact): void
    {
        if ($contact->vault_id !== $this->vaultId()) {
            throw new NotFoundHttpException;
        }
    }
}
