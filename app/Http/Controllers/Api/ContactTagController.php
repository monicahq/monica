<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\Tags\AttachTagService;
use App\Services\Tags\DetachTagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ContactTagController extends Controller
{
    public function attach(Request $request, string $id, AttachTagService $service)
    {
        Gate::authorize('vault-editor', Auth::user()->vault_id);

        $contact = Contact::query()
            ->where('vault_id', Auth::user()->vault_id)
            ->findOrFail($id);

        $data = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        $service->execute($contact, $data['tag_ids']);

        return response()->json([
            'message' => 'tags attached',
        ]);
    }

    public function detach(string $id, int $tagId, DetachTagService $service)
    {
        Gate::authorize('vault-editor', Auth::user()->vault_id);

        $contact = Contact::query()
            ->where('vault_id', Auth::user()->vault_id)
            ->findOrFail($id);

        $service->execute($contact, $tagId);

        return response()->json([
            'message' => 'tag detached',
        ]);
    }
}
