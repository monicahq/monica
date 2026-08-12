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
 *
 * This is one action and not two, because sending in the first version and
 * sending in the fifth are the same act for whoever performs them. What differs
 * is only what happens to the listing while it waits: one that has never been
 * published has nothing to show and stops being visible as anything, and one
 * already in the catalogue stays on the version people are installing until the
 * new one is approved.
 *
 * A change note is required from the second version on. It is what every
 * installer reads before deciding whether to move, and a version that arrives
 * without one leaves them nothing to decide with. The first has nothing to have
 * changed from.
 *
 * The payload is taken here rather than at approval, so that what is moderated
 * is exactly what was sent, and not whatever the author has done to their
 * account in the meantime.
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

    /**
     * Get what the listing publishes. A template and a section are the two
     * things the payload knows how to write down; an activity type will be the
     * third when there is one to publish.
     */
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
