<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\DismissDuplicateCandidate;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactDuplicateCandidateResource;
use App\Models\ContactDuplicateCandidate;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactDuplicateDismissalController extends Controller
{
    public function update(Request $request, string $vault, string $candidate): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $dismissed = new DismissDuplicateCandidate(
            user: $request->user(),
            candidate: ContactDuplicateCandidate::query()
                ->where('vault_id', $vault->id)
                ->where('id', $candidate)
                ->firstOrFail(),
        )->execute();

        return new ContactDuplicateCandidateResource($dismissed)->response();
    }

    private function vault(Request $request, string $uuid): Vault
    {
        return $request->user()->account->vaults()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
