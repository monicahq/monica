<?php

namespace App\Jobs\Settings;

use App\Models\Account\Account;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use App\Services\Account\Subscription\ActivateLicenceKey;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Contact\Avatar\GetAvatarsFromInternet as GetAvatarsFromInternetService;
use Carbon\Carbon;

class CheckLicenceKeys implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! config('monica.licence_key_encryption_key')) {
            return;
        }

        if (config('monica.customer_portal_url') == '') {
            return;
        }

        $accounts = Account::where('valid_until_at', '<', Carbon::now())
            ->whereNotNull('licence_key')
            ->get();

        foreach ($accounts as $account) {
            app(ActivateLicenceKey::class)->execute([
                'account_id' => $account->id,
                'licence_key' => $account->licence_key,
            ]);
        }
    }
}
