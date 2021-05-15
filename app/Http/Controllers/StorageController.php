<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Account\Photo;
use App\Helpers\StorageHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;

class StorageController
{
    /**
     * Download file authorization.
     *
     * @param Request $request
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request, string $file)
    {
        $accountId = auth()->user()->account_id;
        $folder = Str::before($file, '/');
        $original_filename = null;

        switch ($folder) {
            case 'avatars':
                $flag = Contact::where([
                    'account_id' => $accountId,
                    ['avatar_default_url', 'like', "$file%"],
                ])->first();
                $original_filename = Str::after($file, '/');
                break;

            case 'photos':
                $flag = Photo::where([
                    'account_id' => $accountId,
                    'new_filename' => $file,
                ])->first();
                $original_filename = $flag ? $flag->original_filename : null;
                break;

            case 'documents':
                $flag = Document::where([
                    'account_id' => $accountId,
                    'new_filename' => $file,
                ])->first();
                $original_filename = $flag ? $flag->original_filename : null;
                break;

            default:
                $flag = false;
        }
        if (!$flag) {
            abort(404);
        }
        return StorageHelper::disk(config('filesystem.default'))
            ->response($file, $original_filename);
    }
}
