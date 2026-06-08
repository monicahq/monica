<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Vault;
use App\Services\Tags\CreateTagService;
use App\Services\Tags\DeleteTagService;
use App\Services\Tags\UpdateTagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class VaultSettingsTagController extends Controller
{
    public function store(Request $request, Vault $vault, CreateTagService $service)
    {
        Gate::authorize('vault-manager', $vault);

        Auth::user()->forceFill(['vault_id' => $vault->id]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'tag_category' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $tag = $service->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoTag($tag),
        ], 201);
    }

    public function update(Request $request, Vault $vault, Tag $tag, UpdateTagService $service)
    {
        Gate::authorize('vault-manager', $vault);

        Auth::user()->forceFill(['vault_id' => $vault->id]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'tag_category' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $tag = $service->execute($tag, $data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoTag($tag),
        ], 200);
    }

    public function destroy(Request $request, Vault $vault, Tag $tag, DeleteTagService $service)
    {
        Gate::authorize('vault-manager', $vault);

        Auth::user()->forceFill(['vault_id' => $vault->id]);

        $service->execute($tag);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
