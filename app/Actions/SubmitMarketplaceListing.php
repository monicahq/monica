<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CategoryIsNotActive;
use App\Exceptions\ListingIsAlreadyAwaitingModeration;
use App\Exceptions\ListingIsWithdrawn;
use App\Helpers\MarketplacePayload;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Send a listing in for review, with the structure as it stands frozen into it.
 */
class SubmitMarketplaceListing
{
    private MarketplaceListingVersion $version;

    public function __construct(
        private readonly User $user,
        private readonly MarketplaceListing $listing,
        private ?string $changeNote = null,
    ) {}

    public function execute(): MarketplaceListingVersion
    {
        $this->sanitize();
        $this->validate();
        $this->submit();
        $this->log();

        return $this->version;
    }

    private function sanitize(): void
    {
        $this->changeNote = TextSanitizer::nullablePlainText($this->changeNote);
    }

    private function validate(): void
    {
        if ($this->listing->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This listing is in another account.');
        }

        if ($this->listing->status === MarketplaceListingStatusEnum::Withdrawn) {
            throw new ListingIsWithdrawn("{$this->listing->name} has been taken out of the marketplace.");
        }

        if ($this->listing->status->awaitsModeration()) {
            throw new ListingIsAlreadyAwaitingModeration("{$this->listing->name} is already waiting on a decision.");
        }

        if (! $this->listing->category->is_active) {
            throw new CategoryIsNotActive("Nothing new can be published into {$this->listing->category->name}.");
        }

        if ($this->listing->versions()->exists() && $this->changeNote === null) {
            throw new InvalidArgumentException('Sending in a new version of a listing needs a note saying what changed.');
        }
    }

    private function submit(): void
    {
        $previous = $this->listing->versions()->first();
        $payload = MarketplacePayload::of($this->listable());

        DB::transaction(function () use ($previous, $payload): void {
            $this->version = MarketplaceListingVersion::query()->create([
                'marketplace_listing_id' => $this->listing->id,
                'version_number' => $previous === null ? 1 : $previous->version_number + 1,
                'payload' => $payload,
                'change_note' => $this->changeNote,
                'status' => MarketplaceVersionStatusEnum::Submitted,
                'previous_version_id' => $previous?->id,
                'install_count' => 0,
                'checksum' => MarketplacePayload::checksum($payload),
            ]);

            $this->listing->status = MarketplaceListingStatusEnum::Submitted;
            $this->listing->rejection_reason = null;
            $this->listing->save();
        });
    }

    private function listable(): Template|Section
    {
        $listable = $this->listing->listable;

        if (! $listable instanceof Template && ! $listable instanceof Section) {
            throw new ModelNotFoundException('This listing points at nothing that can be published.');
        }

        return $listable;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::MarketplaceListingSubmission,
            parameters: [
                'name' => $this->listing->name,
                'version' => $this->version->version_number,
            ],
            loggable: $this->listing,
        )->onQueue('low');
    }
}
