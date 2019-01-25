<?php

namespace App\Http\Controllers\Settings;

use App\Models\Account\Photo;
use App\Models\Contact\Document;
use App\Http\Controllers\Controller;

class StorageController extends Controller
{
    /**
     * Get all the information about the account in terms of storage.
     */
    public function index()
    {
        $documents = Document::where('account_id', auth()->user()->account->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $photos = Photo::where('account_id', auth()->user()->account->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // size is in bytes in the database
        $currentAccountSize = auth()->user()->account->getStorageSize();

        if ($currentAccountSize != 0) {
            $currentAccountSize = round($currentAccountSize / 1000000);
        }

        // correspondingPercent
        $percentUsage = round($currentAccountSize * 100 / config('monica.max_storage_size'));

        return view('settings.storage.index')
            ->withDocuments($documents)
            ->withPhotos($photos)
            ->withCurrentAccountSize($currentAccountSize)
            ->withAccountLimit(config('monica.max_storage_size'))
            ->withPercentUsage($percentUsage);
    }
}
