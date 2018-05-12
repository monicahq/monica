<?php

namespace App\Jobs;

use App\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * This job adds a changelog entry for all users in a given account.
 */
class AddChangelogEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $account;
    protected $changelogId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Account $account, int $changelogId)
    {
        $this->account = $account;
        $this->changelogId = $changelogId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->account->addUnreadChangelogEntry($this->changelogId);
    }
}
