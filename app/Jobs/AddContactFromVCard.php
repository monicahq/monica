<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\Account\ImportJob;
use App\Services\VCard\ImportVCard;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddContactFromVCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ImportJob
     */
    protected $importJob;
    /**
     * @var string
     */
    protected $behaviour;

    /**
     * Create a new job instance.
     *
     * @param  ImportJob  $importJob
     * @param  string  $behaviour
     * @return void
     */
    public function __construct(ImportJob $importJob, string $behaviour = ImportVCard::BEHAVIOUR_ADD)
    {
        $this->importJob = $importJob;
        $this->behaviour = $behaviour;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->importJob->process($this->behaviour);
    }
}
