<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Transfer;

use App\Actions\DownloadAccountExport;
use App\Http\Controllers\Controller;
use App\Models\AccountExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountExportDownloadController extends Controller
{
    public function show(Request $request, AccountExport $accountExport): StreamedResponse
    {
        new DownloadAccountExport(
            user: $request->user(),
            export: $accountExport,
        )->execute();

        return Storage::disk($accountExport->disk)->download($accountExport->path, basename($accountExport->path), [
            'Content-Type' => 'application/json',
        ]);
    }
}
