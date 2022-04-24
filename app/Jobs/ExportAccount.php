<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Http\File;
use Illuminate\Bus\Queueable;
use App\Helpers\StorageHelper;
use App\Models\Account\ExportJob;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Account\Settings\SqlExportAccount;
use App\Services\Account\Settings\JsonExportAccount;

class ExportAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * Export job.
     *
     * @var ExportJob
     */
    protected $exportJob;

    /**
     * Create a new job instance.
     *
     * @param  ExportJob  $exportJob
     * @param  string|null  $path
     */
    public function __construct(ExportJob $exportJob, string $path = null)
    {
        $exportJob->status = ExportJob::EXPORT_TODO;
        $exportJob->save();
        $this->exportJob = $exportJob->withoutRelations();
        $this->path = $path ?? 'exports';
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->exportJob->start();

        $tempFileName = '';
        $handler = $this->exportJob->type === ExportJob::JSON ?
            app(JsonExportAccount::class) :
            app(SqlExportAccount::class);
        try {
            $tempFileName = $handler->execute([
                'account_id' => $this->exportJob->account_id,
                'user_id' => $this->exportJob->user_id,
            ]);

            // get the temp file that we just created
            $tempFilePath = StorageHelper::disk('local')->path($tempFileName);

            // move the file to the public storage
            $file = StorageHelper::disk(config('filesystems.default'))
                ->putFileAs($this->path, new File($tempFilePath), basename($tempFileName));

            $this->exportJob->location = config('filesystems.default');
            $this->exportJob->filename = $file;

            $this->exportJob->end();
        } catch (Throwable $e) {
            $this->fail($e);
        } finally {
            // delete old file from temp folder
            $storage = Storage::disk('local');
            if ($storage->exists($tempFileName)) {
                $storage->delete($tempFileName);
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     */
    public function failed(Throwable $exception): void
    {
        $this->exportJob->status = ExportJob::EXPORT_FAILED;
        $this->exportJob->save();
    }
}
