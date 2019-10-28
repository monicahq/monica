<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Account\Settings\ExportAccount;

class ExportAccountAsSQL
{
    use Dispatchable, SerializesModels;

    protected $file = '';
    protected $path = '';

    /**
     * Storage disk used to store the exported file.
     * @var string
     */
    public const STORAGE = 'public';

    /**
     * Create a new job instance.
     *
     * @param string|null $file
     * @param string|null $path
     */
    public function __construct($file = null, $path = null)
    {
        $this->path = $path ?? 'exports/';
        $this->file = $file ?? rand().'.sql';
    }

    /**
     * Execute the job.
     *
     * @return string
     */
    public function handle()
    {
        $downloadPath = $this->path.$this->file;

        $tempFileName = app(ExportAccount::class)
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'user_id' => Auth::user()->id,
                ]);

        // get the temp file that we just created
        $contents = Storage::disk('local')->get($tempFileName);

        // move the file to the public storage
        Storage::disk(self::STORAGE)->put($downloadPath, $contents);

        // delete old file from temp folder
        Storage::disk('local')->delete($tempFileName);

        return $downloadPath;
    }
}
