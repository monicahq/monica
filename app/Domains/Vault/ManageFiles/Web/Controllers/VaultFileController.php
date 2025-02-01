<?php

namespace App\Domains\Vault\ManageFiles\Web\Controllers;

use App\Domains\Contact\ManageDocuments\Services\DestroyFile;
use App\Domains\Vault\ManageFiles\Web\ViewHelpers\VaultFileIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VaultFileController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        $files = File::where('vault_id', $vaultId)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return Inertia::render('Vault/Files/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultFileIndexViewHelper::data($files, Auth::user(), $vault),
            'paginator' => PaginatorHelper::getData($files),
            'tab' => 'index',
        ]);
    }

    public function photos(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        $files = File::where('vault_id', $vaultId)
            ->where('type', File::TYPE_PHOTO)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return Inertia::render('Vault/Files/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultFileIndexViewHelper::data($files, Auth::user(), $vault),
            'paginator' => PaginatorHelper::getData($files),
            'tab' => 'photos',
        ]);
    }

    public function documents(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        $files = File::where('vault_id', $vaultId)
            ->where('type', File::TYPE_DOCUMENT)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return Inertia::render('Vault/Files/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultFileIndexViewHelper::data($files, Auth::user(), $vault),
            'paginator' => PaginatorHelper::getData($files),
            'tab' => 'documents',
        ]);
    }

    public function avatars(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        $files = File::where('vault_id', $vaultId)
            ->where('type', File::TYPE_AVATAR)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return Inertia::render('Vault/Files/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultFileIndexViewHelper::data($files, Auth::user(), $vault),
            'paginator' => PaginatorHelper::getData($files),
            'tab' => 'avatars',
        ]);
    }

    public function destroy(Request $request, string $vaultId, int $fileId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'file_id' => $fileId,
        ];

        (new DestroyFile)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
