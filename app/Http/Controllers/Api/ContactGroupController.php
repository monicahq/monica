<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupMembershipResource;
use App\Models\Contact;
use App\Models\GroupMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactGroupController extends Controller
{
    public function index(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        $memberships = GroupMember::query()
            ->where('contact_id', $contact->id)
            ->whereHas('group', fn ($query) => $query->where('vault_id', $vault->id))
            ->with(GroupMembershipResource::RELATIONS)
            ->orderByRaw('case when left_on is null then 0 else 1 end')
            ->orderByDesc('left_on')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $memberships
                ->map(fn (GroupMember $membership): array => new GroupMembershipResource($membership)->resolve($request))
                ->all(),
        ]);
    }
}
