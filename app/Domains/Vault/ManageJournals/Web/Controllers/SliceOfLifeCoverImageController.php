<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Contact\ManageDocuments\Services\UploadFile;
use App\Domains\Vault\ManageJournals\Services\RemoveSliceOfLifeCoverImage;
use App\Domains\Vault\ManageJournals\Services\SetSliceOfLifeCoverImage;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\SliceOfLifeShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\SliceOfLife;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SliceOfLifeCoverImageController extends Controller
{
    public function update(Request $request, string $vaultId, int $journalId, int $sliceId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'uuid' => $request->input('uuid'),
            'name' => $request->input('name'),
            'original_url' => $request->input('original_url'),
            'cdn_url' => $request->input('cdn_url'),
            'mime_type' => $request->input('mime_type'),
            'size' => $request->input('size'),
            'type' => File::TYPE_PHOTO,
        ];

        $file = (new UploadFile)->execute($data);

        $slice = (new SetSliceOfLifeCoverImage)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'slice_of_life_id' => $sliceId,
            'file_id' => $file->id,
        ]);

        return response()->json([
            'data' => SliceOfLifeShowViewHelper::dtoSlice($slice),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $journalId, int $sliceId)
    {
        (new RemoveSliceOfLifeCoverImage)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'slice_of_life_id' => $sliceId,
        ]);

        $slice = SliceOfLife::findOrFail($sliceId);

        return response()->json([
            'data' => SliceOfLifeShowViewHelper::dtoSlice($slice),
        ], 200);
    }
}
