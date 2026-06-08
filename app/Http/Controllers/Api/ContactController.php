<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contacts\ListContactsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ContactController extends Controller
{
    public function index(Request $request, ListContactsService $service)
    {
        Gate::authorize('vault-viewer', Auth::user()->vault_id);

        $tagIds = $request->input('tags', []);

        if (! is_array($tagIds)) {
            $tagIds = [];
        }

        return response()->json([
            'data' => $service->execute($tagIds),
        ]);
    }
}
