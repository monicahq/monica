<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;

/**
 * What one submission shows: everything its author sent in, so a decision is
 * taken on what was actually submitted rather than on its name.
 *
 * The payload is shown as it is, formatted to be read. It is the structure
 * somebody else's account will hold once this is approved, and no summary of it
 * is a substitute for looking.
 */
class ModerationSubmissionViewModel
{
    public function __construct(
        private readonly MarketplaceListingVersion $version,
    ) {}

    public function version(): MarketplaceListingVersion
    {
        return $this->version;
    }

    public function listing(): MarketplaceListing
    {
        return $this->version->listing;
    }

    /**
     * The payload, laid out to be read rather than parsed. Slashes are left
     * alone because escaping them turns every path in the structure into
     * something nobody can check at a glance.
     */
    public function payload(): string
    {
        return (string) json_encode(
            $this->version->payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * Whether a decision is still open. A submission somebody else decided a
     * moment ago is shown as it stands rather than with controls that would
     * fail.
     */
    public function awaitsDecision(): bool
    {
        return $this->version->status->awaitsModeration();
    }
}
