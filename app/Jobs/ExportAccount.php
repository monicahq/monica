<?php

namespace App\Jobs;

use Illuminate\Http\File;
use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Account\Settings\SqlExportAccount;
use App\Services\Account\Settings\JsonExportAccount;

class ExportAccount
{
    use Dispatchable, SerializesModels;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * Format to use.
     * @var string
     */
    protected $format;

    /**
     * Storage disk used to store the exported file.
     *
     * @var string
     */
    public const STORAGE = 'public';

    /**
     * Export as SQL format.
     * @var string
     */
    public const SQL = 'sql';

    /**
     * Export as JSON format.
     * @var string
     */
    public const JSON = 'json';

    /**
     * Create a new job instance.
     *
     * @param  string  $format
     * @param  string|null  $path
     */
    public function __construct(string $format = self::SQL, $path = null)
    {
        $this->format = $format;
        $this->path = $path ?? 'exports';
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $tempFileName = '';
        switch ($this->format) {
            case self::JSON:
                $handler = app(JsonExportAccount::class);
                break;
            default:
                $handler = app(SqlExportAccount::class);
                break;
        }
        try {
            $tempFileName = $handler->execute([
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
