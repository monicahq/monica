<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\UpdateKnowledgeFolderPosition;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeFolder;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KnowledgeFolderPositionController extends Controller
{
    public function update(Request $request, Vault $vault, KnowledgeFolder $knowledgeFolder): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateKnowledgeFolderPosition(
            user: $request->user(),
            folder: $knowledgeFolder,
            position: $request->integer('position'),
        )->execute();

        return redirect()->back()->with('status', __('The folder is moved.'));
    }
}
