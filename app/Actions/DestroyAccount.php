<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ProviderUnreachable;
use App\Models\Account;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\File;
use App\Models\SupportAttachment;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Stripe\Subscription as StripeSubscription;

/**
 * Delete an account and everything under it, every file it wrote on any disk included. The person
 * owning it, and an administrator, both delete through here, so neither leaves behind what the other
 * removes.
 *
 * Every subscription Stripe still holds open is stopped there first, an unpaid or incomplete one
 * included, and the deletion is refused outright when Stripe does not answer: deleting anyway would leave somebody being charged with no account to show them what
 * for. The period already paid for is not refunded, and the deletion warning says so.
 */
class DestroyAccount
{
    private ?User $user;

    public function __construct(
        private readonly Account $account,
    ) {}

    public function execute(): void
    {
        $this->user = $this->account->userIncludingDeleted;

        $this->stopTheSubscription();
        $this->destroyPhoto();
        $this->destroyApiKeys();
        $this->destroySessions();
        $this->forgetFlags();
        $this->destroyAccountFiles();
        $this->destroySupportFiles();
        $this->destroy();
    }

    private function stopTheSubscription(): void
    {
        $subscription = $this->account->subscription();

        if ($subscription === null) {
            return;
        }

        if ($subscription->ended()) {
            return;
        }

        if (in_array($subscription->stripe_status, [StripeSubscription::STATUS_CANCELED, StripeSubscription::STATUS_INCOMPLETE_EXPIRED], true)) {
            return;
        }

        try {
            $subscription->cancelNow();
        } catch (Exception $exception) {
            throw new ProviderUnreachable(__('We could not reach Stripe to stop your subscription, so nothing was deleted. Please try again in a moment.'), 0, $exception);
        }
    }

    private function destroyPhoto(): void
    {
        if ($this->user?->hasPhoto() !== true) {
            return;
        }

        foreach (User::photoPixelSizes() as $pixels) {
            $this->disk()->delete($this->user->photoVariantPath($pixels));
        }
    }

    private function destroyApiKeys(): void
    {
        $this->user?->tokens()->delete();
    }

    private function destroySessions(): void
    {
        if ($this->user === null || config('session.driver') !== 'database') {
            return;
        }

        DB::table((string) config('session.table'))->where('user_id', $this->user->id)->delete();
    }

    private function forgetFlags(): void
    {
        Feature::for($this->account)->forget(Feature::stored());
    }

    private function destroyAccountFiles(): void
    {
        $disks = File::query()
            ->where('account_id', $this->account->id)
            ->pluck('disk')
            ->merge(AccountExport::query()->where('account_id', $this->account->id)->pluck('disk'))
            ->merge(AccountImport::query()->where('account_id', $this->account->id)->pluck('disk'))
            ->unique();

        foreach ($disks as $disk) {
            Storage::disk($disk)->deleteDirectory(File::directoryFor($this->account->id, null));
        }
    }

    private function destroySupportFiles(): void
    {
        $disks = SupportAttachment::query()
            ->where('account_id', $this->account->id)
            ->distinct()
            ->pluck('disk');

        foreach ($disks as $disk) {
            Storage::disk($disk)->deleteDirectory(SupportAttachment::directoryFor($this->account->id));
        }
    }

    private function destroy(): void
    {
        $this->account->delete();
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('filesystems.default'));
    }
}
