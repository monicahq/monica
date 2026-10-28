<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ProviderUnreachable;
use App\Models\Account;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;

/**
 * Delete an account and everything under it, the export and import files on disk included.
 *
 * A subscription is stopped at Stripe first, and the deletion is refused outright when Stripe does
 * not answer: deleting anyway would leave somebody being charged with no account to show them what
 * for. The period already paid for is not refunded, and the deletion warning says so.
 */
class DestroyAccount
{
    public function __construct(
        private readonly User $user,
        private readonly Account $account,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->stopTheSubscription();
        $this->destroyPhoto();
        $this->destroyApiKeys();
        $this->forgetFlags();
        $this->destroyTransferFiles();
        $this->destroy();
    }

    private function stopTheSubscription(): void
    {
        $subscription = $this->account->subscription();

        if ($subscription?->valid() !== true) {
            return;
        }

        try {
            $subscription->cancelNow();
        } catch (Exception $exception) {
            throw new ProviderUnreachable(__('We could not reach Stripe to stop your subscription, so nothing was deleted. Please try again in a moment.'), 0, $exception);
        }
    }

    private function validate(): void
    {
        if ($this->user->account_id !== $this->account->id) {
            throw new ModelNotFoundException('Account not found');
        }
    }

    private function destroyPhoto(): void
    {
        if (! $this->user->hasPhoto()) {
            return;
        }

        foreach (User::photoPixelSizes() as $pixels) {
            $this->disk()->delete($this->user->photoVariantPath($pixels));
        }
    }

    private function destroyApiKeys(): void
    {
        $this->user->tokens()->delete();
    }

    private function forgetFlags(): void
    {
        Feature::for($this->account)->forget(Feature::stored());
    }

    private function destroyTransferFiles(): void
    {
        $disks = AccountExport::query()
            ->where('account_id', $this->account->id)
            ->pluck('disk')
            ->merge(AccountImport::query()->where('account_id', $this->account->id)->pluck('disk'))
            ->unique();

        foreach ($disks as $disk) {
            Storage::disk($disk)->deleteDirectory($this->account->transfersDirectory());
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
