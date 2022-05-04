<?php

namespace App\Http\Controllers\Settings;

use App\Jobs\ExportAccount;
use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use App\Helpers\StorageHelper;
use App\Models\Account\ExportJob;
use App\Http\Controllers\Controller;

class ExportController extends Controller
{
    /**
     * Display the export view.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $exports = ExportJob::where([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->user()->id,
        ])
            ->orderByDesc('created_at')
            ->get();

        return view('settings.export')
            ->withAccountHasLimitations(AccountHelper::hasLimitations(auth()->user()->account))
            ->withExports($exports);
    }

    /**
     * Exports the data of the account in SQL format.
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response|null
     */
    public function storeSql()
    {
        $job = $this->newExport(ExportJob::SQL);
        ExportAccount::dispatch($job);

        return redirect()->route('settings.export.index')
            ->withStatus(trans('settings.export_submitted'));
    }

    /**
     * Exports the data of the account in SQL format.
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response|null
     */
    public function storeJson()
    {
        $job = $this->newExport(ExportJob::JSON);
        ExportAccount::dispatch($job);

        return redirect()->route('settings.export.index')
            ->withStatus(trans('settings.export_submitted'));
    }

    /**
     * Create a new ExportJob.
     *
     * @param  string  $type
     * @return ExportJob
     */
    private function newExport(string $type): ExportJob
    {
        $exports = ExportJob::where([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->user()->id,
        ])
            ->orderBy('created_at')
            ->get();

        if ($exports->count() >= config('monica.export_size')) {
            $job = $exports->first();
            try {
                if ($job->filename !== null) {
                    StorageHelper::disk($job->location)
                        ->delete($job->filename);
                }
            } finally {
                $job->delete();
            }
        }

        return ExportJob::create([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->user()->id,
            'type' => $type,
        ]);
    }

    /**
     * Download the generated file.
     *
     * @param  Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response|null
     */
    public function download(Request $request, string $uuid)
    {
        $job = ExportJob::where([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->user()->id,
            'uuid' => $uuid,
        ])->firstOrFail();

        if ($job->status !== ExportJob::EXPORT_DONE) {
            return redirect()->route('settings.export.index')
                ->withErrors(trans('settings.export_not_done'));
        }
        $disk = StorageHelper::disk($job->location);

        return $disk->response($job->filename,
                "monica.{$job->type}",
                [
                    'Content-Type' => "application/{$job->type}; charset=utf-8",
                    'Content-Disposition' => "attachment; filename=monica.{$job->type}",
                ]
            );
    }
}
