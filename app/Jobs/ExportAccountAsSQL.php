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

        $sql = app(ExportAccount::class)
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'user_id' => Auth::user()->id,
                ]);

        Storage::disk(self::STORAGE)->put($downloadPath, $sql);

        return $downloadPath;
    }
}
