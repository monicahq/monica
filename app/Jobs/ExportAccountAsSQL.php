<?php

namespace App\Jobs;

use Illuminate\Http\File;
use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Account\Settings\ExportAccount;

class ExportAccountAsSQL
{
    use Dispatchable, SerializesModels;

    protected $path = '';

    /**
     * Storage disk used to store the exported file.
     * @var string
     */
    public const STORAGE = 'public';

    /**
     * Create a new job instance.
     *
     * @param string|null $path
     */
    public function __construct($path = null)
    {
        $this->path = $path ?? 'exports';
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $tempFileName = null;
        try {
            $tempFileName = app(ExportAccount::class)
                    ->execute([
                        'account_id' => Auth::user()->account_id,
                        'user_id' => Auth::user()->id,
                    ]);

            // get the temp file that we just created
            $tempFilePath = disk_adapter('local')->getPathPrefix().$tempFileName;

            // move the file to the public storage
            return StorageHelper::disk(self::STORAGE)
                ->putFileAs($this->path, new File($tempFilePath), basename($tempFileName));
        } finally {
            // delete old file from temp folder
            $storage = Storage::disk('local');
            if ($storage->exists($tempFileName)) {
                $storage->delete($tempFileName);
            }
        }
    }
}
